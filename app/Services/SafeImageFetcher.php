<?php

namespace App\Services;

use App\Exceptions\UnsafeImageException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class SafeImageFetcher
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /** @var list<string> */
    private const BLOCKED_IPV4_RANGES = [
        '0.0.0.0/8', '10.0.0.0/8', '100.64.0.0/10', '127.0.0.0/8',
        '169.254.0.0/16', '172.16.0.0/12', '192.0.0.0/24', '192.0.2.0/24',
        '192.88.99.0/24', '192.168.0.0/16', '198.18.0.0/15', '198.51.100.0/24',
        '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4',
    ];

    /** @var list<string> */
    private const BLOCKED_IPV6_RANGES = [
        '::/96', '::ffff:0:0/96', '64:ff9b::/96', '64:ff9b:1::/48', '100::/64',
        '2001::/23', '2001:db8::/32', '2002::/16', '3fff::/20', '5f00::/16',
        'fc00::/7', 'fe80::/10', 'ff00::/8',
    ];

    /**
     * @param  null|callable(string): list<string>  $dnsResolver
     * @param  null|callable(string): array<int, array<string, mixed>>  $dnsRecordResolver
     */
    public function __construct(
        private $dnsResolver = null,
        private $dnsRecordResolver = null,
    ) {}

    public function fetch(string $url): FetchedImage
    {
        $target = $this->validateUrl($url);
        $addresses = $this->resolveAndValidateHost($target['host']);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'safe-image-');

        if ($temporaryPath === false) {
            throw new UnsafeImageException('temporary_file');
        }

        $maxBytes = (int) config('security.image_fetcher.max_bytes', 1024 * 1024);

        try {
            $response = Http::accept(implode(', ', self::ALLOWED_MIME_TYPES))
                ->withHeader('Accept-Encoding', 'identity')
                ->connectTimeout((int) config('security.image_fetcher.connect_timeout', 3))
                ->timeout((int) config('security.image_fetcher.timeout', 5))
                ->withoutRedirecting()
                ->withOptions([
                    'sink' => $temporaryPath,
                    'allow_redirects' => false,
                    // Do not inflate a small compressed response into an oversized body.
                    'decode_content' => false,
                    // Force a direct connection: an environment proxy could resolve
                    // the target itself and bypass the CURLOPT_RESOLVE pinning.
                    'proxy' => '',
                    'curl' => [
                        CURLOPT_RESOLVE => [$this->curlResolveEntry($target['host'], $target['port'], $addresses[0])],
                        CURLOPT_MAXFILESIZE_LARGE => $maxBytes,
                    ],
                    'progress' => static function ($downloadTotal, $downloadedBytes) use ($maxBytes): void {
                        if ($downloadTotal > $maxBytes || $downloadedBytes > $maxBytes) {
                            throw new UnsafeImageException('body_too_large');
                        }
                    },
                ])
                ->get($url);

            if (! $response->successful()) {
                throw new UnsafeImageException($response->redirect() ? 'redirect' : 'http_status');
            }

            $declaredLength = $response->header('Content-Length');
            if ($declaredLength !== null && ctype_digit($declaredLength) && (int) $declaredLength > $maxBytes) {
                throw new UnsafeImageException('body_too_large');
            }

            $contents = file_get_contents($temporaryPath);
            // Http::fake() does not write to Guzzle's sink, so use its in-memory body in tests only.
            if ($contents === '' && app()->runningUnitTests()) {
                $contents = $response->body();
            }

            if ($contents === false || strlen($contents) === 0 || strlen($contents) > $maxBytes) {
                throw new UnsafeImageException('invalid_size');
            }

            $declaredMime = strtolower(trim(strtok((string) $response->header('Content-Type'), ';') ?: ''));
            if (! in_array($declaredMime, self::ALLOWED_MIME_TYPES, true)) {
                throw new UnsafeImageException('declared_mime');
            }

            $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
            $imageInfo = @getimagesizefromstring($contents);
            if ($imageInfo === false
                || ! in_array($detectedMime, self::ALLOWED_MIME_TYPES, true)
                || ($imageInfo['mime'] ?? null) !== $detectedMime) {
                throw new UnsafeImageException('content_mime');
            }

            if ($detectedMime !== $declaredMime) {
                throw new UnsafeImageException('mime_mismatch');
            }

            return new FetchedImage($contents, $detectedMime);
        } catch (UnsafeImageException $exception) {
            throw $exception;
        } catch (ConnectionException) {
            throw new UnsafeImageException('connection');
        } catch (Throwable) {
            throw new UnsafeImageException('request');
        } finally {
            @unlink($temporaryPath);
        }
    }

    /** @return array{host: string, port: int} */
    public function validateUrl(string $url): array
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new UnsafeImageException('malformed_url');
        }

        $parts = parse_url($url);
        if (! is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https') {
            throw new UnsafeImageException('scheme');
        }

        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            throw new UnsafeImageException('url_components');
        }

        $host = strtolower($parts['host'] ?? '');
        $port = (int) ($parts['port'] ?? 443);
        if (! $this->isValidHost($host) || ! in_array($port, config('security.image_fetcher.allowed_ports', [443]), true)) {
            throw new UnsafeImageException('host_or_port');
        }

        $this->assertHostAllowed($host);

        return ['host' => $host, 'port' => $port];
    }

    /** @return list<string> */
    public function resolveAndValidateHost(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $addresses = [$host];
        } else {
            $resolver = $this->dnsResolver ?? fn (string $name): array => $this->resolveDns($name);
            $addresses = array_values(array_unique($resolver($host)));
        }

        if ($addresses === []) {
            throw new UnsafeImageException('dns');
        }

        foreach ($addresses as $address) {
            if (! $this->isGloballyRoutableIp($address)) {
                throw new UnsafeImageException('non_public_ip');
            }
        }

        return $addresses;
    }

    public function isGloballyRoutableIp(string $address): bool
    {
        if (filter_var($address, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $ranges = str_contains($address, ':') ? self::BLOCKED_IPV6_RANGES : self::BLOCKED_IPV4_RANGES;
        foreach ($ranges as $range) {
            if ($this->ipInCidr($address, $range)) {
                return false;
            }
        }

        return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    private function isValidHost(string $host): bool
    {
        if ($host === '' || strlen($host) > 253 || $host !== rtrim($host, '.')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }

        // ASCII LDH names only: ambiguous Unicode/IDN and shorthand numeric hosts are rejected.
        if (str_contains($host, 'xn--')
            || ! preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z](?:[a-z0-9-]{0,61}[a-z0-9])$/', $host)) {
            return false;
        }

        return $host !== 'localhost'
            && ! str_ends_with($host, '.localhost')
            && ! str_ends_with($host, '.local')
            && ! str_ends_with($host, '.localdomain')
            && $host !== 'localdomain';
    }

    private function assertHostAllowed(string $host): void
    {
        $allowedHosts = config('security.image_fetcher.allowed_hosts', []);
        if ($allowedHosts === []) {
            return;
        }

        $allowSubdomains = (bool) config('security.image_fetcher.allow_subdomains', true);
        foreach ($allowedHosts as $allowedHost) {
            $allowedHost = strtolower(rtrim((string) $allowedHost, '.'));
            if ($host === $allowedHost || ($allowSubdomains && str_ends_with($host, '.'.$allowedHost))) {
                return;
            }
        }

        throw new UnsafeImageException('allowlist');
    }

    /** @return list<string> */
    private function resolveDns(string $host, array $visited = [], int $depth = 0): array
    {
        if ($depth > 8 || isset($visited[$host])) {
            return [];
        }

        $visited[$host] = true;
        $recordResolver = $this->dnsRecordResolver
            ?? static fn (string $name): array|false => dns_get_record($name, DNS_A | DNS_AAAA | DNS_CNAME);
        $records = $recordResolver($host);
        if ($records === false) {
            return [];
        }

        $addresses = [];
        foreach ($records as $record) {
            if (isset($record['ip'])) {
                $addresses[] = $record['ip'];
            }
            if (isset($record['ipv6'])) {
                $addresses[] = $record['ipv6'];
            }
            if (isset($record['target'])) {
                $target = strtolower(rtrim((string) $record['target'], '.'));
                if (! $this->isValidHost($target)) {
                    return [];
                }

                $addresses = [...$addresses, ...$this->resolveDns($target, $visited, $depth + 1)];
            }
        }

        return array_values(array_unique($addresses));
    }

    private function curlResolveEntry(string $host, int $port, string $address): string
    {
        $address = str_contains($address, ':') ? "[$address]" : $address;

        // Without the '+' prefix this entry remains an authoritative override for
        // the lifetime of the cURL handle instead of expiring like a DNS cache item.
        return "{$host}:{$port}:{$address}";
    }

    private function ipInCidr(string $address, string $cidr): bool
    {
        [$network, $prefix] = explode('/', $cidr, 2);
        $addressBytes = inet_pton($address);
        $networkBytes = inet_pton($network);
        if ($addressBytes === false || $networkBytes === false || strlen($addressBytes) !== strlen($networkBytes)) {
            return false;
        }

        $prefix = (int) $prefix;
        $wholeBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;
        if (substr($addressBytes, 0, $wholeBytes) !== substr($networkBytes, 0, $wholeBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($addressBytes[$wholeBytes]) & $mask) === (ord($networkBytes[$wholeBytes]) & $mask);
    }
}

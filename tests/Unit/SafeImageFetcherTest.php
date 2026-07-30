<?php

namespace Tests\Unit;

use App\Exceptions\UnsafeImageException;
use App\Services\SafeImageFetcher;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SafeImageFetcherTest extends TestCase
{
    private const PUBLIC_IP = '93.184.216.34';

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('security.image_fetcher.allowed_hosts', []);
        config()->set('security.image_fetcher.allowed_ports', [443]);
        config()->set('security.image_fetcher.max_bytes', 1024 * 1024);
        Http::preventStrayRequests();
    }

    #[DataProvider('unsafeUrlProvider')]
    public function test_it_rejects_unsafe_urls(string $url): void
    {
        $this->expectException(UnsafeImageException::class);

        $this->fetcher()->fetch($url);
    }

    public static function unsafeUrlProvider(): array
    {
        return [
            ['http://example.com/a.jpg'],
            ['http://localhost:8001/user-data.php'],
            ['https://localhost/image.jpg'],
            ['https://127.0.0.1/image.jpg'],
            ['https://127.1/image.jpg'],
            ['https://[::1]/image.jpg'],
            ['https://0.0.0.0/image.jpg'],
            ['https://10.0.0.1/image.jpg'],
            ['https://172.16.0.1/image.jpg'],
            ['https://192.168.1.1/image.jpg'],
            ['https://169.254.169.254/latest/meta-data/'],
            ['https://[fe80::1]/image.jpg'],
            ['https://user:password@example.com/image.jpg'],
            ['file:///etc/passwd'],
            ['ftp://example.com/file'],
            ['gopher://127.0.0.1/'],
            ['https://example.com:8443/a.jpg'],
            ['https://example.com/a.jpg#fragment'],
            ['https://éxample.com/a.jpg'],
            ['https://xn--xample-9ua.com/a.jpg'],
            ['https://service.localhost/image.jpg'],
            ['https://localhost.localdomain/image.jpg'],
            ['https://0177.0.0.1/image.jpg'],
            ['https://0x7f000001/image.jpg'],
            ['https://2130706433/image.jpg'],
            ['https://127.0.0.1./image.jpg'],
            ['https://example.com./image.jpg'],
            ['https://user%40evil.test@example.com/image.jpg'],
            ['https://[::ffff:127.0.0.1]/image.jpg'],
            ['https://[64:ff9b::a9fe:a9fe]/image.jpg'],
        ];
    }

    public function test_it_enforces_an_exact_dot_delimited_allowlist(): void
    {
        config()->set('security.image_fetcher.allowed_hosts', ['example.com']);
        $fetcher = $this->fetcher();

        $this->assertSame('example.com', $fetcher->validateUrl('https://example.com/a.jpg')['host']);
        $this->assertSame('img.example.com', $fetcher->validateUrl('https://img.example.com/a.jpg')['host']);

        foreach (['https://evilexample.com/a.jpg', 'https://example.com.attacker.test/a.jpg'] as $url) {
            try {
                $fetcher->validateUrl($url);
                $this->fail("URL should have been rejected: {$url}");
            } catch (UnsafeImageException $exception) {
                $this->assertSame('allowlist', $exception->category);
            }
        }
    }

    public function test_it_rejects_a_host_when_any_dns_answer_is_not_public(): void
    {
        $fetcher = $this->fetcher([self::PUBLIC_IP, '10.0.0.1']);

        $this->expectException(UnsafeImageException::class);
        $fetcher->fetch('https://example.com/a.jpg');
    }

    public function test_it_follows_and_validates_every_cname_answer(): void
    {
        $fetcher = new SafeImageFetcher(null, static function (string $host): array {
            return match ($host) {
                'example.com' => [['type' => 'CNAME', 'target' => 'cdn.example.net.']],
                'cdn.example.net' => [
                    ['type' => 'A', 'ip' => self::PUBLIC_IP],
                    ['type' => 'A', 'ip' => '169.254.169.254'],
                ],
                default => [],
            };
        });

        $this->expectException(UnsafeImageException::class);
        $fetcher->fetch('https://example.com/a.jpg');
    }

    public function test_it_can_fetch_through_a_cname_that_only_resolves_publicly(): void
    {
        $fetcher = new SafeImageFetcher(null, static function (string $host): array {
            return match ($host) {
                'example.com' => [['type' => 'CNAME', 'target' => 'cdn.example.net.']],
                'cdn.example.net' => [['type' => 'A', 'ip' => self::PUBLIC_IP]],
                default => [],
            };
        });
        Http::fake(['*' => Http::response(base64_decode(self::PNG), 200, ['Content-Type' => 'image/png'])]);

        $this->assertSame('image/png', $fetcher->fetch('https://example.com/image.png')->mimeType);
        Http::assertSentCount(1);
    }

    public function test_it_accepts_uppercase_hosts_after_safe_normalization(): void
    {
        config()->set('security.image_fetcher.allowed_hosts', ['example.com']);

        $this->assertSame(
            'example.com',
            $this->fetcher()->validateUrl('https://EXAMPLE.COM/image.jpg')['host'],
        );
    }

    public function test_it_only_accepts_an_alternate_port_when_configured(): void
    {
        config()->set('security.image_fetcher.allowed_ports', [443, 8443]);

        $this->assertSame(
            8443,
            $this->fetcher()->validateUrl('https://example.com:8443/image.jpg')['port'],
        );
    }

    #[DataProvider('nonPublicIpProvider')]
    public function test_it_rejects_non_globally_routable_ip_ranges(string $ip): void
    {
        $this->assertFalse($this->fetcher()->isGloballyRoutableIp($ip));
    }

    public static function nonPublicIpProvider(): array
    {
        return [
            ['127.0.0.1'], ['0.0.0.1'], ['10.0.0.1'], ['100.64.0.1'],
            ['169.254.169.254'], ['172.31.255.255'], ['192.168.1.1'], ['192.0.2.1'],
            ['198.18.0.1'], ['198.51.100.1'], ['203.0.113.1'], ['224.0.0.1'],
            ['240.0.0.1'], ['::'], ['::1'], ['::ffff:127.0.0.1'], ['fc00::1'],
            ['fe80::1'], ['ff00::1'], ['2001:db8::1'], ['64:ff9b::a00:1'],
            ['3fff::1'], ['5f00::1'],
        ];
    }

    public function test_it_fetches_a_small_genuine_allowed_image(): void
    {
        config()->set('security.image_fetcher.allowed_hosts', ['example.com']);
        Http::fake([
            'https://example.com/image.png' => Http::response(base64_decode(self::PNG), 200, [
                'Content-Type' => 'image/png; charset=binary',
                'Content-Length' => (string) strlen(base64_decode(self::PNG)),
            ]),
        ]);

        $image = $this->fetcher()->fetch('https://example.com/image.png');

        $this->assertSame('image/png', $image->mimeType);
        $this->assertStringStartsWith('data:image/png;base64,', $image->dataUri());
        Http::assertSentCount(1);
    }

    #[DataProvider('invalidResponseProvider')]
    public function test_it_rejects_invalid_responses(int $status, string $contentType, string $body): void
    {
        Http::fake(['*' => Http::response($body, $status, ['Content-Type' => $contentType])]);

        $this->expectException(UnsafeImageException::class);
        $this->fetcher()->fetch('https://example.com/image.jpg');
    }

    public static function invalidResponseProvider(): array
    {
        return [
            'not found' => [404, 'image/jpeg', 'missing'],
            'html response' => [200, 'text/html; charset=utf-8', '<html>not an image</html>'],
            'spoofed jpeg' => [200, 'image/jpeg', '<html>not an image</html>'],
        ];
    }

    #[DataProvider('unsafeRedirectProvider')]
    public function test_it_does_not_follow_an_unsafe_redirect(string $location): void
    {
        Http::fake([
            'https://example.com/image.jpg' => Http::response('', 302, [
                'Location' => $location,
            ]),
        ]);

        try {
            $this->fetcher()->fetch('https://example.com/image.jpg');
            $this->fail('The redirect should have been rejected.');
        } catch (UnsafeImageException $exception) {
            $this->assertSame('redirect', $exception->category);
        }

        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://example.com/image.jpg');
    }

    public static function unsafeRedirectProvider(): array
    {
        return [
            'localhost' => ['http://localhost:8001/user-data.php'],
            'cloud metadata' => ['http://169.254.169.254/latest/meta-data/'],
        ];
    }

    public function test_curl_pinning_entry_is_a_non_expiring_override(): void
    {
        $method = new \ReflectionMethod(SafeImageFetcher::class, 'curlResolveEntry');

        $this->assertSame(
            'example.com:443:93.184.216.34',
            $method->invoke($this->fetcher(), 'example.com', 443, self::PUBLIC_IP),
        );
    }

    public function test_it_rejects_an_image_larger_than_the_limit(): void
    {
        config()->set('security.image_fetcher.max_bytes', 32);
        Http::fake(['*' => Http::response(base64_decode(self::PNG), 200, [
            'Content-Type' => 'image/png',
            'Content-Length' => '100',
        ])]);

        $this->expectException(UnsafeImageException::class);
        $this->fetcher()->fetch('https://example.com/image.png');
    }

    public function test_it_rejects_an_oversized_body_without_content_length(): void
    {
        config()->set('security.image_fetcher.max_bytes', 32);
        Http::fake(['*' => Http::response(base64_decode(self::PNG), 200, [
            'Content-Type' => 'image/png',
        ])]);

        $this->expectException(UnsafeImageException::class);
        $this->fetcher()->fetch('https://example.com/image.png');
    }

    private function fetcher(?array $addresses = null): SafeImageFetcher
    {
        $addresses ??= [self::PUBLIC_IP];

        return new SafeImageFetcher(static fn (string $host): array => $addresses);
    }
}

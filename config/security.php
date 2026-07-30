<?php

$allowedImageHosts = array_values(array_filter(array_map(
    static fn (string $host): string => strtolower(trim($host)),
    explode(',', (string) env('SSRF_ALLOWED_IMAGE_HOSTS', ''))
)));

$allowedImagePorts = array_values(array_filter(array_map(
    static fn (string $port): int => (int) trim($port),
    explode(',', (string) env('SSRF_ALLOWED_IMAGE_PORTS', '443'))
)));

return [
    'image_fetcher' => [
        // A non-empty allowlist is strongly recommended in production. Each entry
        // permits the exact host and, when enabled below, its dot-delimited subdomains.
        'allowed_hosts' => $allowedImageHosts,
        'allow_subdomains' => (bool) env('SSRF_ALLOW_IMAGE_SUBDOMAINS', true),
        'allowed_ports' => $allowedImagePorts ?: [443],
        'max_bytes' => (int) env('SSRF_IMAGE_MAX_BYTES', 1024 * 1024),
        'connect_timeout' => (int) env('SSRF_IMAGE_CONNECT_TIMEOUT', 3),
        'timeout' => (int) env('SSRF_IMAGE_TIMEOUT', 5),
    ],
];

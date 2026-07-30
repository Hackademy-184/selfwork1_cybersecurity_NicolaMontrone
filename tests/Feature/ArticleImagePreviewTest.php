<?php

namespace Tests\Feature;

use App\Services\SafeImageFetcher;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleImagePreviewTest extends TestCase
{
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        RateLimiter::clear('article-image-preview:'.hash('sha256', 'ip:127.0.0.1'));
        $this->app->instance(SafeImageFetcher::class, new SafeImageFetcher(
            static fn (string $host): array => ['93.184.216.34']
        ));
    }

    public function test_component_only_renders_a_verified_data_uri(): void
    {
        Http::fake(['*' => Http::response(base64_decode(self::PNG), 200, ['Content-Type' => 'image/png'])]);

        Livewire::test(\App\Livewire\ArticleImagePreview::class)
            ->set('imageUrl', 'https://example.com/image.png')
            ->call('fetchPreview')
            ->assertSet('errorMessage', null)
            ->assertSet('imageData', fn ($value): bool => str_starts_with($value, 'data:image/png;base64,'))
            ->assertSee('data:image/png;base64,', false)
            ->assertDontSee('https://example.com/image.png', false);
    }

    public function test_component_clears_preview_and_shows_a_generic_error(): void
    {
        Livewire::test(\App\Livewire\ArticleImagePreview::class)
            ->set('imageUrl', 'https://127.0.0.1/private')
            ->call('fetchPreview')
            ->assertSet('imageData', null)
            ->assertSet('errorMessage', 'Impossibile recuperare un’immagine valida da questo indirizzo.')
            ->assertDontSee('127.0.0.1', false);

        Http::assertNothingSent();
    }

    public function test_component_never_exposes_an_invalid_response_body(): void
    {
        Log::spy();
        Http::fake(['*' => Http::response('INTERNAL_SECRET_TOKEN', 200, [
            'Content-Type' => 'text/html',
        ])]);

        Livewire::test(\App\Livewire\ArticleImagePreview::class)
            ->set('imageUrl', 'https://example.com/image.jpg?token=SENSITIVE_QUERY_TOKEN')
            ->call('fetchPreview')
            ->assertSet('imageData', null)
            ->assertSet('errorMessage', 'Impossibile recuperare un’immagine valida da questo indirizzo.')
            ->assertDontSee('INTERNAL_SECRET_TOKEN', false)
            ->assertDontSee('SENSITIVE_QUERY_TOKEN', false);

        Log::shouldHaveReceived('notice')->once()->withArgs(
            fn (string $message, array $context): bool => $context['host'] === 'example.com'
                && ! str_contains(json_encode($context), 'SENSITIVE_QUERY_TOKEN')
                && ! str_contains(json_encode($context), 'INTERNAL_SECRET_TOKEN')
        );
    }

    public function test_component_hides_unexpected_internal_errors(): void
    {
        $this->mock(SafeImageFetcher::class, function ($mock): void {
            $mock->shouldReceive('fetch')->once()->andThrow(new \RuntimeException('INTERNAL_NETWORK_DETAIL'));
        });

        Livewire::test(\App\Livewire\ArticleImagePreview::class)
            ->set('imageUrl', 'https://example.com/image.jpg')
            ->call('fetchPreview')
            ->assertSet('imageData', null)
            ->assertSet('errorMessage', 'Impossibile recuperare un’immagine valida da questo indirizzo.')
            ->assertDontSee('INTERNAL_NETWORK_DETAIL', false);
    }

    public function test_component_limits_preview_requests_to_ten_per_minute(): void
    {
        Http::fake(['*' => Http::response(base64_decode(self::PNG), 200, ['Content-Type' => 'image/png'])]);
        $component = Livewire::test(\App\Livewire\ArticleImagePreview::class)
            ->set('imageUrl', 'https://example.com/image.png');

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $component->call('fetchPreview')->assertSet('errorMessage', null);
        }

        $component->call('fetchPreview')
            ->assertSet('imageData', null)
            ->assertSet('errorMessage', 'Impossibile recuperare l’anteprima. Riprova più tardi.');

        Http::assertSentCount(10);
    }
}

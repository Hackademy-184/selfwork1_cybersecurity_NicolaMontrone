<?php

namespace App\Livewire;

use App\Exceptions\UnsafeImageException;
use App\Services\SafeImageFetcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Throwable;

class ArticleImagePreview extends Component
{
    public string $imageUrl = '';

    public ?string $imageData = null;

    public ?string $errorMessage = null;

    public function fetchPreview(SafeImageFetcher $fetcher): void
    {
        $this->imageData = null;
        $this->errorMessage = null;

        $this->validate([
            'imageUrl' => ['required', 'string', 'max:2048'],
        ]);

        $identity = auth()->id() !== null ? 'user:'.auth()->id() : 'ip:'.request()->ip();
        $rateLimitKey = 'article-image-preview:'.hash('sha256', $identity);

        if (! RateLimiter::attempt($rateLimitKey, 10, fn (): bool => true, 60)) {
            $this->errorMessage = 'Impossibile recuperare l’anteprima. Riprova più tardi.';

            Log::warning('Remote image preview rejected', [
                'user_id' => auth()->id(),
                'host' => $this->safeHostname(),
                'outcome' => 'rejected',
                'category' => 'rate_limit',
            ]);

            return;
        }

        try {
            $this->imageData = $fetcher->fetch($this->imageUrl)->dataUri();

            Log::info('Remote image preview fetched', [
                'user_id' => auth()->id(),
                'host' => $this->safeHostname(),
                'outcome' => 'success',
            ]);
        } catch (UnsafeImageException $exception) {
            $this->imageData = null;
            $this->errorMessage = 'Impossibile recuperare un’immagine valida da questo indirizzo.';

            Log::notice('Remote image preview rejected', [
                'user_id' => auth()->id(),
                'host' => $this->safeHostname(),
                'outcome' => 'rejected',
                'category' => $exception->category,
            ]);
        } catch (Throwable) {
            $this->imageData = null;
            $this->errorMessage = 'Impossibile recuperare un’immagine valida da questo indirizzo.';

            Log::error('Remote image preview failed', [
                'user_id' => auth()->id(),
                'host' => $this->safeHostname(),
                'outcome' => 'error',
                'category' => 'internal',
            ]);
        }
    }

    public function updatedImageUrl(): void
    {
        $this->imageData = null;
        $this->errorMessage = null;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.article-image-preview');
    }

    private function safeHostname(): ?string
    {
        $host = parse_url($this->imageUrl, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : null;
    }
}

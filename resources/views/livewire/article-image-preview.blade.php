<div class="mb-3">
    <label for="article-image-url" class="form-label fw-semibold">Image URL</label>
    <div class="input-group">
        <input
            id="article-image-url"
            type="url"
            class="form-control"
            wire:model="imageUrl"
            placeholder="https://images.example.com/photo.jpg"
            maxlength="2048"
            autocomplete="off"
        >
        <button class="btn btn-outline-primary" type="button" wire:click="fetchPreview" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="fetchPreview">Preview</span>
            <span wire:loading wire:target="fetchPreview">Loading…</span>
        </button>
    </div>

    @error('imageUrl')
        <div class="text-danger mt-2" role="alert">Inserisci un URL HTTPS valido.</div>
    @enderror

    @if ($errorMessage)
        <div class="alert alert-warning mt-3 mb-0" role="alert">{{ $errorMessage }}</div>
    @endif

    @if ($imageData && ! $errorMessage)
        <div class="mt-3">
            <img src="{{ $imageData }}" class="img-fluid rounded" alt="Remote image preview">
        </div>
    @endif
</div>

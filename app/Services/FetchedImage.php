<?php

namespace App\Services;

final readonly class FetchedImage
{
    public function __construct(
        public string $contents,
        public string $mimeType,
    ) {}

    public function dataUri(): string
    {
        return "data:{$this->mimeType};base64,".base64_encode($this->contents);
    }
}

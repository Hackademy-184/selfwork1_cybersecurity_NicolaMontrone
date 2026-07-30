<?php

namespace App\Exceptions;

use RuntimeException;

class UnsafeImageException extends RuntimeException
{
    public function __construct(public readonly string $category)
    {
        parent::__construct('The remote image could not be retrieved.');
    }
}

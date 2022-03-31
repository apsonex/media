<?php

namespace Apsonex\Media\Concerns;


use Illuminate\Support\Arr;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Mime\MimeTypesInterface;

trait InteractsWithMimeTypes
{

    protected ?MimeTypesInterface $mime = null;

    /**
     * Get the mime types instance.
     */
    public function getMimeTypes(): MimeTypesInterface
    {
        return $this->mime ??= new MimeTypes;
    }

    /**
     * Get the MIME type for a file based on the file's extension.
     */
    public function guessMimeFromFilename($filename): string
    {
        return $this->guessMimeFromExtension(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Get the MIME type for a given extension or return all mimes.
     */
    public function guessMimeFromExtension($extension): string
    {
        return Arr::first($this->getMimeTypes()->getMimeTypes($extension)) ?? 'application/octet-stream';
    }

    /**
     * Search for the extension of a given MIME type.
     */
    public function guessExtensionFromMimeType($mimeType): ?string
    {
        return Arr::first($this->getMimeTypes()->getExtensions($mimeType));
    }
}
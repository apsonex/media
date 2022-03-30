<?php

namespace Apsonex\Media\Factory\Results;

class ImageResult
{

    public ?string $path = null;
    public ?string $directory = null;
    public ?string $filename = null;
    public ?string $basename = null;
    public ?string $extension = null;
    public bool|null $optimize = false;
    public ?string $mime = null;
    public ?int $size = null;

    public static function make(array $data): static
    {
        $self = new static();

        foreach ($data as $key => $value) {
            if (property_exists($self, $key)) {
                $self->$key = $value;
            }
        }

        return $self;
    }
}
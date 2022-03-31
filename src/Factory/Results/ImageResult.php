<?php

namespace Apsonex\Media\Factory\Results;

use Illuminate\Contracts\Support\Arrayable;

class ImageResult implements Arrayable
{

    public ?string $disk = null;
    public ?string $path = null;
    public ?string $directory = null;
    public ?string $filename = null;
    public ?string $basename = null;
    public ?string $extension = null;
    public bool|null $optimize = false;
    public ?string $mime = null;
    public ?int $size = null;
    public ?int $width = null;
    public ?int $height = null;

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

    public function toArray(): array
    {
        return [
            'disk'      => $this->disk,
            'path'      => $this->path,
            'directory' => $this->directory,
            'filename'  => $this->filename,
            'basename'  => $this->basename,
            'extension' => $this->extension,
            'optimize'  => $this->optimize,
            'mime'      => $this->mime,
            'size'      => $this->size,
            'width'     => $this->width,
            'height'    => $this->height,
        ];
    }
}
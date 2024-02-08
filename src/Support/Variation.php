<?php

namespace Apsonex\Media\Support;

class Variation
{
    public string $name;

    public int $width;

    public int $height;

    public bool $constrainAspectRatio = true;

    public function width(int $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function height(int $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function constrainAspectRatio(bool $constrainAspectRatio = true): static
    {
        $this->constrainAspectRatio = $constrainAspectRatio;
        return $this;
    }

    public function small(): static
    {
        $this->name('sm');
        $this->width(474);
        $this->height(316);
        $this->constrainAspectRatio();

        return $this;;
    }

    public function large(): static
    {
        $this->name('lg');
        $this->width(1280);
        $this->height(720);
        $this->constrainAspectRatio();
        return $this;
    }

    public function hd(): static
    {
        $this->name('hd');
        $this->width(1920);
        $this->height(1080);
        $this->constrainAspectRatio();
        return $this;
    }

    public function suffix(): string
    {
        return implode('-', [
            $this->name,
            $this->width . 'w',
            $this->height . 'h',
        ]);
    }


    public static function make(): static
    {
        return new static;
    }
}

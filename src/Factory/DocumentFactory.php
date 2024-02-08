<?php

namespace Apsonex\Media\Factory;

use Apsonex\Media\Concerns\InteractWithTemporaryDirectory;
use Apsonex\Media\Exceptions\InvalidSourceTypeException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class DocumentFactory extends BaseFactory implements FactoryContract
{
    use InteractWithTemporaryDirectory;

    public UploadedFile $file;

    public $one;

    public function source(UploadedFile|string $src): static
    {
        throw_if(is_string($src), new InvalidSourceTypeException("Only Uploaded file instance is accepted"));

        $this->file = $src;

        $this->ext = $src->guessClientExtension();

        $this->configure();

        $this->one = $this->options;

        return $this;
    }

    protected function configure()
    {
        $this->ensureValidPath();

        $this->options = array_merge($this->options, [
            'mime'      => $this->guessMimeFromExtension($this->ext),
            'extension' => $this->ext,
            'type'      => 'document',
        ]);
    }

    public function process(): array
    {
        $this->options['variations']['original'] = $this->saveToDisk();

        return array_merge($this->baseAttributes(), [
            'variations' => $this->options['variations']
        ]);
    }

    protected function saveToDisk(): array
    {
        $this->disk->put($this->options['path'], $this->file->getContent(), [
            // 'visibility' => $this->options['visibility'] === 'public' ? 'public' : 'private',
            'mime' => $this->guessMimeFromExtension($this->ext),
        ]);

        $this->options['size'] = (int)$this->disk->size($this->options['path']);

        return [
            'path'      => $this->options['path'],
            'filename'  => $this->options['filename'],
            'basename'  => $this->options['basename'],
            'extension' => $this->options['extension'],
            'mime'      => $this->guessMimeFromExtension($this->ext),
            'size'      => $this->options['size'],
            'optimize'  => false,
        ];
    }

    protected function baseAttributes(): array
    {
        return [
            'disk'       => $this->diskName,
            'directory'  => $this->options['directory'],
            'visibility' => $this->options['visibility'],
            'type'       => $this->options['type'],
            'size'       => $this->options['size'],
            'mime'       => $this->options['mime'],
            'optimize'   => false,
        ];
    }
}
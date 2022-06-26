<?php

namespace Apsonex\Media\Factory;

use Apsonex\Media\Actions\ImageOptimizeAction;
use enshrined\svgSanitize\Sanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class SvgFactory extends BaseFactory implements FactoryContract
{

    protected string $svg;

    /**
     * Make instance of static
     */
    public static function make($src, $options = []): static
    {
        return (new static())->mergeOptions($options)->source($src);
    }

    public function source(mixed $src): static
    {
        $this->svg = is_string($src) ? $src : $src->getContent();
        //$this->ext = $src instanceof UploadedFile ? $src->guessClientExtension() : null;
        $this->sanitizeSvg();
        $this->ext = 'svg';
        $this->configure();
        return $this;
    }

    public function sanitizeSvg()
    {
        $sanitizer = new Sanitizer();
        $sanitizer->removeRemoteReferences(true);
        $this->svg = $sanitizer->sanitize($this->svg);
    }


    /**
     * Process request
     */
    public function process(): array
    {
        $this->options['variations']['original'] = $this->saveToDisk();

        $data = array_merge($this->baseAttributes(), [
            'variations' => $this->options['variations']
        ]);

        return $this->optimizeIfRequested($data);
    }

    /**
     * Save to disk
     */
    protected function saveToDisk(): array
    {
        $this->disk->put($this->options['path'], $this->svg, [
            'visibility' => $this->options['visibility'] === 'public' ? 'public' : 'private'
        ]);
        $this->options['size'] = (int)$this->disk->size($this->options['path']);

        return [
            'path'      => $this->options['path'],
            'filename'  => $this->options['filename'],
            'basename'  => $this->options['basename'],
            'extension' => $this->options['extension'],
            'mime'      => 'image/svg+xml',
            'size'      => $this->options['size'],
            'optimize'  => false,
        ];
    }

    /**
     * Configure paths, directory, basename, mime, extension
     */
    protected function configure()
    {
        $this->ensureValidPath();

        $this->options = array_merge($this->options, [
            'mime'      => 'image/svg+xml',
            'extension' => $this->guessExtensionFromMimeType('image/svg+xml'),
            'type'      => 'image',
        ]);
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

    protected function optimizeIfRequested($data)
    {
        if (!$this->triggerOptimization) return $data;

        $options = [
            'srcDisk'          => $this->diskName,
            'from'             => null,
            'to'               => null,
            'targetDisk'       => null,
            'quality'          => $this->optimizationOptions['quality'],
            'onFinishCallback' => $this->optimizationOptions['onFinishTrigger'] ?? null
        ];

        $totalNewSize = 0;
        $newData = $data;

        collect($data['variations'])
            ->when($this->triggerOptimization === 'queue', function (Collection $variations) use ($options) {
                $variations->each(function ($variation) use ($options) {
                    $options = array_merge($options, ['from' => $variation['path']]);
                    ImageOptimizeAction::queue(...$options);
                });
            })
            ->when($this->triggerOptimization !== 'queue', function (Collection $variations) use ($options, &$newData, &$totalNewSize) {
                $variations->each(function ($variation, $name) use ($options, &$newData, &$totalNewSize) {
                    $options = array_merge($options, ['from' => $variation['path']]);
                    $result = ImageOptimizeAction::make(...$options)->optimize();
                    $newSize = Storage::disk($options['srcDisk'])->size($options['from']);
                    $newData['optimize'] = true;
                    $newData['size'] = $totalNewSize + $newSize;
                    $totalNewSize = $totalNewSize + $newSize;
                    $newData['variations'][$name] = [
                        ...$newData['variations'][$name],
                        'size'     => $newSize,
                        'optimize' => true,
                    ];
                });
            });

        return $newData;
    }
}

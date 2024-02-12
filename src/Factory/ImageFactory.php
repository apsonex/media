<?php

namespace Apsonex\Media\Factory;


use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Intervention\Image\Image;
use Illuminate\Support\Collection;
use Intervention\Image\Constraint;
use Apsonex\Media\Support\Variation;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Apsonex\Media\Actions\ImageOptimizeAction;
use Apsonex\Media\Concerns\InteractsWithOptimizer;
use Apsonex\Media\Actions\MakeImageVariationsAction;
use Apsonex\Media\Concerns\InteractWithTemporaryDirectory;

class ImageFactory extends BaseFactory implements FactoryContract
{

    use InteractsWithOptimizer;

    use InteractWithTemporaryDirectory;

    protected string $interventionDriver = 'imagick';


    /**
     * Image instance
     */
    public ?Image $image;

    protected ?string $encodingFormat = null;

    protected array $variationsData = [];

    /**
     * Encode Image
     */
    public function encode(string $encode): static
    {
        $this->encodingFormat = $encode;
        return $this;
    }

    /**
     * Set Variations to be done
     */
    public function variations(Variation $variation): static
    {
        $this->variationsData = array_merge($this->variationsData, Arr::wrap($variation));

        return $this;
    }

    public function exportQuality(int $quality = 75): static
    {
        $this->exportQuality = ($quality > 0 && $quality < 100) ? $quality : 75;
        return $this;
    }

    /**
     * Process request
     */
    public function process(): array
    {
        throw_if(empty($this->variationsData), \RuntimeException::class, "Invalid variations data for images");

        $this->configure();

        $this->options['variations'] = [];

        $this->image->backup();

        /** @var Variation $variation */
        foreach ($this->variationsData as $variation) {
            $this->options['variations'][$variation->name] = $this->saveToDisk($variation);

            $this->image->reset();
        }

        $data = array_merge($this->baseAttributes(), [
            'variations' => $this->options['variations']
        ]);

        $res = $this->optimizeIfRequested($data);

        $this->image?->destroy();

        $this->image = null;

        return array_merge($res, [
            'size' => collect($this->options['variations'])->sum('size'),
        ]);
    }

    /**
     * Save to disk
     */
    protected function saveToDisk(Variation $variation): array
    {
        $pathinfo = pathinfo($this->options['path']);

        $dir = $pathinfo['dirname'];

        $file = str($pathinfo['filename'])->slug()->toString() . '-' . $variation->suffix();

        $newPath = $dir . '/' . $file . '.' . $this->options['extension'];

        $this->disk->put(
            $newPath,
            $this->image
                ->fit($variation->width, $variation->height, function (\Intervention\Image\Constraint $constraint) {
                    $constraint->aspectRatio();
                })
                ->encode($this->ext, $this->exportQuality)
        );

        $size = (int)$this->disk->size($newPath);

        return [
            'path'      => $newPath,
            'filename'  => $file,
            'basename'  => $file . '.' . $this->options['extension'],
            'extension' => $this->options['extension'],
            'mime'      => $this->image->mime(),
            'size'      => $size,
            'width'     => $this->image->width(),
            'height'    => $this->image->height(),
            'optimized'  => false,
        ];
    }

    /**
     * Configure paths, directory, basename, mime, extension
     */
    protected function configure()
    {
        $this->ensureValidPath();

        $this->options = array_merge($this->options, [
            'mime'      => $this->image->mime(),
            'extension' => $this->ext,
            'type'      => 'image',
        ]);
    }




    /**
     * Check is optimization required
     */
    protected function needOptimization(): bool
    {
        return $this->triggerOptimization === true;
    }


    /**
     * Instantiate Intervention Image object
     */
    public function source(mixed $src): static
    {
        try {
            if ($src instanceof \Intervention\Image\Image) {
                $this->image = $src;
                return $this;
            }

            $manager = (new ImageManager(['driver' => $this->interventionDriver]));

            $this->image = (is_object($src) && method_exists($src, 'getContent')) ?
                $manager->make($src->getContent()) :
                $manager->make($src);

            $this->ext = $this->guessExtensionFromMimeType($this->image->mime());

            return $this;
        } catch (Exception $e) {
            throw new Exception("Image source in not readable @ ImageFactory." . $e->getMessage());
        }
    }

    /**
     * Modify Intervention Image Instance
     */
    public function modifyImage($callback): static
    {
        $this->image = $callback($this->image);
        return $this;
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

    /**
     * Trigger optimization
     * We will queue it if $triggerOptimization === 'queue'
     * else invoke inline
     */
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

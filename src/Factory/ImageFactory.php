<?php

namespace Apsonex\Media\Factory;


use Exception;
use Illuminate\Support\Str;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Apsonex\Media\Concerns\InteractsWithOptimizer;
use Apsonex\Media\Concerns\InteractsWithMimeTypes;
use Apsonex\Media\Concerns\InteractWithTemporaryDirectory;

class ImageFactory
{

    use InteractsWithOptimizer;
    use InteractsWithMimeTypes;
    use InteractWithTemporaryDirectory;

    protected string $interventionDriver = 'imagick';

    protected bool $triggerOptimization = false;

    protected int $exportQuality = 75;

    /**
     * Factory options
     */
    public array $options = [
        'disk'       => null,
        'size'       => null,
        'mime'       => null,
        'visibility' => 'private',
        'directory'  => null,
        'filename'   => null,
        'basename'   => null,
        'extension'  => null,
        'optimized'  => false,
        'variations' => [
            'original' => [
                'path'      => null,
                'filename'  => null,
                'basename'  => null,
                'extension' => null,
                'optimized' => false,
                'mime'      => null,
                'size'      => null,
                'width'     => null,
                'height'    => null,
            ]
        ],
    ];

    /**
     * Image instance
     */
    public Image $image;

    /**
     * Full target path of the images.
     * If not provided we will make one.
     */
    public ?string $targetPath;

    /**
     * Storage Disk
     */
    public Filesystem $disk;

    protected ?string $encodingFormat = null;

    protected array $variationsData = [
        'data'  => null,
        'queue' => false,
    ];


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->disk = Storage::disk();

        $this->targetPath = null;
    }

    /**
     * Make instance of static
     */
    public static function make($src, $options = []): static
    {
        return (new static())->mergeOptions($options)->source($src);
    }

    /**
     * Set target path
     */
    public function path(?string $path = null): static
    {
        $this->options['path'] = $path;
        return $this;
    }

    /**
     * Mark visibility public
     */
    public function visibilityPublic(): static
    {
        $this->options['visibility'] = 'public';
        return $this;
    }

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
    public function variations(array $variations, $queue = false): static
    {
        $this->variationsData = [
            'data'  => $variations,
            'queue' => $queue === true,
        ];
        return $this;
    }

    /**
     * Set Storage Disk
     */
    public function storageDisk(Filesystem $disk = null): static
    {
        $this->disk = $disk ?: $this->disk;
        return $this;
    }

    public function exportQuality(int $quality = 75): static
    {
        $this->exportQuality = ($quality > 0 && $quality < 100) ? $quality : 75;
        return $this;
    }

    /**
     * Merge options
     */
    public function mergeOptions(array $options = []): static
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * Configure to do optimization
     */
    public function optimize(): static
    {
        $this->triggerOptimization = true;
        return $this;
    }

    /**
     * Process request
     */
    public function process(): array
    {
        $this->configure();

        $this->image->encode($this->extension(), $this->exportQuality);

        $this->options['variations']['original'] = $this->saveToDisk();

        return array_merge($this->baseAttributes(), [
            'variations' => $this->options['variations']
        ]);

        //        return $this->needOptimization() ?
        //            $this->optimizeAndSaveToDisk() :
        //            $this->saveToDisk($this->options['path'], $this->image);
    }

    /**
     * Save to disk
     */
    protected function saveToDisk(): array
    {
        $this->disk->put($this->options['path'], $this->image, [
            'visibility' => $this->options['visibility'] === 'public' ? 'public' : 'private'
        ]);
        $this->options['size'] = (int)$this->disk->size($this->options['path']);

        return [
            'path'      => $this->options['path'],
            'filename'  => $this->options['filename'],
            'basename'  => $this->options['basename'],
            'extension' => $this->options['extension'],
            'mime'      => $this->image->mime(),
            'size'      => $this->options['size'],
            'width'     => $this->image->width(),
            'height'    => $this->image->height(),
            'optimize'  => false,
        ];

        //        return $this->imageResults([
        //            'optimize' => false,
        //            'width'    => $this->image->width(),
        //            'height'   => $this->image->height(),
        //            'size'     => (int)$this->disk->size($this->options['path']),
        //        ]);
    }

    /**
     * Configure paths, directory, basename, mime, extension
     */
    protected function configure()
    {
        $this->ensureValidPath();

        $this->options = array_merge($this->options, [
            'mime'      => $this->image->mime(),
            'extension' => $this->extension(),
            'type'      => 'image',
        ]);
    }

    protected function ensureValidPath()
    {
        $pathinfo = pathinfo($this->options['path'] ??= $this->randomName());

        $dirname = $pathinfo['dirname'] === '.' ? null : $pathinfo['dirname'];

        $this->options = array_merge($this->options, [
            'path'      => implode('/', array_filter([$dirname, $pathinfo['basename']])),
            'directory' => $dirname,
            'basename'  => $pathinfo['basename'], // name.extension
            'filename'  => $pathinfo['filename'], // name
            'extension' => $pathinfo['extension'],
        ]);
    }

    /**
     * Random name
     */
    protected function randomName(): string
    {
        return md5(Str::uuid()->toString()) . '.' . $this->extension();
    }

    /**
     * Get extension
     */
    protected function extension(): string
    {
        return static::guessExtensionFromMimeType($this->image->mime());
    }
    //    /**
    //     * Optimize and save
    //     */
    //    protected function optimizeAndSaveToDisk(): ImageResult
    //    {
    //        $tempDir = $this->temporaryDirectory();
    //
    //        $tempPath = $tempDir->path(Str::uuid() . '.' . $this->extension());
    //
    //        $this->image->save($tempPath);
    //
    //        $this->optimizer()->optimize($tempPath);
    //
    //        $this->saveToDisk($this->options['path'], File::get($tempPath));
    //
    //        $tempDir->delete();
    //
    //        return $this->imageResults([
    //            'optimize' => true,
    //            'width'    => $this->image->width(),
    //            'height'   => $this->image->height(),
    //            'size'     => (int)$this->disk->size($this->options['path']),
    //        ])->toArray();

    //    }

    protected function imageResults($overrides = [])
    {
        return ImageResult::make(
            array_merge(
                $this->options,
                [
                    'disk' => $this->disk->getConfig()['driver']
                ],
                $overrides,
            )
        );
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
            'disk'       => $this->disk->getConfig()['driver'],
            'directory'  => $this->options['directory'],
            'visibility' => $this->options['visibility'],
            'type'       => $this->options['type'],
            'size'       => $this->options['size'],
            'mime'       => $this->options['mime'],
            'optimize'   => false,
        ];
    }
}

<?php

namespace Apsonex\Media\Factory;

use Exception;
use Illuminate\Support\Str;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\File;
use Symfony\Component\Mime\MimeTypes;
use Illuminate\Support\Facades\Storage;
use Apsonex\Media\Factory\Results\ImageResult;
use Illuminate\Contracts\Filesystem\Filesystem;
use Apsonex\Media\Factory\Concerns\InteractsWithOptimizer;
use Apsonex\Media\Factory\Concerns\InteractWithTemporaryDirectory;

class ImageFactory
{

    use InteractsWithOptimizer;
    use InteractWithTemporaryDirectory;

    /**
     * Factory options
     */
    public array $options = [
        'driver'    => 'imagick',
        'path'      => null,
        'directory' => null,
        'filename'  => null,
        'basename'  => null,
        'extension' => null,
        'optimize'  => true,
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


    /**
     * Variation Data
     */
    public array $variationsData = [
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
     * Set target path
     */
    public function path(?string $path = null): static
    {
        $this->options['path'] = $path;
        return $this;
    }

    /**
     * Encode Image
     */
    public function encode(string $encode): static
    {
        $this->options['encode'] = $encode;
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

    /**
     * Make instance of static
     */
    public static function make($src, $options = []): static
    {
        return (new static())->mergeOptions($options)->source($src);
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
        $this->options['optimize'] = true;
        return $this;
    }

    /**
     * Process request
     */
    public function process(): ImageResult
    {
        $this->configurePaths();

        $this->image->encode($this->extension());

        return $this->needOptimization() ?
            $this->optimizeAndSaveToDisk() :
            $this->saveToDisk($this->options['path'], $this->image);
    }

    /**
     * Configure paths, directory, basename, mime, extension
     */
    protected function configurePaths()
    {
        $this->ensureValidPath();

        $this->options = array_merge($this->options, [
            'mime'      => $this->image->mime(),
            'extension' => $this->extension(),
        ]);
    }

    protected function ensureValidPath()
    {
        $providedPath = str($this->options['path'] ??= $this->randomName());

        $this->options = array_merge($this->options, [
            'path'      => $providedPath->toString(),
            'directory' => $providedPath->dirname()->is('.') ? null : $providedPath->dirname()->toString(),
            'basename'  => $providedPath->basename()->beforeLast('.')->toString(),
            'filename'  => $providedPath->basename()->toString(),
            'extension' => $this->extension(),
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
        return app(MimeTypes::class)->getExtensions($this->image->mime())[0] ?? 'jpg';
    }

    /**
     * Optimize and save
     */
    protected function optimizeAndSaveToDisk(): ImageResult
    {
        $tempDir = $this->temporaryDirectory();

        $tempPath = $tempDir->path(Str::uuid() . '.' . $this->extension());

        $this->image->save($tempPath);

        $this->optimizer()->optimize($tempPath);

        $this->saveToDisk($this->options['path'], File::get($tempPath));

        $tempDir->delete();

        return $this->imageResults([
            'optimize' => true,
            'width'    => $this->image->width(),
            'height'   => $this->image->height(),
            'size'     => (int)$this->disk->size($this->options['path']),
        ]);
    }

    /**
     * Save to disk
     */
    protected function saveToDisk($path, $img): ImageResult
    {
        $this->disk->put($path, $img);

        return $this->imageResults([
            'optimize' => false,
            'width'    => $this->image->width(),
            'height'   => $this->image->height(),
            'size'     => (int)$this->disk->size($this->options['path']),
        ]);
    }

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
        return $this->options['optimize'] === true;
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

            $manager = (new ImageManager(['driver' => $this->options['driver']]));

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
}
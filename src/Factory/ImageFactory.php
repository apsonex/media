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
    protected array $options = [
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
    protected Image $image;

    /**
     * Full target path of the images.
     * If not provided we will make one.
     */
    protected ?string $targetPath;

    /**
     * Storage Disk
     */
    protected Filesystem $disk;


    /**
     * Variation Data
     */
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
            'filename'  => $providedPath->basename(),
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

        return ImageResult::make([
            'path'      => $this->options['path'],
            'directory' => $this->options['directory'],
            'filename'  => $this->options['filename'],
            'basename'  => $this->options['basename'],
            'extension' => $this->options['extension'],
            'optimize'  => true,
            'mime'      => $this->options['mime'],
            'size'      => (int)$this->disk->size($this->options['path']),
        ]);
    }

    /**
     * Save to disk
     */
    protected function saveToDisk($path, $img): ImageResult
    {
        $this->disk->put($path, $img);

        return ImageResult::make([]);
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
}
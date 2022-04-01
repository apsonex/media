<?php

namespace Apsonex\Media\Actions;

use Apsonex\Media\Concerns\HasSerializedCallback;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Intervention\Image\Image;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Svgo;
use Spatie\ImageOptimizer\Optimizers\Cwebp;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\ImageOptimizer\Optimizers\Pngquant;
use Spatie\ImageOptimizer\Optimizers\Gifsicle;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Illuminate\Contracts\Filesystem\Filesystem;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Apsonex\Media\Concerns\InteractsWithMimeTypes;
use Apsonex\Media\Concerns\InteractsWithOptimizer;
use Spatie\LaravelImageOptimizer\OptimizerChainFactory;
use Apsonex\Media\Concerns\InteractWithTemporaryDirectory;

class MakeImageVariationsAction
{

    use InteractsWithMimeTypes;
    use HasSerializedCallback;
    use InteractsWithMimeTypes;


    protected string $path;
    protected array $variations = [];
    protected Filesystem $srcDisk;
    protected Filesystem $targetDisk;
    protected mixed $callback = null;
    protected string $visibility;
    protected string $driver = 'imagick';
    protected ?Image $image;
    protected ?string $extension;
    protected ?string $directory;
    protected ?string $basename;
    protected ?string $filename;
    protected string $mime;
    protected array $finishedVariations = [];


    public function __construct(string $path, array $variations, string $srcDisk, ?string $targetDisk, mixed $callback)
    {
        $this->path = $path;
        $this->variations = $variations;
        $this->srcDisk = Storage::disk($srcDisk);
        $this->targetDisk = $targetDisk ? Storage::disk($targetDisk) : Storage::disk($srcDisk);
        $this->callback = $callback;
    }

    public static function queue(string $path, array $variations, string $srcDisk, ?string $targetDisk, mixed $callback)
    {
        dispatch(function () use ($path,  $variations,  $srcDisk, $targetDisk,  $callback) {
            MakeImageVariationsAction::execute($path,  $variations,  $srcDisk, $targetDisk,  $callback);
        });
    }

    /**
     * Execute
     */
    public static function execute(string $path, array $variations, string $srcDisk, ?string $targetDisk = null, mixed $callback = null): array
    {
        return (new static($path, $variations, $srcDisk, $targetDisk, $callback))->process();
    }

    protected function process(): array
    {
        if (empty($this->variations)) return $this->finishedVariations;

        $this->configure();

        $this->processVariations();

        $this->triggerCallback($this->callback, $this->finishedVariations);

        return $this->finishedVariations;
    }

    protected function configure()
    {
        $pathinfo = pathinfo(trim($this->path, '/'));
        $dirname = str($pathinfo['dirname'] ?? null)->replace('.', '')->trim('/')->toString();
        $this->extension = $pathinfo['extension'] ?? 'jpg';
        $this->directory = $dirname === "" ? null : $dirname;
        $this->basename = $pathinfo['basename'];
        $this->filename = $pathinfo['filename'];
        $this->mime = static::guessMimeFromExtension($this->extension);
        $this->visibility = $this->srcDisk->getVisibility($this->path);
        $this->image = (new ImageManager(['driver' => $this->driver]))->make($this->srcDisk->get($this->path));
        $this->extension = static::guessExtensionFromMimeType($this->image->mime());
    }

    protected function processVariations()
    {
        $this->image->backup();

        collect($this->variations)->each(fn($variation, $name) => $this->processVariation($name, $variation));
    }

    protected function processVariation($name, $variation)
    {
        [$w, $h] = explode('x', strtolower(explode('|', $variation)[0]));

        $this->targetDisk->put(
            $newPath = $this->variationPath($name, $w, $h),
            $this->image->fit($w, $h)->encode($this->extension),
            ['visibility' => $this->visibility]
        );

        $this->finishedVariations[$name] = [
            'path'       => $newPath,
            'width'      => $w,
            'height'     => $h,
            'basename'   => $this->basename,
            'extension'  => $this->extension,
            'visibility' => $this->visibility,
            'mime'       => $this->image->mime(),
            'size'       => $this->targetDisk->size($newPath),
        ];

        ImageOptimizeAction::queue($this->srcDisk->getConfig()['driver'], $newPath);

        $this->image->reset();
    }

    protected function variationPath($name, string $w, string $h): string
    {
        $filename = $this->filename . " " . $name . " " . "{$w}x${h}";

        return implode('/', array_filter([
            $this->directory,
            str($filename)->slug()->toString() . "." . $this->extension,
        ]));
    }


}
<?php

namespace Apsonex\Media\Actions;

use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Apsonex\Media\Concerns\HasSerializedCallback;
use Apsonex\Media\Concerns\InteractsWithMimeTypes;

class MakeImageVariationsAction
{

    use InteractsWithMimeTypes;
    use HasSerializedCallback;
    use InteractsWithMimeTypes;


    protected string $path;
    protected array $variations = [];
    protected Filesystem $srcDisk;
    protected string $srcDiskName;
    protected Filesystem $targetDisk;
    protected string $targetDiskName;
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


    public function __construct(string $path, array $variations, string $srcDisk, ?string $targetDisk = null, mixed $callback = null)
    {
        $this->path = $path;
        $this->variations = $variations;
        $this->srcDisk = Storage::disk($this->srcDiskName = $srcDisk);
        $this->targetDisk = $targetDisk ? Storage::disk($this->targetDiskName = $targetDisk) : Storage::disk($this->targetDiskName = $srcDisk);
        $this->callback = $callback;
    }

    /**
     * Queue process
     */
    public static function queue(string $path, array $variations, string $srcDisk, ?string $targetDisk = null, mixed $callback = null, $onQueue = 'default')
    {
        dispatch(function () use ($path, $variations, $srcDisk, $targetDisk, $callback) {
            MakeImageVariationsAction::execute($path, $variations, $srcDisk, $targetDisk, $callback);
        })->onQueue($onQueue);;
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

        ImageOptimizeAction::queue($this->targetDiskName, $newPath);

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
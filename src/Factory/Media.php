<?php

namespace Apsonex\Media\Factory;

use Illuminate\Support\Arr;
use Intervention\Image\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Apsonex\Media\Actions\ImageOptimizeAction;
use Illuminate\Contracts\Filesystem\Filesystem;
use Apsonex\Media\Actions\MakeImageVariationsAction;
use Apsonex\Media\Actions\DeleteImageVariationsAction;

class Media
{

    public function svgFactory(UploadedFile $src, ?string $targetPath = null, string $disk = null): SvgFactory
    {
        return SvgFactory::make($src)->path($targetPath)->storageDisk($disk);
    }
    
    /**
     * Make image factory
     */
    public function imageFactory(Image|UploadedFile|string $src, ?string $targetPath = null, string $disk = null): ImageFactory
    {
        return ImageFactory::make($src)->path($targetPath)->storageDisk($disk);
    }

    /**
     * Optimize images
     */
    public function imageOptimize(string $srcDisk, string $srcPath, string $srcTarget = null, string $targetDisk = null, int $quality = 85, mixed $callback = null): bool
    {
        return ImageOptimizeAction::make(
            srcDisk: $srcDisk,
            from: $srcPath,
            to: $srcTarget,
            targetDisk: $targetDisk,
            quality: $quality,
            onFinishCallback: $callback,
        )->optimize();
    }

    /**
     * Queue Image Optimizations
     */
    public function queueImageOptimize(string $srcDisk, string $srcPath, string $srcTarget = null, string $targetDisk = null, int $quality = 85, mixed $callback = null, $onQueue = 'default')
    {
        ImageOptimizeAction::queue(
            srcDisk: $srcDisk,
            from: $srcPath,
            to: $srcTarget,
            targetDisk: $targetDisk,
            quality: $quality,
            onFinishCallback: $callback,
            onQueue: $onQueue,
        );
    }

    /**
     * Make image variations
     */
    public function imageVariations(string $path, array $variations, string $srcDisk, string $targetDisk = null, $callback = null): array
    {
        return MakeImageVariationsAction::execute($path, $variations, $srcDisk, $targetDisk, $callback);
    }

    /**
     * Queue Image variations process
     */
    public function queueImageVariations(string $path, array $variations, string $srcDisk, string $targetDisk = null, $callback = null, string $onQueue = 'default')
    {
        MakeImageVariationsAction::queue($path, $variations, $srcDisk, $targetDisk, $callback, $onQueue);
    }

    /**
     * Delete image variations
     */
    public function deleteImageVariations(string $diskDriver, array $variations, mixed $callback = null): bool
    {
        return DeleteImageVariationsAction::execute($diskDriver, $variations, $callback);
    }

    /**
     * Queue Delete image variations
     */
    public function queueDeleteImageVariations(string $diskDriver, array $variations, mixed $callback = null, string $onQueue = 'default')
    {
        DeleteImageVariationsAction::queue($diskDriver, $variations, $callback, $onQueue);
    }

    /**
     * Delete empty directories
     */
    public static function deleteDirectoriesIfEmpty(string|Filesystem $disk, array|string $paths)
    {
        $disk = is_string($disk) ? Storage::disk($disk) : $disk;

        collect(Arr::wrap($paths))
            ->map(fn($p) => pathinfo($p)['dirname'] ?? null)
            ->filter()
            ->filter(fn($p) => $p !== '.')
            ->unique()
            ->map(function ($dir) use ($disk) {
                $files = $disk->files($dir);
                $dirs = $disk->directories($dir);
                if (empty($files) && empty($dirs)) {
                    $disk->deleteDirectory($dir);
                }
            });
    }

}
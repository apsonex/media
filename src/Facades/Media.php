<?php

namespace Apsonex\Media\Facades;

use Intervention\Image\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Apsonex\Media\Factory\ImageFactory;
use Illuminate\Contracts\Filesystem\Filesystem;


/**
 * @method static void deleteDirectoriesIfEmpty(string|Filesystem $disk, array|string $paths)
 * @method static bool deleteImageVariations(string $disk, array $variations, mixed $callback = null)
 * @method static ImageFactory imageFactory(Image|UploadedFile|string $src, string $targetPath = null, string $disk = null)
 * @method static array imageVariations(string $path, array $variations, string $srcDisk, string $targetDisk = null, $callback = null)
 * @method static void queueDeleteImageVariations(string $disk, array $variations, mixed $callback = null, string $onQueue = 'default')
 * @method static bool imageOptimize(string $srcDisk, string $srcPath, string $srcTarget = null, string $targetDisk = null, mixed $callback = null)
 * @method static void queueImageVariations(string $path, array $variations, string $srcDisk, string $targetDisk = null, $callback = null, string $onQueue = 'default')
 * @method static void queueImageOptimize(string $srcDisk, string $srcPath, string $srcTarget = null, string $targetDisk = null, mixed $callback = null, string $onQueue = 'default')
 */
class Media extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'media';
    }

}
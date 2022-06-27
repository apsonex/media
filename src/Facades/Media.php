<?php

namespace Apsonex\Media\Facades;

use Apsonex\Media\Factory\SvgFactory;
use Intervention\Image\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Apsonex\Media\Factory\ImageFactory;
use Apsonex\Media\Factory\DocumentFactory;
use Illuminate\Contracts\Filesystem\Filesystem;


/**
 * @method static SvgFactory svgFactory(Image|UploadedFile|string $src, string $targetPath = null, string $disk = null)
 * @method static ImageFactory imageFactory(Image|UploadedFile|string $src, string $targetPath = null, string $disk = null)
 * @method static DocumentFactory documentFactory(UploadedFile|string $src, string $targetPath = null, string $disk = null)
 * @method static array imageVariations(string $path, array $variations, string $srcDisk, string $targetDisk = null, $callback = null)
 * @method static bool imageOptimize(string $srcDisk, string $srcPath, string $srcTarget = null, string $targetDisk = null, int $quality = 85, mixed $callback = null)
 * @method static void queueImageVariations(string $path, array $variations, string $srcDisk, string $targetDisk = null, $callback = null, string $onQueue = 'default')
 * @method static void queueImageOptimize(string $srcDisk, string $srcPath, string $srcTarget = null, string $targetDisk = null, int $quality = 85, mixed $callback = null, string $onQueue = 'default')
 * @method static bool deleteVariations(string $disk, array $variations, mixed $callback = null)
 * @method static void queueDeleteVariations(string $disk, array $variations, mixed $callback = null, string $onQueue = 'default')
 * @method static void deleteDirectoriesIfEmpty(string|Filesystem $disk, array|string $paths)
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
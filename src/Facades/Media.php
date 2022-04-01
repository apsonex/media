<?php

namespace Apsonex\Media\Facades;

use Apsonex\Media\Actions\ImageOptimizeAction;
use Apsonex\Media\Factory\ImageFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Intervention\Image\Image;


/**
 * @method static ImageFactory imageFactory(Image|UploadedFile|string $src, string $targetPath = null, string $disk = null)
 * @method static ImageOptimizeAction optimizeImage(string $srcDisk, string $srcPath, string $srcTarget = null, string $targetDisk = null, mixed $callback = null)
 * @method static array makeImageVariations(string $path, array $variations, string $srcDisk, string $targetDisk = null, $callback = null)
 * @method static bool deleteImageVariations(string $disk, array $variations, mixed $callback = null)
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
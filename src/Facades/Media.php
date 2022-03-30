<?php

namespace Apsonex\Media\Facades;

use Apsonex\Media\Factory\ImageFactory;
use Apsonex\Media\Factory\ImageOptimizer;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Image;


/**
 * @method static ImageFactory putImage(Image|UploadedFile|string $src, string $targetPath = null, ?Filesystem $disk = null)
 * @method static ImageOptimizer imageOptimizer(Filesystem $srcDisk, string $srcPath, string $srcTarget = null, ?Filesystem $targetDisk = null, $keepOriginal = false)
 * method static bool deleteVariations(DocumentModel $document, $deleteEmptyDir = false)d
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
<?php

namespace Apsonex\Media\Tests;

use Apsonex\Media\Facades\Media;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MediaImageFactoryTest extends TestCase
{

    /** @test */
    public function it_store_image_to_disk()
    {
        $this->cleanTempDir();

        $uploadedFile = $this->testImage('food-hd-long.jpg');

        $data = Media::imageFactory($uploadedFile, 'some/path/name.jpg', Storage::disk('local'))->visibilityPublic()->process();

        $this->assertCount(1, File::allFiles(static::$tempDir . '/some/path'));

        $image = File::allFiles(static::$tempDir . '/some/path')[0];

        $this->assertEquals('name.jpg', $image->getFilename());

        $this->cleanTempDir();
    }

    /** @test */
    public function image_can_be_store_without_path_and_disk()
    {
        $this->cleanTempDir();

        $this->assertCount(0, File::allFiles(static::$tempDir));

        $uploadedFile = $this->testImage('food-hd-long.jpg');

        Media::imageFactory($uploadedFile)->process();

        $this->assertCount(1, File::allFiles(static::$tempDir));

        $this->cleanTempDir();
    }

    /** @test */
    public function it_can_upload_rets_object_images()
    {
        $this->cleanTempDir();

        $results = mls_data()->testImagesResponse(1);

        Media::imageFactory($results->first()->getContent())->process();

        $this->assertCount(1, File::allFiles(static::$tempDir));

        $this->cleanTempDir();
    }

    /** @test */
    public function it_can_trigger_image_optimization()
    {
        $this->cleanTempDir();

        $results = mls_data()->testImagesResponse(1);

        Media::imageFactory($results->first()->getContent())->optimize(true)->process();

        $this->assertCount(1, File::allFiles(static::$tempDir));

        $this->cleanTempDir();
    }
}
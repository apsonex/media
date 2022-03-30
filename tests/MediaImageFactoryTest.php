<?php

namespace Apsonex\Media\Tests;

use Apsonex\Media\Facades\Media;
use Apsonex\Media\Factory\ImageSize;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MediaImageFactoryTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->emptyTempDirectory();
    }

    /** @test */
    public function it_store_image_to_disk()
    {
        $uploadedFile = $this->testImage('food-hd-long.jpg');

        Media::putImage($uploadedFile, 'some/path/name.jpg', Storage::disk('local'))->process();

        $this->assertCount(1, File::allFiles(static::$tempDir . '/some/path'));

        $image = File::allFiles(static::$tempDir . '/some/path')[0];

        $this->assertEquals('name.jpg', $image->getFilename());
    }

    /** @test */
    public function image_can_be_store_without_path_and_disk()
    {
        $this->assertCount(0, File::allFiles(static::$tempDir));

        $uploadedFile = $this->testImage('food-hd-long.jpg');

        Media::putImage($uploadedFile)->process();

        $this->assertCount(1, File::allFiles(static::$tempDir));
    }

    /** @test */
    public function image_can_be_optimized()
    {
        $this->assertCount(0, File::allFiles(static::$tempDir));

        $uploadedFile = $this->testImage('food-hd-long.jpg');

        Media::putImage($uploadedFile)
            ->temporaryDirLocation(static::$tempDir . '/temp-dir')
            ->optimize()
            ->process();

        $this->assertCount(1, File::allFiles(static::$tempDir));
    }

    /** @test */
    public function it_can_upload_rets_object_images()
    {
        $this->assertCount(0, File::allFiles(static::$tempDir));

        $results = mls_data()->testImagesResponse(1);

        Media::putImage($results->first()->getContent())
            ->temporaryDirLocation(static::$tempDir . '/temp-dir')
            ->optimize()
            ->process();

        $this->assertCount(1, File::allFiles(static::$tempDir));
    }

    /** @test */
    public function it_can_optimize_images()
    {
        $this->assertCount(0, File::allFiles(static::$tempDir));

        $results = $this->testImage('test.jpg');

        $results->storePubliclyAs('path', 'test.jpg');

        $size1 = Storage::disk()->size('path/test.jpg');

        Media::imageOptimizer(Storage::disk(), 'path/test.jpg')
            ->temporaryDirLocation(static::$tempDir . '/temp-dir')
            ->optimize();

        $size2 = Storage::disk()->size('path/test.jpg');

        $this->assertTrue($size2 < $size1);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        foreach (File::directories(self::$tempDir) as $dir) {
            File::deleteDirectory($dir);
        }

        foreach (File::allFiles(self::$tempDir) as $allFile) {
            File::delete($allFile);
        }
    }
}
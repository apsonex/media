<?php

namespace Apsonex\Media\Tests;

use Apsonex\Media\Facades\Media;
use Apsonex\Media\SerializationExample;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Apsonex\Media\Actions\ImageOptimizeAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SvgOptimizeActionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_store_svg_to_disk()
    {
        $this->cleanTempDir();

        $uploadedFile = $this->testMedia('logo.svg');

        $data = Media::svgFactory($uploadedFile, 'some/path/logo.svg', 'local')->visibilityPublic()->process();

        $this->assertCount(1, File::allFiles(static::$tempDir . '/some/path'));

        $image = File::allFiles(static::$tempDir . '/some/path')[0];

        $this->assertEquals('logo.svg', $image->getFilename());

        $this->cleanTempDir();
    }

    /** @test */
    public function svg_can_be_store_without_path_and_disk()
    {
        $this->cleanTempDir();

        $this->assertCount(0, File::allFiles(static::$tempDir));

        $uploadedFile = $this->testMedia('logo.svg');

        Media::svgFactory($uploadedFile)->process();

        $this->assertCount(1, File::allFiles(static::$tempDir));

        $this->cleanTempDir();
    }

    /** @test */
    public function it_can_queue_svg_optimization()
    {
        config()->set('queue.default', 'database');

        $this->cleanTempDir();

        $this->createBatchJobSchema();

        $uploadedFile = $this->testMedia('logo.svg');

        $path = 'path/logo.svg';

        Media::svgFactory($uploadedFile, $path, 'local')->queueOptimization()->process();

        $size1 = Storage::disk('local')->size($path);

        $this->assertCount(1, $this->allJobs());

        $this->processQueuedClosure($this->allJobs()->first());

        $size2 = Storage::disk('local')->size($path);

        $this->assertTrue($size1 > $size2);

        $this->cleanTempDir();
    }
}
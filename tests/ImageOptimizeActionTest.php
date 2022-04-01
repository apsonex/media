<?php

namespace Apsonex\Media\Tests;

use Apsonex\Media\Facades\Media;
use Apsonex\Media\SerializationExample;
use Illuminate\Support\Facades\Storage;
use Apsonex\Media\Actions\ImageOptimizeAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImageOptimizeActionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_optimization_skip_if_optimized_size_is_larger_than_original()
    {
        $uploadedFile = $this->testImage('food-hd.jpg');

        Media::imageFactory($uploadedFile, 'some/path/name.jpg', Storage::disk('local'))->process();

        $size1 = Storage::disk('local')->size('some/path/name.jpg');

        ImageOptimizeAction::make(
            srcDisk: Storage::disk('local')->getConfig()['driver'],
            from: 'some/path/name.jpg',
            targetDisk: null,
            quality: 60
        )->optimize();

        $size2 = Storage::disk('local')->size('some/path/name.jpg');

        $this->assertTrue($size1 > $size2);
    }

    /** @test */
    public function it_can_queue_image_optimization()
    {
        $this->cleanTempDir();

        $this->createBatchJobSchema();

        $uploadedFile = $this->testImage('food-hd.jpg');

        $path = 'path/food-hd.jpg';

        Media::imageFactory($uploadedFile, $path, Storage::disk('local'))->process();

        $size1 = Storage::disk('local')->size($path);

        ImageOptimizeAction::queue(
            srcDisk: Storage::disk('local')->getConfig()['driver'],
            from: $path,
            targetDisk: null,
            quality: 60
        );

        $this->assertCount(1, $this->allJobs());

        $this->processQueuedClosure($this->allJobs()->first());

        $size2 = Storage::disk('local')->size($path);

        $this->assertTrue($size1 > $size2);

        $this->cleanTempDir();
    }

    /** @test */
    public function after_image_optimization_it_can_trigger_serialized_callback()
    {
        $uploadedFile = $this->testImage('food-hd.jpg');

        Media::imageFactory($uploadedFile, 'some/path/name.jpg', Storage::disk('local'))->process();

        ImageOptimizeAction::make(
            srcDisk: Storage::disk('local')->getConfig()['driver'],
            from: 'some/path/name.jpg',
            targetDisk: null,
            quality: 60,
            onFinishCallback: new SerializationExample('guri')
        )->optimize();
    }

}
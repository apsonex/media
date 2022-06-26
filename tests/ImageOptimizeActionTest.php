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
        $uploadedFile = $this->testMedia('food-hd.jpg');

        Media::imageFactory($uploadedFile, 'some/path/name.jpg', 'local')->process();

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
        config()->set('queue.default', 'database');

        $this->withoutExceptionHandling();

        $this->cleanTempDir();

        $this->createBatchJobSchema();

        $uploadedFile = $this->testMedia('food-hd.jpg');

        $path = 'path/food-hd.jpg';

        Media::imageFactory($uploadedFile, $path, 'local')->queueOptimization(['quality' => 50])->process();

        $size1 = Storage::disk('local')->size($path);

        $this->assertCount(1, $this->allJobs());

        $this->processQueuedClosure($this->allJobs()->first());

        $size2 = Storage::disk('local')->size($path);

        $this->assertTrue($size1 > $size2);

        $this->cleanTempDir();
    }
}
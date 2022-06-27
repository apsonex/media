<?php

namespace Apsonex\Media\Tests;

use Apsonex\Media\Actions\DeleteVariationsAction;
use Apsonex\Media\Actions\MakeImageVariationsAction;
use Apsonex\Media\Facades\Media;
use Apsonex\Media\Factory\ImageSize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class MakeImageVariationsActionTest extends TestCase
{

    use RefreshDatabase;

    /** @test */
    public function it_make_image_variations_provided()
    {
        $this->createBatchJobSchema();

        $this->cleanTempDir();;

        $variations = [
            'facebook-post' => ImageSize::facebookPost,
            'twitter-post'  => ImageSize::twitterPost,
        ];

        $result = MakeImageVariationsAction::execute(
            $this->uploadImageToDisk('/one/name.jpg'),
            $variations,
            Storage::disk('local')->getConfig()['driver'],
        );

        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_can_delete_variations()
    {
        $this->cleanTempDir();;

        $variations = [
            'facebook-post' => ImageSize::facebookPost,
            'twitter-post'  => ImageSize::twitterPost,
        ];

        $result = MakeImageVariationsAction::execute(
            $this->uploadImageToDisk('/one/name.jpg'),
            $variations,
            Storage::disk('local')->getConfig()['driver'],
        );

        $this->assertCount(2, $result);

        DeleteVariationsAction::execute(Storage::disk('local')->getConfig()['driver'], ['variations' => $result], function () {
            dD('one');
        });
    }

    protected function uploadImageToDisk($path = 'some/path/name.jpg')
    {
        $uploadedFile = $this->testMedia('food-hd.jpg');

        Media::imageFactory($uploadedFile, $path, 'local')->visibilityPublic()->process();

        return $path;
    }

}
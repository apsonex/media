<?php

namespace Apsonex\Media\Tests;

use Apsonex\Media\Factory\Media;

class MediaBindingTest extends TestCase
{

    /** @test */
    public function it_bind_media_factory_to_container()
    {
        $this->assertInstanceOf(Media::class, media());
    }

}
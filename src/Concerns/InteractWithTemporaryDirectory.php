<?php

namespace Apsonex\Media\Concerns;

use Spatie\TemporaryDirectory\TemporaryDirectory;

trait InteractWithTemporaryDirectory
{

    /**
     * Temp Directory Location
     */
    protected ?string $tempDirLocation = null;


    /**
     * Specify Temporary Directory Location
     */
    public function temporaryDirLocation($location): static
    {
        $this->tempDirLocation = $location;
        return $this;
    }



    /**
     * Get Temporary Directory Instance
     */
    protected function temporaryDirectory(): TemporaryDirectory
    {
        $tempDir = new TemporaryDirectory();

        if ($this->tempDirLocation) {
            $tempDir->location($this->tempDirLocation);
        }

        return $tempDir->create();
    }

}
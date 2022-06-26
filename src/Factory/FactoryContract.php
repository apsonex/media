<?php

namespace Apsonex\Media\Factory;

use Illuminate\Http\UploadedFile;

interface FactoryContract
{

    public function source(UploadedFile|string $src): static;

}
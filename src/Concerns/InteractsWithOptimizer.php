<?php

namespace Apsonex\Media\Concerns;

use Spatie\ImageOptimizer\OptimizerChain;
use function app;

trait InteractsWithOptimizer
{

    /**
     * Get image optimizer
     */
    protected function optimizer(): OptimizerChain
    {
        return app(\Spatie\ImageOptimizer\OptimizerChain::class);
    }

}
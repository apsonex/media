<?php

namespace Apsonex\Media\Factory\Concerns;

use Spatie\ImageOptimizer\OptimizerChain;

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
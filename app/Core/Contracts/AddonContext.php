<?php

namespace App\Core\Contracts;

use Illuminate\Contracts\Foundation\Application;

class AddonContext
{
    public function __construct(
        public readonly Application $app
    ) {}

    public function isProduction(): bool
    {
        return $this->app->isProduction();
    }
}

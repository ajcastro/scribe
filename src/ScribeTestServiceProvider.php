<?php

namespace Knuckles\Scribe;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\ServiceProvider;
use Knuckles\Scribe\Tests\HttpExamples\HttpExampleCreatorMiddleware;

class ScribeTestServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (
            $this->app->environment('testing') &&
            $this->app->runningInConsole() &&
            config('scribe.generate_test_examples', true)
        ) {
            $this->registerMiddleware();
        }
    }

    private function registerMiddleware(): void
    {
        $this->app[HttpKernel::class]->pushMiddleware(HttpExampleCreatorMiddleware::class);
    }
}

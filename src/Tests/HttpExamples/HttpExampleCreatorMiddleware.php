<?php

namespace Knuckles\Scribe\Tests\HttpExamples;

use Closure;
use Illuminate\Testing\TestResponse;
use Knuckles\Scribe\Tests\ExampleCreator;
use Knuckles\Scribe\Tests\ExampleRequest;

class HttpExampleCreatorMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $exampleCreator = ExampleCreator::getInstanceForRoute($request->route());

        $exampleCreator->addExampleRequest(new ExampleRequest($request, $response));

        return $response;
    }
}

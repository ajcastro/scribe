<?php

namespace Knuckles\Scribe\HttpExamples;

use Closure;
use Illuminate\Testing\TestResponse;
use Styde\Enlighten\Enlighten;

class HttpExampleCreatorMiddleware
{
    public function handle($request, Closure $next)
    {
        // Create the example and persist the request data before
        // running the actual request, so if the HTTP call fails
        // we will have information about the original request.
        // $this->httpExampleCreator->createHttpExample($request);

        dump('pumasok');
        $response = $next($request);

        dump($response->getContent());
        sleep(2);

        // $this->httpExampleCreator->saveHttpResponseData($request, $response);

        return $response;
    }
}

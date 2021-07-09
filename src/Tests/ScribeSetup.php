<?php

namespace Knuckles\Scribe\Tests;

use Knuckles\Scribe\Exceptions\LaravelNotPresent;

trait ScribeSetup
{
    public function setUpScribe(): void
    {
        if (empty($this->app)) {
            throw new LaravelNotPresent;
        }

        if (config('scribe.generate_test_examples', true)) {
            $this->afterApplicationCreated(function () {
                $this->makeExample();
            });

            $this->beforeApplicationDestroyed(function () {
                // $this->saveExampleStatus();
            });
        }
    }

    private function makeExample(): void
    {
        $exampleCreator = new ExampleCreator([
            'test'         => $this,
            'methodName'   => $this->getName(false),
            'providedData' => $this->getProvidedData(),
            'dataName'     => $this->dataName(),
        ]);

        ExampleCreator::setCurrentInstance($exampleCreator);

        // $this->app->make(ExampleCreator::class)->makeExample(
        //     get_class($this),
        //     $this->getName(false),
        //     $this->getProvidedData(),
        //     $this->dataName()
        // );
    }
}

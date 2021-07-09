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
                dump('making example...');
                $this->makeExample();
            });

            $this->beforeApplicationDestroyed(function () {
                dump('writing examples...');
                $instances = ExampleCreator::getInstances();
                foreach ($instances as $instance) {
                    dump($instance->toArray());
                }
                ExampleCreator::flushInstances();
                // $this->saveExampleStatus();
            });
        }
    }

    private function makeExample(): void
    {
        $exampleCreator = new ExampleCreator([
            'test'         => $this,
            'testMethod'   => $this->getName(false),
            'providedData' => $this->getProvidedData(),
            'dataName'     => $this->dataName(),
        ]);

        ExampleCreator::setCurrentInstance($exampleCreator);
    }
}

<?php

namespace Knuckles\Scribe\GroupedEndpoints;

use Knuckles\Camel\Camel;
use Knuckles\Scribe\Commands\GenerateDocumentation;
use Knuckles\Scribe\Matching\RouteMatcherInterface;

class GroupedEndpointsFactory
{
    public static function make(GenerateDocumentation $command, RouteMatcherInterface $routeMatcher): GroupedEndpointsContract
    {
        if ($command->option('no-extraction')) {
            return new GroupedEndpointsFromCamelDir;
        }

        if ($command->getDocConfig()->get('from_tests.enabled')) {
            return new GroupedEndpointsFromTests($command);
        }

        if ($command->isForcing()) {
            return new GroupedEndpointsFromApp($command, $routeMatcher, false);
        }

        if ($command->shouldExtract()) {
            return new GroupedEndpointsFromApp($command, $routeMatcher, true);
        }
    }
}

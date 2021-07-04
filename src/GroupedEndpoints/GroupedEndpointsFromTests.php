<?php

namespace Knuckles\Scribe\GroupedEndpoints;

use Knuckles\Camel\Camel;
use Knuckles\Scribe\Commands\GenerateDocumentation;
use Knuckles\Scribe\Matching\RouteMatcherInterface;

class GroupedEndpointsFromTests extends GroupedEndpointsAbstract implements GroupedEndpointsContract
{
    private bool $encounteredErrors = false;
    private array $endpointGroupIndexes = [];

    public function hasEncounteredErrors(): bool
    {
        return $this->encounteredErrors;
    }

    protected function extractEndpointsInfoAndWriteToDisk(): array
    {
        $endpoints = $this->extractEndpointsInfoFromTests();
        $groupedEndpoints = Camel::groupEndpoints($endpoints, $this->endpointGroupIndexes);
        $this->writeEndpointsToDisk($groupedEndpoints);
        $groupedEndpoints = Camel::prepareGroupedEndpointsForOutput($groupedEndpoints);
        return $groupedEndpoints;
    }

    private function extractEndpointsInfoFromTests(): array
    {
        // TODO: Implement:
        // First, run the phpunit tests to extract the $groupedEndpoints
        // and write them to the yaml files just like when we write them when we extracted them from Laravel app.
        // Then return the $groupedEndpoints.
        return [];
    }
}

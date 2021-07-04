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
        // TODO: Run the phpunit tests to extract and return the endpoints.
        return [];
    }
}

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
        $routes = $this->routeMatcher->matchFromTestsOnly()->getRoutes($this->docConfig->get('routes'), $this->docConfig->get('router'));
        $endpoints = $this->extractEndpointsInfoFromTests($routes);
        $groupedEndpoints = Camel::groupEndpoints($endpoints, $this->endpointGroupIndexes);
        $this->writeEndpointsToDisk($groupedEndpoints);
        $groupedEndpoints = Camel::prepareGroupedEndpointsForOutput($groupedEndpoints);
        return $groupedEndpoints;
    }

    private function extractEndpointsInfoFromTests(array $routes): array
    {
        // TODO: code
        return [];
    }
}

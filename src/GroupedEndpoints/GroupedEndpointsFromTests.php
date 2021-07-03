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
        $latestEndpointsData = [];
        $cachedEndpoints = [];
        $groups = [];

        if ($this->preserveUserChanges && is_dir(static::$camelDir) && is_dir(static::$cacheDir)) {
            $latestEndpointsData = Camel::loadEndpointsToFlatPrimitivesArray(static::$camelDir);
            $cachedEndpoints = Camel::loadEndpointsToFlatPrimitivesArray(static::$cacheDir, true);
            $groups = Camel::loadEndpointsIntoGroups(static::$camelDir);
        }

        $endpoints = $this->extractEndpointsInfoFromTests();
        $groupedEndpoints = Camel::groupEndpoints($endpoints, $this->endpointGroupIndexes);
        $this->writeEndpointsToDisk($groupedEndpoints);
        $groupedEndpoints = Camel::prepareGroupedEndpointsForOutput($groupedEndpoints);
        return $groupedEndpoints;
    }

    private function extractEndpointsInfoFromTests(): array
    {
        // TODO: Implement logic
        return [];
    }
}

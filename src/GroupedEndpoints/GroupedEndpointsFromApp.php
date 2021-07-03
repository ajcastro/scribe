<?php

namespace Knuckles\Scribe\GroupedEndpoints;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Knuckles\Camel\Camel;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Camel\Output\OutputEndpointData;
use Knuckles\Scribe\Commands\GenerateDocumentation;
use Knuckles\Scribe\Extracting\ApiDetails;
use Knuckles\Scribe\Extracting\Extractor;
use Knuckles\Scribe\Matching\MatchedRoute;
use Knuckles\Scribe\Matching\RouteMatcherInterface;
use Knuckles\Scribe\Tools\ConsoleOutputUtils as c;
use Knuckles\Scribe\Tools\ErrorHandlingUtils as e;
use Knuckles\Scribe\Tools\Utils as u;
use Knuckles\Scribe\Tools\Utils;
use Mpociot\Reflection\DocBlock;
use Mpociot\Reflection\DocBlock\Tag;
use ReflectionClass;
use Symfony\Component\Yaml\Yaml;

class GroupedEndpointsFromApp extends GroupedEndpointsAbstract implements GroupedEndpointsContract
{
    private $routeMatcher;
    private bool $preserveUserChanges;
    private bool $encounteredErrors = false;
    private array $endpointGroupIndexes = [];

    public function __construct(GenerateDocumentation $command, RouteMatcherInterface $routeMatcher, bool $preserveUserChanges)
    {
        parent::__construct($command);

        $this->routeMatcher = $routeMatcher;
        $this->preserveUserChanges = $preserveUserChanges;
    }

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

        $routes = $this->routeMatcher->getRoutes($this->docConfig->get('routes'), $this->docConfig->get('router'));
        $endpoints = $this->extractEndpointsInfoFromLaravelApp($routes, $cachedEndpoints, $latestEndpointsData, $groups);
        $groupedEndpoints = Camel::groupEndpoints($endpoints, $this->endpointGroupIndexes);
        $this->writeEndpointsToDisk($groupedEndpoints);
        $this->writeExampleCustomEndpoint();
        $groupedEndpoints = Camel::prepareGroupedEndpointsForOutput($groupedEndpoints);
        return $groupedEndpoints;
    }

    /**
     * @param MatchedRoute[] $matches
     * @param array $cachedEndpoints
     * @param array $latestEndpointsData
     * @param array[] $groups
     *
     * @return array
     * @throws \Exception
     */
    private function extractEndpointsInfoFromLaravelApp(array $matches, array $cachedEndpoints, array $latestEndpointsData, array $groups): array
    {
        $generator = new Extractor($this->docConfig);
        $parsedEndpoints = [];

        foreach ($matches as $routeItem) {
            $route = $routeItem->getRoute();

            $routeControllerAndMethod = u::getRouteClassAndMethodNames($route);
            if (!$this->isValidRoute($routeControllerAndMethod)) {
                c::warn('Skipping invalid route: ' . c::getRouteRepresentation($route));
                continue;
            }

            if (!$this->doesControllerMethodExist($routeControllerAndMethod)) {
                c::warn('Skipping route: ' . c::getRouteRepresentation($route) . ' - Controller method does not exist.');
                continue;
            }

            if ($this->isRouteHiddenFromDocumentation($routeControllerAndMethod)) {
                c::warn('Skipping route: ' . c::getRouteRepresentation($route) . ': @hideFromAPIDocumentation was specified.');
                continue;
            }

            try {
                c::info('Processing route: ' . c::getRouteRepresentation($route));
                $currentEndpointData = $generator->processRoute($route, $routeItem->getRules());
                // If latest data is different from cached data, merge latest into current
                [$currentEndpointData, $index] = $this->mergeAnyEndpointDataUpdates($currentEndpointData, $cachedEndpoints, $latestEndpointsData, $groups);

                // We need to preserve order of endpoints, in case user did custom sorting
                $parsedEndpoints[] = $currentEndpointData;
                if ($index !== null) {
                    $this->endpointGroupIndexes[$currentEndpointData->endpointId()] = $index;
                }
                c::success('Processed route: ' . c::getRouteRepresentation($route));
            } catch (\Exception $exception) {
                $this->encounteredErrors = true;
                c::error('Failed processing route: ' . c::getRouteRepresentation($route) . ' - Exception encountered.');
                e::dumpExceptionIfVerbose($exception);
            }
        }

        return $parsedEndpoints;
    }

    /**
     * @param ExtractedEndpointData $endpointData
     * @param array[] $cachedEndpoints
     * @param array[] $latestEndpointsData
     * @param array[] $groups
     *
     * @return array The extracted endpoint data and the endpoint's index in the group file
     */
    private function mergeAnyEndpointDataUpdates(ExtractedEndpointData $endpointData, array $cachedEndpoints, array $latestEndpointsData, array $groups): array
    {
        // First, find the corresponding endpoint in cached and latest
        $thisEndpointCached = Arr::first($cachedEndpoints, function (array $endpoint) use ($endpointData) {
            return $endpoint['uri'] === $endpointData->uri && $endpoint['httpMethods'] === $endpointData->httpMethods;
        });
        if (!$thisEndpointCached) {
            return [$endpointData, null];
        }

        $thisEndpointLatest = Arr::first($latestEndpointsData, function (array $endpoint) use ($endpointData) {
            return $endpoint['uri'] === $endpointData->uri && $endpoint['httpMethods'] == $endpointData->httpMethods;
        });
        if (!$thisEndpointLatest) {
            return [$endpointData, null];
        }

        // Then compare cached and latest to see what sections changed.
        $properties = [
            'metadata',
            'headers',
            'urlParameters',
            'queryParameters',
            'bodyParameters',
            'responses',
            'responseFields',
        ];

        $changed = [];
        foreach ($properties as $property) {
            if ($thisEndpointCached[$property] != $thisEndpointLatest[$property]) {
                $changed[] = $property;
            }
        }

        // Finally, merge any changed sections.
        $thisEndpointLatest = OutputEndpointData::create($thisEndpointLatest);
        foreach ($changed as $property) {
            $endpointData->$property = $thisEndpointLatest->$property;
        }
        $index = Camel::getEndpointIndexInGroup($groups, $thisEndpointLatest);

        return [$endpointData, $index];
    }

    private function isValidRoute(array $routeControllerAndMethod = null): bool
    {
        if (is_array($routeControllerAndMethod)) {
            [$classOrObject, $method] = $routeControllerAndMethod;
            if (u::isInvokableObject($classOrObject)) {
                return true;
            }
            $routeControllerAndMethod = $classOrObject . '@' . $method;
        }

        return !is_callable($routeControllerAndMethod) && !is_null($routeControllerAndMethod);
    }

    private function doesControllerMethodExist(array $routeControllerAndMethod): bool
    {
        [$class, $method] = $routeControllerAndMethod;
        $reflection = new ReflectionClass($class);

        if ($reflection->hasMethod($method)) {
            return true;
        }

        return false;
    }

    private function isRouteHiddenFromDocumentation(array $routeControllerAndMethod): bool
    {
        if (!($class = $routeControllerAndMethod[0]) instanceof \Closure) {
            $classDocBlock = new DocBlock((new ReflectionClass($class))->getDocComment() ?: '');
            $shouldIgnoreClass = collect($classDocBlock->getTags())
                ->filter(function (Tag $tag) {
                    return Str::lower($tag->getName()) === 'hidefromapidocumentation';
                })->isNotEmpty();

            if ($shouldIgnoreClass) {
                return true;
            }
        }

        $methodDocBlock = new DocBlock(u::getReflectedRouteMethod($routeControllerAndMethod)->getDocComment() ?: '');
        $shouldIgnoreMethod = collect($methodDocBlock->getTags())
            ->filter(function (Tag $tag) {
                return Str::lower($tag->getName()) === 'hidefromapidocumentation';
            })->isNotEmpty();

        return $shouldIgnoreMethod;
    }

    protected function writeExampleCustomEndpoint(): void
    {
        // We add an example to guide users in case they need to add a custom endpoint.
        if (!file_exists(static::$camelDir . '/custom.0.yaml')) {
            copy(__DIR__ . '/../../resources/example_custom_endpoint.yaml', static::$camelDir . '/custom.0.yaml');
        }
    }
}
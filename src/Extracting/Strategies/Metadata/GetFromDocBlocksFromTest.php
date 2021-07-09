<?php

namespace Knuckles\Scribe\Extracting\Strategies\Metadata;

use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\RouteDocBlocker;
use Knuckles\Scribe\Extracting\Strategies\Strategy;
use Mpociot\Reflection\DocBlock;

class GetFromDocBlocksFromTest extends GetFromDocBlocks
{
    public function __invoke(ExtractedEndpointData $endpointData, array $routeRules): array
    {
        $docBlocks = RouteDocBlocker::getDocBlocksFromRoute($endpointData->route);
        $methodDocBlock = $docBlocks['method'];
        $classDocBlock = $docBlocks['class'];

        return $this->getMetadataFromDocBlock($methodDocBlock, $classDocBlock);
    }
}

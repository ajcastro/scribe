<?php

namespace Knuckles\Scribe\Tests;

class ExampleCreator
{
    public $test;
    public $methodName;
    public $providedData;
    public $dataName;

    public static $currentInstance;

    public function __construct(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public static function getCurrentInstance()
    {
        return static::$currentInstance;
    }

    public static function setCurrentInstance($instance)
    {
        static::$currentInstance = $instance;
    }
}

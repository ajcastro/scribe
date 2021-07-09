<?php

namespace Knuckles\Scribe\Tests\Traits;

trait SetProps
{
    public function setProps(array $props)
    {
        foreach ($props as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }
}

<?php

namespace app\attributes;

#[\Attribute]
class Route
{
    public function __construct(public string $route, public string $method = 'GET')
    {

    }
}
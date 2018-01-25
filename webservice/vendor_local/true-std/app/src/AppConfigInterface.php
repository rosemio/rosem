<?php

namespace TrueStd\App;

interface AppConfigInterface
{
    public function get(string $key, $default = null);

    public function set(string $key, $value) : void;
}

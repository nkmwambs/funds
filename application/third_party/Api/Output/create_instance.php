<?php

$class = $path_parts['filename'];

$obj = new $class();

self::$output = $obj->output();
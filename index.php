<?php
$path = explode("/", $_SERVER['REQUEST_URI']);
$last = $path[count($path) - 1];
echo str_starts_with($last, "hello");

<?php

$QueryArr = explode("/", trim(explode('?', str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']))[0], '/'));
$Action = $QueryArr[0];
// $arr = explode("/", $Action);
// echo $Action . "<br>";
print_r($Action);

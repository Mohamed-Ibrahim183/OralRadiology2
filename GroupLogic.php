<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

require_once("./DataBase.class.php");
require_once("./Group.class.php");

$db = new DATABASE();
$pdo = $db->createConnection("oralradiology");
$group = new GROUP($pdo);


$path = explode("/", $_SERVER['REQUEST_URI']);
$last = $path[count($path) - 1];


switch ($_SERVER["REQUEST_METHOD"]) {
  case "POST": // insert
    if ($last === "Insert") {
      $group->Insert($_POST);
    }
    if ($last === "Delete") {
      // print_r($_POST);
      $group->Delete($_POST);
      echo "DELETED";
    }
    die();
  case "GET":
    if ($last === "Groups") {
      print_r($group->getAll());
    }
    if ($last === "getGroupsNames") {
      $data = $group->getGroupsNames();
      echo $data !== null ? json_encode($data) : "error";
      // $data = $group->getAll();
      // echo $data !== null ? $data : json_encode(array('Error' => 'Groups Error'));
    }
    die();
}

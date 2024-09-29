<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");


require_once("./DataBase.class.php");
require_once("./Group.class.php");

$db = new DATABASE();
$pdo = $db->createConnection("oralradiology");
$group = new GROUP($pdo);

$QueryArr = explode("/", trim(explode('?', str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']))[0], '/'));
$Action = $QueryArr[0];

switch ($_SERVER["REQUEST_METHOD"]) {
  case "POST":
    switch ($Action) {
      case "Insert":
        echo $group->Insert($_POST);
        break;
      case "Delete":
        $group->Delete($_POST);
        echo "DELETED";
        break;
      case "Changes":
        foreach ($_POST as $userID => $GroupName)
          $group->insertUserInGroup($userID, $GroupName);
        break;
    }
    break;

  case "GET":
    switch ($Action) {
      case "Groups":
        print_r($group->getAll());
        break;
      case "getGroupsNames":
        $data = $group->getGroupsNames();
        echo $data !== null ? json_encode($data) : "error";
        break;
      case "UsersMails":
        $result = $group->getUsersInGroup($QueryArr[-1]);
        echo json_encode($result);
        break;
    }
    break;
}

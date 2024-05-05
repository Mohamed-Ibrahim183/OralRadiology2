<?php

// FIRST OF ALL
require_once('./User.class.php');
require_once('./DataBase.class.php');
$db = new DATABASE();
$pdo = $db->createConnection("oralradiology");
$user = new USER($pdo);
$path = explode("/", $_SERVER['REQUEST_URI']);
$last = $path[count($path) - 1];
session_start();



// LOGIC
switch ($_SERVER["REQUEST_METHOD"]) {
  case "POST": // insert
    if ($last === "Insert") {
      $tableColumns = ["Password", "Type", "MSAId", "Name", "Email"];
      $lastID = $user->Insert($_POST, $tableColumns, "users", "");
      if (isset($_FILES["personalImage"])) {
        $selectedUser = $user->getMSAId();
        $user->uploadImage($_FILES["personalImage"], $selectedUser, $lastID);
      }
    }
    if ($last === "Login") {
      $data = $user->Login($_POST["MSAId"], $_POST["Password"]);
      if ($data)
        echo json_encode($data);
      // print_r($_POST);
    }
    die();
  case "GET":
    if ($path[count($path) - 2] === "getUserID") {
      echo json_encode($user->getUser($last, "Id"));
    }
    if ($path[count($path) - 2] === "getUserMSAId") {
      echo json_encode($user->getUser($last, "MSAId"));
    }
}

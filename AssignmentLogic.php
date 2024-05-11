<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

require_once("./Assignment.class.php");
require_once('./DataBase.class.php');
$db = new DATABASE();
$conn = $db->Connection2();
$pdo = $db->createConnection();
$assignment = new Assignment($conn);
$path = explode("/", $_SERVER['REQUEST_URI']);
$last = $path[count($path) - 1];

// print_r($path);
switch ($_SERVER["REQUEST_METHOD"]) {
  case 'POST':
    if ($last === "InsertAssignment") {
      if ($assignment->insertAssignment($_POST)) {
        echo "Inserted";
      }
      die();
    }
    if ($last === "AssignmentGroup") {
      // print_r($_POST);
      $res = $assignment->InsertAssignmentGroup($_POST);
      echo $res;
    }
    die();

  case 'GET':

    if ($last === "GetAll") {
      echo $assignment->fetchAllAssignments();
      die();
    }
    if (isset($_GET["assignmentId"])) {
      if (trim($_GET["assignmentId"]) !== "") {
        $assignmentData = $assignment->fetchAssignment($_GET["assignmentId"]);
        echo $assignmentData !== null ? json_encode($assignmentData) : json_encode(["error" => "No data found for assignment ID $assignmentId"]);
        die();
      }
    }
    if ($last === "AssignmentGroupsShow") {

      $res = $assignment->AssignmentGroupsShow($pdo);
      echo json_encode($res);
    }
}

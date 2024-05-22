<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

require_once("./Assignment.class.php");
require_once('./DataBase.class.php');
require_once("./User.class.php");

$db = new DATABASE();
$conn = $db->Connection2();
$pdo = $db->createConnection();
$assignment = new Assignment($conn);
// $path = explode('/', explode("?", $_SERVER['REQUEST_URI'], 2)[0]);
// $last = end($path);
$path = explode("/", $_SERVER['REQUEST_URI']);
$last = $path[count($path) - 1];

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
    // print_r($_GET);
    // echo $last;
    if ($last === "GetAll") {
      echo $assignment->fetchAllAssignments();
      die();
    }

    if ($last === "AssignmentGroupsShow") {

      $res = $assignment->AssignmentGroupsShow($pdo);
      echo json_encode($res);
    }
    if (str_starts_with($last, "GetSubmissionAssignment")) {
      $assignmentClass = new Assignment($pdo); // Should pass the correct connection variable
      $userClass = new USER($pdo);

      // Fetch parameters from URL
      $assignmentId = isset($_GET['assignmentId']) ? (int)$_GET['assignmentId'] : null;
      $userId = isset($_GET['userId']) ? (int)$_GET['userId'] : null;

      if (!$assignmentId || !$userId) {
        echo json_encode(['error' => 'Missing required parameters']);
        http_response_code(400); // Bad request
        exit;
      }

      try {
        // Fetch submissions data
        $submissions = $assignmentClass->getSubmissionsByAssignmentId($assignmentId);

        if (!$submissions) {
          echo json_encode(['error' => 'No submissions found']);
          http_response_code(404); // Not found
          exit;
        }

        $responseData = [];
        foreach ($submissions as $submission) {
          $userData = $userClass->getUser($submission['StudentId'], 'Id');
          if ($userData) {
            $userData['submitTime'] = $submission['submitTime'];
            $responseData[] = $userData;
          }
        }

        echo json_encode($responseData); // Corrected the response format
      } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        http_response_code(500); // Internal Server Error
      }
      die();
    }
    if (str_starts_with($last, "FetchAssignmentImages")) {
      $assignment = new Assignment($pdo);

      $studentId = isset($_GET['studentId']) ? (int)$_GET['studentId'] : null;
      $assignmentId = isset($_GET['assignmentId'])  ? (int)$_GET['assignmentId'] : null;

      if (!$studentId || !$assignmentId) {
        echo json_encode(['error' => 'Missing required parameters']);
        http_response_code(400); // Bad request
        exit;
      }

      try {
        $images = $assignment->getAssignmentImages($studentId, $assignmentId);
        if ($images === false) {
          throw new Exception("Failed to fetch images.");
        }

        if (empty($images)) {
          echo json_encode(['error' => 'No images found for the provided IDs']);
          http_response_code(404); // Not found
        } else {
          echo json_encode($images);
        }
      } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
      }
      die();
    }
    if (isset($_GET["assignmentId"])) {
      if (trim($_GET["assignmentId"]) !== "") {
        $assignmentData = $assignment->fetchAssignment($_GET["assignmentId"]);
        echo $assignmentData !== null ? json_encode($assignmentData) : json_encode(["error" => "No data found for assignment ID $assignmentId"]);
        die();
      }
    }
}

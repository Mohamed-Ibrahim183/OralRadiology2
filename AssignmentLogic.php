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
      $res = $assignment->InsertAssignmentGroup($_POST);
      echo $res;
    }
    if ($last == "SubmitAssignment") {
    }
    if ($last === "addCategory") {
      print_r($_POST);
      $done = $assignment->addCategory($pdo, $_POST["Name"]);
      echo $done ? "done" : "Error on adding the category";
    }
    if ($last === "UploadAssignmentImage") {
      $assignmentId = $_POST['assignmentId'];
      $studentId = $_POST['StudentId'];
      $category = json_decode($_POST['category'], true);

      $image = [
        "name" => $_FILES['file']['name'],
        "size" => $_FILES['file']['size'],
        "tmp_name" => $_FILES['file']['tmp_name'],
        "type" => $_FILES['file']['type']
      ];
      $result = $assignment->uploadAssignmentImage($pdo, $image, $studentId, $assignmentId, 1);
      if ($result != "Done") {
        echo json_encode(["msg" => $result]);
        exit;
      }
      echo json_encode(["msg" => "All files uploaded successfully."]);
      die();
    }
    if ($last === "newSubmission") {
      $done = $assignment->addNewSubmission($pdo, $_POST["studentId"], $_POST["assignmentId"]);
      echo $done ? "done" : "Error on adding the submission";
    }
    if ($last === "DeleteAssignment") {
      $done = $assignment->deleteAssignment($pdo, $_POST["assignmentId"]);
      echo $done ? "done" : "Error on deleting the assignment";
    }
    if ($last === "EvaluateImage") {
      // $done = $assignment->evaluateImage($pdo, $_POST["assignmentId"], $_POST
      $done = $assignment->evaluateImage($pdo, $_POST["ImageId"], $_POST["Grade"]);
      echo $done ? "Image Evaluated" : "Error on evaluating the image";
    }



  case 'GET':
    if ($last === "GetAll") {
      echo $assignment->fetchAllAssignments();
      die();
    }
    if ($last === "GetSubmission") {
      error_log("Processing GetSubmission request");

      $userId = $_GET['userId'] ?? null;

      if (!$userId) {
        error_log("User ID is missing");
        echo json_encode(['error' => 'No user ID provided']);
        die();
      } else {
        error_log("User ID: $userId");
      }

      $query = "SELECT assignmentId FROM submissions WHERE StudentId = $userId";
      $result = $this->conn->query($query);

      if ($result === false) {
        error_log("Database query failed: " . $this->conn->error);
        echo json_encode(['error' => "Failed to fetch assignments: " . $this->conn->error]);
        die();
      } else {
        error_log("Database query succeeded");
      }

      $assignments = [];
      while ($row = $result->fetch_assoc()) {
        $assignmentId = $row['assignmentId'];
        error_log("Fetched assignmentId: $assignmentId");

        // Fetch assignment name
        $stmt = $this->conn->prepare("SELECT Name FROM assignments WHERE Id = ?");
        $stmt->bind_param("i", $assignmentId);
        $stmt->execute();
        $nameResult = $stmt->get_result();
        if ($nameResult === false) {
          error_log("Failed to fetch assignment name: " . $this->conn->error);
          echo json_encode(['error' => "Failed to fetch assignment name: " . $this->conn->error]);
          die();
        }
        $nameRow = $nameResult->fetch_assoc();
        $assignmentName = $nameRow['Name'];
        error_log("Fetched assignmentName: $assignmentName");

        // Get total grade from Chart function
        $totalGrade = $assignment->Chart($assignmentId, $userId);
        error_log("Fetched totalGrade: $totalGrade");

        $assignments[] = [
          'assignmentId' => $assignmentId,
          'assignmentName' => $assignmentName,
          'totalGrade' => $totalGrade
        ];
      }

      echo json_encode(['assignments' => $assignments]);
      die();
    }


    if ($last === "GetCategories") {
      $cats = $assignment->getCategories($pdo);
      if ($cats)
        echo json_encode($cats);
      die();
    }

    if ($last === "GetCategories") {
      $cats = $assignment->getCategories($pdo);
      if ($cats)
        echo json_encode($cats);
      die();
    }
    if ($last === "submissionsStatus") {
      // for professor dashboard (Chart)
      $done = $assignment->getSubmissionStatus($pdo);
      echo $done ? json_encode($done) : "Error";
      exit();
    }

    if ($last === "AssignmentGroupsShow") {

      $res = $assignment->AssignmentGroupsShow($pdo);
      echo json_encode($res);
    }
    if (str_starts_with($last, "GetSubmissionAssignment")) {
      // echo "not empty";
      $assignmentClass = new Assignment($pdo); // Should pass the correct connection variable
      $userClass = new USER($pdo);

      // Fetch parameters from URL
      if (!isset($_GET['assignmentId'])) {
        echo json_encode(['error' => 'Missing required parameters (assignmentId)']);
        exit;
      }
      $assignmentId = (int)$_GET['assignmentId'];

      try {
        // Fetch submissions data
        $submissions = $assignmentClass->getSubmissionsByAssignmentId($assignmentId);
        if (!$submissions) {
          echo json_encode(['error' => 'No submissions found']);
          exit;
        }

        $responseData = [];
        foreach ($submissions as $submission) {
          $userData = $userClass->getUser($submission['StudentId'], 'Id');
          if ($userData)
            $responseData[] = $userData;
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

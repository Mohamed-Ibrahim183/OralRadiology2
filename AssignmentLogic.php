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
$assignment = new Assignment($pdo);



$QueryArr = explode("/", trim(explode('?', str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']))[0], '/'));
$Action = $QueryArr[0];

switch ($_SERVER["REQUEST_METHOD"]) {
  case 'POST':
    switch ($Action) {
      case "InsertAssignment":
        if ($assignment->insertAssignment($_POST)) {
          echo "Inserted";
        }
        break;
      case "DeleteAssignment":
        $done = $assignment->deleteAssignment($_POST["assignmentId"]);
        echo $done ? "done" : "Error on deleting the assignment";
        break;
      case "AssignmentGroup":
        $res = $assignment->InsertAssignmentGroup($_POST);
        echo $res;
        break;
      case "addCategory":
        print_r($_POST);
        $done = $assignment->addCategory($_POST["Name"]);
        echo $done ? "done" : "Error on adding the category";
        break;
      case "UploadAssignmentImage":
        print_r($_POST);
        $assignmentId = $_POST['assignmentId'];
        $studentId = $_POST['StudentId'];
        $category = json_decode($_POST['category'], true);

        $image = [
          "name" => $_FILES['file']['name'],
          "size" => $_FILES['file']['size'],
          "tmp_name" => $_FILES['file']['tmp_name'],
          "type" => $_FILES['file']['type']
        ];
        $result = $assignment->uploadAssignmentImage($pdo, $image, $studentId, $assignmentId, $_POST["category"], $_POST["submission"]); // fix the cat
        if ($result != "Done") {
          echo json_encode(["msg" => $result]);
          exit;
        }
        echo json_encode(["msg" => "All files uploaded successfully."]);
        break;
      case "newSubmission":
        $done = $assignment->addNewSubmission($pdo, $_POST["studentId"], $_POST["assignmentId"]);
        echo $done ? $pdo->lastInsertId() : "Error on adding the submission";
        break;
      case "EvaluateImage":
        // $done = $assignment->evaluateImage($pdo, $_POST["assignmentId"], $_POST
        $done = $assignment->evaluateImage($_POST["ImageId"], $_POST["Grade"]);
        echo $done ? "Image Evaluated" : "Error on evaluating the image";
        break;
      case "EditCategory":
        print_r($_POST);
        echo $assignment->editCategory($_POST["categoryId"], $_POST["newName"]);
        break;
    }

  case 'GET':
    switch ($Action) {
      case "GetAll":
        echo $assignment->fetchAllAssignments();
        break;
        case "getSingleAssignmentData":   
          if (isset($_GET['assignmentId'])) {
            $assignmentId = intval($_GET['assignmentId']); 
            $assignmentData = $assignment->getSingleAssignmentData($assignmentId);
            
            echo json_encode(
                 $assignmentData
            );
          } else {
            echo json_encode([
                'success' => false,
                'message' => 'assignmentId not provided'
            ]);
          }
          break;
        
      case "GetCategories":
        $cats = $assignment->getCategories();
        if ($cats)
          echo json_encode($cats);
        break;
      case "GetAssignmentSubmissionReport":
        echo json_encode($assignment->assignmentSubmissionReport());
        break;
      case "GetAssignmentSubmissionStudentReport":
        echo json_encode($assignment->studentAssignmentsReport($_GET["Id"]));
        // echo $assignment->studentAssignmentsReport($_GET["Id"]);
        break;
      case "DeleteCategory":
        echo $assignment->deleteCategory($_GET["Id"]);
        break;
      case "submissionsStatus":
        // for professor dashboard (Chart)
        $done = $assignment->getSubmissionStatus();
        echo $done ? json_encode($done) : "Error";
        break;
      case "AssignmentGroupsShow":
        $res = $assignment->AssignmentGroupsShow();
        echo json_encode($res);
        break;
      case "GetSubmissionAssignment":
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

            $userData["submission"] = $submission["Id"];
            $userData["Grade"] = $assignment->getGrade($submission["Id"]);
            $userData["submitTime"] = $submission["submitTime"];
            if ($userData)
              $responseData[] = $userData;
          }

          echo json_encode($responseData); // Corrected the response format
        } catch (Exception $e) {
          echo json_encode(['error' => $e->getMessage()]);

          http_response_code(500); // Internal Server Error
        }
        break;
      case "FetchAssignmentImages":
        try {
          $images = $assignment->getAssignmentImages($_GET["submission"]);
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
        break;
      case "GetAssignmentsForUser":
        echo json_encode($assignment->getAssignmentForUser($_GET["userId"]));
        break;
      case "GetSubmissionForUserReport":
        $submissions = $assignment->GetSubmissionById($_GET["StudentId"]);
        foreach ($submissions as &$submission) {
          // 1. set grade for the submission
          if (!isset($submission["Id"]))
            continue;

          $submission["Grade"] = $assignment->getGrade($submission["Id"]);
          // print_r($submission);

          // 2. get assignment Name for each submission
          $query = "SELECT * from assignments where Id=:selectedAssignment;";
          $stmt = $pdo->prepare($query);
          $stmt->bindParam(":selectedAssignment", $submission["assignmentId"]);
          $stmt->execute();
          $currentAssignment = $stmt->fetch(PDO::FETCH_ASSOC);
          // print_r($currentAssignment);
          $submission["assignmentName"] = $currentAssignment["Name"];

          // 3. get images for each submission
          $images = $assignment->getAssignmentImages($submission["Id"]);
          foreach ($images as &$image) {
            $query = "SELECT Name from categories where Id=:selected;";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":selected", $image["CategoryId"]);
            $stmt->execute();
            $category = $stmt->fetch(PDO::FETCH_ASSOC);

            $image["Category"] = $category["Name"];
          }
          $submission["images"] = $images;
        }

        echo json_encode($submissions);
        break;
      case "GetSubmissionById":
        echo json_encode($assignment->GetSubmissionById($_GET["StudentId"]));
        break;
      case "GetSubmissionByUserAndAssignment":
        $res = $assignment->GetSubmissionByUserAndAssignment($_GET["userId"], $_GET["assignmentId"]);
        echo json_encode($res);
        break;
      case "GetSubmissionByUser":
        if (isset($_GET["userId"])) {
          $res = $assignment->GetSubmissionByUser($_GET["userId"]);
          echo json_encode($res);
        } else {
          echo json_encode(['error' => 'User ID is missing']);
        }
        break;
      case "fetchAssignment":
        if (trim($_GET["assignmentId"]) !== "") {
          $assignmentData = $assignment->fetchAssignment($_GET["assignmentId"]);
          echo $assignmentData !== null ? json_encode($assignmentData) : json_encode(["error" => "No data found for assignment ID $assignmentId"]);
        }
        break;
    }
}

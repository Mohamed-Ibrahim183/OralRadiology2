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
      case "UpdateAssignment":
        if ($assignment->UpdateAssignment($_POST)) {
          echo "Updated";
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
        $weekNum = $_POST['weekNum'];

        $image = [
          "name" => $_FILES['file']['name'],
          "size" => $_FILES['file']['size'],
          "tmp_name" => $_FILES['file']['tmp_name'],
          "type" => $_FILES['file']['type']
        ];
        $result = $assignment->uploadAssignmentImage($pdo, $image, $studentId, $assignmentId, $_POST["category"], $_POST["submission"], $weekNum); // fix the cat
        if ($result != "Done") {
          echo json_encode(["msg" => $result]);
          exit;
        }
        echo json_encode(["msg" => "All files uploaded successfully."]);
        break;
      case "newSubmission":
        $done = $assignment->addNewSubmission($pdo, $_POST["studentId"], $_POST["assignmentId"], $_POST["weekNum"]);
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
      case "updateStartWeek":
        echo $assignment->updateStartWeek($_POST);
        break;
    }

  case 'GET':
    switch ($Action) {
      case "GetAll":
        echo $assignment->fetchAllAssignments();
        break;
      case "getAllAssignmentsNames":
        echo $assignment->getAllAssignmentsNames();
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
        // print_r($_GET);
        echo  json_encode($assignment->getFullSubmissionDataByAssignmentId($_GET["assignmentId"]));
        break;
      case "FetchAssignmentImages":

        if (isset($_GET["submission"]))
          echo json_encode($assignment->getAssignmentImages($_GET["submission"]));
        break;
      case "GetAssignmentsForUser":
        echo json_encode($assignment->getAssignmentForUser($_GET["userId"]));
        break;
      case "GetSubmissionForUserReport":

        echo json_encode($assignment->GetSubmissionForUserReport($_GET["StudentId"]));
        break;
      case "getBestGradeStudentAssignment":
        echo json_encode($assignment->getBestGrade($_GET["assignmentId"], $_GET["studentId"]));
        break;
      case "GetSubmissionById":
        echo json_encode($assignment->GetSubmissionById($_GET["StudentId"]));
        break;
      case "GetSubmissionByUserAndAssignment":
        $res = $assignment->GetSubmissionByUserAndAssignment($_GET["userId"], $_GET["assignmentId"]);
        echo json_encode($res);
        break;
      case "getSubmissionUserAssignmentWeek":
        $res = $assignment->getSubmissionUserAssignmentWeek($_GET["userId"], $_GET["assignmentId"], $_GET["weekNum"]);
        echo json_encode($res);
        break;
      case "getSubmittedAssignmentCategories":
        echo json_encode($assignment->getSubmittedAssignmentCategories($_GET["userId"], $_GET["assignmentId"], $_GET["weekNum"]));
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
      case "getstartweek":
        echo json_encode($assignment->getstartweek());
        break;
    }
}

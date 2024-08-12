<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");
class Assignment
{
	private $Id, $Name, $ProfessorId, $maxLimitImages, $Topic, $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
	}
	public function insertAssignment($data)
	{
		foreach ($data as $value) {
			if (empty($value)) {
				echo json_encode(['error' => 'Missing required fields']);
				http_response_code(400); // Bad request
				return false;
			}
		}
		$stmt = $this->conn->prepare("INSERT INTO assignments (Name, ProfessorId, maxLimitImages, Topic) VALUES (?, ?, ?, ?)");
		if (!$stmt) {
			echo "Prepare failed: (" . $this->conn->errno . ") " . $this->conn->error;
			return false;
		}
		$stmt->bind_param("siis", $data['Name'], $data['ProfessorId'], $data['maxLimitImages'], $data['Topic']);
		if (!$stmt->execute()) {
			echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
			return false;
		}
		$stmt->close();
		return true;
	}
	public function fetchAssignment($assignmentId)
	{
		$stmt = $this->conn->prepare("SELECT Name, Topic, maxLimitImages FROM assignments WHERE Id = ?");
		if (!$stmt) {
			echo "Prepare failed: (" . $this->conn->errno . ") " . $this->conn->error;
			return false;
		}

		$stmt->bind_param("i", $assignmentId);
		$stmt->execute();
		$result = $stmt->get_result();
		$assignmentData = $result->fetch_assoc();
		$stmt->close();
		return $assignmentData;
	}
	public function fetchAllAssignments()
	{
		$query = "SELECT * FROM assignments";
		$result = $this->conn->query($query);

		if ($result === false) {
			return json_encode(['error' => "Failed to fetch assignments: " . $this->conn->error]);
		}

		$assignments = [];
		while ($row = $result->fetch_assoc()) {
			$assignments[] = $row;
		}
		return json_encode($assignments);
	}
	public function uploadImages($files, $assignmentId, $studentId, $maxImages, $ASName, $MSAId)
	{
		$uploadedFiles = 0;
		$responses = [];

		// Create submission record only once
		$submitTime = date("Y-m-d H:i:s");
		$stmtSubmissions = $this->conn->prepare("INSERT INTO submissions (assignmentId, StudentId, submitTime) VALUES (?, ?, ?)");
		if (!$stmtSubmissions) {
			return ['error' => 'Failed to prepare submission statement: ' . $this->conn->error];
		}
		$stmtSubmissions->bind_param('iis', $assignmentId, $studentId, $submitTime);
		if (!$stmtSubmissions->execute()) {
			return ['error' => 'Failed to insert submission record: ' . $stmtSubmissions->error];
		}
		$stmtSubmissions->close();

		foreach ($files['tmp_name'] as $key => $tmpName) {
			if ($uploadedFiles >= $maxImages) {
				$responses[] = ['error' => 'Image limit exceeded'];
				break;
			}

			$studentDirectory = "../uploads/$ASName";
			if (!file_exists($studentDirectory)) {
				mkdir($studentDirectory, 0777, true);
			}
			$studentDirectory = "../uploads/$ASName/$MSAId";
			if (!file_exists($studentDirectory)) {
				mkdir($studentDirectory, 0777, true);
			}
			$numFiles = 1 + count(array_diff(scandir($studentDirectory), array('.', '..')));

			$newFileName = "{$ASName}-student-{$MSAId}-Img{$numFiles}.jpg";
			$targetPath = "../uploads/$ASName/$MSAId/" . $newFileName;

			if (move_uploaded_file($tmpName, $targetPath)) {
				$stmt = $this->conn->prepare("INSERT INTO assignmentimages (Path, StudentID, AssignmentId, CategoryId) VALUES (?, ?, ?, 1)");
				if (!$stmt) {
					$responses[] = ['error' => 'Failed to prepare statement'];
					continue;
				}
				$stmt->bind_param('sii', $targetPath, $studentId, $assignmentId);
				if ($stmt->execute()) {
					$responses[] = ['success' => 'Image uploaded successfully', 'path' => $targetPath];
					$uploadedFiles++;
				} else {
					$responses[] = ['error' => 'Failed to insert image record: ' . $stmt->error];
				}
				$stmt->close();
			} else {
				$responses[] = ['error' => 'Failed to move uploaded file'];
			}
		}
		return $responses;
	}

	public function getCategories($pdo)
	{
		$query = "Select * from categories;";
		$stmt = $pdo->prepare($query);
		$stmt->execute();
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $results;
	}
	public function addCategory($pdo, $category)
	{
		$query = "Insert Into categories (Name) Values (:Cat)";
		$stmt = $pdo->prepare($query);
		$name = htmlspecialchars(trim($category));
		$stmt->bindParam(":Cat", $category);
		$stmt->execute();
		return True;
	}
	public function InsertAssignmentGroup($data)
	{
		foreach ($data as $value) {
			if ($value === "")
				return false; // Indicate that the insertion failed due to missing data
		}
		$action = "Insert";
		$query = "Select * from GroupsAssignments";
		$result = $this->conn->query($query);
		while ($row = $result->fetch_assoc()) {
			if ($row["Assignment"] === $data["AssignmentId"] and $row["Group"] === $data["GroupId"]) {
				$action = "Update";
				break;
			}
		}
		$open = date("Y-m-d H:i:s", strtotime($data["openTime"]));
		$close = date("Y-m-d H:i:s", strtotime($data["closeTime"]));;
		$assignmentId = $data["AssignmentId"];
		$groupId = $data["GroupId"];
		if ($action === "Insert") {

			$stmt = $this->conn->prepare("INSERT INTO GroupsAssignments (`open`, `close`, `Assignment`, `Group`) VALUES (?, ?, ?, ?)");
			if (!$stmt) {
				echo "Prepare failed: (" . $this->conn->errno . ") " . $this->conn->error;
				return false;
			}

			$stmt->bind_param("ssii", $open, $close, $assignmentId, $groupId);
			if (!$stmt->execute()) {
				echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
				return false;
			}

			$stmt->close();
			return true;
		} else if ($action === "Update") {
			$stmt = $this->conn->prepare("Update GroupsAssignments set open =?, close =? WHERE Assignment =? AND `Group` =?");
			$stmt->bind_param("ssii", $open, $close, $assignmentId, $groupId);
			$stmt->execute();
			$stmt->close();
			return true;
		}
	}
	public function DeleteAssignment($pdo, $assignmentId)
	{
		$query = "DELETE from assignments where Id=:Selected;";
		$stmt = $pdo->prepare($query);
		$stmt->bindParam(':Selected', $assignmentId);
		$stmt->execute();
		return true;
	}
	function countFilesInFolder($folderPath)
	{
		// chatGPT
		// Check if the directory exists 
		if (!is_dir($folderPath)) {
			return 0; // Return 0 if the folder does not exist
		}

		// Get an array of all files and directories in the folder
		$files = scandir($folderPath);

		// Filter out the current (.) and parent (..) directories
		$files = array_diff($files, array('.', '..'));

		// Count the number of items in the array
		$fileCount = count($files);

		return $fileCount;
	}
	public function addNewSubmission($pdo, $student, $assignment)
	{
		$query = "INSERT into submissions (StudentId, assignmentId) Values (:student, :assignment);";
		$stmt = $pdo->prepare($query);
		$stmt->bindParam(':student', $student);
		$stmt->bindParam(':assignment', $assignment);
		$stmt->execute();
		return true;
	}
	public function uploadAssignmentImage($pdo, $image, $studentId, $assignmentId, $category, $submission)
	{
		$query = "SELECT Id from categories where Name=:category;";
		$stmt = $pdo->prepare($query);
		$stmt->bindParam(':category', $category);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		$category = $result["Id"];

		if ($submission < 0)
			return ["Err" => "error in submission Id, try again sub=" . $submission];
		$fileName = $image["name"];
		$fileSize = $image["size"];
		$fileTmpName = $image["tmp_name"];
		$fileType = $image["type"];

		// Allowed file types
		$allowedTypes = array("image/jpeg", "image/png", "image/gif");

		// Validate file type
		if (!in_array($fileType, $allowedTypes)) {
			return "Error: File is not an image.";
		}
		$targetDir = "../uploads/Assignments/" . $assignmentId . "/" . $studentId;
		if (!is_dir($targetDir)) {
			if (!mkdir($targetDir, 0777, true)) {
				return "Error: Failed to create directory.";
			}
		}
		$countFiles = $this->countFilesInFolder($targetDir);
		// Generate unique filename
		$extension = pathinfo($fileName, PATHINFO_EXTENSION);
		$targetFile = $targetDir . "/" . $countFiles . "." . $extension;

		// Move uploaded file to target directory
		if (move_uploaded_file($fileTmpName, $targetFile)) {
			// Update database with file path
			$query = "INSERT INTO assignmentimages (Path, StudentID, AssignmentId, CategoryId, submissionId) 
										VALUES (:path, :student, :assignment, :category, :submission)";
			$stmt = $pdo->prepare($query);
			$stmt->bindParam(":path", $targetFile);
			$stmt->bindParam(":student", $studentId);
			$stmt->bindParam(":assignment", $assignmentId);
			$stmt->bindParam(":category", $category);
			$stmt->bindParam(":submission", $submission);

			if ($stmt->execute()) {
				return "Done";
			} else {
				return "Error: Database update failed.";
			}
		} else {
			return "Error: Failed to move uploaded file.";
		}
	}
	public function getAssignmentForUser($pdo, $user)
	{
		// 1.get the user Group from the usersInGroups table
		$query = "SELECT GroupId FROM usersingroups WHERE userId=:student";
		$stmt = $pdo->prepare($query);
		$stmt->bindParam(":student", $user);
		$stmt->execute();
		$group = $stmt->fetch(PDO::FETCH_ASSOC); // ["GroupId"]
		if (!$group)
			return ["Err" => 1]; // user not in a group


		// 2. get all assignments Ids that allowed for this group
		$query = "SELECT * FROM groupsassignments WHERE `Group`=:group";
		$stmt = $pdo->prepare($query);
		$stmt->bindParam(":group", $group["GroupId"]);
		$stmt->execute();
		$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC); // ["Assignment"]

		$assignmentList = array();
		foreach ($assignments as $assignment) {
			$query = "SELECT * from assignments where Id=:assignment";
			$stmt = $pdo->prepare($query);
			$stmt->bindParam(":assignment", $assignment["Assignment"]);
			$stmt->execute();
			$newAssignment = $stmt->fetch(PDO::FETCH_ASSOC);
			$assignmentList[] = $newAssignment;
		}
		return $assignmentList;
	}
	public function getSubmissionStatus($pdo)
	{
		$query = "SELECT * from assignments;";
		$stmt = $pdo->prepare($query);
		$stmt->execute();
		$Assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (!$Assignments) return false;
		foreach ($Assignments as $key => $value) {
			$query = "SELECT * from submissions Where assignmentId=:selected;";
			$stmt = $pdo->prepare($query);
			$stmt->bindParam(":selected", $value["Id"]);
			$stmt->execute();
			$assSubmission = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$Assignments[$key]["Submitted"] = $assSubmission ?  count($assSubmission) : 0;
		}
		return $Assignments;
	}

	public function AssignmentGroupsShow($pdo)
	{
		$query = "Select * from GroupsAssignments";
		$stmt = $pdo->prepare($query);
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$final = [];
		foreach ($result as $value) {
			$query2 = "SELECT Name from `groups` where id =:selected";
			$stmt2 = $pdo->prepare($query2);
			$stmt2->bindParam(':selected', $value["Group"]);
			$stmt2->execute();
			$groupName = $stmt2->fetch(PDO::FETCH_ASSOC);
			// echo $groupName["Name"];

			$query2 = "SELECT Name from `assignments` where id =:selected";
			$stmt2 = $pdo->prepare($query2);
			$stmt2->bindParam(':selected', $value["Assignment"]);
			$stmt2->execute();
			$AssignmentName = $stmt2->fetch(PDO::FETCH_ASSOC);
			// echo $AssignmentName["Name"];

			array_push($final, ["openTime" => $value["open"], "closeTime" => $value["close"], "group" => $groupName["Name"], "Assignment" => $AssignmentName["Name"]]);
		}
		return $final;
	}
	public function evaluateImage($pdo, $imageId, $grade)
	{
		$grade = $grade > 100 ? 100 : $grade;
		$grade = $grade < 0 ? 0 : $grade;

		$query = "UPDATE assignmentimages set Grade=:Grade Where Id=:Selected";
		$stmt = $pdo->prepare($query);
		$stmt->bindParam(":Selected", $imageId);
		$stmt->bindParam(":Grade", $grade);
		$stmt->execute();
		return true;
	}

	public function getSubmissionsByAssignmentId($assignmentId)
	{
		// Prepare the initial statement to get submissions
		$stmt = $this->conn->prepare("SELECT * FROM submissions WHERE assignmentId = :assignmentId");
		if (!$stmt) {
			echo "Prepare failed: (" . $this->conn->errorCode() . ") " . $this->conn->errorInfo()[2];
			return false;
		}

		// Bind the assignment ID and execute the statement
		$stmt->bindValue(':assignmentId', $assignmentId, PDO::PARAM_INT);
		$stmt->execute();
		$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// // Iterate over the submissions to fetch student images and calculate grades
		// foreach ($submissions as $key => $value) {
		// 	// foreach ($submissions as &$submission) {
		// 	$studentId = $submissions[$key]["StudentId"];

		// 	// Prepare the statement to get assignment images for the current student
		// 	$stmtImages = $this->conn->prepare("SELECT * FROM assignmentimages WHERE assignmentId = :assignmentId AND StudentId = :studentId");
		// 	$stmtImages->bindValue(':assignmentId', $assignmentId, PDO::PARAM_INT);
		// 	$stmtImages->bindValue(':studentId', $studentId, PDO::PARAM_INT);
		// 	$stmtImages->execute();
		// 	$studentImages = $stmtImages->fetchAll(PDO::FETCH_ASSOC);
		// 	// print_r($studentImages);
		// 	// Calculate the total grade for the current student
		// 	$totalGrade = 1;
		// 	foreach ($studentImages as $image) {
		// 		$totalGrade += $image["Grade"];
		// 	}

		// 	// Add the calculated grade to the submission
		// 	$submissions[$key]["Grade"] = $totalGrade;
		// }

		return $submissions;
	}
	public function getGrade($pdo, $submission)
	{
		$query = "SELECT * FROM assignmentimages WHERE submissionId=:selected";
		$stmt = $pdo->prepare($query);
		$stmt->bindParam(":selected", $submission);
		$stmt->execute();
		$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$totalGrade = 0;
		foreach ($grades as $row) {
			$totalGrade += $row["Grade"];
		}
		return ["Total" => $totalGrade, "count" => count($grades)];
	}
	public function getAssignmentImages($pdo, $submission)
	{
		$query = "SELECT * FROM assignmentimages WHERE submissionId=:submission;";
		$stmt = $pdo->prepare($query);
		$stmt->bindParam(":submission", $submission);
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}
	public function AddAssignmentGradeAndCommentPDO($assignmentId, $studentId, $grade, $comment)
	{
		$stmt = $this->conn->prepare("UPDATE submissions SET Grade = ?, Comment = ? WHERE StudentId = ? AND assignmentId = ?");
		if (!$stmt) {
			echo "Prepare failed: (" . $this->conn->errorCode() . ") " . $this->conn->errorInfo()[2];
			return false;
		}

		$stmt->bindValue(1, $grade, PDO::PARAM_STR);
		$stmt->bindValue(2, $comment, PDO::PARAM_STR);
		$stmt->bindValue(3, $studentId, PDO::PARAM_INT);
		$stmt->bindValue(4, $assignmentId, PDO::PARAM_INT);

		if (!$stmt->execute()) {
			echo "Execute failed: (" . $stmt->errorCode() . ") " . $stmt->errorInfo()[2];
			return false;
		}

		$stmt->closeCursor();
		return true;
	}
	public function fetchAllSubmissions()
	{
		$query = "SELECT * FROM submissions";
		$result = $this->conn->query($query);

		if ($result === false) {
			return json_encode(['error' => "Failed to fetch assignments: " . $this->conn->error]);
		}

		$submissions = [];
		while ($row = $result->fetch_assoc()) {
			$submissions[] = $row;
		}
		return json_encode($submissions);
	}
	public function GetSubmissionByUserAndAssignment($pdo, $user, $assignment)
	{
		// WHERE StudentId=:user AND assignmentId=:assignment;
		$query = "SELECT * FROM submissions  WHERE StudentId=:user AND assignmentId=:assignment;";
		$stmt = $pdo->prepare($query);
		$stmt->bindParam(":user", $user);
		$stmt->bindParam(":assignment", $assignment);
		$stmt->execute();
		// $grade = $this->getGrade($pdo, )
		$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach ($submissions as &$submission) {
			$submission['Grade'] = $this->getGrade($pdo, $submission['Id']);
		}
		return $submissions;
	}
	public function GetSubmissionById($pdo, $student)
	{
		$query = "SELECT * from submissions where StudentId=:selected;";
		$stmt = $pdo->prepare($query);
		$stmt->bindParam(":selected", $student);
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}
	public function Chart($assignmentId, $studentId)
	{
		$query = "SELECT SUM(Grade) as TotalGrade FROM assignmentimages WHERE AssignmentId = $assignmentId AND StudentID = $studentId";
		$result = $this->conn->query($query);

		if ($result === false) {
			return json_encode(['error' => "Failed to fetch assignments: " . $this->conn->error]);
		}

		$row = $result->fetch_assoc();
		$totalGrade = $row['TotalGrade'];

		return $totalGrade;
	}
}

<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");
class Assignment
{
	private PDO $pdo;
	public function __construct($pdo)
	{
		$this->pdo = $pdo;
	}
	function prepareAndBind(string $query, $params, bool $execute = false): PDOStatement
	{
		$keys = array_keys($params);
		$query .=  !empty($keys) ?  " (:" . implode(", :", $keys) . ");" : ";";
		$stmt = $this->pdo->prepare($query);
		$stmt = !empty($keys) ? $this->bindParams($params, $stmt) : $stmt;
		if ($execute) $stmt->execute();
		return $stmt;
	}
	function bindParams(array $params, PDOStatement &$stmt, bool $execute = false): PDOStatement
	{
		foreach ($params as $key => $value)
			$stmt->bindValue($this->ensureColon($key), $value);
		if ($execute) $stmt->execute();
		return $stmt;
	}

	private function ensureColon($key): string
	{
		// Check if the key already starts with a colon
		return strpos($key, ':') === 0 ? $key : ':' . $key;
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

		$stmt = $this->pdo->prepare("INSERT INTO assignments (Name, ProfessorId, maxLimitImages, Topic)
			VALUES (:name, :professor, :maxLimitImages, :topic)");
		$this->bindParams([
			"name" => $data['Name'],
			"professor" => $data['ProfessorId'],
			"maxLimitImages" => $data["maxLimitImages"],
			"topic" => $data["Topic"]
		], $stmt, true);

		return true;
	}
	public function fetchAssignment($assignmentId)
	{
		$stmt = $this->pdo->prepare("SELECT Name, Topic, maxLimitImages FROM assignments WHERE Id=:id;");
		$stmt->bindParam(":id", $assignmentId);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
	public function fetchAllAssignments()
	{
		$stmt = $this->pdo->prepare("SELECT * FROM assignments");
		$query = "SELECT * FROM assignments";
		$stmt->execute();
		return json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	}
	public function uploadImages($files, $assignmentId, $studentId, $maxImages, $ASName, $MSAId)
	{
		$uploadedFiles = 0;
		$responses = [];

		// Create submission record only once
		$submitTime = date("Y-m-d H:i:s");
		$stmt = $this->pdo->prepare("INSERT INTO submissions (assignmentId, StudentId, submitTime) VALUES (:assignment, :student, :time);");
		// $stmtSubmissions = $this->conn->prepare();
		$this->bindParams([
			"assignment" => $assignmentId,
			"student" => $studentId,
			"time" => $submitTime
		], $stmt);
		$stmt->execute();
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
				$stmt = $this->pdo->prepare("INSERT INTO assignmentimages (Path, StudentID, AssignmentId, CategoryId)
					VALUES (:path, :student, :assignment, 1)");
				$this->bindParams([
					"path" => $targetPath,
					"student" => $studentId,
					"assignment" => $assignmentId,
				], $stmt);
				if ($stmt->execute()) {
					$responses[] = ['success' => 'Image uploaded successfully', 'path' => $targetPath];
					$uploadedFiles++;
				}
			} else
				$responses[] = ['error' => 'Failed to move uploaded file'];
		}
		return $responses;
	}

	public function getCategories()
	{
		$stmt = $this->pdo->prepare("Select * from categories;");
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function addCategory($category)
	{
		$this->prepareAndBind("Insert Into categories (Name) Values", [
			"Cat" => $category
		], true);
		return True;
	}
	public function InsertAssignmentGroup($data)
	{
		foreach ($data as $value) {
			if ($value === "")
				return false; // Indicate that the insertion failed due to missing data
		}
		$action = "Insert";
		$stmt = $this->pdo->prepare("Select * from GroupsAssignments");
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
			$this->prepareAndBind("INSERT INTO GroupsAssignments (`open`, `close`, `Assignment`, `Group`) VALUES", [
				"open" => $open,
				"close" => $close,
				"assignment" => $assignmentId,
				"group" => $groupId
			], true);
			return true;
		} else if ($action === "Update") {
			$stmt = $this->pdo->prepare("UPDATE GroupsAssignments set open=:openTime, close=:closeTime WHERE Assignment=:assignment AND `Group`=:group;");
			$this->bindParams([
				"openTime" => $open,
				"closeTime" => $close,
				"assignment" => $assignmentId,
				"group" => $groupId
			], $stmt, true);
			return true;
		}
	}
	// --------------------------------
	public function DeleteAssignment($assignmentId)
	{
		$stmt = $this->pdo->prepare("DELETE from assignments where Id=:Selected;");
		$this->bindParams([
			"Selected" => $assignmentId
		], $stmt, true);
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
		$this->prepareAndBind("INSERT into submissions (StudentId, assignmentId) Values", [
			"student" => $student,
			$assignment => $assignment
		], true);
		return true;
	}
	public function uploadAssignmentImage($pdo, $image, $studentId, $assignmentId, $category, $submission)
	{
		$stmt = $this->pdo->prepare("SELECT Id from categories where Name=:category;");
		$this->bindParams([
			"category" => $category
		], $stmt, true);
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
			$this->prepareAndBind(
				"INSERT INTO assignmentimages (Path, StudentID, AssignmentId, CategoryId, submissionId) VALUES",
				[
					"path" => $targetFile,
					"student" => $studentId,
					"assignment" => $assignmentId,
					"category" => $category,
					"submission" => $submission
				],
				true
			);
		} else {
			return "Error: Failed to move uploaded file.";
		}
	}
	public function getAssignmentForUser($user)
	{
		// 1.get the user Group from the usersInGroups table
		$stmt = $this->pdo->prepare("SELECT GroupId FROM usersingroups WHERE userId=:student;");
		$this->bindParams([
			"student" => $user
		], $stmt, true);
		$group = $stmt->fetch(PDO::FETCH_ASSOC); // ["GroupId"]
		if (!$group)
			return ["Err" => 1]; // user not in a group

		// 2. get all assignments Ids that allowed for this group
		$stmt = $this->pdo->prepare("SELECT * FROM groupsassignments WHERE `Group`=:group;");
		$this->bindParams([
			"group" => $group["GroupId"]
		], $stmt, true);
		$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$assignmentList = array();
		foreach ($assignments as $assignment) {
			$stmt = $this->pdo->prepare("SELECT * from assignments where Id=:assignment;");
			$this->bindParams([
				"assignment" => $assignment["Assignment"]
			], $stmt, true);
			$newAssignment = $stmt->fetch(PDO::FETCH_ASSOC);

			// Add the open and close attributes to the assignment
			$newAssignment['open'] = $assignment['open'];
			$newAssignment['close'] = $assignment['close'];

			$assignmentList[] = $newAssignment;
		}

		return $assignmentList;
	}
	public function getSubmissionStatus()
	{
		$stmt = $this->pdo->prepare("SELECT * from assignments;");
		$stmt->execute();
		$Assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (!$Assignments) return false;
		foreach ($Assignments as $key => $value) {
			$stmt = $this->prepareAndBind("SELECT * from submissions Where assignmentId=:selected;", [
				"selected" => $value["Id"]
			], true);
			$assSubmission = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$Assignments[$key]["Submitted"] = $assSubmission ?  count($assSubmission) : 0;
		}
		return $Assignments;
	}

	public function AssignmentGroupsShow()
	{
		$stmt = $this->pdo->prepare("Select * from GroupsAssignments");
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$final = [];
		foreach ($result as $value) {
			$stmt2 = $this->pdo->prepare("SELECT Name from `groups` where id =:selected;");
			$this->bindParams([
				"selected" => $value["Group"]
			], $stmt2, true);
			$groupName = $stmt2->fetch(PDO::FETCH_ASSOC);

			$stmt2 = $this->pdo->prepare("SELECT Name from `assignments` where id =:selected;");
			$this->bindParams([
				"selected" => $value["Assignment"]
			], $stmt2, true);
			$AssignmentName = $stmt2->fetch(PDO::FETCH_ASSOC);
			array_push($final, ["openTime" => $value["open"], "closeTime" => $value["close"], "group" => $groupName["Name"], "Assignment" => $AssignmentName["Name"]]);
		}
		return $final;
	}
	public function evaluateImage($imageId, $grade)
	{
		$grade = $grade > 100 ? 100 : $grade;
		$grade = $grade < 0 ? 0 : $grade;
		$stmt = $this->pdo->prepare("UPDATE assignmentimages set Grade=:Grade Where Id=:Selected");
		$this->bindParams([
			"Selected" => $imageId,
			"Grade" => $grade
		], $stmt, true);
		return true;
	}

	public function getSubmissionsByAssignmentId($assignmentId)
	{
		// Prepare the initial statement to get submissions
		$stmt = $this->pdo->prepare("SELECT * FROM submissions WHERE assignmentId = :assignmentId");
		$this->bindParams([
			"assignmentId" => $assignmentId
		], $stmt, true);
		$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $submissions;
	}
	public function getGrade($submission)
	{
		$stmt = $this->pdo->prepare("SELECT * FROM assignmentimages WHERE submissionId=:selected;");
		$this->bindParams([
			"selected" => $submission
		], $stmt, true);
		$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$totalGrade = 0;
		foreach ($grades as $row)
			$totalGrade += $row["Grade"];

		return ["Total" => $totalGrade, "count" => count($grades)];
	}
	public function getAssignmentImages($submission)
	{
		$query = "SELECT * FROM assignmentimages WHERE submissionId=:submission;";
		$stmt = $this->pdo->prepare($query);
		$stmt->bindParam(":submission", $submission);
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}
	public function AddAssignmentGradeAndCommentPDO($assignmentId, $studentId, $grade, $comment)
	{
		$stmt = $this->pdo->prepare("UPDATE submissions SET Grade=:grade, Comment=:comment WHERE StudentId=:student AND assignmentId=:assignment;");
		$this->bindParams([
			"grade" => $grade,
			"comment" => $comment,
			"student" => $studentId,
			"assignment" => $assignmentId
		], $stmt, true);
		return true;
	}
	public function fetchAllSubmissions()
	{
		$stmt = $this->pdo->prepare("SELECT * FROM submissions");
		$stmt->execute();
		return json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	}
	public function GetSubmissionByUserAndAssignment($user, $assignment)
	{
		$stmt = $this->pdo->prepare(("SELECT * FROM submissions  WHERE StudentId=:user AND assignmentId=:assignment;"));
		$this->bindParams([
			"user" => $user,
			"assignment" => $assignment
		], $stmt, true);
		$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach ($submissions as &$submission)
			$submission['Grade'] = $this->getGrade($submission['Id']);
		return $submissions;
	}
	public function GetSubmissionByUser($user)
	{
		try {
			$stmt = $this->pdo->prepare("SELECT * FROM submissions WHERE StudentId = :selected");
			$stmt = $this->bindParams([
				"selected" => $user
			], $stmt, true);
			$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
			foreach ($submissions as &$submission)
				$submission['Grade'] = $this->getGrade($submission['Id']);
			return $submissions;
		} catch (Exception $e) {
			return ['error' => $e->getMessage()];
		}
	}
	public function GetSubmissionById($student)
	{

		$stmt = $this->pdo->prepare("SELECT * from submissions where StudentId=:selected;");
		$this->bindParams([
			"selected" => $student
		], $stmt, true);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function Chart($assignmentId, $studentId)
	{
		$stmt = $this->pdo->prepare("SELECT SUM(Grade) as TotalGrade FROM assignmentimages WHERE AssignmentId=:assignment AND StudentID=:student");
		$stmt = $this->bindParams([
			"assignment" => $assignmentId,
			"student" => $studentId
		], $stmt, true);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row['TotalGrade'];
	}
}

<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

require_once("./Helpers.class.php");

class Assignment
{
	private PDO $pdo;
	private Helpers $helpers;
	public function __construct($pdo)
	{
		$this->pdo = $pdo;
		$this->helpers = new Helpers($this->pdo);
	}

	public function insertAssignment($data)
	{

		// Insert the assignment into the assignments table
		$stmt = $this->pdo->prepare("INSERT INTO assignments (Name, ProfessorId, Topic)
			VALUES (:name, :professor, :topic)");
		$this->helpers->bindParams([
			"name" => $data['Name'],
			"professor" => $data['ProfessorId'],
			"topic" => $data["Topic"]
		], $stmt, true);


		// Retrieve the last inserted assignment ID
		$assignmentId = $this->pdo->lastInsertId();

		// Insert categories into the assignment_categories table
		$stmt = $this->pdo->prepare("INSERT INTO assignment_categories (assignment_id, category_id)
			VALUES (:assignment_id, :category_id)");
		$categoryArray = explode(",", $data['categories']);

		foreach ($categoryArray as $category) {
			// Bind the assignment ID and category ID for each category
			$stmt->bindParam(':assignment_id', $assignmentId);
			$stmt->bindParam(':category_id', $category);
			$stmt->execute();  // Execute the insert for each category
		}
		// Insert weeks into the assignment_weeks table
		$stmt = $this->pdo->prepare("INSERT INTO assignment_weeks (assignment_id, week_num)
		VALUES (:assignment_id, :week_num)");

		$weekNumArray = explode(",", $data['weekNum']);
		//echo($weekNumArray);
		foreach ($weekNumArray as $week) {
			// Bind the assignment ID and category ID for each category
			$stmt->bindParam(':assignment_id', $assignmentId);
			$stmt->bindParam(':week_num', $week);
			$stmt->execute();  // Execute the insert for each category
		}
		//Get groups data and slots
		require_once('./Group.class.php');

		$group = new GROUP($this->pdo);
		$daysOfWeek = [
			"sunday" => 0,
			"monday" => 1,
			"tuesday" => 2,
			"wednesday" => 3,
			"thursday" => 4,
			"friday" => 5,
			"saturday" => 6
		];

		//get the start week
		$startWeek = $this->getstartweek();
		//start day
		$startDay = $startWeek[0]["Day"];
		//1
		$groupsSlots = json_decode($group->getAll(), true);
		foreach ($groupsSlots as $groupSlot) {
			foreach ($groupSlot["Slots"] as $slot) {
				$targetDay = $slot["Day"];
				$StartTime = $slot["StartTime"];
				$EndTime = $slot["EndTime"];
			}

			foreach ($weekNumArray as $weekNum) {
				$wantedDay = $this->getWantedDay($startDay, $weekNum, $targetDay);
				// echo "Week $weekNum: Wanted Day is $wantedDay\n";
				$open = new DateTime($wantedDay . ' ' . $StartTime);
				$open = $open->format('Y-m-d H:i:s');
				$close = new DateTime($wantedDay . ' ' . $EndTime);
				$close = $close->format('Y-m-d H:i:s');

				$this->helpers->prepareAndBind("INSERT INTO GroupsAssignments (`open`, `close`, `Assignment`, `Group`,`week_num`) VALUES", [
					"open" => $open,
					"close" => $close,
					"assignment" => $assignmentId,
					"group" => $groupSlot["Id"],
					"week_num" => $weekNum,
				], true);
			}
		}
		return true;
	}
	public function UpdateAssignment($data)
	{
		// Update the assignment in the assignments table
		$stmt = $this->pdo->prepare("UPDATE assignments 
									 SET Name = :name, Topic = :topic
									 WHERE Id = :id");
		$this->helpers->bindParams([
			"id" => $data['Id'],
			"name" => $data['Name'],
			"topic" => $data['Topic']
		], $stmt, true);

		// Update categories in the assignment_categories table
		$stmt = $this->pdo->prepare("DELETE FROM assignment_categories WHERE assignment_id = :assignment_id");
		$stmt->bindParam(':assignment_id', $data['Id']);
		$stmt->execute();

		$stmt = $this->pdo->prepare("INSERT INTO assignment_categories (assignment_id, category_id)
									 VALUES (:assignment_id, :category_id)");
		$categoryArray = explode(",", $data['categories']);

		foreach ($categoryArray as $category) {
			$stmt->bindParam(':assignment_id', $data['Id']);
			$stmt->bindParam(':category_id', $category);
			$stmt->execute();
		}

		// Update weeks in the assignment_weeks table
		$stmt = $this->pdo->prepare("DELETE FROM assignment_weeks WHERE assignment_id = :assignment_id");
		$stmt->bindParam(':assignment_id', $data['Id']);
		$stmt->execute();

		$stmt = $this->pdo->prepare("INSERT INTO assignment_weeks (assignment_id, week_num)
		VALUES (:assignment_id, :week_num)");
		$weekNumArray = explode(",", $data['weeks']);

		foreach ($weekNumArray as $week) {
			$stmt->bindValue(':assignment_id', $data['Id']);
			$stmt->bindValue(':week_num', $week);
			$stmt->execute();
		}
		//
		$stmt = $this->pdo->prepare("DELETE FROM groupsassignments WHERE Assignment = :Assignment");
		$stmt->bindParam(':Assignment', $data['Id']);
		$stmt->execute();

		// Update group assignments
		require_once('./Group.class.php');
		$group = new GROUP($this->pdo);
		$daysOfWeek = [
			"sunday" => 0,
			"monday" => 1,
			"tuesday" => 2,
			"wednesday" => 3,
			"thursday" => 4,
			"friday" => 5,
			"saturday" => 6
		];

		// Get the start week
		$startWeek = $this->getstartweek();
		$startDay = $startWeek[0]["Day"];
		$groupsSlots = json_decode($group->getAll(), true);

		foreach ($groupsSlots as $groupSlot) {
			foreach ($groupSlot["Slots"] as $slot) {
				$targetDay = $slot["Day"];
				$StartTime = $slot["StartTime"];
				$EndTime = $slot["EndTime"];
			}

			foreach ($weekNumArray as $weekNum) {
				$wantedDay = $this->getWantedDay($startDay, $weekNum, $targetDay);
				$open = new DateTime($wantedDay . ' ' . $StartTime);
				$open = $open->format('Y-m-d H:i:s');
				$close = new DateTime($wantedDay . ' ' . $EndTime);
				$close = $close->format('Y-m-d H:i:s');

				$this->helpers->prepareAndBind("INSERT INTO GroupsAssignments (`open`, `close`, `Assignment`, `Group`,`week_num`) VALUES", [
					"open" => $open,
					"close" => $close,
					"assignment" => $data['Id'],
					"group" => $groupSlot["Id"],
					"week_num" => $weekNum,
				], true);
			}
		}

		return true;
	}
	public function getWantedDay($startDay, $weeknum, $targetDay)
	{
		$startDate = new DateTime($startDay);
		if ($weeknum > 1) {
			$startDate->modify('+' . ($weeknum - 1) . ' weeks');
		}
		$startDayOfWeek = $startDate->format('l'); // Get the name of the start day (e.g., "Saturday")

		if (strtolower($startDayOfWeek) !== strtolower($targetDay)) {
			// Modify the startDate to the next occurrence of the target day
			$startDate->modify("next $targetDay");
		}

		return $startDate->format('Y-m-d'); // Return the wanted day in 'Y-m-d' format
	}
	public function fetchAssignment($assignmentId)
	{
		$stmt = $this->pdo->prepare("SELECT Name, Topic, maxLimitImages FROM assignments WHERE Id=:id;");
		$stmt->bindParam(":id", $assignmentId);
		$stmt->execute();
		$assignmentData = $stmt->fetch(PDO::FETCH_ASSOC);
		// Fetch the category IDs associated with the assignment

		$stmt2 = $this->pdo->prepare("SELECT category_id FROM assignment_categories WHERE assignment_id=:assignment_id;");
		$stmt2->bindParam(":assignment_id", $assignmentId);
		$stmt2->execute();
		$categories = $stmt2->fetchAll(PDO::FETCH_COLUMN);

		// Prepare to fetch category names
		$stmt3 = $this->pdo->prepare("SELECT Id, Name FROM categories WHERE Id=:Id;");
		$responses = []; // Initialize the response array
		foreach ($categories as $cat) {
			$stmt3->bindParam(":Id", $cat);
			$stmt3->execute();
			$category = $stmt3->fetch(PDO::FETCH_ASSOC);
			if ($category) {
				$responses[] = ['categoryId' => $cat, 'categoryName' => $category['Name']];
			}
		}
		$assignmentData['categories'] = $responses;
		return $assignmentData;
	}
	public function getSingleAssignmentData($assignmentId)
	{
		$stmt = $this->pdo->prepare("SELECT Name, Topic, maxLimitImages FROM assignments WHERE Id=:id;");
		$stmt->bindParam(":id", $assignmentId);
		$stmt->execute();
		$assignmentData = $stmt->fetch(PDO::FETCH_ASSOC);
		// Fetch the category IDs associated with the assignment

		$stmt2 = $this->pdo->prepare("SELECT category_id FROM assignment_categories WHERE assignment_id=:assignment_id;");
		$stmt2->bindParam(":assignment_id", $assignmentId);
		$stmt2->execute();
		$categories = $stmt2->fetchAll(PDO::FETCH_COLUMN);

		// Prepare to fetch category names
		$stmt3 = $this->pdo->prepare("SELECT Id, Name FROM categories WHERE Id=:Id;");
		$responses = []; // Initialize the response array
		foreach ($categories as $cat) {
			$stmt3->bindParam(":Id", $cat);
			$stmt3->execute();
			$category = $stmt3->fetch(PDO::FETCH_ASSOC);
			if ($category) {
				$responses[] = ['categoryId' => $cat, 'categoryName' => $category['Name']];
			}
		}
		$assignmentData['categories'] = $responses;
		$stmt4 = $this->pdo->prepare("SELECT week_num FROM assignment_weeks WHERE assignment_id=:assignment_id;");
		$stmt4->bindParam(":assignment_id", $assignmentId);
		$stmt4->execute();
		$weeks = $stmt4->fetchAll(PDO::FETCH_COLUMN);
		$assignmentData['weeks'] = $weeks;
		return json_decode(json_encode($assignmentData));
	}
	public function getAllStudentsAssignmentsBestGrades()
	{
		$stmt = $this->pdo->prepare("SELECT Id, MSAId FROM users WHERE Type='Student'");
		$stmt->execute();
		$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function fetchAllAssignments()
	{
		$stmt = $this->pdo->prepare("SELECT * FROM assignments");
		$query = "SELECT * FROM assignments";
		$stmt->execute();
		return json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	}
	public function getAllAssignmentsNames()
	{
		$stmt = $this->pdo->prepare("SELECT Id, Name FROM assignments");
		$query = "SELECT * FROM assignments";
		$stmt->execute();
		return json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	}
	public function getGradesRows()
	{
		// Prepare the statement to get students once
		$stmt = $this->pdo->prepare("SELECT Id, MSAId, Name FROM users WHERE Type='Student'");
		$stmt->execute();
		$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$stmtAssignments = $this->pdo->prepare("SELECT Id FROM assignments");
		$stmtAssignments->execute();
		$assignments = $stmtAssignments->fetchAll(PDO::FETCH_ASSOC);
		$newStudentsArray = [];
		foreach ($students as $student) {
			$assGrades = [];
			foreach ($assignments as $ass) {
				$assGrade = $this->getBestGrade($ass["Id"], $student["Id"]);
				$assGrade["assignmentId"] = $ass["Id"];
				$assGrades[] = $assGrade;
			}
			$student["Grades"] = $assGrades;
			// print_r($student);
			$newStudentsArray[] = $student;
		}

		//print_r($students);
		return json_encode($newStudentsArray);
	}
	public function uploadImages($files, $assignmentId, $studentId, $maxImages, $ASName, $MSAId)
	{
		$uploadedFiles = 0;
		$responses = [];

		// Create submission record only once
		$submitTime = date("Y-m-d H:i:s");
		$stmt = $this->pdo->prepare("INSERT INTO submissions (assignmentId, StudentId, submitTime) VALUES (:assignment, :student, :time);");
		// $stmtSubmissions = $this->conn->prepare();
		$this->helpers->bindParams([
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
				$this->helpers->bindParams([
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
	public function setDoctorsNote(int $submissionId, string $doctorComment)
	{
		$stmt = $this->pdo->prepare("UPDATE submissions SET DoctorComment=:comment WHERE Id=:selected");
		$stmt->execute(["comment" => htmlspecialchars(trim($doctorComment)), "selected" => $submissionId]);
		return "UPDATED";
	}

	public function assignmentSubmissionReport()
	{
		// 1.select all assignments
		$stmt = $this->pdo->prepare("SELECT * FROM assignments");
		$stmt->execute();
		$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
		// 2. get how many submission for each assignment
		foreach ($assignments as &$assignment) {
			$stmt = $this->pdo->prepare("SELECT COUNT(*) from submissions WHERE assignmentId=:assignment;");
			$stmt->execute([":assignment" => $assignment["Id"]]);
			$assignment["SubmissionCount"] = $stmt->fetchColumn();
		}
		return $assignments;
	}

	public function getBestGrade(int $assignmentId, int $studentId): array | int
	{
		$bestGrade = 0;
		$submissions = $this->getSubmissionsByStudentAndAssignmentToBest($studentId, $assignmentId);

		foreach ($submissions as &$sub) {
			if ($sub["BestGrade"])
				$bestGrade = $sub["Grade"];
		}
		if ($bestGrade === 0) {
			$bestGrade = [
				"Total" => 0,
				"count" => 0,
			];
		}

		return $bestGrade;
	}
	public function GetSubmissionForUserReport(int $studentId)
	{
		// Fetch all assignments
		$stmt = $this->pdo->prepare("SELECT * from assignments");
		$stmt->execute();
		$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$submissions = [];

		foreach ($assignments as $assignment) {
			// Get submissions by student and assignment
			$subs = $this->getSubmissionsByStudentAndAssignmentToBest($studentId, $assignment["Id"]);

			if (count($subs) > 0)
				$submissions = array_merge($submissions, $subs);
			foreach ($submissions as &$sub) {
				// Loop over submissions and check if 'Id' exists

				if (isset($sub["Id"])) {  // Ensure 'Id' exists in the $sub array
					$images = $this->getAssignmentImages($sub["Id"]);
					foreach ($images as &$image) {
						$query = "SELECT Name FROM categories WHERE Id=:selected;";
						$stmt = $this->pdo->prepare($query);
						$stmt->bindParam(":selected", $image["CategoryId"]);
						$stmt->execute();
						$category = $stmt->fetch(PDO::FETCH_ASSOC);

						$image["Category"] = $category["Name"];
					}
					$sub["images"] = $images;
				}
			}
		}
		return $submissions;
	}

	public function getMaxGradeSubmissions(array $submissions)
	{
		$maxGrade = 0;
		foreach ($submissions as $submission) {
			$result = $this->getGrade($submission["Id"]);
			if ($result["Total"] > $maxGrade)
				$maxGrade = $result["Total"];
		}
		// echo "DONE GRADE \n";
		return $maxGrade;
	}
	public function studentAssignmentsReport(int $studentId)
	{
		// 1. select all assignments
		$stmt = $this->pdo->prepare("SELECT Name, Id FROM assignments");
		$stmt->execute();
		$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// echo "one\n";

		// 2. for each assignment get student best grade and avg grade
		foreach ($assignments as &$assignment) {
			// 2.1 get grade for this student
			$submissions = $this->GetSubmissionByUserAndAssignment($studentId, $assignment["Id"]);
			$maxForUser = $this->getMaxGradeSubmissions($submissions);
			$assignment["StudentGrade"] = $maxForUser;

			// 2.2 get avg grade for all the students
			$submissionsByAssignment = $this->getSubmissionsByAssignmentId($assignment["Id"]);
			$totalGrades = 0;

			foreach ($submissionsByAssignment as $sub) {
				$result = $this->getGrade($sub["Id"]);
				$totalGrades += $result["Total"];
			}

			$assignment["AVGGrades"] = count($submissionsByAssignment) !== 0 ? $totalGrades / count($submissionsByAssignment) : 0;
		}
		return $assignments;
	}

	public function editCategory($id, $name)
	{
		$stmt = $this->pdo->prepare("UPDATE categories SET Name = :name WHERE Id=:selected");
		$stmt->execute([":name" => $name, ":selected" => $id]);
		return "UPDATED";
	}
	public function deleteCategory($id)
	{
		$stmt = $this->pdo->prepare("DELETE FROM categories WHERE Id = :id");
		$stmt->execute([":id" => $id]);
		return "DELETED";
	}

	public function getCategories()
	{
		$stmt = $this->pdo->prepare("Select * from categories;");
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function addCategory($category)
	{
		$this->helpers->prepareAndBind("Insert Into categories (Name) Values", [
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
			$this->helpers->prepareAndBind("INSERT INTO GroupsAssignments (`open`, `close`, `Assignment`, `Group`) VALUES", [
				"open" => $open,
				"close" => $close,
				"assignment" => $assignmentId,
				"group" => $groupId
			], true);
			return true;
		} else if ($action === "Update") {
			$stmt = $this->pdo->prepare("UPDATE GroupsAssignments set open=:openTime, close=:closeTime WHERE Assignment=:assignment AND `Group`=:group;");
			$this->helpers->bindParams([
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
		//Delete the week of the assignment from assignments week table
		$stmt = $this->pdo->prepare("DELETE from assignment_weeks where assignment_id=:Selected;");
		$this->helpers->bindParams([
			"Selected" => $assignmentId
		], $stmt, true);
		//Delete the categories of the assignment from assignments categories table
		$stmt = $this->pdo->prepare("DELETE from assignment_categories where assignment_id=:Selected;");
		$this->helpers->bindParams([
			"Selected" => $assignmentId
		], $stmt, true);
		//Delete the assignment from the assignment table
		$stmt = $this->pdo->prepare("DELETE from assignments where Id=:Selected;");
		$this->helpers->bindParams([
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
	public function addNewSubmission($pdo, $student, $assignment, $week)
	{
		$this->helpers->prepareAndBind("INSERT into submissions (StudentId, assignmentId, weekNum) Values", [
			"student" => $student,
			$assignment => $assignment,
			"weekNum" => $week
		], true);
		return true;
	}
	public function uploadAssignmentImage($pdo, $image, $studentId, $assignmentId, $category, $submission, $weekNum)
	{
		$stmt = $this->pdo->prepare("SELECT Id from categories where Name=:category;");
		$this->helpers->bindParams([
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
			$this->helpers->prepareAndBind(
				"INSERT INTO assignmentimages (Path, StudentID, AssignmentId, CategoryId, submissionId, weekNum) VALUES",
				[
					"path" => $targetFile,
					"student" => $studentId,
					"assignment" => $assignmentId,
					"category" => $category,
					"submission" => $submission,
					"weekNum" => $weekNum

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
		$this->helpers->bindParams([
			"student" => $user
		], $stmt, true);
		$group = $stmt->fetch(PDO::FETCH_ASSOC); // ["GroupId"]
		if (!$group)
			return ["Err" => 1]; // user not in a group

		// 2. get all assignments Ids that allowed for this group
		$stmt = $this->pdo->prepare("SELECT * FROM groupsassignments WHERE `Group`=:group;");
		$this->helpers->bindParams([
			"group" => $group["GroupId"]
		], $stmt, true);
		$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$assignmentList = array();
		foreach ($assignments as $assignment) {
			$stmt = $this->pdo->prepare("SELECT * from assignments where Id=:assignment;");
			$this->helpers->bindParams([
				"assignment" => $assignment["Assignment"]
			], $stmt, true);
			$newAssignment = $stmt->fetch(PDO::FETCH_ASSOC);

			// Add the open and close attributes to the assignment
			$newAssignment['open'] = $assignment['open'];
			$newAssignment['close'] = $assignment['close'];
			$newAssignment['week_num'] = $assignment['week_num'];

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
			$stmt = $this->helpers->prepareAndBind("SELECT * from submissions Where assignmentId=:selected;", [
				"selected" => $value["Id"]
			], true);
			$assSubmission = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$Assignments[$key]["Submitted"] = $assSubmission ?  count($assSubmission) : 0;
		}
		return $Assignments;
	}
	public function updateStartWeek($day)
	{


		// Convert array to string and format date
		$formattedDay = date('Y-m-d', strtotime(is_array($day) ? implode('', $day) : $day));

		// Update the database
		$stmt = $this->pdo->prepare("UPDATE startweek SET Day = :day WHERE Id=1");
		$stmt->bindParam(':day', $formattedDay);
		$stmt->execute();

		$startWeek = $this->getstartweek();
		$startDay = $startWeek[0]["Day"];

		$stmt = $this->pdo->prepare("SELECT Id FROM assignments");
		$stmt->execute();
		$assignmentIds = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach ($assignmentIds as $assid) {
			$assignmentid = $assid["Id"];
			$stmt = $this->pdo->prepare("SELECT week_num FROM assignment_weeks WHERE assignment_id = :assignment_id;");
			$stmt->bindParam(":assignment_id", $assignmentid, PDO::PARAM_INT);
			$stmt->execute();
			$weekNums = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$weekNumArray = array_column($weekNums, 'week_num');

			// Get groups data and slots
			require_once('./Group.class.php');
			$group = new GROUP($this->pdo);

			$groupsSlots = json_decode($group->getAll(), true);

			foreach ($groupsSlots as $groupSlot) {
				foreach ($groupSlot["Slots"] as $slot) {
					$targetDay = $slot["Day"];
					$StartTime = $slot["StartTime"];
					$EndTime = $slot["EndTime"];
				}

				foreach ($weekNumArray as $weekNum) {
					$wantedDay = $this->getWantedDay($startDay, $weekNum, $targetDay);

					$open = new DateTime($wantedDay . ' ' . $StartTime);
					$open = $open->format('Y-m-d H:i:s');

					$close = new DateTime($wantedDay . ' ' . $EndTime);
					$close = $close->format('Y-m-d H:i:s');

					// Check if the record exists
					$stmt = $this->pdo->prepare("SELECT COUNT(*) FROM groupsassignments WHERE `Assignment` = :assignmentid AND `Group` = :groupid AND `week_num` = :week_num");
					$stmt->bindParam(':assignmentid', $assignmentid, PDO::PARAM_INT);
					$stmt->bindParam(':groupid', $groupSlot["Id"], PDO::PARAM_INT);
					$stmt->bindParam(':week_num', $weekNum, PDO::PARAM_INT);
					$stmt->execute();
					$exists = $stmt->fetchColumn() > 0;

					if ($exists) {
						// Update existing record
						$stmt = $this->pdo->prepare("UPDATE groupsassignments SET `open` = :open, `close` = :close WHERE `Assignment` = :assignmentid AND `Group` = :groupid AND `week_num` = :week_num");
					} else {
						// Insert new record
						$stmt = $this->pdo->prepare("INSERT INTO groupsassignments (`Assignment`, `Group`, `open`, `close`, `week_num`) VALUES (:assignmentid, :groupid, :open, :close, :week_num)");
					}

					// Bind parameters and execute
					try {
						$stmt->bindParam(':assignmentid', $assignmentid, PDO::PARAM_INT);
						$stmt->bindParam(':groupid', $groupSlot["Id"], PDO::PARAM_INT);
						$stmt->bindParam(':open', $open);
						$stmt->bindParam(':close', $close);
						$stmt->bindParam(':week_num', $weekNum, PDO::PARAM_INT);
						$stmt->execute();
					} catch (PDOException $e) {
						echo "Error in query: " . $e->getMessage();
					}
				}
			}
		}
		return true;
	}
	public function getstartweek()
	{
		$stmt = $this->pdo->prepare("SELECT * from startweek;");
		$stmt->execute();
		$startweek = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return $startweek;
	}
	public function AssignmentGroupsShow()
	{
		$stmt = $this->pdo->prepare("Select * from GroupsAssignments");
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$final = [];
		foreach ($result as $value) {
			$stmt2 = $this->pdo->prepare("SELECT Name from `groups` where id =:selected;");
			$this->helpers->bindParams([
				"selected" => $value["Group"]
			], $stmt2, true);
			$groupName = $stmt2->fetch(PDO::FETCH_ASSOC);

			$stmt2 = $this->pdo->prepare("SELECT Name from `assignments` where id =:selected;");
			$this->helpers->bindParams([
				"selected" => $value["Assignment"]
			], $stmt2, true);
			$AssignmentName = $stmt2->fetch(PDO::FETCH_ASSOC);
			array_push($final, ["openTime" => $value["open"], "closeTime" => $value["close"], "group" => $groupName["Name"], "Assignment" => $AssignmentName["Name"]]);
		}
		return $final;
	}
	public function evaluateImage($imageId, $grade)
	{
		$grade = $grade > 10 ? 10 : $grade;
		$grade = $grade < 0 ? 0 : $grade;
		$stmt = $this->pdo->prepare("UPDATE assignmentimages set Grade=:Grade Where Id=:Selected");
		$this->helpers->bindParams([
			"Selected" => $imageId,
			"Grade" => $grade
		], $stmt, true);
		return true;
	}

	public function getSubmissionsByAssignmentId(int $assignmentId)
	{
		// Prepare the initial statement to get submissions
		$stmt = $this->pdo->prepare("SELECT * FROM submissions WHERE assignmentId = :assignmentId");
		$this->helpers->bindParams([
			"assignmentId" => $assignmentId
		], $stmt, true);
		$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $submissions;
	}
	public function getSubmissionsByStudentAndAssignmentToBest(int $studentId, int $assignmentId)
	{
		$stmt = $this->pdo->prepare("SELECT * FROM submissions WHERE StudentId = :student and assignmentId=:assignment");
		$stmt->execute([":student" => $studentId, ":assignment" => $assignmentId]);
		$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$stmt = $this->pdo->prepare("SELECT NAME from assignments where Id=:selected");
		$stmt->execute([":selected" => $assignmentId]);
		$name = $stmt->fetchColumn();

		$maxGrade = -1;
		$maxKey = "";
		foreach ($submissions as $key => &$sub) {
			$sub["Grade"] = $this->getGrade($sub["Id"]);
			$sub["assignmentName"] = $name;
			if ($sub["Grade"]["Total"] > $maxGrade) {
				$maxGrade = $sub["Grade"]["Total"];
				$maxKey = $key;
			}
			$sub["BestGrade"] = false;
		}
		if ($maxKey !== "")
			$submissions[$maxKey]["BestGrade"]  = true;
		return $submissions;
	}
	public function getFullSubmissionDataByAssignmentId(int $assignmentId)
	{
		$submissions = $this->getSubmissionsByAssignmentId($assignmentId);
		$responseData = [];
		$userClass = new USER(($this->pdo));
		foreach ($submissions as $submission) {
			$userData = $userClass->getUser($submission['StudentId'], 'Id');

			$userData["submission"] = $submission["Id"];
			$userData["Grade"] = $this->getGrade($submission["Id"]);
			$userData["submitTime"] = $submission["submitTime"];

			$userData["weekNum"] = $submission["weekNum"];
			if ($userData)
				$responseData[] = $userData;
		}
		return $responseData;
		// $stmt = $this->pdo->prepare("SELECT * from assignments");
		// $stmt->execute();
		// $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
		// foreach($assignments as $assignment){

		// }
	}
	public function getGrade($submission)
	{
		$stmt = $this->pdo->prepare("SELECT * FROM assignmentimages WHERE submissionId=:selected;");
		$this->helpers->bindParams([
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
		foreach ($result as &$image) {
			$stmt = $this->pdo->prepare("SELECT Name from categories Where Id=:selected");
			$stmt->execute([":selected" => $image["CategoryId"]]);
			$image["CategoryName"] = $stmt->fetchColumn();
		}
		return $result;
	}
	public function GetSubmissionByUserAndAssignment($user, $assignment)
	{
		$stmt = $this->pdo->prepare(("SELECT * FROM submissions  WHERE StudentId=:user AND assignmentId=:assignment;"));
		$this->helpers->bindParams([
			"user" => $user,
			"assignment" => $assignment
		], $stmt, true);
		$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach ($submissions as &$submission)
			$submission['Grade'] = $this->getGrade($submission['Id']);
		return $submissions;
	}
	public function getSubmissionUserAssignmentWeek($user, $assignment, $week)
	{
		$stmt = $this->pdo->prepare(("SELECT * FROM submissions  WHERE StudentId=:user AND assignmentId=:assignment AND weekNum=:week;"));
		$this->helpers->bindParams([
			"user" => $user,
			"assignment" => $assignment,
			"week" => $week
		], $stmt, true);
		$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (count($submissions) > 0) {
			return true;
		}
		return false;
	}
	public function getSubmittedAssignmentCategories($user, $assignment,  $week)
	{
		$stmt = $this->pdo->prepare(("SELECT CategoryId FROM assignmentimages  WHERE StudentId=:user AND assignmentId=:assignment AND weekNum=:week;"));
		$this->helpers->bindParams([
			"user" => $user,
			"assignment" => $assignment,
			"week" => $week
		], $stmt, true);
		$AssignmentCategories = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

		return $AssignmentCategories;
	}
	public function GetSubmissionByUser($user)
	{
		try {
			$stmt = $this->pdo->prepare("SELECT * FROM submissions WHERE StudentId = :selected");
			$stmt = $this->helpers->bindParams([
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
		$this->helpers->bindParams([
			"selected" => $student
		], $stmt, true);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}

<?php
// require 'connection.php';

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

    public function getSubmissionsByAssignmentId($assignmentId)
    {
        $stmt = $this->conn->prepare("SELECT * FROM submissions WHERE assignmentId = :assignmentId");
        if (!$stmt) {
            echo "Prepare failed: (" . $this->conn->errorCode() . ") " . $this->conn->errorInfo()[2];
            return false;
        }

        $stmt->bindValue(':assignmentId', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $submissions;
    }

}

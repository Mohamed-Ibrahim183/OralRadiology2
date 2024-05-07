<?php
    require 'connection.php'; 
class Assignment {
    private $conn;

    function __construct($conn) {
        $this->conn = $conn;
        if ($this->conn->connect_error) {
            throw new Exception("Connection failed: " . $this->conn->connect_error);
        }
    }
    public function addAssignment($data, $userId) {
        if (!$this->conn->begin_transaction()) {
            throw new Exception("Failed to start transaction: " . $this->conn->error);
        }

        $stmt = $this->conn->prepare("INSERT INTO assignments (Name, ProfessorId, maxLimitImages, Topic) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siis", $data['requirementName'], $userId, $data['maxImages'], $data['topicName']);
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert assignment: ' . $stmt->error);
        }
        $assignmentId = $stmt->insert_id;
        $stmt->close();

        // Insert group assignments
        foreach ($data['groups'] as $groupId => $times) {
            $stmt = $this->conn->prepare("INSERT INTO groupsassignments (`open`, `close`, Assignment, `Group`) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $times['openTime'], $times['closeTime'], $assignmentId, $groupId);
            if (!$stmt->execute()) {
                throw new Exception('Failed to insert group assignment: ' . $stmt->error);
            }
            $stmt->close();
        }

        $this->conn->commit();
        return ['success' => true, 'assignmentId' => $assignmentId];
    }

    public function getAssignments($assignmentId = null) {
        if ($assignmentId !== null) {
            $sql = "SELECT Name, Topic, Id FROM assignments WHERE Id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $assignmentId);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $sql = "SELECT Name, Topic, Id FROM assignments";
            $result = $this->conn->query($sql);
        }
    
        $assignments = [];
    
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $assignments[] = $row;
            }
        }
    
        return $assignments;
    }
    

    public function uploadAssignment($userId, $assignmentId, $files) {
        $response = [];

        foreach ($files['tmp_name'] as $key => $tmpName) {
            $originalName = basename($files['name'][$key]);
            $newFileName = "assignment{$assignmentId}_user{$userId}_{$originalName}";
            $targetPath = $_SERVER['DOCUMENT_ROOT'] . "/Projects/uploads/$userId/" . $newFileName;

            if (move_uploaded_file($tmpName, $targetPath)) {
                $dbPath = "Projects/uploads/$userId/" . $newFileName;
                $sql = "INSERT INTO assignmentimages (Path, StudentID, AssignmentId, CategoryId) VALUES (?, ?, ?, 1)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('sii', $dbPath, $userId, $assignmentId);
                if ($stmt->execute()) {
                    $response[] = [
                        'success' => 'Record inserted successfully',
                        'path' => "http://localhost/" . $dbPath
                    ];
                } else {
                    $response[] = ['error' => 'Execute error: ' . $stmt->error];
                }
                $stmt->close();
            } else {
                $response[] = ['error' => 'Failed to upload file: ' . $originalName];
            }
        }

        return $response;
    }

    function __destruct() {
        $this->conn->close();
    }
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $assignment = new Assignment($conn);

    // Assume session starts and user ID is verified
    session_start();
    $data = json_decode(file_get_contents("php://input"), true);
    $userId = $_SESSION['userId'];

    // Example method calls
    $result = $assignment->addAssignment($data, $userId);
    echo json_encode($result);

    $assignments = $assignment->getAssignments();
    echo json_encode($assignments);

    // Assuming $assignmentId and $_FILES are defined and valid
    $uploadResult = $assignment->uploadAssignment($userId, $assignmentId, $_FILES);
    echo json_encode($uploadResult);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
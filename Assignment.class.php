<?php
require 'connection.php';  

class Assignment {
    private $Id;
    private $Name;
    private $ProfessorId;
    private $maxLimitImages;
    private $Topic;
    private $conn; 
    public function __construct($conn) {
    $this->conn = $conn;
    }
    public function insertAssignment($data) {
        if (empty($data['Name']) || empty($data['ProfessorId']) || empty($data['maxLimitImages']) || empty($data['Topic'])) {
            return false;  // Data validation
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
    public function fetchAssignment($assignmentId) {
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
    public function fetchAllAssignments() {
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
    public function uploadImages($files, $assignmentId, $studentId, $maxImages) {
        $uploadedFiles = 0;
        $responses = [];
        foreach ($files['tmp_name'] as $key => $tmpName) {
            if ($uploadedFiles >= $maxImages) {
                $responses[] = ['error' => 'Image limit exceeded'];
                break;
            }

            $originalName = basename($files['name'][$key]);
            $newFileName = "assignment{$assignmentId}_student{$studentId}_{$originalName}";
            $targetPath = "Students/" . $newFileName;

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
}
?>

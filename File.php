<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

// Maximum file size allowed (5MB)
$maxFileSize = 5 * 1024 * 1024; // 5MB in bytes

if ($_FILES) {
  $file = $_FILES["File"];
  $fileName = $file["name"];
  $fileSize = $file["size"];
  $fileTmpName = $file["tmp_name"];
  $fileType = $file["type"];
  $fileError = $file["error"];

  // Check if file is an image
  $allowedTypes = array("image/jpeg", "image/png", "image/gif");
  if (!in_array($fileType, $allowedTypes)) {
    echo "Error: File is not an image.";
    exit();
  }

  // Check file size
  if ($fileSize > $maxFileSize) {
    echo "Error: File size exceeds the maximum limit (5MB).";
    exit();
  }

  $targetDir = "./OralImages/";
  $targetFile = $targetDir . $fileName;

  if (move_uploaded_file($fileTmpName, $targetFile)) {
    echo "The file " . $fileName . " has been uploaded.";
  } else {
    echo "Sorry, there was an error uploading your file.";
  }
} else {
  echo "No file uploaded.";
}

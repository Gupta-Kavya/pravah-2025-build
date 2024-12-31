<?php
header('Content-Type: application/json');

// Enable CORS headers to allow requests from any origin
header('Access-Control-Allow-Origin: *'); // Allow any domain
header('Access-Control-Allow-Methods: POST, GET, OPTIONS'); // Allow certain HTTP methods
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Allow necessary headers

// Handle OPTIONS request (pre-flight request)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Define the upload directory (relative to the script location)
$target_dir = "uploads/"; // Ensure this directory exists and is writable
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true); // Create the directory if it doesn't exist
}

$response = array();

// Check if a file has been uploaded
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES["fileToUpload"])) {

    $original_file = $_FILES["fileToUpload"]["tmp_name"];
    $imageFileType = strtolower(pathinfo($_FILES["fileToUpload"]["name"], PATHINFO_EXTENSION));

    // Check if the file is a valid image
    $check = getimagesize($original_file);
    if ($check === false) {
        $response['error'] = "File is not an image.";
        echo json_encode($response);
        exit;
    }

    // Limit file size to 1MB (optional)
    if ($_FILES["fileToUpload"]["size"] > 1000000) {
        $response['error'] = "Sorry, your file is too large.";
        echo json_encode($response);
        exit;
    }

    // Allow certain file formats (JPG, JPEG, PNG, GIF)
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        $response['error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        echo json_encode($response);
        exit;
    }

    // Generate a random name for the uploaded file
    $random_name = uniqid(rand(), true) . '.webp';
    $target_file = $target_dir . $random_name;

    // Convert the image to WebP format
    $image = null;
    switch ($imageFileType) {
        case 'jpg':
        case 'jpeg':
            $image = imagecreatefromjpeg($original_file);
            break;
        case 'png':
            $image = imagecreatefrompng($original_file);
            break;
        case 'gif':
            $image = imagecreatefromgif($original_file);
            break;
    }

    if ($image) {
        // Save the image as WebP
        if (imagewebp($image, $target_file, 80)) { // 80 is the quality percentage
            imagedestroy($image);
            // Success - send back the image URL in JSON format
            $imageUrl = "http://" . $_SERVER['HTTP_HOST'] . "/api/" . $target_file;
            $response['url'] = $imageUrl;
        } else {
            $response['error'] = "Sorry, there was an error converting your file to WebP.";
        }
    } else {
        $response['error'] = "Failed to process the uploaded image.";
    }
} else {
    $response['error'] = "No file uploaded or invalid request.";
}

// Output the response as JSON
echo json_encode($response);
?>

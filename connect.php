<?php
// This line enables detailed error reporting, which is very useful for debugging.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ===============================================
// 1. DATABASE CONNECTION
// ===============================================

// Database credentials for XAMPP's default MySQL setup
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "flower"; // The database name you provided

// Create a new database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ===============================================
// 2. RECAPTCHA CONFIGURATION
// ===============================================
// 🟢 IMPORTANT: Please replace "YOUR_SECRET_KEY" with your actual reCAPTCHA secret key.
// This key is different from the site key used in your HTML.
$recaptcha_secret = "6Ld_f6UrAAAAAH-rClHZoUo1kTcXqAkX-G5rtXFp";

// ===============================================
// 3. HANDLE FORM SUBMISSION
// ===============================================

// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the reCAPTCHA response exists
    if (!isset($_POST['g-recaptcha-response'])) {
        die("reCAPTCHA validation failed. Please check the box.");
    }
    
    // Get the reCAPTCHA response from the form
    $recaptcha_response = $_POST['g-recaptcha-response'];
    
    // Prepare data for the reCAPTCHA verification request
    $data = [
        'secret' => $recaptcha_secret,
        'response' => $recaptcha_response
    ];
    
    // Send the request to Google's reCAPTCHA verification API
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context  = stream_context_create($options);
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    $response = file_get_contents($verify_url, false, $context);
    $result = json_decode($response, true);
    
    // Check if reCAPTCHA verification was successful
    if (!$result['success']) {
        die("reCAPTCHA verification failed. Please try again.");
    }
    
    // If reCAPTCHA is successful, proceed with form data
    
    // Sanitize and get form data.
    $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
    $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
    $number = isset($_POST['number']) ? htmlspecialchars(trim($_POST['number'])) : '';
    $message = isset($_POST['message']) ? htmlspecialchars(trim($_POST['message'])) : '';

    // ===============================================
    // 4. SECURELY INSERT DATA WITH PREPARED STATEMENTS
    // ===============================================

    // SQL statement to insert into the 'contact' table with your fields.
    $sql = "INSERT INTO `contact` (`name`, `email`, `number`, `message`) VALUES (?, ?, ?, ?)";
    
    // Create a prepared statement object
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Bind the variables to the prepared statement as parameters.
        $stmt->bind_param("ssss", $name, $email, $number, $message);
        
        // Execute the statement
        if ($stmt->execute()) {
            echo "Thank you! Your message has been sent successfully.";
        } else {
            echo "Error executing statement: " . $stmt->error;
        }
        
        // Close the statement
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}

// ===============================================
// 5. CLOSE THE CONNECTION
// ===============================================
$conn->close();
?>

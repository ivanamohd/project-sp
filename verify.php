<?php

// Include config file
require_once "config.php";
require_once "mail.php";

// Initialize the session
session_start();

// Check if the user is logged in, otherwise redirect to the login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Check Code
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // Retrieve the code from the session
    $verificationCode = $_SESSION['verification_code'];

    // Get the entered code from the user
    $enteredCode = $_POST['codes'];

    if ($enteredCode == $verificationCode) {
        header("Location: admin/dashboard.php");
        if ($_SESSION["role"] == 1) {
            // Redirect user to admin dashboard page
            header("location: admin/dashboard.php");
        } else {
            // Redirect user to welcome page
            header("location: welcome.php");
        }
        exit;
    } else {
        echo "<script>alert('Wrong code.');</script>";
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Verify</title>
</head>

<body>

    <h1>Verification Code</h1>

    <div>
        <br>An email was sent to your address. Enter the code from the email below to access your account:<br><br>
        <form method="post">
            <input type="text" name="codes" placeholder="Enter your code"><br>
            <br>
            <input type="submit" value="Submit">
        </form>
    </div>

</body>

</html>
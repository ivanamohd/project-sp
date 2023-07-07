<?php
// Initialize the session
session_start();

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$current_password = $new_password = $confirm_password = "";
$current_password_err = $new_password_err = $confirm_password_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate current password
    if (empty($con->real_escape_string($_POST["current_password"]))) {
        $current_password_err = "Please enter the current password.";
    } elseif (strlen($con->real_escape_string($_POST["current_password"])) < 8) {
        $current_password_err = "Password must have atleast 8 characters.";
    } else {
        $sql = "SELECT * FROM users WHERE id = ?";
        $statement = $con->prepare($sql);
        $statement->bind_param('i', $_SESSION["id"]);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result->fetch_assoc();

        if (!empty($row)) {
            $hashedPassword = $row["password"];
            if (password_verify($_POST["current_password"], $hashedPassword)) {
                // matched
            } else
                $current_password_err = "Current password does not match.";
        }
    }

    // Validate new password
    if (empty($con->real_escape_string($_POST["new_password"]))) {
        $new_password_err = "Please enter the new password.";
    } elseif (strlen($con->real_escape_string($_POST["new_password"])) < 8) {
        $new_password_err = "Password must have atleast 8 characters.";
    } elseif (!preg_match("#[0-9]+#", $con->real_escape_string($_POST["new_password"]))) {
        $new_password_err = "Password must contain at least 1 number.";
    } elseif (!preg_match("#[A-Z]+#", $con->real_escape_string($_POST["new_password"]))) {
        $new_password_err = "Password must contain at least 1 capital letter.";
    } elseif (!preg_match("#[a-z]+#", $con->real_escape_string($_POST["new_password"]))) {
        $new_password_err = "Password must contain at least 1 lowercase letter.";
    } else {
        $new_password = $con->real_escape_string($_POST["new_password"]);
    }

    // Validate confirm password
    if (empty($con->real_escape_string($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm the password.";
    } else {
        $confirm_password = $con->real_escape_string($_POST["confirm_password"]);
        if (empty($new_password_err) && ($new_password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }

    // Check input errors before updating the database
    if (empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)) {
        // Prepare an update statement
        $sql = "UPDATE users SET password = ? WHERE id = ?";

        if ($stmt = mysqli_prepare($con, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "si", $param_password, $param_id);

            // Set parameters
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);
            $param_id = $_SESSION["id"];

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Password updated successfully. Destroy the session, and redirect to login page
                session_destroy();
                header("location: index.php");
                exit();
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Close connection
    mysqli_close($con);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
</head>

<body>
    <h2>Reset Password</h2>
    <p>Please fill out this form to reset your password.</p>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <label>Current Password</label>
        <input type="password" name="current_password" class="form-control <?php echo (!empty($current_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $current_password; ?>">
        <span class="invalid-feedback"><?php echo $current_password_err; ?></span>
        <br><br><label>New Password</label>
        <input type="password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $new_password; ?>">
        <span class="invalid-feedback"><?php echo $new_password_err; ?></span>

        <br><br><label>Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
        <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>

        <br><br><input type="submit" class="btn btn-primary" value="Submit">
        <a class="btn btn-link ml-2" href="welcome.php">Cancel</a>

    </form>
</body>

</html>
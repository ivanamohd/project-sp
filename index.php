<?php
// Set session cookie parameters
session_set_cookie_params([
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);

// Initialize the session
session_start();

// Regenerate session ID
session_regenerate_id();

// Check if the user is already logged in, if yes then redirect user to welcome or dashboard page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    if ($_SESSION["role"] == 1) {
        header("location: admin/dashboard.php");
        session_regenerate_id();
        exit;
    } else {
        header("location: welcome.php");
        session_regenerate_id();
        exit;
    }
}

// Include config file
require_once "config.php";
require_once "mail.php";

// Define variables and initialize with empty values
$username_input = $password_input = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Escape special characters, if any
    $username_input = $con->real_escape_string($_POST['username']);
    $password_input = $con->real_escape_string($_POST['password']);

    // Check if username is empty
    if (empty(trim($username_input))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($username_input);
    }

    // Check if password is empty
    if (empty(trim($password_input))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($password_input);
    }

    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT id, username, password, role, email FROM users WHERE username = ?";

        if ($stmt = mysqli_prepare($con, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);

            // Set parameters
            $param_username = $username;

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);

                // Check if username exists, if yes then verify password
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role, $email);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, so start a new session
                            session_start();

                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["email"] = $email;
                            $_SESSION["role"] = $role;

                            // Generate a random code
                            $code = rand(10000, 99999);

                            // Store the code in the session
                            $_SESSION['verification_code'] = $code;

                            $message = "Your codes is " . $code;
                            $subject = "Verification Code";
                            $recipient = $email;
                            send_mail($recipient, $subject, $message);

                            header("location: verify.php");
                        } else {
                            // Password is incorrect, display a generic error message
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    // Username doesn't exist, display a generic error message
                    $login_err = "Invalid username or password.";
                }
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
    <title>Login</title>
</head>

<body>
    <h2>Login</h2>
    <p>Please fill in your credentials to login.</p>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <label>Username:</label>
        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username_input; ?>">
        <span class="invalid-feedback"><?php echo $username_err; ?></span>

        <br><br><label>Password:</label>
        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
        <span class="invalid-feedback"><?php echo $password_err; ?></span>

        <br><br><input type="submit" class="btn btn-primary" value="Login">
        <input type="reset" class="btn btn-secondary ml-2" value="Reset">

        <?php
        if (!empty($login_err)) {
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }
        ?>

        <p><a href="register.php">Register</a></p>
        <p><a href="forgot_pass.php">Forgot Password</a></p>
    </form>

</body>

</html>
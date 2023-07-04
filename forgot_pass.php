<?php
// Include config file
require_once "config.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

// Define variables and initialize with empty values
$email = "";
$email_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate email
    if (empty($con->real_escape_string($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE email = ?";

        if ($stmt = mysqli_prepare($con, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);

            // Set parameters
            $param_email = $con->real_escape_string($_POST["email"]);

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                /* store result */
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) != 1) {
                    $email_err = "Email does not exist.";
                } else {
                    $email = $con->real_escape_string($_POST["email"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }


    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);

    // Check input errors before inserting in database
    if (empty($email_err)) {

        $str = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz";
        $password_length = 8;
        $new_pass = substr(str_shuffle($str), 0, $password_length);

        $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);

        $stmt = $con->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        $stmt->execute();
        if ($stmt->affected_rows != 1) {
            return "There was a connection error, please try again.";
        }

        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'hostelcare23@gmail.com';
        $mail->Password = 'dnezxhkjxaoeyigj';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('hostelcare23@gmail.com');

        $mail->addAddress($_POST["email"]);
        $mail->isHTML(true);
        $mail->Subject = "Password Recovery";
        $mail->Body = "You can log in with your new password" . "\r\n" . $new_pass;

        if ($mail->send()) {
            echo "<script>alert('Email sent. Please check your inbox.');</script>";
        } else echo "<script>alert('Error in sending email.');</script>";
    }

    // Close connection
    mysqli_close($con);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Recover Password</title>
</head>

<body>
    <h2>Recover Password</h2>
    <p>Please fill this form to recover pasword.</p>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

        <label>Email:</label>
        <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>"
            value="<?php echo $email; ?>">
        <span class="invalid-feedback"><?php echo $email_err; ?></span>



        <br><br>
        <input type="submit" class="btn btn-primary" value="Submit">
        <input type="reset" class="btn btn-secondary ml-2" value="Reset">

        <p>Remembered your password? <a href="index.php">Login here</a>.</p>
    </form>
</body>

</html>
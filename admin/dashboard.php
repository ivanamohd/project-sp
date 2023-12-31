<?php
// Initialize the session
session_start();

// Check if the user is logged in and role is 1, if not then redirect user to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 1) {
    header("location: ../index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>

<body>
    <h1 class="my-5">Hi admin <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. Welcome!</h1>
    <p>
        <a href="product.php" class="btn btn-warning">Manage Products</a>
        <a href="../reset_pass.php" class="btn btn-warning">Reset Your Password</a>
        <a href="../logout.php" class="btn btn-danger ml-3">Logout</a>
    </p>
</body>

</html>
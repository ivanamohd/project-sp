<?php
// Start the session
session_start();

// Check if the user is logged in and role is 0, if not then redirect user to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 0) {
    header("location: index.php");
    exit;
}

require_once "config.php";
require_once 'encryption.php';
// Regenerate session ID
session_regenerate_id();

if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32)); // Generate a random token value
}

if (!isset($_SESSION['lastaccess'])) {
    $_SESSION['lastaccess'] = time();
} else {
    // Check if the last access is over 15 minutes
    if (time() > ($_SESSION['lastaccess'] + 900)) {
        session_unset();
        session_destroy();
    } else {
        $_SESSION['lastaccess'] = time();
    }
}

$card_number_err = $card_expiry_err = $card_cvv_err = $user_name_err = $user_contact_err = $user_address_err = "";

// Generate or retrieve the key pair
if (!isset($_SESSION['private_key']) || !isset($_SESSION['public_key'])) {
    // Generate a new key pair
    $config = [
        "digest_alg" => "sha256",
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];
    $resource = openssl_pkey_new($config);

    // Get private key
    openssl_pkey_export($resource, $privateKey);

    // Get public key
    $publicKey = openssl_pkey_get_details($resource)['key'];

    // Store the keys in the session
    $_SESSION['private_key'] = $privateKey;
    $_SESSION['public_key'] = $publicKey;
} else {
    // Retrieve the key pair from the session
    $privateKey = $_SESSION['private_key'];
    $publicKey = $_SESSION['public_key'];
}

// Check if the payment form is submitted
if (isset($_POST["submit_payment"])) {
    // Verify CSRF token
    if (!empty($_POST['token']) && $_POST['token'] === $_SESSION['token']) {
        // Retrieve payment information from the form
        $card_number = $_POST["card_number"];
        $card_expiry = $_POST["card_expiry"];
        $card_cvv = $_POST["card_cvv"];
        $user_name = $_POST["user_name"];
        $user_contact = $_POST["user_contact"];
        $user_address = $_POST["user_address"];

        // Encrypt using the public key
        openssl_public_encrypt($user_name, $encrypted_user_name, $public_key);
        $penc_user_name = bin2hex($encrypted_user_name);

        // Encryption key
        $key = 'thekey';

        // Check input errors before inserting in database
        if (!empty($card_number) && !empty($card_expiry) && !empty($card_cvv) && !empty($user_name) && !empty($user_contact) && !empty($user_address)) {
            // Validate username
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $user_name)) {
                $user_name_err = "Username can only contain letters, numbers, and underscores.";
            } else {
                // Validate contact number
                if (!preg_match('/^\d{10,11}$/', $user_contact)) {
                    $user_contact_err = "Contact number should be 10 to 11 digits.";
                } else {
                    // Validate address
                    if (!preg_match('/^[a-zA-Z0-9\s\/.-]+$/', $user_address)) {
                        $user_address_err = "Address can only contain letters, numbers, spaces, slashes (/), dashes (-), and dots (.)";
                    } else {
                        // Validate CVV
                        if (!preg_match('/^[0-9]{3,4}$/', $card_cvv)) {
                            $card_cvv_err = "Invalid CVV. CVV must be a 3 or 4-digit number.";
                        } else {
                            // Validate card number
                            if (strlen($card_number) != 16) {
                                $card_number_err = "Invalid credit card number length. Please enter a 16-digit card number.";
                            } else {
                                // Validate expiry date format (MM/YY)
                                if (preg_match('/^(0[1-9]|1[0-2])\/(2[2-9]|[3-9][0-9])$/', $card_expiry)) {
                                    // Extract month and year from the expiry date
                                    $expiry_parts = explode('/', $card_expiry);
                                    $expiry_month = $expiry_parts[0];
                                    $expiry_year = $expiry_parts[1];

                                    // Validate expiry date values
                                    $current_month = date('m');
                                    $current_year = date('y');

                                    if ($expiry_year >= $current_year && ($expiry_year > $current_year || $expiry_month >= $current_month)) {
                                        // Expiry date is valid, proceed with further processing

                                        // cart
                                        $order_details = $_SESSION["shopping_cart"];
                                        $item_total = $_SESSION["total"];

                                        foreach ($order_details as $order) {
                                            $item_id = $order['item_id'];
                                            $item_name = $order['item_name'];
                                            $item_quantity = $order['item_quantity'];
                                            $item_price = $order['item_price'];
                                        }

                                        // Encrypt the credit card number
                                        $encrypted_card_number = encryptData($card_number, $key);

                                        // Encrypt the card expiry date
                                        $encrypted_card_expiry = encryptData($card_expiry, $key);

                                        // Encrypt the card CVV
                                        $encrypted_card_cvv = encryptData($card_cvv, $key);

                                        // Encrypt the user contact number
                                        $encrypted_user_contact = encryptData($user_contact, $key);

                                        // Prepare an insert statement
                                        $sql = "INSERT INTO orders (item_id, item_name, item_quantity, item_price, item_total, card_number, card_expiry, card_cvv, user_name, user_contact, user_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                                        if ($stmt = mysqli_prepare($con, $sql)) {
                                            // Bind variables to the prepared statement as parameters
                                            mysqli_stmt_bind_param($stmt, "sssssssssss", $param_item_id, $param_item_name, $param_item_quantity, $param_item_price, $param_item_total, $param_card_number, $param_card_expiry, $param_card_cvv, $param_user_name, $param_user_contact, $param_user_address);

                                            // Set parameters
                                            $param_item_id = $item_id;
                                            $param_item_name = $item_name;
                                            $param_item_quantity = $item_quantity;
                                            $param_item_price = $item_price;
                                            $param_item_total = $item_total;
                                            $param_card_number = $encrypted_card_number;
                                            $param_card_expiry = $encrypted_card_expiry;
                                            $param_card_cvv = $encrypted_card_cvv;
                                            $param_user_name = $user_name;
                                            $param_user_contact = $encrypted_user_contact;
                                            $param_user_address = $user_address;

                                            // Attempt to execute the prepared statement
                                            if (mysqli_stmt_execute($stmt)) {
                                                // Clear the shopping cart
                                                unset($_SESSION["shopping_cart"]);
                                                // Redirect to login page
                                                echo "<script>
                                                        alert('Payment successful!');
                                                        window.location.href = 'index.php';
                                                      </script>";
                                            } else {
                                                echo "Oops! Something went wrong. Please try again later.";
                                            }

                                            // Close statement
                                            mysqli_stmt_close($stmt);
                                        }
                                    } else {
                                        $card_expiry_err = "Invalid expiry date. Please enter a valid future expiry date.";
                                    }
                                } else {
                                    $card_expiry_err = "Invalid expiry date format. Please enter the expiry date in the format MM/YY.";
                                }
                            }
                        }
                    }
                }
            }
        }
    } else {
        // Invalid CSRF token, show an error message
        echo "Invalid CSRF token. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Checkout</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
    <script>
        function goBack() {
            window.history.back();
        }
    </script>
</head>

<body>
    <br />
    <div class="container" style="width:700px;">
        <h3 align="center">Checkout</h3><br />

        <!-- Display the cart items -->
        <div class="table-responsive">
            <table class="table table-bordered">
                <tr>
                    <th width="40%">Item Name</th>
                    <th width="10%">Quantity</th>
                    <th width="20%">Price</th>
                    <th width="15%">Total</th>
                </tr>
                <?php
                if (isset($_SESSION["shopping_cart"]) && !empty($_SESSION["shopping_cart"])) {
                    $total = 0;
                    foreach ($_SESSION["shopping_cart"] as $keys => $values) {
                ?>
                        <tr>
                            <td><?php echo htmlspecialchars($values["item_name"]); ?></td>
                            <td><?php echo htmlspecialchars($values["item_quantity"]); ?></td>
                            <td>RM <?php echo htmlspecialchars($values["item_price"]); ?></td>
                            <td>RM
                                <?php echo htmlspecialchars(number_format($values["item_quantity"] * $values["item_price"], 2)); ?>
                            </td>
                        </tr>
                    <?php
                        $total = $total + ($values["item_quantity"] * $values["item_price"]);
                    }
                    ?>
                    <tr>
                        <td colspan="3" align="right">Total</td>
                        <td align="right">RM <?php echo number_format($total, 2); ?></td>
                    </tr>
                <?php
                } else {
                    echo "<tr><td colspan='4'>No items in the cart</td></tr>";
                }
                ?>
            </table>
        </div>

        <!-- Payment form -->
        <div align="center">
            <h4>Payment Information</h4>
            <?php if (isset($error_message)) { ?>
                <p style="color: red;"><?php echo $error_message; ?></p>
            <?php } ?>
            <form method="post" action="checkout.php">
                <input type="text" name="card_number" placeholder="Card Number" required <?php echo (!empty($card_number_err)) ? 'is-invalid' : ''; ?> value="<?php echo isset($_POST["card_number"]) ? $_POST["card_number"] : ''; ?>">
                <span class="invalid-feedback"><?php echo $card_number_err; ?></span><br><br>

                <input type="text" name="card_expiry" placeholder="Expiry Date" required <?php echo (!empty($card_expiry_err)) ? 'is-invalid' : ''; ?> value="<?php echo isset($_POST["card_expiry"]) ? $_POST["card_expiry"] : ''; ?>">
                <span class="invalid-feedback"><?php echo $card_expiry_err; ?></span><br><br>

                <input type="text" name="card_cvv" placeholder="CVV" required <?php echo (!empty($card_cvv_error)) ? 'is-invalid' : ''; ?> value="<?php echo isset($_POST["card_cvv"]) ? $_POST["card_cvv"] : ''; ?>">
                <span class="invalid-feedback"><?php echo $card_cvv_err; ?></span><br><br>

                <input type="text" name="user_name" placeholder="Name" required <?php echo (!empty($user_name_err)) ? 'is-invalid' : ''; ?> value="<?php echo isset($_POST["user_name"]) ? $_POST["user_name"] : ''; ?>">
                <span class="invalid-feedback"><?php echo $user_name_err; ?></span><br><br>

                <input type="text" name="user_contact" placeholder="Contact Number" required <?php echo (!empty($user_name_err)) ? 'is-invalid' : ''; ?> value="<?php echo isset($_POST["user_contact"]) ? $_POST["user_contact"] : ''; ?>">
                <span class="invalid-feedback"><?php echo $user_contact_err; ?></span><br><br>

                <input type="text" name="user_address" placeholder="Address" required <?php echo (!empty($user_address_err)) ? 'is-invalid' : ''; ?> value="<?php echo isset($_POST["user_address"]) ? $_POST["user_address"] : ''; ?>">
                <span class="invalid-feedback"><?php echo $user_address_err; ?></span><br><br>

                <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
                <input type="submit" name="submit_payment" class="btn btn-success" value="Make Payment">
                <button class="btn btn-primary" onclick="goBack()">Back</button>
            </form>
        </div>
    </div>
    <br />
</body>

</html>
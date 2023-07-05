<?php
session_start();
require_once "../config.php";

// Check if the user is logged in and role is 1, if not then redirect user to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 1) {
    header("location: ../index.php");
    exit;
}

$name_err = $description_err = $price_err = "";

// Delete button is clicked
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_id"])) {
    // Retrieve product ID from the form
    $id = $_POST["delete_id"];

    // Prepare a delete statement
    $query = "DELETE FROM products WHERE id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);

    // Execute the delete statement
    if (mysqli_stmt_execute($stmt)) {
        // Redirect to the current page to refresh the product list
        header("Location: " . $_SERVER["PHP_SELF"]);
        exit;
    } else {
        // Handle the error if the deletion fails
        echo "Error deleting the product.";
    }

    // Close the statement
    mysqli_stmt_close($stmt);
}

// Save button is clicked
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {
    // Retrieve form data
    $action = $_POST["action"];
    $id = $_POST["id"];
    $name = $_POST["name"];
    $description = $_POST["description"];
    $price = $_POST["price"];

    if (!empty($name) && !empty($description) && !empty($price)) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            $name_err = "Name can only contain letters, numbers, and underscores.";
        } elseif (!preg_match('/^[a-zA-Z0-9\s\/.$*-]+$/', $description)) {
            $description_err = "Description can only contain letters, numbers, spaces, slashes (/), dashes (-), and dots (.)";
        } elseif (!preg_match('/^[0-9.]+$/', $price)) {
            $number_err = "Invalid input. Only numbers and decimals are allowed.";
        } else {
            // Add Product or Edit Product
            if ($action == "create") {
                // Prepare an insert statement
                $query = "INSERT INTO products (name, description, price) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($con, $query);
                mysqli_stmt_bind_param($stmt, "ssd", $name, $description, $price);
            } elseif ($action == "edit") {
                // Prepare an update statement
                $query = "UPDATE products SET name = ?, description = ?, price = ? WHERE id = ?";
                $stmt = mysqli_prepare($con, $query);
                mysqli_stmt_bind_param($stmt, "ssdi", $name, $description, $price, $id);
            }

            // Execute the statement
            if (mysqli_stmt_execute($stmt)) {
                // Redirect to the current page to refresh the product list
                header("Location: " . $_SERVER["PHP_SELF"]);
                exit;
            } else {
                // Handle the error if the insertion or update fails
                echo "Error " . ($action == "create" ? "adding" : "updating") . " the product.";
            }

            // Close the statement
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage Products</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
</head>

<body>
    <br />
    <div class="container" style="width:700px;">
        <h2 style="text-align:center">Manage Cat Food</h2><br />
        <div class="col-md-4">
            <form method="post" action="<?php echo $_SERVER["PHP_SELF"]; ?>" id="manage-product">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="id" value="">
                <div style="border:1px solid #333; background-color:#f1f1f1; border-radius:5px; padding:16px; text-align:center">
                    <div class="form-group">
                        <label class="control-label">Product Name</label>
                        <input type="text" class="form-control" name="name" required>
                        <span class="invalid-feedback"><?php echo $name_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Product Description</label>
                        <textarea cols="30" rows="3" class="form-control" name="description" required></textarea>
                        <span class="invalid-feedback"><?php echo $description_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Price</label>
                        <input type="number" class="form-control text-right" name="price" step="any" required>
                        <span class="invalid-feedback"><?php echo $price_err; ?></span>
                    </div>
                    <button type="submit">Save</button>
                    <button type="button" onclick="resetForm()">Cancel</button>
                </div>
            </form>
        </div>
        <h3 style="text-align:center">Cat Food List</h3>
        <!-- Table Panel -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center">Product</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            $query = "SELECT * FROM products ORDER BY id ASC";
                            $result = mysqli_query($con, $query);
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_array($result)) {
                            ?>
                                    <tr>
                                        <td class="text-center"><?php echo $i++ ?></td>
                                        <td>
                                            <p>Name: <b><?php echo $row['name'] ?></b></p>
                                            <p>Description: <b class="truncate"><?php echo $row['description'] ?></b></p>
                                            <p>Price: <b><?php echo "RM " . number_format($row['price'], 2) ?></b></p>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-primary edit_product" type="button" data-id="<?php echo $row['id'] ?>" data-name="<?php echo $row['name'] ?>" data-description="<?php echo $row['description'] ?>" data-price="<?php echo $row['price'] ?>">Edit</button>
                                            <form method="post" style="display: inline-block;">
                                                <input type="hidden" name="delete_id" value="<?php echo $row['id'] ?>" />
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo "<tr><td colspan='3'>No products found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Table Panel -->
    </div>
    <script>
        $(document).ready(function() {
            $('.edit_product').on('click', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var description = $(this).data('description');
                var price = $(this).data('price');

                $('#manage-product [name="action"]').val('edit');
                $('#manage-product [name="id"]').val(id);
                $('#manage-product [name="name"]').val(name);
                $('#manage-product [name="description"]').val(description);
                $('#manage-product [name="price"]').val(price);
            });
        });

        function resetForm() {
            $('#manage-product [name="action"]').val('create');
            $('#manage-product [name="id"]').val('');
            $('#manage-product')[0].reset();
        }
    </script>
</body>

</html>
<?php
// add_product.php
include 'db_connect.php';

if (isset($_POST['add_product'])) {
    $category_id = intval($_POST['category_id']);
    $subcategory_id = intval($_POST['subcategory_id']);
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $sizes = trim($_POST['sizes']);
    $meta = trim($_POST['meta']);

    // Handle image upload
    $imageName = $_FILES['image']['name'];
    $imageTmp = $_FILES['image']['tmp_name'];
    $uploadDir = "uploads/";

    // Create upload dir if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $imagePath = $uploadDir . basename($imageName);

    if (move_uploaded_file($imageTmp, $imagePath)) {
        // Insert into products table
        $stmt = $conn->prepare("INSERT INTO products (category_id, subcategory_id, product_name, price, sizes, meta, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisdsss", $category_id, $subcategory_id, $name, $price, $sizes, $meta, $imagePath);

        if ($stmt->execute()) {
            header("Location: item_management.php?success=1");
            exit();
        } else {
            echo "Database insert error: " . $stmt->error;
        }
    } else {
        echo "Failed to upload image.";
    }
}
?>

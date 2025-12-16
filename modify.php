<?php
header('Content-Type: application/json');

$serverName = "LAPTOP-T6HO0TLL\SQLEXPRESS";
$connectionOptions = [
    "Database" => "DLSU",
    "Uid" => "",
    "PWD" => ""
];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn == false) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

function uploadFile($file)
{
    if (!$file || $file['error'] !== 0) {
        return null;
    }

    $destination = 'productimages/';
    $originalName = basename($file['name']);
    $fileType = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    $allowedTypes = ['jpg', 'jpeg', 'png'];
    if (!in_array($fileType, $allowedTypes)) {
        return null;
    }


    $uniqueName = "product_" . time() . "_" . uniqid() . ".{$fileType}";
    $targetPath = $destination . $uniqueName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [
            'name' => $uniqueName,
            'path' => $targetPath
        ];
    }

    return null;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'update') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $category = $_POST['category'];
        $price = floatval($_POST['price']);

        $sql = "SELECT IMAGE_NAME, IMAGE_PATH FROM CAFEPRODUCTS WHERE PRODUCT_ID = ?";
        $params = [$id];
        $query = sqlsrv_query($conn, $sql, $params);
        $currentImageName = null;
        $currentImagePath = null;

        if ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
            $currentImageName = $row['IMAGE_NAME'];
            $currentImagePath = $row['IMAGE_PATH'];
        }
        $imageName = $currentImageName;
        $imagePath = $currentImagePath;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $uploadResult = uploadFile($_FILES['image']);
            if ($uploadResult) {
                $imageName = $uploadResult['name'];
                $imagePath = $uploadResult['path'];
            }
        }

        $sql = "UPDATE CAFEPRODUCTS 
                SET PRODUCT_NAME = ?, CATEGORY = ?, PRICE = ?, IMAGE_NAME = ?, IMAGE_PATH = ?
                WHERE PRODUCT_ID = ?";

        $params = [$name, $category, $price, $imageName, $imagePath, $id];
        $result = sqlsrv_query($conn, $sql, $params);

        if (!$result) {
            throw new Exception("Update failed: " . print_r(sqlsrv_errors(), true));
        }

        echo json_encode(['status' => 'success', 'message' => 'Product updated successfully']);
        exit();
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';

        if (empty($id)) {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? '';
        }

        if (empty($id)) {
            throw new Exception("Product ID is required for deletion");
        }

        $sql = "SELECT IMAGE_NAME, IMAGE_PATH FROM CAFEPRODUCTS WHERE PRODUCT_ID = ?";
        $params = [$id];
        $query = sqlsrv_query($conn, $sql, $params);

        if ($query && $row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
            $imageName = $row['IMAGE_NAME'];
            $imagePath = $row['IMAGE_PATH'];

            $sql = "DELETE FROM CAFEPRODUCTS WHERE PRODUCT_ID = ?";
            $result = sqlsrv_query($conn, $sql, $params);

            if (!$result) {
                throw new Exception("Delete failed: " . print_r(sqlsrv_errors(), true));
            }

            if ($imagePath && file_exists($imagePath)) {
                unlink($imagePath);
            }

            echo json_encode(['status' => 'success', 'message' => 'Product deleted successfully']);
        } else {
            throw new Exception("Product not found");
        }
        exit();
    }

    if ($action === 'add') {
        $name = $_POST['name'];
        $category = $_POST['category'];
        $price = floatval($_POST['price']);

        $imageName = null;
        $imagePath = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $uploadResult = uploadFile($_FILES['image']);
            if ($uploadResult) {
                $imageName = $uploadResult['name'];
                $imagePath = $uploadResult['path'];
            }
        }

        $sql = "INSERT INTO CAFEPRODUCTS (PRODUCT_NAME, CATEGORY, PRICE, IMAGE_NAME, IMAGE_PATH) 
                VALUES (?, ?, ?, ?, ?)";

        $params = [$name, $category, $price, $imageName, $imagePath];
        $result = sqlsrv_query($conn, $sql, $params);

        if (!$result) {
            throw new Exception("Add failed: " . print_r(sqlsrv_errors(), true));
        }

        echo json_encode(['status' => 'success', 'message' => 'Product added successfully']);
        exit();
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
} catch (Exception $e) {
    error_log("Modify.php Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
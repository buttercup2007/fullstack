<?php
session_start();

$host = "mysql"; 
$user = "root";
$pass = "password";
$dbname = "fullstack";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['id'])) {
    die("Product ID not provided.");
}

$id = intval($_GET['id']);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_type = $_POST["product_type"];
    $fabriek = $_POST["fabriek"];
    $aantal = $_POST["aantal"];
    $minimum_aantal = $_POST["minimum_aantal"];
    $inkoopprijs = $_POST["inkoopprijs"];
    $verkoopsprijs = $_POST["verkoopsprijs"];
    $locatie = $_POST["locatie"];

    $update = $conn->prepare("
        UPDATE product 
        SET product_type=?, fabriek=?, aantal=?, minimum_aantal=?, inkoopprijs=?, verkoopsprijs=?, locatie=? 
        WHERE id=?
    ");

    $update->bind_param(
        "ssiiidsi",
        $product_type, $fabriek, $aantal, $minimum_aantal,
        $inkoopprijs, $verkoopsprijs, $locatie, $id
    );

    $update->execute();
    $update->close();

    header("Location: voorraad.php?updated=1");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM product WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    die("Product not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
</head>
<body>

<h2>Edit Product</h2>

<form method="POST">

    <label>Product Type:</label><br>
    <input type="text" name="product_type" value="<?= htmlspecialchars($product['product_type']) ?>" required><br><br>

    <label>Fabriek:</label><br>
    <input type="text" name="fabriek" value="<?= htmlspecialchars($product['fabriek']) ?>" required><br><br>

    <label>Aantal:</label><br>
    <input type="number" name="aantal" value="<?= htmlspecialchars($product['aantal']) ?>" required><br><br>

    <label>Minimum Aantal:</label><br>
    <input type="number" name="minimum_aantal" value="<?= htmlspecialchars($product['minimum_aantal']) ?>" required><br><br>

    <label>Inkoopprijs (€):</label><br>
    <input type="number" step="0.01" name="inkoopprijs" value="<?= htmlspecialchars($product['inkoopprijs']) ?>" required><br><br>

    <label>Verkoopsprijs (€):</label><br>
    <input type="number" step="0.01" name="verkoopsprijs" value="<?= htmlspecialchars($product['verkoopsprijs']) ?>" required><br><br>

    <label>Locatie:</label><br>
    <input type="text" name="locatie" value="<?= htmlspecialchars($product['locatie']) ?>" required><br><br>

    <button type="submit">Update</button>
    <a href="voorraad.php">Cancel</a>

</form>

</body>
</html>

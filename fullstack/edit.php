<?php
session_start();

$conn = new mysqli("mysql", "root", "password", "fullstack");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['id'])) {
    die("Product ID not provided.");
}

$id = (int)$_GET['id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_type = $_POST["product_type"];
    $fabriek = $_POST["fabriek"];
    $minimum_aantal = (int)$_POST["minimum_aantal"];
    $inkoopprijs = (float)$_POST["inkoopprijs"];
    $verkoopsprijs = (float)$_POST["verkoopsprijs"];

    $update = $conn->prepare("
        UPDATE product
        SET product_type=?, fabriek=?, minimum_aantal=?, inkoopprijs=?, verkoopsprijs=?
        WHERE product_id=?
    ");

    $update->bind_param(
        "ssiddi",
        $product_type, $fabriek, $minimum_aantal,
        $inkoopprijs, $verkoopsprijs, $id
    );

    $update->execute();
    $update->close();

    header("Location: voorraad.php?updated=1");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM product WHERE product_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    die("Product not found.");
}
?>

<form method="POST">

<label>Product Type:</label><br>
<input type="text" name="product_type" value="<?= htmlspecialchars($product['product_type']) ?>" required><br><br>

<label>Fabriek:</label><br>
<input type="text" name="fabriek" value="<?= htmlspecialchars($product['fabriek']) ?>" required><br><br>

<label>Minimum Aantal:</label><br>
<input type="number" name="minimum_aantal" value="<?= $product['minimum_aantal'] ?>" required><br><br>

<label>Inkoopprijs (€):</label><br>
<input type="number" step="0.01" name="inkoopprijs" value="<?= $product['inkoopprijs'] ?>" required><br><br>

<label>Verkoopsprijs (€):</label><br>
<input type="number" step="0.01" name="verkoopsprijs" value="<?= $product['verkoopsprijs'] ?>" required><br><br>

<button type="submit">Update</button>
<a href="voorraad.php">Cancel</a>

</form>

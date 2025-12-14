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

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM product WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $product = $result->fetch_assoc();

        $insert = $conn->prepare("
            INSERT INTO koop 
            (product_type, fabriek, aantal, inkoopprijs, verkoopsprijs, locatie)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $insert->bind_param(
            "ssidds",
            $product['product_type'],
            $product['fabriek'],
            $product['aantal'],
            $product['inkoopprijs'],
            $product['verkoopsprijs'],
            $product['locatie']
        );

        $insert->execute();
        $insert->close();

        $delete = $conn->prepare("DELETE FROM product WHERE id = ?");
        $delete->bind_param("i", $id);
        $delete->execute();
        $delete->close();
    }

    $stmt->close();

    header("Location: koop.php");
    exit;
}

$result = $conn->query("SELECT * FROM koop ORDER BY bought_at DESC");
$gekochteProducten = [];
while ($row = $result->fetch_assoc()) {
    $gekochteProducten[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Koop</title>
    <link rel="stylesheet" href="voorraad.css">
</head>
<body>

<div class="top-bar">
    <a href="voorraad.php">Producten</a>
    <a href="voorraadLocatie.php">Locaties</a>
    <a href="koop.php">Koop</a>
</div>

<h1>Gekochte Producten</h1>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Product Type</th>
            <th>Fabriek</th>
            <th>Aantal</th>
            <th>Inkoopprijs</th>
            <th>Verkoopsprijs</th>
            <th>Locatie</th>
            <th>Gekocht op</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($gekochteProducten)): ?>
            <?php foreach ($gekochteProducten as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['product_type']) ?></td>
                    <td><?= htmlspecialchars($p['fabriek']) ?></td>
                    <td><?= $p['aantal'] ?></td>
                    <td>€<?= number_format($p['inkoopprijs'], 2) ?></td>
                    <td>€<?= number_format($p['verkoopsprijs'], 2) ?></td>
                    <td><?= htmlspecialchars($p['locatie']) ?></td>
                    <td><?= $p['bought_at'] ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8">Nog geen producten gekocht.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>

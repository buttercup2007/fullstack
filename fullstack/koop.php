<?php
session_start();

$conn = new mysqli("mysql", "root", "password", "fullstack");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['buy_quantity']) ? (int)$_POST['buy_quantity'] : 1;

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("
            SELECT 
                p.product_type,
                p.fabriek,
                p.inkoopprijs,
                p.verkoopsprijs,
                v.aantal,
                v.voorraad_id,
                l.naam AS locatie
            FROM product p
            JOIN voorraad v ON p.product_id = v.product_id
            JOIN locatie l ON v.locatie_id = l.locatie_id
            WHERE p.product_id = ? AND v.aantal >= ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $product_id, $quantity);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) throw new Exception("Not enough stock");

        $insert = $conn->prepare("
            INSERT INTO koop (product_type, fabriek, aantal, inkoopprijs, verkoopsprijs, locatie)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insert->bind_param(
            "ssidds",
            $product['product_type'],
            $product['fabriek'],
            $quantity,
            $product['inkoopprijs'],
            $product['verkoopsprijs'],
            $product['locatie']
        );
        $insert->execute();
        $insert->close();

        $update = $conn->prepare("UPDATE voorraad SET aantal = aantal - ? WHERE voorraad_id = ?");
        $update->bind_param("ii", $quantity, $product['voorraad_id']);
        $update->execute();
        $update->close();

        $conn->commit();
        header("Location: koop.php?success=1");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: voorraad.php?error=nostock");
        exit;
    }
}

$result = $conn->query("SELECT * FROM koop ORDER BY bought_at DESC");
$koop = [];
while ($row = $result->fetch_assoc()) $koop[] = $row;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Koop Page</title>
<link rel="stylesheet" href="voorraad.css">
<style>
table { border-collapse: collapse; width: 100%; }
th, td { border:1px solid #ccc; padding:8px; text-align:center; }
.top-bar { margin-bottom:20px; }
</style>
</head>
<body>

<div class="top-bar">
    <a href="voorraad.php">Producten</a>
    <a href="koop.php">Koop</a>
</div>

<h1>Purchased Products</h1>

<?php if (!empty($koop)): ?>
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
<th>Bought At</th>
</tr>
</thead>
<tbody>
<?php foreach ($koop as $k): ?>
<tr>
<td><?= $k['id'] ?></td>
<td><?= htmlspecialchars($k['product_type']) ?></td>
<td><?= htmlspecialchars($k['fabriek']) ?></td>
<td><?= (int)$k['aantal'] ?></td>
<td>€<?= number_format($k['inkoopprijs'],2) ?></td>
<td>€<?= number_format($k['verkoopsprijs'],2) ?></td>
<td><?= htmlspecialchars($k['locatie']) ?></td>
<td><?= $k['bought_at'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p>No products bought yet.</p>
<?php endif; ?>

</body>
</html>

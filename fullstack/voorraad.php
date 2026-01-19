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

$response = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $conn->query("DELETE FROM voorraad WHERE product_id = $delete_id");
    $conn->query("DELETE FROM product WHERE product_id = $delete_id");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {

    $product_type = $_POST['product_type'] ?? '';
    $fabriek = $_POST['fabriek'] ?? '';
    $locatie = $_POST['locatie'] ?? '';
    $aantal = (int)($_POST['aantal'] ?? 1);
    $minimum_aantal = (int)($_POST['minimum_aantal'] ?? 0);
    $inkoopprijs = (float)($_POST['inkoopprijs'] ?? 0);
    $verkoopsprijs = (float)($_POST['verkoopsprijs'] ?? 0);

    if (!empty($product_type) && !empty($fabriek) && !empty($locatie)) {

        $check_product = $conn->prepare("SELECT product_id FROM product WHERE product_type = ? AND fabriek = ?");
        $check_product->bind_param("ss", $product_type, $fabriek);
        $check_product->execute();
        $check_product->bind_result($product_id);
        $found_product = $check_product->fetch();
        $check_product->close();

        if (!$found_product) {
        
            $stmt = $conn->prepare("
                INSERT INTO product (product_type, fabriek, minimum_aantal, inkoopprijs, verkoopsprijs)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssidd", $product_type, $fabriek, $minimum_aantal, $inkoopprijs, $verkoopsprijs);
            $stmt->execute();
            $product_id = $conn->insert_id;
            $stmt->close();
        }

        $loc_stmt = $conn->prepare("SELECT locatie_id FROM locatie WHERE naam = ?");
        $loc_stmt->bind_param("s", $locatie);
        $loc_stmt->execute();
        $loc_stmt->bind_result($locatie_id);
        $loc_stmt->fetch();
        $loc_stmt->close();

        if (!$locatie_id) {
            $insert_loc = $conn->prepare("INSERT INTO locatie (naam) VALUES (?)");
            $insert_loc->bind_param("s", $locatie);
            $insert_loc->execute();
            $locatie_id = $conn->insert_id;
            $insert_loc->close();
        }

        $check_voorraad = $conn->prepare("SELECT voorraad_id, aantal FROM voorraad WHERE product_id = ? AND locatie_id = ?");
        $check_voorraad->bind_param("ii", $product_id, $locatie_id);
        $check_voorraad->execute();
        $check_voorraad->bind_result($voorraad_id, $existing_aantal);
        $found_voorraad = $check_voorraad->fetch();
        $check_voorraad->close();

        if ($found_voorraad) {
            $update_stmt = $conn->prepare("UPDATE voorraad SET aantal = aantal + ? WHERE voorraad_id = ?");
            $update_stmt->bind_param("ii", $aantal, $voorraad_id);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            $voorraad_stmt = $conn->prepare("INSERT INTO voorraad (product_id, locatie_id, aantal) VALUES (?, ?, ?)");
            $voorraad_stmt->bind_param("iii", $product_id, $locatie_id, $aantal);
            $voorraad_stmt->execute();
            $voorraad_stmt->close();
        }

        header("Location: voorraad.php");
        exit;
    } else {
        $response = ["success" => false, "message" => "Please fill all fields correctly."];
    }
}

$filter_name = $_GET['filter_name'] ?? '';
$filter_fabriek = $_GET['filter_fabriek'] ?? '';
$filter_locatie = $_GET['filter_locatie'] ?? '';

$sql = "
SELECT 
    p.product_id, 
    p.product_type, 
    p.fabriek, 
    p.minimum_aantal, 
    p.inkoopprijs, 
    p.verkoopsprijs, 
    COALESCE(SUM(v.aantal),0) AS totaal_aantal,
    GROUP_CONCAT(DISTINCT l.naam SEPARATOR ', ') AS locaties
FROM product p
LEFT JOIN voorraad v ON p.product_id = v.product_id
LEFT JOIN locatie l ON v.locatie_id = l.locatie_id
WHERE 1=1
";

if (!empty($filter_name)) $sql .= " AND p.product_type LIKE '%" . $conn->real_escape_string($filter_name) . "%'";
if (!empty($filter_fabriek)) $sql .= " AND p.fabriek LIKE '%" . $conn->real_escape_string($filter_fabriek) . "%'";
if (!empty($filter_locatie)) $sql .= " AND l.naam LIKE '%" . $conn->real_escape_string($filter_locatie) . "%'";

$sql .= " GROUP BY p.product_id ORDER BY p.product_id DESC";

$result = $conn->query($sql);
$products = [];
while ($row = $result->fetch_assoc()) $products[] = $row;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Product Management</title>
<link rel="stylesheet" href="voorraad.css">
<style>
.product-item { display: inline-block; padding:10px; border:1px solid #ccc; border-radius:6px; position:relative; min-width:120px; text-align:center; margin:5px;}
.stock-badge { position:absolute; top:-5px; left:-5px; background:red; color:white; font-size:12px; font-weight:bold; padding:3px 6px; border-radius:50%;}
.low-stock { background-color:#ffe0e0; }
label { font-weight:bold; display:block; margin-bottom:2px; }
input, select, button { padding:5px; margin-right:10px; }
form.add-product { display:flex; flex-wrap:wrap; align-items:flex-end; gap:10px; }
</style>
</head>
<body>

<div class="top-bar">
    <a href="voorraad.php">Producten</a>
    <a href="voorraadLocatie.php">Locaties</a>
    <a href="koop.php">Koop</a>
</div>

<h1>Product Management System</h1>

<h2>Add New Product</h2>
<?php if ($response): ?>
<p class="<?= $response['success'] ? 'success' : 'error' ?>"><?= htmlspecialchars($response['message']) ?></p>
<?php endif; ?>

<form method="POST" class="add-product">
    <div>
        <label>Product Type:</label>
        <input type="text" name="product_type" placeholder="4-in-1 Schuurmachine" required />
    </div>
    <div>
        <label>Fabriek:</label>
        <input type="text" name="fabriek" placeholder="Black & Decker" required />
    </div>
    <div>
        <label>Aantal:</label>
        <input type="number" name="aantal" placeholder="1" value="1" min="1" required />
    </div>
    <div>
        <label>Minimum Aantal:</label>
        <input type="number" name="minimum_aantal" placeholder="0" value="0" min="0" required />
    </div>
    <div>
        <label>Inkoopprijs (€):</label>
        <input type="number" step="0.01" name="inkoopprijs" placeholder="55.00" value="0" required />
    </div>
    <div>
        <label>Verkoopsprijs (€):</label>
        <input type="number" step="0.01" name="verkoopsprijs" placeholder="67.95" value="0" required />
    </div>
    <div>
        <label>Locatie:</label>
        <select name="locatie" required>
            <option value="">Select locatie</option>
            <option value="Rotterdam">Rotterdam</option>
            <option value="Eindhoven">Eindhoven</option>
            <option value="Almere">Almere</option>
        </select>
    </div>
    <div>
        <button type="submit" name="add_product">Add Product</button>
    </div>
</form>

<h2>Product List</h2>
<table>
<thead>
<tr>
<th>ID</th>
<th>Product Type</th>
<th>Fabriek</th>
<th>Aantal</th>
<th>Minimum</th>
<th>Inkoopprijs</th>
<th>Verkoopsprijs</th>
<th>Locatie(s)</th>
<th>Delete</th>
<th>Edit</th>
<th>Buy</th>
</tr>
</thead>
<tbody>
<?php if (!empty($products)): ?>
<?php foreach ($products as $p): ?>
<tr>
<td><?= $p['product_id'] ?></td>
<td>
<div class="product-item">
<?= htmlspecialchars($p['product_type']) ?>
<?php if ((int)$p['totaal_aantal'] > 0): ?>
<span class="stock-badge"><?= (int)$p['totaal_aantal'] ?></span>
<?php endif; ?>
</div>
</td>
<td><?= htmlspecialchars($p['fabriek']) ?></td>
<td<?= ((int)$p['totaal_aantal'] <= (int)$p['minimum_aantal']) ? ' class="low-stock"' : '' ?>>
<?= (int)$p['totaal_aantal'] ?></td>
<td><?= (int)$p['minimum_aantal'] ?></td>
<td>€<?= number_format($p['inkoopprijs'],2) ?></td>
<td>€<?= number_format($p['verkoopsprijs'],2) ?></td>
<td><?= htmlspecialchars($p['locaties'] ?? '-') ?></td>
<td>
<form method="POST" style="margin:0;">
<button type="submit" name="delete_id" value="<?= $p['product_id'] ?>">Delete</button>
</form>
</td>
<td>
<form method="GET" action="edit.php" style="margin:0;">
<button type="submit" name="id" value="<?= $p['product_id'] ?>">Edit</button>
</form>
</td>
<td>
<form method="GET" action="koop.php" style="margin:0;">
<button type="submit" name="id" value="<?= $p['product_id'] ?>">Buy</button>
</form>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="11">No products found.</td></tr>
<?php endif; ?>
</tbody>
</table>

</body>
</html>



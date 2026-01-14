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
    $delete_id = $_POST['delete_id'];

    $delStmt = $conn->prepare("DELETE FROM product WHERE id = ?");
    $delStmt->bind_param("i", $delete_id);
    $delStmt->execute();
    $delStmt->close();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {

    $product_type = $_POST['product_type'] ?? '';
    $fabriek = $_POST['fabriek'] ?? '';
    $aantal = $_POST['aantal'] ?? '';
    $minimum_aantal = $_POST['minimum_aantal'] ?? 0;
    $inkoopprijs = $_POST['inkoopprijs'] ?? '';
    $verkoopsprijs = $_POST['verkoopsprijs'] ?? '';
    $locatie = $_POST['locatie'] ?? '';

    if (!empty($product_type) && !empty($fabriek) && is_numeric($aantal) &&
        is_numeric($minimum_aantal) && is_numeric($inkoopprijs) && 
        is_numeric($verkoopsprijs) && !empty($locatie)) {

        $stmt = $conn->prepare("
            INSERT INTO product (product_type, fabriek, aantal, inkoopprijs, verkoopsprijs, locatie, minimum_aantal, `delete`, `edit`, `buy`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, 0)
        ");

        $stmt->bind_param(
            "ssiddsi",
            $product_type,  
            $fabriek,          
            $aantal,           
            $inkoopprijs,     
            $verkoopsprijs,    
            $locatie,         
            $minimum_aantal    
        );
        

        if ($stmt->execute()) {
            header("Location: voorraad.php");
            exit;
        } else {
            $response = ["success" => false, "message" => "Error: " . $stmt->error];
        }

        $stmt->close();
    } else {
        $response = ["success" => false, "message" => "Please fill all fields correctly."];
    }
}

$filter_name = $_GET['filter_name'] ?? '';
$filter_fabriek = $_GET['filter_fabriek'] ?? '';
$filter_locatie = $_GET['filter_locatie'] ?? '';

$sql = "SELECT * FROM product WHERE 1=1";

if (!empty($filter_name)) {
    $name = "%" . $conn->real_escape_string($filter_name) . "%";
    $sql .= " AND product_type LIKE '$name'";
}
if (!empty($filter_fabriek)) {
    $fab = "%" . $conn->real_escape_string($filter_fabriek) . "%";
    $sql .= " AND fabriek LIKE '$fab'";
}
if (!empty($filter_locatie)) {
    $loc = "%" . $conn->real_escape_string($filter_locatie) . "%";
    $sql .= " AND locatie LIKE '$loc'";
}

$sql .= " ORDER BY id DESC";

$result = $conn->query($sql);
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Management</title>
    <link rel="stylesheet" href="voorraad.css">
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
    <p class="<?= $response['success'] ? 'success' : 'error' ?>">
        <?= htmlspecialchars($response['message']) ?>
    </p>
<?php endif; ?>

<form method="POST">
    <input type="text" name="product_type" placeholder="Product Type" required />
    <input type="text" name="fabriek" placeholder="Fabriek" required />
    <input type="number" name="aantal" placeholder="Aantal" required />
    <input type="number" name="minimum_aantal" placeholder="Minimum Aantal" required />
    <input type="number" step="0.01" name="inkoopprijs" placeholder="Inkoopprijs (€)" required />
    <input type="number" step="0.01" name="verkoopsprijs" placeholder="Verkoopsprijs (€)" required />
    
    <select name="locatie" required>
    <option value="">Select locatie</option>
    <option value="Rotterdam">Rotterdam</option>
    <option value="Eindhoven">Eindhoven</option>
    <option value="Almere">Almere</option>
    </select>


    <button type="submit" name="add_product" value="1">Add Product</button>
</form>


<h2>Filters Products</h2>
<form method="GET">
    <input type="text" name="filter_name" placeholder="Search by Product Type" value="<?= htmlspecialchars($filter_name) ?>" />
    <input type="text" name="filter_fabriek" placeholder="Search by Fabriek" value="<?= htmlspecialchars($filter_fabriek) ?>" />
    <input type="text" name="filter_locatie" placeholder="Search by Locatie" value="<?= htmlspecialchars($filter_locatie) ?>" />
    <button type="submit">Apply Filters</button>
    <a href="<?= $_SERVER['PHP_SELF'] ?>">Reset</a>
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
            <th>Locatie</th>
            <th>Delete</th>
            <th>Edit</th>
            <th>Buy</th>
        </tr>
    </thead>

    <tbody>
    <?php if (!empty($products)): ?>
    <?php foreach ($products as $p): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><?= htmlspecialchars($p['product_type']) ?></td>
            <td><?= htmlspecialchars($p['fabriek']) ?></td>

            <?php
            $antal = (int)$p['aantal'];
            $min = (int)$p['minimum_aantal'];
            $lowStock = ($antal <= $min);
            ?>

            <td<?= $lowStock ? ' class="low-stock"' : '' ?>>
                <?= htmlspecialchars($antal) ?>
                <?php if ($lowStock): ?> ! <?php endif; ?>
            </td>

            <td><?= $p['minimum_aantal'] ?></td>
            <td>€<?= number_format($p['inkoopprijs'], 2) ?></td>
            <td>€<?= number_format($p['verkoopsprijs'], 2) ?></td>
            <td><?= htmlspecialchars($p['locatie']) ?></td>

        <div style="display: inline-flex; gap: 5px; align-items: center;"></div>
        <td>
        <form method="POST" style="margin:0;">
        <button type="submit" name="delete_id" value="<?= $p['id'] ?>" class="delete-btn">Delete</button>
        </form>
        </td>

        <td>
        <form method="GET" action="edit.php" style="margin:0;">
        <button type="submit" name="id" value="<?= $p['id'] ?>" class="edit-btn">Edit</button>
        </form>
        </td>
        
        <td>
        <form method="GET" action="koop.php" style="margin:0;">
        <button type="submit" name="id" value="<?= $p['id'] ?>" class="koop-btn">Buy</button>
        
        </form>
        </td>

        </div>
        </td>

        

    <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="10">No products found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</body>
</html>


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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_location'])) {

    $location_name = $_POST['location_name'] ?? '';

    if (!empty($location_name)) {

        $stmt = $conn->prepare("INSERT INTO locatie (naam) VALUES (?)");
        $stmt->bind_param("s", $location_name);

        if ($stmt->execute()) {
           
            header("Location: voorraadLocatie.php");
            exit;
        } else {
            $response = ["success" => false, "message" => "Error: " . $stmt->error];
        }

        $stmt->close();

    } else {
        $response = ["success" => false, "message" => "Please provide a location name."];
    }
}

$filter_location = $_GET['filter_location'] ?? '';

$sql = "SELECT * FROM locatie WHERE 1=1";

if (!empty($filter_location)) {
    $loc = "%" . $conn->real_escape_string($filter_location) . "%";
    $sql .= " AND naam LIKE '$loc'";
}

$sql .= " ORDER BY id DESC";

$result = $conn->query($sql);
$locaties = [];

while ($row = $result->fetch_assoc()) {
    $locaties[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Location Management</title>
    <link rel="stylesheet" href="voorraad.css">
</head>
<body>

<div style="background:#fff; padding:15px; margin-bottom:20px; border-radius:6px; box-shadow:0 0 4px #ccc;">
    <a href="voorraad.php" style="margin-right:20px;">Producten</a>
    <a href="voorraadLocatie.php">Locaties</a>
</div>

<h1>Location Management System</h1>

<h2>Add New Location</h2>

<?php if ($response): ?>
    <p class="<?= $response['success'] ? 'success' : 'error' ?>">
        <?= htmlspecialchars($response['message']) ?>
    </p>
<?php endif; ?>

<form method="POST">
    <input type="text" name="location_name" placeholder="Location Name (e.g., Almere)" required />
    <button type="submit" name="add_location">Add Location</button>
</form>

<h2>Filter Locations</h2>
<form method="GET">
    <input type="text" name="filter_location" placeholder="Search by Location Name" 
           value="<?= htmlspecialchars($filter_location) ?>" />
    <button type="submit">Apply Filters</button>
    <a href="<?= $_SERVER['PHP_SELF'] ?>">Reset</a>
</form>

<h2>Location List</h2>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Location Name</th>
            <th>Created At</th>
        </tr>
    </thead>

    <tbody>
        <?php if (!empty($locaties)): ?>
            <?php foreach ($locaties as $l): ?>
                <tr>
                    <td><?= $l['id'] ?></td>
                    <td><?= htmlspecialchars($l['naam']) ?></td>
                    <td><?= $l['created_at'] ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="3">No locations found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>


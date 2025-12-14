<?php
session_start();

require_once "partials/dbconnection.php";
?>

<!DOCTYPE html>
<html>
<head>
    <title>ToolsForEver</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Dashboard</h1>

<div class="links">
    <a href="dashboard.php">Dashboard</a>
    <a href="nieuwe_product.php">Nieuwe product</a>
    <a href="bestellingen.php">Bestellingen</a>
</div>

<div class="stuff">
    <p>Productnaam</p>
    <p>Categorie</p>
    <p>Aantal op voorraad</p>
    <p>Acties</p>
</div>

</body>
</html>

<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gestioncouruniversitaire";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id_filiere, nom_filiere FROM filiere";
$result = $conn->query($sql);

if (!$result) {
    die("Erreur: " . $conn->error);
}

echo "<h2>Filières trouvées:</h2>";
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id_filiere'] . " - Nom: " . $row['nom_filiere'] . "<br>";
}

$conn->close();
?>
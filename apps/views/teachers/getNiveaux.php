<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gestioncouruniversitaire";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id_personne = $_SESSION['user_id'];
$filiere = $_GET['filiere'];

$sql = "
    SELECT DISTINCT e.niveau_filiere
    FROM etudiant e
    JOIN evaluer ev ON e.id_etudiant = ev.id_etudiant
    JOIN enseigner en ON ev.id_matiere = en.id_matiere
    WHERE en.id_enseignant = ? AND e.nom_filiere = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $id_personne, $filiere);
$stmt->execute();
$result = $stmt->get_result();

$niveaux = [];
while ($row = $result->fetch_assoc()) {
    $niveaux[] = $row['niveau'];
}

echo json_encode($niveaux);
?>
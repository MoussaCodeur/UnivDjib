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

$conn->set_charset("utf8");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$semestre = $_POST['semestre_et'];
$id_enseignant = $_POST['id_enseignant'];

$sql_matieres = "SELECT DISTINCT m.nom_matiere, m.id_matiere 
                 FROM Matiere m 
                 JOIN Enseigner e ON m.id_matiere = e.id_matiere 
                 WHERE e.type_semestre = ? 
                 AND e.id_enseignant = ?";
$stmt = $conn->prepare($sql_matieres);
$stmt->bind_param("si", $semestre, $id_enseignant);
$stmt->execute();
$result_matieres = $stmt->get_result();

$options = "<option value='' disabled selected>Choisir une matière</option>";
while ($row = $result_matieres->fetch_assoc()) {
    $options .= "<option value='" . htmlspecialchars($row['id_matiere']) . "'>" . htmlspecialchars($row['nom_matiere']) . "</option>";
}

echo $options;

$stmt->close();
$conn->close();
?>
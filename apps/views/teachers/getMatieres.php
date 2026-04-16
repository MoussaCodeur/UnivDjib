<?php
session_start();

//Connexion la base de donnees
require_once '../../../config/db.php';

$id_personne = $_SESSION['user_id'];
$semestre = $_GET['semestre'];

$sql = "
    SELECT m.nom_matiere, m.id_matiere
    FROM matiere m
    JOIN enseigner en ON m.id_matiere = en.id_matiere
    WHERE en.id_enseignant = ? AND en.type_semestre = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_personne, $semestre);
$stmt->execute();
$result = $stmt->get_result();

$matieres = [];
while ($row = $result->fetch_assoc()) {
    $matieres[] = $row;
}

echo json_encode($matieres);
?>
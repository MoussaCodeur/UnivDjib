<?php

// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';

$semestre = $_POST['semestre_et'];
$id_enseignant = $_POST['id_enseignant'];

$sql_matieres = "SELECT DISTINCT m.nom_matiere, m.id_matiere 
                 FROM matiere m 
                 JOIN enseigner e ON m.id_matiere = e.id_matiere 
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
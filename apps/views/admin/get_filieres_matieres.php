<?php

// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';


if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'get_filieres':
            $sql = "SELECT id_filiere, nom_filiere FROM filiere group by id_filiere";
            $result = $conn->query($sql);
            $filieres = [];
            while ($row = $result->fetch_assoc()) {
                $filieres[] = $row;
            }
            echo json_encode($filieres);
            break;

        case 'get_matieres':
            if (isset($_GET['filiere_id'], $_GET['niveau'], $_GET['semestre'])) {
                $sql = "SELECT m.id_matiere, m.nom_matiere
                        FROM matiere m
                        WHERE m.id_filiere = ? AND m.niveau_filiere = ? AND m.type_simestre = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $_GET['filiere_id'], $_GET['niveau'], $_GET['semestre']);
                $stmt->execute();
                $result = $stmt->get_result();
                $matieres = [];
                while ($row = $result->fetch_assoc()) {
                    $matieres[] = $row;
                }
                echo json_encode($matieres);
            }
            break;

        case 'get_assistants':
            $sql = "SELECT a.id_assistant as id_utilisateur, CONCAT(p.nom, ' ', p.prenom) AS nom_complet 
                    FROM assistant a 
                    INNER JOIN personne p ON p.id_personne = a.id_assistant 
                    WHERE p.role = 'assistant'";
            $result = $conn->query($sql);
            $assistants = [];
            while ($row = $result->fetch_assoc()) {
                $assistants[] = $row;
            }
            echo json_encode($assistants);
            break;

        case 'get_filiere':
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $sql = "SELECT f.id_filiere, f.nom_filiere, f.responsable_id
                        FROM filiere f
                        WHERE f.id_filiere = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    $row['role'] = 'filieres'; // Ajouter le rôle explicitement
                    echo json_encode($row);
                } else {
                    echo json_encode(['error' => 'Filière non trouvée']);
                }
            }
            break;
        
        case 'get_matiere':
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $sql = "SELECT m.id_matiere, m.nom_matiere, m.coeff, m.id_filiere, 
                        m.niveau_filiere, m.type_simestre
                        FROM matiere m
                        WHERE m.id_matiere = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    $row['role'] = 'matieres'; // Ajouter le rôle explicitement
                    echo json_encode($row);
                } else {
                    echo json_encode(['error' => 'Matière non trouvée']);
                }
            }
            break;
            
    }
}
?>
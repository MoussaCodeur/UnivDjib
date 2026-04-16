<?php

// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';

// Vérifier si une action est définie
if (isset($_GET['action']) && $_GET['action'] == 'get' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $userData = [];
    
    // Récupérer les informations de base de l'utilisateur
    $sql = "SELECT p.*, DATE_FORMAT(p.dateNaissance, '%Y-%m-%d') as dateNaissance FROM personne p WHERE p.id_personne = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $userData = $row;
        $role = $userData['role'];
        
        // Récupérer les informations spécifiques selon le rôle
        switch ($role) {
            case 'etudiant':
                $sql = "SELECT e.*, f.nom_filiere as filiere
                        FROM etudiant e
                        LEFT JOIN filiere f ON e.id_filiere = f.id_filiere
                        WHERE e.id_etudiant = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($etudiantData = $result->fetch_assoc()) {
                    $userData = array_merge($userData, $etudiantData);
                }
                break;
                
            case 'enseignant':
                $sql = "SELECT p.*, 
                        e.specialite, e.telephone, e.statut, 
                        en.id_matiere, en.id_filiere, en.niveau_filiere, 
                        en.nb_heure, en.type_semestre as type_simestre,
                        f.nom_filiere, m.nom_matiere
                    FROM personne p
                    LEFT JOIN enseignant e ON p.id_personne = e.id_enseignant
                    LEFT JOIN enseigner en ON p.id_personne = en.id_enseignant
                    LEFT JOIN filiere f ON en.id_filiere = f.id_filiere
                    LEFT JOIN matiere m ON en.id_matiere = m.id_matiere
                    WHERE p.id_personne = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($enseignantData = $result->fetch_assoc()) {
                    $userData = array_merge($userData, $enseignantData);
                }
                break;
                
            case 'assistant':
                $sql = "SELECT a.* FROM assistant a WHERE a.id_assistant = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($assistantData = $result->fetch_assoc()) {
                    $userData = array_merge($userData, $assistantData);
                }
                break;
                
            case 'president':
                $sql = "SELECT p.* FROM president_jury p WHERE p.id_president = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($presidentData = $result->fetch_assoc()) {
                    $userData = array_merge($userData, $presidentData);
                }
                break;
                
            case 'directeur':
                $sql = "SELECT d.* FROM directeur_etude d WHERE d.id_directeur = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($directeurData = $result->fetch_assoc()) {
                    $userData = array_merge($userData, $directeurData);
                }
                break;
        }
    }
    
    // Retourner les données au format JSON
    header('Content-Type: application/json');
    echo json_encode($userData);
}

// Fermer la connexion
$conn->close();
?>
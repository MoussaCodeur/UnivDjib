<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';

// Initialisation des variables de recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$activeRole = isset($_GET['role']) ? $_GET['role'] : 'all';


// Récupérer l'ID de l'utilisateur depuis la session
$userID = $_SESSION['user_id'];

// Requête pour obtenir les informations de l'utilisateur// Modifier la requête SQL
$sql = "SELECT 
p.*,
e.id_filiere, e.niveau_filiere AS etudiant_niveau,
en.telephone,
a.departement AS assistant_departement, a.niveau AS assistant_niveau
FROM personne p
LEFT JOIN etudiant e ON p.id_personne = e.id_etudiant
LEFT JOIN enseignant en ON p.id_personne = en.id_enseignant
LEFT JOIN assistant a ON p.id_personne = a.id_assistant
WHERE p.id_personne = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erreur dans la requête SQL : " . $conn->error);
}

// Liage des paramètres
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $prenom = $row['prenom'];
    $nom = $row['nom'];
    $email = $row['email'];
    $role = $row['role'];
} else {
    die("Utilisateur non trouvé.");
}

$stmt->close();

// Traitement des actions CRUD si une requête POST est soumise
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Ajouter un utilisateur
    if ($action == 'create') {
        try {
            $role = $_POST['role'];
            
            if ($role == 'filieres' || $role == 'matieres') {
                // Traitement spécial pour filières et matières
                switch ($role) {
                    case 'filieres':
                        $nom_filiere = trim($_POST['filiere']);
                        $responsable_id = trim($_POST['filiere_res']);
                        
                        // Validation
                        if (empty($nom_filiere)) {
                            throw new Exception("Le nom de la filière est requis");
                        }
                        
                        // Vérifier si le responsable existe si fourni
                        if ($responsable_id) {
                            $check_responsable = "SELECT a.id_assistant 
                                                 FROM assistant a 
                                                 INNER JOIN personne p ON p.id_personne = a.id_assistant
                                                 WHERE a.id_assistant = ? AND p.role = 'assistant'";
                            $stmt_check = $conn->prepare($check_responsable);
                            $stmt_check->bind_param("i", $responsable_id);
                            $stmt_check->execute();
                            
                            if ($stmt_check->get_result()->num_rows == 0) {
                                throw new Exception("Le responsable sélectionné n'existe pas ou n'est pas un assistant");
                            }
                        }
                        
                        // Si aucun responsable n'est sélectionné, on met NULL
                        $responsable_value = empty($responsable_id) ? NULL : $responsable_id;
                        
                        $sql = "INSERT INTO filiere (nom_filiere, responsable_id) VALUES (?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $nom_filiere, $responsable_value);
                        
                        if ($stmt->execute()) {
                            header("Location: " . $_SERVER['PHP_SELF'] . "?success=create&type=filieres");
                            exit();
                        } else {
                            throw new Exception("Erreur lors de l'ajout de la filière: " . $stmt->error);
                        }
                        break;
                        
                    case 'matieres':
                        $nom_matiere = trim($_POST['nom_matiere']);
                        $filiere_id = (int)$_POST['filiere_mat'];
                        $niveau = $_POST['niveau_mat'];
                        $semestre = (int)$_POST['simestre_mat'];
                        $coefficient = (float)$_POST['coefficient_mat'];
                    
                        // Validation
                        if (empty($nom_matiere)) {
                            throw new Exception("Le nom de la matière est requis");
                        }
                        
                        if ($coefficient <= 0) {
                            throw new Exception("Le coefficient doit être positif");
                        }
                    
                        // Vérification que la filière existe
                        $check_filiere = "SELECT id_filiere FROM filiere WHERE id_filiere = ?";
                        $stmt_check = $conn->prepare($check_filiere);
                        $stmt_check->bind_param("i", $filiere_id);
                        $stmt_check->execute();
                        
                        if ($stmt_check->get_result()->num_rows == 0) {
                            throw new Exception("La filière sélectionnée n'existe pas");
                        }
                    
                        // Récupération du dernier id_matiere
                        $get_last_id = "SELECT MAX(id_matiere) as last_id FROM matiere";
                        $result = $conn->query($get_last_id);
                        $row = $result->fetch_assoc();
                        $new_id = $row['last_id'] + 1;
                    
                        // Insertion avec l'id_matiere explicite
                        $sql = "INSERT INTO matiere (id_matiere, nom_matiere, coeff, id_filiere, niveau_filiere, type_simestre) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("isdisi", $new_id, $nom_matiere, $coefficient, $filiere_id, $niveau, $semestre);
                    
                        if ($stmt->execute()) {
                            header("Location: " . $_SERVER['PHP_SELF'] . "?success=create&type=matieres");
                            exit();
                        } else {
                            throw new Exception("Erreur lors de l'ajout de la matière: " . $stmt->error);
                        }
                        break;
                }
            } else {
                // Cas normal pour les autres rôles (étudiant, enseignant, etc.)
                $nom = trim($_POST['nom']);
                $prenom = trim($_POST['prenom']);
                $email = trim($_POST['email']);
                $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
                $date_inscription = date('Y-m-d H:i:s');
                $date_naissance = $_POST['date_naissance'];
                
                // Validation des données de base
                if (empty($nom) || empty($prenom) || empty($email) || empty($_POST['mot_de_passe'])) {
                    throw new Exception("Tous les champs obligatoires doivent être remplis");
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("L'email n'est pas valide");
                }
            
                // Vérification que l'email n'existe pas déjà
                $check_email = "SELECT id_personne FROM personne WHERE email = ?";
                $stmt_check = $conn->prepare($check_email);
                $stmt_check->bind_param("s", $email);
                $stmt_check->execute();
                
                if ($stmt_check->get_result()->num_rows > 0) {
                    throw new Exception("Cet email est déjà utilisé par un autre utilisateur.");
                }

                // 1. Insertion dans la table `personne`
                $image_profile = "img.png";
                $sql = "INSERT INTO personne (prenom, nom, email, mot_de_passe, image_profile, role, date_inscription, dateNaissance) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssss", $prenom, $nom, $email, $mot_de_passe, $image_profile, $role, $date_inscription, $date_naissance);
            
                if ($stmt->execute()) {
                    $id_personne = $conn->insert_id; // Récupération de l'ID généré
            
                    // 2. Insertion spécifique selon le rôle
                    switch ($role) {
                        case 'etudiant':
                            $filiere = isset($_POST['filiere_etu']) ? (int)$_POST['filiere_etu'] : 0;
                            $niveau = $_POST['niveau_etudiant'];
                            $statut = "En attente";
                            
                            if (empty($filiere)) {
                                throw new Exception("La filière est requise pour un étudiant");
                            }
                            
                            // Correction: vérifier directement si la filière existe par son ID
                            $sql_check_filiere = "SELECT id_filiere FROM filiere WHERE id_filiere = ?";
                            $stmt_check_filiere = $conn->prepare($sql_check_filiere);
                            $stmt_check_filiere->bind_param("i", $filiere);
                            $stmt_check_filiere->execute();
                            
                            if ($stmt_check_filiere->get_result()->num_rows > 0) {
                                // La filière existe, on peut procéder à l'insertion
                                $sql_etudiant = "INSERT INTO etudiant (id_etudiant, id_filiere, niveau_filiere, statut) VALUES (?, ?, ?, ?)";
                                $stmt_etudiant = $conn->prepare($sql_etudiant);
                                $stmt_etudiant->bind_param("iiss", $id_personne, $filiere, $niveau, $statut);
                                
                                if (!$stmt_etudiant->execute()) {
                                    throw new Exception("Erreur lors de l'ajout de l'étudiant: " . $stmt_etudiant->error);
                                }
                            } else {
                                throw new Exception("La filière sélectionnée n'existe pas.");
                            }
                            break;
                        
                        case 'enseignant':
                            $telephone = isset($_POST['telephone']) ? $_POST['telephone'] : '';
                            $specialite = isset($_POST['grade']) ? $_POST['grade'] : '';
                            $filiere_id = (int)$_POST['filiere_ens'];
                            $niveau = $_POST['niveau_ens'];
                            $semestre = (int)$_POST['simestre_ens'];
                            $matiere_id = (int)$_POST['matiere_ens'];
                            $statut = $_POST['statut']; 
                            $heure = (int)$_POST['heure'];
                            
                            // Validation des données
                            if (empty($matiere_id) || empty($filiere_id)) {
                                throw new Exception("Filière et matière sont requises");
                            }
                            
                            if ($heure <= 0) {
                                throw new Exception("Le nombre d'heures doit être positif");
                            }
                        
                            $sql_ens = "INSERT INTO enseignant (id_enseignant, specialite, telephone, statut) VALUES (?, ?, ?, ?)";
                            $stmt_ens = $conn->prepare($sql_ens);
                            $stmt_ens->bind_param("isss", $id_personne, $specialite, $telephone, $statut);
                            
                            if ($stmt_ens->execute()) {
                                $sql_enseigner = "INSERT INTO enseigner (id_enseignant, id_matiere, id_filiere, niveau_filiere, nb_heure, type_semestre) 
                                                VALUES (?, ?, ?, ?, ?, ?)";
                                $stmt_enseigner = $conn->prepare($sql_enseigner);
                                $stmt_enseigner->bind_param("iiisii", $id_personne, $matiere_id, $filiere_id, $niveau, $heure, $semestre);
                                
                                if (!$stmt_enseigner->execute()) {
                                    throw new Exception("Erreur lors de l'ajout des informations d'enseignement: " . $stmt_enseigner->error);
                                }
                            } else {
                                throw new Exception("Erreur lors de l'ajout de l'enseignant: " . $stmt_ens->error);
                            }
                            break;
                            
                        case 'assistant':
                            $departement_id = (int)$_POST['departement_ass'];
                            $niveau = $_POST['niveau_ass'];
                            
                            if (empty($departement_id)) {
                                throw new Exception("Le département est requis");
                            }
                            
                            // Vérification que le département (filière) existe
                            $sql_get_filiere = "SELECT nom_filiere FROM filiere WHERE id_filiere = ?";
                            $stmt_get_filiere = $conn->prepare($sql_get_filiere);
                            $stmt_get_filiere->bind_param("i", $departement_id);
                            $stmt_get_filiere->execute();
                            $result = $stmt_get_filiere->get_result();
                            
                            if ($result->num_rows > 0) {
                                $row = $result->fetch_assoc();
                                $nom_filiere = $row['nom_filiere'];
                                
                                $sql_assistant = "INSERT INTO assistant (id_assistant, departement, niveau) 
                                                VALUES (?, ?, ?)";
                                $stmt_assistant = $conn->prepare($sql_assistant);
                                $stmt_assistant->bind_param("iss", $id_personne, $nom_filiere, $niveau);
                                
                                if (!$stmt_assistant->execute()) {
                                    throw new Exception("Erreur lors de l'ajout de l'assistant: " . $stmt_assistant->error);
                                }
                            } else {
                                throw new Exception("Le département sélectionné n'existe pas.");
                            }
                            break;
                            
                        case 'president':
                            $sql_president = "INSERT INTO president_jury (id_president) VALUES (?)";
                            $stmt_president = $conn->prepare($sql_president);
                            $stmt_president->bind_param("i", $id_personne);
                            
                            if (!$stmt_president->execute()) {
                                throw new Exception("Erreur lors de l'ajout du président de jury: " . $stmt_president->error);
                            }
                            break;
            
                        case 'directeur':
                            // Si une table directeur existe, à insérer ici. Sinon, inséré uniquement dans `personne`.
                            break;
                    }
            
                    // Si tout s'est bien passé
                    header("Location: " . $_SERVER['PHP_SELF'] . "?success=create");
                    exit();
                } else {
                    throw new Exception("Erreur lors de l'ajout: " . $stmt->error);
                }
            }
        } catch (Exception $e) {
            // Annuler les transactions si nécessaire
            if (isset($id_personne) && isset($conn)) {
                // Supprimer la personne créée en cas d'erreur
                $delete_person = "DELETE FROM personne WHERE id_personne = ?";
                $stmt_delete = $conn->prepare($delete_person);
                $stmt_delete->bind_param("i", $id_personne);
                @$stmt_delete->execute();
            }
            
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode($e->getMessage()));
            exit();
        }
    }
    // Modifier un utilisateur
    else if ($action == 'update') {
        $id = (int)$_POST['id'];
        $role = $_POST['role'];
    
        try {
            $conn->begin_transaction();
    
            // Traitement différent selon le rôle
            if ($role == 'filieres' || $role == 'matieres') {
                // Traitement pour les filières et matières
                switch($role) {
                    case 'filieres':
                        $nom_filiere = trim($_POST['filiere']);
                        $responsable_id = !empty($_POST['filiere_res']) ? (int)$_POST['filiere_res'] : null;
                        
                        // Debug: Vérifiez les valeurs reçues
                        error_log("Mise à jour filière - ID: $id, Nom: $nom_filiere, Responsable: $responsable_id");
                        
                        if (empty($nom_filiere)) {
                            throw new Exception("Le nom de la filière est requis");
                        }
                        
                        // Vérifier si le responsable existe si fourni
                        if ($responsable_id) {
                            $check_responsable = "SELECT a.id_assistant 
                                                FROM assistant a 
                                                INNER JOIN personne p ON p.id_personne = a.id_assistant
                                                WHERE a.id_assistant = ? AND p.role = 'assistant'";
                            $stmt_check = $conn->prepare($check_responsable);
                            $stmt_check->bind_param("i", $responsable_id);
                            $stmt_check->execute();
                            
                            if ($stmt_check->get_result()->num_rows == 0) {
                                throw new Exception("Le responsable sélectionné n'existe pas ou n'est pas un assistant");
                            }
                        }
                        
                        $sql = "UPDATE filiere SET nom_filiere = ?, responsable_id = ? WHERE id_filiere = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sii", $nom_filiere, $responsable_id, $id);
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Erreur SQL: " . $stmt->error);
                        }
                        
                        error_log("Filière mise à jour avec succès");
                        break;
                        
                    case 'matieres':
                        $nom_matiere = trim($_POST['nom_matiere']);
                        $filiere_id = (int)$_POST['filiere_mat'];
                        $niveau = $_POST['niveau_mat'];
                        $semestre = (int)$_POST['simestre_mat'];
                        $coefficient = (float)$_POST['coefficient'];
                        
                        // Debug
                        error_log("Mise à jour matière - ID: $id, Nom: $nom_matiere, Filière: $filiere_id, Niveau: $niveau, Semestre: $semestre, Coeff: $coefficient");
                        
                        if (empty($nom_matiere)) {
                            throw new Exception("Le nom de la matière est requis");
                        }
                        
                        if ($coefficient <= 0) {
                            throw new Exception("Le coefficient doit être un nombre positif");
                        }
                        
                        // Vérifier que la filière existe
                        $check_filiere = "SELECT id_filiere FROM filiere WHERE id_filiere = ?";
                        $stmt_check = $conn->prepare($check_filiere);
                        $stmt_check->bind_param("i", $filiere_id);
                        $stmt_check->execute();
                        
                        if ($stmt_check->get_result()->num_rows == 0) {
                            throw new Exception("La filière sélectionnée n'existe pas");
                        }
                        
                        $sql = "UPDATE matiere SET 
                                nom_matiere = ?, 
                                coeff = ?, 
                                id_filiere = ?, 
                                niveau_filiere = ?, 
                                type_simestre = ? 
                                WHERE id_matiere = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sdisii", $nom_matiere, $coefficient, $filiere_id, $niveau, $semestre, $id);
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Erreur SQL: " . $stmt->error);
                        }
                        
                        error_log("Matière mise à jour avec succès");
                        break;
                }
            } else {
                // Traitement pour les utilisateurs
                $nom = trim($_POST['nom']);
                $prenom = trim($_POST['prenom']);
                $email = trim($_POST['email']);
                $date_naissance = $_POST['date_naissance'];
                
                // Vérifier si l'email existe déjà pour un autre utilisateur
                $sql_check_email = "SELECT id_personne FROM personne WHERE email = ? AND id_personne != ?";
                $stmt_check_email = $conn->prepare($sql_check_email);
                $stmt_check_email->bind_param("si", $email, $id);
                $stmt_check_email->execute();
                $result_check_email = $stmt_check_email->get_result();
                
                if ($result_check_email->num_rows > 0) {
                    throw new Exception("Cet email est déjà utilisé par un autre utilisateur.");
                }
                
                // Mise à jour du mot de passe si fourni
                if (!empty($_POST['mot_de_passe'])) {
                    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
                    $sql = "UPDATE personne SET 
                            prenom = ?, nom = ?, email = ?, dateNaissance = ?, role = ?, mot_de_passe = ?
                            WHERE id_personne = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssssi", $prenom, $nom, $email, $date_naissance, $role, $mot_de_passe, $id);
                } else {
                    $sql = "UPDATE personne SET 
                            prenom = ?, nom = ?, email = ?, dateNaissance = ?, role = ?
                            WHERE id_personne = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssssi", $prenom, $nom, $email, $date_naissance, $role, $id);
                }
                $stmt->execute();
                
                // Vérifier si le rôle a changé
                $sql_check_role = "SELECT role FROM personne WHERE id_personne = ?";
                $stmt_check = $conn->prepare($sql_check_role);
                $stmt_check->bind_param("i", $id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                $old_role = $result_check->fetch_assoc()['role'];
                
                // Si changement de rôle, supprimer les anciennes données
                if ($old_role != $role) {
                    switch($old_role) {
                        case 'etudiant':
                            $sql_delete = "DELETE FROM etudiant WHERE id_etudiant = ?";
                            break;
                        case 'enseignant':
                            $sql_delete_enseigner = "DELETE FROM enseigner WHERE id_enseignant = ?";
                            $stmt_delete_enseigner = $conn->prepare($sql_delete_enseigner);
                            $stmt_delete_enseigner->bind_param("i", $id);
                            $stmt_delete_enseigner->execute();
                            
                            $sql_delete = "DELETE FROM enseignant WHERE id_enseignant = ?";
                            break;
                        case 'assistant':
                            $sql_delete = "DELETE FROM assistant WHERE id_assistant = ?";
                            break;
                        case 'president':
                            $sql_delete = "DELETE FROM president_jury WHERE id_president = ?";
                            break;
                    }
                    
                    if (isset($sql_delete)) {
                        $stmt_delete = $conn->prepare($sql_delete);
                        $stmt_delete->bind_param("i", $id);
                        $stmt_delete->execute();
                    }
                }
                
                // Mise à jour des données spécifiques au rôle
                switch($role) {
                    case 'etudiant':
                        $filiere_id = (int)$_POST['filiere']; // Récupérer directement l'ID
                        $niveau = $_POST['niveau_etudiant'];
                        $statut = "En attente"; // Ou récupérez le statut actuel si nécessaire
                        
                        // Vérifier que la filière existe
                        $check_filiere = "SELECT id_filiere FROM filiere WHERE id_filiere = ?";
                        $stmt_check = $conn->prepare($check_filiere);
                        $stmt_check->bind_param("i", $filiere_id);
                        $stmt_check->execute();
                        
                        if ($stmt_check->get_result()->num_rows > 0) {
                            $sql_etudiant = "INSERT INTO etudiant (id_etudiant, id_filiere, niveau_filiere, statut) 
                                            VALUES (?, ?, ?, ?)
                                            ON DUPLICATE KEY UPDATE
                                            id_filiere = VALUES(id_filiere), 
                                            niveau_filiere = VALUES(niveau_filiere), 
                                            statut = VALUES(statut)";
                            $stmt_etudiant = $conn->prepare($sql_etudiant);
                            $stmt_etudiant->bind_param("iiss", $id, $filiere_id, $niveau, $statut);
                            $stmt_etudiant->execute();
                        } else {
                            throw new Exception("La filière sélectionnée n'existe pas.");
                        }
                        break;
                        
                    case 'enseignant':
                        $telephone = isset($_POST['telephone']) ? $_POST['telephone'] : '';
                        $specialite = isset($_POST['grade']) ? $_POST['grade'] : '';
                        $filiere_id = (int)$_POST['filiere_ens'];
                        $niveau = $_POST['niveau_ens'];
                        $semestre = (int)$_POST['simestre_ens'];
                        $matiere_id = (int)$_POST['matiere_ens'];
                        $statut = $_POST['statut']; 
                        $heure = (int)$_POST['heure'];
                        
                        // Vérification des données
                        if (empty($matiere_id) || empty($filiere_id)) {
                            throw new Exception("Filière ou matière manquante.");
                        }
                        
                        $sql_ens = "INSERT INTO enseignant (id_enseignant, specialite, telephone, statut) 
                                    VALUES (?, ?, ?, ?)
                                    ON DUPLICATE KEY UPDATE
                                    specialite = VALUES(specialite), telephone = VALUES(telephone), 
                                    statut = VALUES(statut)";
                        $stmt_ens = $conn->prepare($sql_ens);
                        $stmt_ens->bind_param("isss", $id, $specialite, $telephone, $statut);
                        $stmt_ens->execute();
                    
                        // Supprimer les anciens enregistrements enseigner
                        $sql_delete_enseigner = "DELETE FROM enseigner WHERE id_enseignant = ?";
                        $stmt_delete_enseigner = $conn->prepare($sql_delete_enseigner);
                        $stmt_delete_enseigner->bind_param("i", $id);
                        $stmt_delete_enseigner->execute();
                        
                        // Insérer le nouvel enregistrement
                        $sql_enseigner = "INSERT INTO enseigner (id_enseignant, id_matiere, id_filiere, niveau_filiere, nb_heure, type_semestre) 
                                        VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt_enseigner = $conn->prepare($sql_enseigner);
                        $stmt_enseigner->bind_param("iiisii", $id, $matiere_id, $filiere_id, $niveau, $heure, $semestre);
                        $stmt_enseigner->execute();
                        break;
                    
                    case 'assistant':
                        $departement_id = (int)$_POST['departement_ass'];
                        $niveau = $_POST['niveau_ass'];
                        
                        // Récupérer le nom du département (filière)
                        $sql_get_filiere = "SELECT nom_filiere FROM filiere WHERE id_filiere = ?";
                        $stmt_get_filiere = $conn->prepare($sql_get_filiere);
                        $stmt_get_filiere->bind_param("i", $departement_id);
                        $stmt_get_filiere->execute();
                        $result = $stmt_get_filiere->get_result();
                        
                        if ($result->num_rows > 0) {
                            $departement = $result->fetch_assoc()['nom_filiere'];
                            
                            $sql_assistant = "INSERT INTO assistant (id_assistant, departement, niveau) 
                                            VALUES (?, ?, ?)
                                            ON DUPLICATE KEY UPDATE
                                            departement = VALUES(departement), niveau = VALUES(niveau)";
                            $stmt_assistant = $conn->prepare($sql_assistant);
                            $stmt_assistant->bind_param("iss", $id, $departement, $niveau);
                            $stmt_assistant->execute();
                        } else {
                            throw new Exception("Le département sélectionné n'existe pas.");
                        }
                        $stmt_get_filiere->close();
                        break;
                        
                    case 'president':
                        $sql_president = "INSERT INTO president_jury (id_president) 
                                        VALUES (?)
                                        ON DUPLICATE KEY UPDATE id_president = VALUES(id_president)";
                        $stmt_president = $conn->prepare($sql_president);
                        $stmt_president->bind_param("i", $id);
                        $stmt_president->execute();
                        break;
                }
            }
    
            $conn->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=update&type=" . urlencode($role));
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Erreur lors de la mise à jour : " . $e->getMessage();
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode($error));
            exit();
        }
    }
    
    // Supprimer un utilisateur
    if ($action == 'delete') {
        $id = $_POST['id'];
        $role = $_POST['role'];

        // Suppression spécifique selon le rôle
        if ($role == 'etudiant') {
            // Supprimer d'abord dans les tables dépendantes
            $stmt = $conn->prepare("DELETE FROM evaluer WHERE id_etudiant = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM recevoir_ressources WHERE id_etudiant = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Ajout: Supprimer les messages de l'étudiant
            $stmt = $conn->prepare("DELETE FROM messages WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM etudiant WHERE id_etudiant = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM personne WHERE id_personne = ?");
            $stmt->bind_param("i", $id);

        } else if ($role == 'enseignant') {
            // Nouvel ordre de suppression pour enseignant
            $stmt = $conn->prepare("DELETE FROM messages WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM ressource WHERE id_enseignant = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM enseigner WHERE id_enseignant = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM enseignant WHERE id_enseignant = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM personne WHERE id_personne = ?");
            $stmt->bind_param("i", $id);

        } else if ($role == 'assistant') {
            $stmt = $conn->prepare("DELETE FROM messages WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM planning WHERE id_assistant = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM assistant WHERE id_assistant = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM personne WHERE id_personne = ?");
            $stmt->bind_param("i", $id);

        } else if ($role == 'directeur_etude') {
            $stmt = $conn->prepare("DELETE FROM messages WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM directeur_etude WHERE id_directeur = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM personne WHERE id_personne = ?");
            $stmt->bind_param("i", $id);

        } else if ($role == 'president_jury') {
            $stmt = $conn->prepare("DELETE FROM messages WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM president_jury WHERE id_president = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM personne WHERE id_personne = ?");
            $stmt->bind_param("i", $id);

        } else if ($role == 'filieres') {
            // Pour les filières, vérifier d'abord les dépendances
            $stmt = $conn->prepare("DELETE FROM etudiant WHERE id_filiere = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM filiere WHERE id_filiere = ?");
            $stmt->bind_param("i", $id);

        } else if ($role == 'matieres') {
            // Pour les matières, vérifier d'abord les dépendances
            $stmt = $conn->prepare("DELETE FROM ressource WHERE id_matiere = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM matiere WHERE id_matiere = ?");
            $stmt->bind_param("i", $id);

        } else {
            // Cas général : supprimer d'abord les messages puis la personne
            $stmt = $conn->prepare("DELETE FROM messages WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM personne WHERE id_personne = ?");
            $stmt->bind_param("i", $id);
        }

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                header("Location: ".$_SERVER['PHP_SELF']."?success=delete&type=".$role);
                exit();
            } else {
                header("Location: ".$_SERVER['PHP_SELF']."?error=not_found&type=".$role);
                exit();
            }
        } else {
            header("Location: ".$_SERVER['PHP_SELF']."?error=delete&type=".$role."&msg=Erreur+lors+de+la+suppression.");
            exit();
        }
    }
}

function loadUsers($conn, $role = null, $page = 1, $perPage = 10, $search = '') {
    $offset = ($page - 1) * $perPage;
    $params = [];
    $types = '';
    
    // Requêtes spécifiques pour étudiants et enseignants
    if ($role == 'etudiant') {
        $sql = "SELECT SQL_CALC_FOUND_ROWS p.*, f.nom_filiere, e.niveau_filiere 
                FROM personne p
                LEFT JOIN etudiant e ON p.id_personne = e.id_etudiant
                LEFT JOIN filiere f ON e.id_filiere = f.id_filiere
                WHERE p.role = 'etudiant'";
    } elseif ($role == 'enseignant') {
        $sql = "SELECT SQL_CALC_FOUND_ROWS p.*, f.nom_filiere, m.nom_matiere,en.niveau_filiere, en.type_semestre, en.nb_heure, ens.statut
                FROM personne p
                LEFT JOIN enseignant ens ON p.id_personne = ens.id_enseignant
                LEFT JOIN enseigner en ON p.id_personne = en.id_enseignant
                LEFT JOIN filiere f ON en.id_filiere = f.id_filiere
                LEFT JOIN matiere m ON en.id_matiere = m.id_matiere
                WHERE p.role = 'enseignant'";
    } elseif ($role == 'assistant') {
        $sql = "SELECT SQL_CALC_FOUND_ROWS p.*, a.departement, a.niveau, f.nom_filiere
                FROM personne p
                LEFT JOIN assistant a ON p.id_personne = a.id_assistant
                LEFT JOIN filiere f ON a.id_assistant = f.responsable_id
                WHERE p.role = 'assistant'";
    } else {
        // Requête par défaut pour les autres rôles
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM personne WHERE 1=1";
        
        if ($role && $role != 'all') {
            $sql .= " AND role = ?";
            $params[] = $role;
            $types .= 's';
        }
    }
    
    // Ajout des conditions de recherche si nécessaire
    if (!empty($search)) {
        if ($role == 'etudiant' || $role == 'enseignant') {
            $sql .= " AND (p.nom LIKE ? OR p.prenom LIKE ? OR p.email LIKE ?)";
        } else {
            $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
        }
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'sss';
    }
    
    // Ajout de la pagination - modification ici pour utiliser le bon alias
    if ($role == 'etudiant' || $role == 'enseignant') {
        $sql .= " ORDER BY nom, prenom LIMIT ? OFFSET ?"; // Retirez le préfixe p. ici
    } else {
        $sql .= " ORDER BY nom, prenom LIMIT ? OFFSET ?";
    }
    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        // Ajoutez ceci pour déboguer
        die("Erreur de préparation: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    
    $totalResult = $conn->query("SELECT FOUND_ROWS() AS total");
    $total = $totalResult->fetch_assoc()['total'];
    
    return ['users' => $users, 'total' => $total];
}

// Traitement des exports
if (isset($_GET['export'])) {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $result = loadUsers($conn, $activeRole, 1, 10000, $search);
    $exportUsers = $result['users'];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=utilisateurs.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Nom', 'Prénom', 'Email', 'Rôle']);
    
    foreach ($exportUsers as $user) {
        fputcsv($output, [
            $user['id_personne'],
            $user['nom'],
            $user['prenom'],
            $user['email'],
            $user['role']
        ]);
    }
    fclose($output);
    exit();
}

// Chargement des données principales
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 6; // 6 éléments par page pour filières et matières

if ($activeRole == 'filieres') {
    // Requête paginée pour les filières
    $offset = ($page - 1) * $perPage;
    
    // Requête pour les données
    $query = "SELECT f.id_filiere, f.nom_filiere , a.departement ,p.nom , p.prenom
              FROM filiere f , assistant a , personne p
              WHERE a.id_assistant = f.responsable_id
              AND p.id_personne = a.id_assistant
              ORDER BY f.id_filiere ASC 
              LIMIT $perPage OFFSET $offset";

    $result = $conn->query($query);
    $users = $result->fetch_all(MYSQLI_ASSOC);
    
    // Requête pour le nombre total
    $countQuery = "SELECT COUNT(*) as total FROM filiere";
    $countResult = $conn->query($countQuery);
    $totalUsers = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalUsers / $perPage);
} 
elseif ($activeRole == 'matieres') {
    // Requête paginée pour les matières
    $offset = ($page - 1) * $perPage;
    
    // Requête pour les données
    $query = "SELECT m.id_matiere, m.nom_matiere, m.coeff, f.nom_filiere, m.niveau_filiere, m.type_simestre 
              FROM matiere m 
              JOIN filiere f ON f.id_filiere = m.id_filiere
              LIMIT $perPage OFFSET $offset";
    $result = $conn->query($query);
    $users = $result->fetch_all(MYSQLI_ASSOC);
    
    // Requête pour le nombre total
    $countQuery = "SELECT COUNT(*) as total FROM matiere";
    $countResult = $conn->query($countQuery);
    $totalUsers = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalUsers / $perPage);
} 
elseif ($activeRole == 'profile') {
    // Requête pour les données
    $query = "SELECT p.id_personne, p.nom, p.prenom, p.email ,p.mot_de_passe,p.dateNaissance
              FROM personne p where p.role = 'admin'";
    $result = $conn->query($query);
    $users = $result->fetch_all(MYSQLI_ASSOC);

    // Requête pour le nombre total
    $countQuery = "SELECT COUNT(*) as total FROM personne WHERE role = 'admin'";
    $countResult = $conn->query($countQuery);
    $totalUsers = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalUsers / $perPage);
    
}
else {
    // Chargement normal des utilisateurs avec les requêtes modifiées
    $result = loadUsers($conn, $activeRole, $page, $perPage, $search);
    $users = $result['users'];
    $totalUsers = $result['total'];
    $totalPages = ceil($totalUsers / $perPage);
}

// Préparation des statistiques
$roleStats = [
    'etudiant' => ['icon' => 'fa-user-graduate', 'color' => 'blue', 'count' => 0],
    'enseignant' => ['icon' => 'fa-chalkboard-teacher', 'color' => 'green', 'count' => 0],
    'assistant' => ['icon' => 'fa-user-friends', 'color' => 'orange', 'count' => 0],
    'president' => ['icon' => 'fa-user-tie', 'color' => 'red', 'count' => 0],
    'directeur' => ['icon' => 'fa-university', 'color' => 'teal', 'count' => 0],
    'faculte' => ['icon' => 'fa-school', 'color' => 'purple', 'count' => 1], // Faculté fixée à 1
    'filieres' => ['icon' => 'fa-graduation-cap', 'color' => 'indigo', 'count' => 0],
    'matieres' => ['icon' => 'fa-book', 'color' => 'deep-orange', 'count' => 0],
    'doyen' => ['icon' => 'fa-book', 'color' => 'deep-orange', 'count' => 0]
];

$countQuery = "SELECT role, COUNT(*) as count FROM personne GROUP BY role";
$countResult = $conn->query($countQuery);

while ($row = $countResult->fetch_assoc()) {
    if (isset($roleStats[$row['role']])) {
        $roleStats[$row['role']]['count'] = $row['count'];
    }
}

// Après la récupération des statistiques par rôle
$progressionQuery = "SELECT 
    role, 
    DATE_FORMAT(date_inscription, '%Y-%m') AS mois, 
    COUNT(*) AS count 
    FROM personne 
    GROUP BY role, mois 
    ORDER BY mois ASC";

$progressionResult = $conn->query($progressionQuery);
$progressionData = [];

while ($row = $progressionResult->fetch_assoc()) {
    $progressionData[$row['role']][] = [
        'mois' => $row['mois'],
        'count' => $row['count']
    ];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceuil Administrateur - Université de Djibouti<</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="../../../public/assets/css/admin_dashboard.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
    
</head>
<body>
    <?php if(isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?= $error ?>
    </div>
    <?php endif; ?>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php 
                $entityType = isset($_GET['type']) ? $_GET['type'] : 'utilisateur';
                
                // Traduction des types
                $entityNames = [
                    'etudiant' => 'étudiant',
                    'enseignant' => 'enseignant',
                    'assistant' => 'assistant',
                    'president' => 'président de jury',
                    'directeur' => 'directeur d\'études',
                    'filieres' => 'filière',
                    'matieres' => 'matière',
                    'profile' => 'profile',
                    'doyen' => 'doyen'
                ];

                $entityDisplay = isset($entityNames[$entityType]) ? $entityNames[$entityType] : $entityType;

                // Détermination de l'article (La ou L')
                $firstLetter = mb_substr($entityDisplay, 0, 1);
                $vowels = ['a', 'e', 'i', 'o', 'u', 'y', 'é'];

                $article = in_array(mb_strtolower($firstLetter), $vowels) ? "L’ " : "La ";

                switch($_GET['success']) {
                    case 'create':
                        echo "$article$entityDisplay a été créée avec succès.";
                        break;
                    case 'update':
                        echo "$article$entityDisplay a été mise à jour avec succès.";
                        break;
                    case 'delete':
                        echo "$article$entityDisplay a été supprimée avec succès.";
                        break;
                }
            ?>
        </div>
    <?php endif; ?>


    <!-- Mobile Toggle Button -->
    <div class="mobile-toggle">
        <i class="fas fa-bars"></i>
    </div>

    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="admin-profile">
                    <div class="admin-avatar">
                        <span class="initials"><?= substr($prenom, 0, 1) . substr($nom, 0, 1) ?></span>
                    </div>
                    <div class="admin-info">
                        <h3><?= htmlspecialchars($prenom . ' ' . $nom) ?></h3>
                        <p><?= ucfirst(htmlspecialchars($role)) ?></p>
                    </div>
                </div>   
            </div>

            <ul class="nav-list">
                <li class="nav-item <?= $activeRole == 'all' ? 'active' : '' ?>">
                    <a href="?role=all" class="nav-link">
                        <i class="fas fa-th-large"></i>
                        Tableau de bord
                    </a>
                </li>
                <li class="nav-item <?= $activeRole == 'profile' ? 'active' : '' ?>">
                    <a href="?role=profile" class="nav-link">
                        <i class="fas fa-user"></i>
                        Mon profile
                    </a>
                </li>
                <li class="nav-item <?= $activeRole == 'filieres' ? 'active' : '' ?>">
                    <a href="?role=filieres" class="nav-link">
                        <i class="fas fa-network-wired"></i>
                        Filiere
                    </a>
                </li>
                <li class="nav-item <?= $activeRole == 'matieres' ? 'active' : '' ?>">
                    <a href="?role=matieres" class="nav-link">
                        <i class="fas fa-book-open"></i>
                        Matiere
                    </a>
                </li>
                <li class="nav-item <?= $activeRole == 'etudiant' ? 'active' : '' ?>">
                    <a href="?role=etudiant" class="nav-link">
                        <i class="fas fa-user-graduate"></i>
                        Étudiants
                    </a>
                </li>
                <li class="nav-item <?= $activeRole == 'enseignant' ? 'active' : '' ?>">
                    <a href="?role=enseignant" class="nav-link">
                        <i class="fas fa-chalkboard-teacher"></i>
                        Enseignants
                    </a>
                </li>
                <li class="nav-item <?= $activeRole == 'assistant' ? 'active' : '' ?>">
                    <a href="?role=assistant" class="nav-link">
                        <i class="fas fa-user-friends"></i>
                        Assistants
                    </a>
                </li>
                <li class="nav-item <?= $activeRole == 'president' ? 'active' : '' ?>">
                    <a href="?role=president" class="nav-link">
                        <i class="fas fa-user-tie"></i>
                        Présidents Jury
                    </a>
                </li>
                <li class="nav-item <?= $activeRole == 'directeur' ? 'active' : '' ?>">
                    <a href="?role=directeur" class="nav-link">
                        <i class="fas fa-university"></i>
                        Directeurs Études
                    </a>
                </li>
                <li class="nav-item <?= $activeRole == 'doyen' ? 'active' : '' ?>">
                    <a href="?role=doyen" class="nav-link">
                        <i class="fas fa-university"></i>
                        Doyen
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#statistiques" class="nav-link" id="stats-link">
                        <i class="fas fa-chart-bar"></i>
                        Statistiques
                    </a>
                </li>
            </ul>

            <div class="logout-section">
                <a href="../../../config/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Déconnexion
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Ajouter dans la section recherche -->
            <?php if ($activeRole != 'all'): ?>
                <div class="card search-card">
                    <div class="section-header">
                        <form method="GET" class="search-form">
                            <input type="hidden" name="role" value="<?= $activeRole ?>">
                            <div class="search-container">
                                <div class="search-input-group">
                                    <input 
                                        type="text" 
                                        name="search" 
                                        placeholder="Rechercher un nom, prénom ou email..." 
                                        value="<?= htmlspecialchars($search) ?>"
                                        class="search-input"
                                    >
                                    <button type="submit" class="search-button">
                                        <i class="fas fa-search search-icon"></i>
                                        <span class="search-text">Lancer la recherche</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="export-buttons" style="margin-bottom: 1.5rem;">
                    <a href="?export=csv&role=<?= $activeRole ?>&search=<?= urlencode($search) ?>" 
                    class="btn btn-info">
                        <i class="fas fa-file-csv"></i> Exporter CSV
                    </a>
                
                </div>
                <?php endif; ?>
                    <div class="page-header">
                        <h1 class="page-title">
                            <?php 
                                switch($activeRole) {
                                    case 'filieres':
                                        echo "Gestion des filieres";
                                        break;
                                        case 'matieres':
                                            echo "Gestion des matieres";
                                            break;
                                    case 'etudiant':
                                        echo "Gestion des Étudiants";
                                        break;
                                    case 'enseignant':
                                        echo "Gestion des Enseignants";
                                        break;
                                    case 'assistant':
                                        echo "Gestion des Assistants";
                                        break;
                                    case 'president':
                                        echo "Gestion des Présidents de Jury";
                                        break;
                                    case 'directeur':
                                        echo "Gestion des Directeurs d'Études";
                                        break;

                                    case 'profile':
                                        echo "Profile administrateur";
                                        break;
                                        
                                    case 'doyen':
                                        echo "Gestion des Doyen";
                                        break;
                                    default:
                                        echo "Tableau de Bord";
                                }
                            ?>
                        </h1>
                <?php if ($activeRole != 'all'): ?>
                <button class="btn btn-success" id="addUserBtn">
                    <i class="fas fa-plus"></i>
                    Ajouter <?= 
                               ($activeRole == 'filieres' ? 'un filiere' : 
                               ($activeRole == 'matieres' ? 'un matieres' : 
                               ($activeRole == 'etudiant' ? 'un étudiant' : 
                               ($activeRole == 'enseignant' ? 'un enseignant' : 
                               ($activeRole == 'assistant' ? 'un assistant' : 
                               ($activeRole == 'president' ? 'un président' : 
                               ($activeRole == 'doyen' ? 'un doyen' : 
                               ($activeRole == 'directeur' ? 'un directeur' : 'un utilisateur')))))))) ?>
                </button>
                <?php endif; ?>
            </div>

            <!-- Dashboard / Statistiques View -->
            <?php if ($activeRole == 'all'): ?>
                <div class="card">
                    <div class="section-header">
                        <h2 class="section-title">Progression des inscriptions</h2>
                    </div>
                    <div class="chart-container" style="position: relative; height:400px;">
                        <canvas id="progressionChart"></canvas>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($activeRole == 'all'): ?>
                <div class="tab-content active" id="dashboard">
                    <div class="stats-grid">
                        <?php
                        // Compter le nombre d'utilisateurs par rôle
                        $conn = new mysqli($servername, $username, $password, $dbname);
                        
                        $roleStats = [
                            'etudiant' => ['icon' => 'fa-user-graduate', 'color' => 'blue', 'count' => 0],
                            'enseignant' => ['icon' => 'fa-chalkboard-teacher', 'color' => 'green', 'count' => 0],
                            'assistant' => ['icon' => 'fa-user-friends', 'color' => 'orange', 'count' => 0],
                            'president' => ['icon' => 'fa-user-tie', 'color' => 'red', 'count' => 0],
                            'directeur' => ['icon' => 'fa-university', 'color' => 'teal', 'count' => 0],
                            'doyen' => ['icon' => 'fa-university', 'color' => 'teal', 'count' => 0],
                        ];
                        
                        $countQuery = "SELECT role, COUNT(*) as count FROM personne GROUP BY role";
                        $countResult = $conn->query($countQuery);
                        
                        if ($countResult) {
                            while ($row = $countResult->fetch_assoc()) {
                                if (isset($roleStats[$row['role']])) {
                                    $roleStats[$row['role']]['count'] = $row['count'];
                                }
                            }
                        }
                        
                        $totalUsers = 0;
                        foreach ($roleStats as $count) {
                            $totalUsers += $count['count'];
                        }
                        // Statistiques pour les facultés, filières et matières
                        $facultyCount = 1; // Comme demandé, on fixe à 1
                        $filiereCount = 0;
                        $matiereCount = 0;

                        // Compter le nombre de filières
                        $filiereQuery = "SELECT COUNT(*) as count FROM filiere";
                        $filiereResult = $conn->query($filiereQuery);
                        if ($filiereResult) {
                            $filiereCount = $filiereResult->fetch_assoc()['count'];
                        }
                        
                        // Compter le nombre de matières
                        $matiereQuery = "SELECT COUNT(*) as count FROM matiere";
                        $matiereResult = $conn->query($matiereQuery);
                        if ($matiereResult) {
                            $matiereCount = $matiereResult->fetch_assoc()['count'];
                        }
                        
                        // Afficher une carte pour le total
                        ?>
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-number"><?= $totalUsers ?></div>
                            <div class="stat-label">Total utilisateurs</div>
                        </div>
                        
                        <?php
                            // Afficher une carte pour chaque rôle
                            foreach ($roleStats as $role => $data): ?>
                            <div class="stat-card">
                                <div class="stat-icon <?= $data['color'] ?>">
                                    <i class="fas <?= $data['icon'] ?>"></i>
                                </div>
                                <div class="stat-number"><?= $data['count'] ?></div>
                                <div class="stat-label"><?= ucfirst($role) ?>s</div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Cartes pour faculté, filière et matière -->
                        <div class="stat-card">
                            <div class="stat-icon purple">
                                <i class="fas fa-school"></i>
                            </div>
                            <div class="stat-number"><?= $facultyCount ?></div>
                            <div class="stat-label">Faculté</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon indigo">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="stat-number"><?= $filiereCount ?></div>
                            <div class="stat-label">Filières</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon deep-orange">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-number"><?= $matiereCount ?></div>
                            <div class="stat-label">Matières</div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="section-header">
                        <h2 class="section-title">Utilisateurs récemment inscrits</h2>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Date d'inscription</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recentUsers = "SELECT * FROM personne ORDER BY date_inscription DESC LIMIT 5";
                                $recentResult = $conn->query($recentUsers);
                                
                                if ($recentResult && $recentResult->num_rows > 0) {
                                    while ($user = $recentResult->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $user['id_personne'] . "</td>";
                                        echo "<td>" . htmlspecialchars($user['nom']) . "</td>";
                                        echo "<td>" . htmlspecialchars($user['prenom']) . "</td>";
                                        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                                        
                                        // Badge de rôle avec classe CSS différente selon le rôle
                                        $badgeClass = "";
                                        switch($user['role']) {
                                            case 'etudiant': $badgeClass = "badge-primary"; break;
                                            case 'enseignant': $badgeClass = "badge-success"; break;
                                            case 'assistant': $badgeClass = "badge-warning"; break;
                                            case 'president': $badgeClass = "badge-danger"; break;
                                            case 'directeur': $badgeClass = "badge-info"; break;
                                            case 'doyen': $badgeClass = "badge-info"; break;
                                        }
                                        
                                        echo "<td><span class='badge " . $badgeClass . "'>" . ucfirst($user['role']) . "</span></td>";
                                        echo "<td>" . date("d/m/Y H:i", strtotime($user['date_inscription'])) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>Aucun utilisateur trouvé</td></tr>";
                                }
                                $conn->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <!-- Users Table View for specific role -->
                <div class="tab-content active">
                    <div class="card">
                        <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <?php if ($activeRole == 'filieres'): ?>
                                        <th>ID</th>
                                        <th>Nom de la filière</th>
                                        <th>Responsable(Assistant(e))</th>
                                        <th>Actions</th>
                                    <?php elseif ($activeRole == 'matieres'): ?>
                                        <th>ID</th>
                                        <th>Nom de la matière</th>
                                        <th>Coefficient</th>
                                        <th>Filière</th>
                                        <th>Niveau</th>
                                        <th>Semestre</th>
                                        <th>Actions</th>
                                    <?php elseif ($activeRole == 'etudiant'): ?>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Email</th>
                                        <th>Filière</th>
                                        <th>Niveau</th>
                                        <th>Date Naissance</th>
                                        <th>Actions</th>
                                    <?php elseif ($activeRole == 'enseignant'): ?>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Email</th>
                                        <th>Filière</th>
                                        <th>Niveau</th>
                                        <th>Matière</th>
                                        <th>Semestre</th>
                                        <th>Heures</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    <?php elseif ($activeRole == 'assistant'): ?>
                                        <!-- Structure spécifique pour les assistants -->
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Email</th>
                                        <th>Département (Filière)</th>
                                        <th>Niveau</th>
                                        <th>Date Naissance</th>
                                        <th>Actions</th>
                                    
                                    <?php elseif ($activeRole == 'profile'): ?>
                                    <!-- Structure spécifique pour les assistants -->
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Email</th>
                                    <th>Mot de passe</th>
                                    <th>Date Naissance</th>

                                    <?php else: ?>
                                        <!-- Structure par défaut pour les autres rôles -->
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Email</th>
                                        <th>Date Naissance</th>
                                        <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($users) > 0): ?>
                                    <?php foreach ($users as $item): ?>
                                    <tr>
                                        <?php if ($activeRole == 'filieres'): ?>
                                            <!-- Code existant pour les filières -->
                                            <td><?= htmlspecialchars($item['id_filiere']) ?></td>
                                            <td><?= htmlspecialchars($item['nom_filiere']) ?></td>
                                            <td><?= htmlspecialchars($item['nom']) ?> <?= htmlspecialchars($item['prenom']) ?></td>
                                            <td class="actions">
                                                <button class="btn btn-sm btn-primary edit-btn" 
                                                        data-id="<?= $item['id_filiere'] ?>">
                                                    <i class="fas fa-edit"></i> Modifier
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-btn" 
                                                        data-id="<?= htmlspecialchars($item['id_filiere']) ?>" 
                                                        data-nom="<?= htmlspecialchars($item['nom_filiere']) ?>" 
                                                        data-prenom=""
                                                        data-role="filieres">
                                                    <i class="fas fa-trash"></i> Supprimer
                                                </button>
                                            </td>
                                        <?php elseif ($activeRole == 'matieres'): ?>
                                            <!-- Code existant pour les matières -->
                                            <td><?= htmlspecialchars($item['id_matiere']) ?></td>
                                            <td><?= htmlspecialchars($item['nom_matiere']) ?></td>
                                            <td><?= htmlspecialchars($item['coeff']) ?></td>
                                            <td><?= htmlspecialchars($item['nom_filiere']) ?></td>
                                            <td><?= htmlspecialchars($item['niveau_filiere']) ?></td>
                                            <td><?= htmlspecialchars($item['type_simestre']) ?></td>
                                            <td class="actions">
                                                <button class="btn btn-sm btn-primary edit-btn" 
                                                        data-id="<?= $item['id_matiere'] ?>">
                                                    <i class="fas fa-edit"></i> Modifier
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-btn" 
                                                        data-id="<?= $item['id_matiere'] ?>" 
                                                        data-role="matieres"
                                                        data-nom="<?= htmlspecialchars($item['nom_matiere']) ?>" 
                                                        data-prenom="">
                                                    <i class="fas fa-trash"></i> Supprimer
                                                </button>
                                            </td>
                                        <?php elseif ($activeRole == 'etudiant'): ?>
                                            <!-- Structure pour les étudiants -->
                                            <td><?= $item['id_personne'] ?></td>
                                            <td><?= htmlspecialchars($item['nom']) ?></td>
                                            <td><?= htmlspecialchars($item['prenom']) ?></td>
                                            <td><?= htmlspecialchars($item['email']) ?></td>
                                            <td><?= htmlspecialchars($item['nom_filiere']) ?></td>
                                            <td><?= htmlspecialchars($item['niveau_filiere']) ?></td>
                                            <td><?= isset($item['dateNaissance']) ? date("d/m/Y", strtotime($item['dateNaissance'])) : "N/A" ?></td>
                                            <td class="actions">
                                                <button class="btn btn-sm btn-primary edit-btn" 
                                                        data-id="<?= $item['id_personne'] ?>">
                                                    <i class="fas fa-edit"></i> Modifier
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-btn"
                                                        data-id="<?= $item['id_personne'] ?>" 
                                                        data-nom="<?= htmlspecialchars($item['nom']) ?>" 
                                                        data-prenom="<?= htmlspecialchars($item['prenom']) ?>" 
                                                        data-role="<?= $activeRole ?>">
                                                    <i class="fas fa-trash"></i> Supprimer
                                                </button>
                                            </td>
                                        <?php elseif ($activeRole == 'enseignant'): ?>
                                            <!-- Structure pour les enseignants -->
                                            <td><?= $item['id_personne'] ?></td>
                                            <td><?= htmlspecialchars($item['nom']) ?></td>
                                            <td><?= htmlspecialchars($item['prenom']) ?></td>
                                            <td><?= htmlspecialchars($item['email']) ?></td>
                                            <td><?= htmlspecialchars($item['nom_filiere']) ?></td>
                                            <td><?= htmlspecialchars($item['niveau_filiere']) ?></td>
                                            <td><?= htmlspecialchars($item['nom_matiere']) ?></td>
                                            <td><?= htmlspecialchars($item['type_semestre']) ?></td>
                                            <td><?= htmlspecialchars($item['nb_heure']) ?></td>
                                            <td><?= htmlspecialchars($item['statut']) ?></td>
                                            <td class="actions">
                                                <button class="btn btn-sm btn-primary edit-btn" 
                                                        data-id="<?= $item['id_personne'] ?>">
                                                    <i class="fas fa-edit"></i> Modifier
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-btn"
                                                        data-id="<?= $item['id_personne'] ?>" 
                                                        data-nom="<?= htmlspecialchars($item['nom']) ?>" 
                                                        data-prenom="<?= htmlspecialchars($item['prenom']) ?>" 
                                                        data-role="<?= $activeRole ?>">
                                                    <i class="fas fa-trash"></i> Supprimer
                                                </button>
                                            </td>
                                        
                                            <?php elseif ($activeRole == 'assistant'): ?>
                                            <!-- Ligne pour les assistants -->
                                            <td><?= $item['id_personne'] ?></td>
                                            <td><?= htmlspecialchars($item['nom']) ?></td>
                                            <td><?= htmlspecialchars($item['prenom']) ?></td>
                                            <td><?= htmlspecialchars($item['email']) ?></td>
                                            <td><?= htmlspecialchars($item['departement']) ?></td>
                                            <td><?= htmlspecialchars($item['niveau'] ) ?></td>
                                            <td><?= isset($item['dateNaissance']) ? date("d/m/Y", strtotime($item['dateNaissance'])) : "N/A" ?></td>
                                            <td class="actions">
                                                <button class="btn btn-sm btn-primary edit-btn" 
                                                        data-id="<?= $item['id_personne'] ?>">
                                                    <i class="fas fa-edit"></i> Modifier
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-btn"
                                                        data-id="<?= $item['id_personne'] ?>" 
                                                        data-nom="<?= htmlspecialchars($item['nom']) ?>" 
                                                        data-prenom="<?= htmlspecialchars($item['prenom']) ?>" 
                                                        data-role="<?= $activeRole ?>">
                                                    <i class="fas fa-trash"></i> Supprimer
                                                </button>
                                            </td>
                                            <?php elseif ($activeRole == 'profile'): ?>
                                            <!-- Ligne pour les assistants -->
                                            <td><?= $item['id_personne'] ?></td>
                                            <td><?= htmlspecialchars($item['nom']) ?></td>
                                            <td><?= htmlspecialchars($item['prenom']) ?></td>
                                            <td><?= htmlspecialchars($item['email']) ?></td>
                                            <td><?= htmlspecialchars($item['mot_de_passe'] ) ?></td>
                                            <td><?= htmlspecialchars($item['dateNaissance'] ) ?></td>

                                        <?php else: ?>
                                            <!-- Structure par défaut pour les autres rôles -->
                                            <td><?= $item['id_personne'] ?></td>
                                            <td><?= htmlspecialchars($item['nom']) ?></td>
                                            <td><?= htmlspecialchars($item['prenom']) ?></td>
                                            <td><?= htmlspecialchars($item['email']) ?></td>
                                            <td><?= isset($item['dateNaissance']) ? date("d/m/Y", strtotime($item['dateNaissance'])) : "N/A" ?></td>
                                            <td class="actions">
                                                <button class="btn btn-sm btn-primary edit-btn" 
                                                        data-id="<?= $item['id_personne'] ?>">
                                                    <i class="fas fa-edit"></i> Modifier
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-btn"
                                                        data-id="<?= $item['id_personne'] ?>" 
                                                        data-nom="<?= htmlspecialchars($item['nom']) ?>" 
                                                        data-prenom="<?= htmlspecialchars($item['prenom']) ?>" 
                                                        data-role="<?= $activeRole ?>">
                                                    <i class="fas fa-trash"></i> Supprimer
                                                </button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?= 
                                            ($activeRole == 'filieres') ? 3 : 
                                            (($activeRole == 'matieres') ? 7 : 
                                            (($activeRole == 'etudiant') ? 8 : 
                                            (($activeRole == 'enseignant') ? 11 : 6))) 
                                        ?>" style="text-align: center;">
                                            Aucune donnée trouvée
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                            
                            
                            <div class="pagination" style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                                <?php if ($totalPages > 1): ?>
                                    <?php if ($page > 1): ?>
                                        <a href="?role=<?= $activeRole ?>&page=<?= $page - 1 ?>" class="btn btn-sm btn-secondary">
                                            &laquo; Précédent
                                        </a>
                                    <?php endif; ?>

                                    <?php
                                    // Afficher un nombre limité de pages autour de la page courante
                                    $start = max(1, $page - 2);
                                    $end = min($totalPages, $page + 2);
                                    
                                    for ($i = $start; $i <= $end; $i++): ?>
                                        <a href="?role=<?= $activeRole ?>&page=<?= $i ?>" 
                                        class="btn btn-sm <?= $i == $page ? 'btn-primary' : 'btn-secondary' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <a href="?role=<?= $activeRole ?>&page=<?= $page + 1 ?>" class="btn btn-sm btn-secondary">
                                            Suivant &raquo;
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Ajouter un utilisateur</h2>
                <button type="button" class="close-btn" id="closeAddModal">&times;</button>
            </div>
            <div class="modal-body">
            <form id="addUserForm" method="POST">
                <input type="hidden" name="action" value="create">

                <div class="form-group general-fields">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" required>
                </div>
                
                <div class="form-group general-fields">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" required>
                </div>
                
                <div class="form-group general-fields">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group general-fields">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                </div>
                
                <div class="form-group general-fields">
                    <label for="date_naissance">Date de naissance</label>
                    <input type="date" id="date_naissance" name="date_naissance" required>
                </div>

                <!-- Champs spécifiques aux rôles -->
                <div id="etudiantFields" class="role-specific-fields">
                    <div class="form-group">
                        <label for="filiere_etu">Filière</label>
                        <select id="filiere_etu" name="filiere_etu">
                            <option value="">Sélectionner une filière</option>
                            <!-- Les options de filières seront ajoutées dynamiquement ici -->
                        </select>
                        <!-- Le datalist sera ajouté dynamiquement par JavaScript -->
                    </div>
                    <div class="form-group">
                        <label for="niveau">Niveau</label>
                        <select id="niveau" name="niveau_etudiant">
                            <option value="L1">L1</option>
                            <option value="L2">L2</option>
                            <option value="L3">L3</option>
                        </select>
                    </div>

                </div>

                <!-- Partie du formulaire enseignant avec les champs améliorés -->
                <div id="enseignantFields" class="role-specific-fields">
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone">
                    </div>

                    <div class="form-group">
                        <label for="grade">Grade</label>
                        <input type="text" id="grade" name="grade">
                    </div>
                    
                    <div class="form-group">
                        <label for="filiere_ens">Filière</label>
                        <select id="filiere_ens" name="filiere_ens">
                            <option value="">Sélectionner une filière</option>
                            <!-- Les options de filières seront ajoutées dynamiquement ici -->
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="niveau_ens">Niveau</label>
                        <select id="niveau_ens" name="niveau_ens">
                            <option value="">Sélectionner un niveau</option>
                            <option value="L1">L1</option>
                            <option value="L2">L2</option>
                            <option value="L3">L3</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="simestre_ens">Semestre</label>
                        <select id="simestre_ens" name="simestre_ens">
                            <option value="">Sélectionner un semestre</option>
                            <option value="1">Semestre 1</option>
                            <option value="2">Semestre 2</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="matiere_ens">Matière</label>
                        <select id="matiere_ens" name="matiere_ens">
                            <option value="">Sélectionner une matière</option>
                            <!-- Les options de matières seront ajoutées dynamiquement ici -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="heure">Nombre d'heures</label>
                        <input type="number" name="heure" id="heure">
                    </div>
                    
                    <div class="form-group">
                        <label for="statut">Statut</label>
                        <select id="statut" name="statut">
                            <option value="">Sélectionner un statut</option>
                            <option value="Permanent">Permanent</option>
                            <option value="Vacataire">Vacataire</option>
                        </select>
                    </div>
                </div>

                <div id="assistantFields" class="role-specific-fields">
                    <div class="form-group">
                        <label for="departement_ass">Département</label>
                        <select id="departement_ass" name="departement_ass">
                            <option value="">Sélectionner une département</option>
                            <!-- Les options seront ajoutées dynamiquement -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="niveau_ass">Niveau</label>
                        <select id="niveau_ass" name="niveau_ass">
                            <option value="L1">L1</option>
                            <option value="L2">L2</option>
                            <option value="L3">L3</option>
                        </select>
                    </div>
                </div>

                <div id="filieresFields" class="role-specific-fields">
                    <div class="form-group">
                        <label for="filiere">Filiere</label>
                        <input type="text" id="filiere" name="filiere">
                    </div>
                    
                    <div class="form-group">
                        <label for="filiere_res">Responsable(Assistant(e))</label>
                        <select id="filiere_res" name="filiere_res">
                            <option value="">Sélectionner un responsable</option>
                            <!-- Les options de responsable seront ajoutées dynamiquement ici -->
                        </select>
                    </div>
                </div>

                <div id="matieresFields" class="role-specific-fields">
                    <div class="form-group">
                        <label for="nom_matiere">Nom matiere</label>
                        <input type="text" id="nom_matiere" name="nom_matiere">
                    </div>
                    
                    <div class="form-group">
                        <label for="filiere_mat">Filière</label>
                        <select id="filiere_mat" name="filiere_mat">
                            <option value="">Sélectionner une filière</option>
                            <!-- Les options de filières seront ajoutées dynamiquement ici -->
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="niveau_mat">Niveau</label>
                        <select id="niveau_mat" name="niveau_mat">
                            <option value="">Sélectionner un niveau</option>
                            <option value="L1">L1</option>
                            <option value="L2">L2</option>
                            <option value="L3">L3</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="simestre_mat">Semestre</label>
                        <select id="simestre_mat" name="simestre_mat">
                            <option value="">Sélectionner un semestre</option>
                            <option value="1">Semestre 1</option>
                            <option value="2">Semestre 2</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="coefficient_mat">Coefficient</label>
                        <input type="number" id="coefficient_mat" name="coefficient_mat">
                    </div>
                    
                    
                </div>
                
                <div class="form-group">
                    <label for="role">Rôle</label>
                    <select id="role" name="role" required>
                        <option value="etudiant" <?= $activeRole == 'etudiant' ? 'selected' : '' ?>>Étudiant</option>
                        <option value="enseignant" <?= $activeRole == 'enseignant' ? 'selected' : '' ?>>Enseignant</option>
                        <option value="assistant" <?= $activeRole == 'assistant' ? 'selected' : '' ?>>Assistant</option>
                        <option value="president" <?= $activeRole == 'president' ? 'selected' : '' ?>>Président de Jury</option>
                        <option value="directeur" <?= $activeRole == 'directeur' ? 'selected' : '' ?>>Directeur d'Études</option>
                        <option value="filieres" <?= $activeRole == 'filieres' ? 'selected' : '' ?>>Filière</option>
                        <option value="matieres" <?= $activeRole == 'matieres' ? 'selected' : '' ?>>Matiere</option>
                        <option value="doyen" <?= $activeRole == 'doyen' ? 'selected' : '' ?>>Doyen</option>
                        

                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="cancelAddBtn">Annuler</button>
                    <button type="submit" class="btn btn-success">Enregistrer</button>
                </div>
            </form>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Récupérer les filières dès le chargement de la page
            getFilieres();
            getAssistants(); // Charger les assistants pour le rôle "filieres"
            
            // Ajout des écouteurs d'événements pour tous les champs qui doivent déclencher une mise à jour
            const filiereSelect = document.getElementById('filiere_ens');
            const niveauSelect = document.getElementById('niveau_ens');
            const semestreSelect = document.getElementById('simestre_ens');
            
            filiereSelect.addEventListener('change', getMatieres);
            niveauSelect.addEventListener('change', getMatieres);
            semestreSelect.addEventListener('change', getMatieres);
            
            // Fonction pour récupérer les filières
            function getFilieres() {
                console.log("Démarrage de la récupération des filières...");
                
                fetch('get_filieres_matieres.php?action=get_filieres')
                    .then(response => {
                        console.log("Réponse reçue pour les filières", response);
                        
                        if (!response.ok) {
                            throw new Error(`Erreur réseau: ${response.status} - ${response.statusText}`);
                        }
                        return response.text();
                    })
                    .then(rawText => {
                        console.log("Texte brut reçu:", rawText);
                        
                        try {
                            const data = JSON.parse(rawText);
                            console.log("Données JSON parsées:", data);
                            
                            // Liste des selecteurs où il faut ajouter les filières
                            const filiereSelectors = [
                                'filiere_ens',    // Enseignant
                                'filiere_etu',    // Étudiant
                                'departement_ass', // Assistant (département)
                                'filiere_mat',    // Matière
                                'filiere'         // Filière (pour le responsable)
                            ];
                            
                            // Pour chaque sélecteur, ajouter les options de filières
                            filiereSelectors.forEach(selector => {
                                const selectElement = document.getElementById(selector);
                                if (selectElement) {
                                    selectElement.innerHTML = '<option value="">Sélectionner une filière</option>';
                                    
                                    if (Array.isArray(data) && data.length > 0) {
                                        data.forEach(filiere => {
                                            const option = document.createElement('option');
                                            option.value = filiere.id_filiere;
                                            option.textContent = filiere.nom_filiere;
                                            selectElement.appendChild(option);
                                        });
                                    }
                                }
                            });
                            
                        } catch (error) {
                            console.error("Erreur lors du parsing JSON:", error, "Texte reçu:", rawText);
                        }
                    })
                    .catch(error => {
                        console.error('Erreur de récupération des filières:', error);
                        alert("Erreur lors de la récupération des filières. Consultez la console pour plus de détails.");
                    });
            }
            
            // Fonction pour récupérer les assistants (responsables de filières)
            function getAssistants() {
                console.log("Démarrage de la récupération des assistants...");
                
                fetch('get_filieres_matieres.php?action=get_assistants')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Erreur réseau: ${response.status} - ${response.statusText}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log("Données des assistants reçues:", data);
                        
                        const responsableSelect = document.getElementById('filiere_res');
                        if (responsableSelect) {
                            responsableSelect.innerHTML = '<option value="">Sélectionner un responsable</option>';
                            
                            if (Array.isArray(data) && data.length > 0) {
                                data.forEach(assistant => {
                                    const option = document.createElement('option');
                                    option.value = assistant.id_utilisateur;
                                    option.textContent = assistant.nom_complet;
                                    responsableSelect.appendChild(option);
                                });
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Erreur de récupération des assistants:', error);
                    });
            }
        });

        // Fonction pour récupérer les matières (déjà existante)
        function getMatieres() {
            const filiereId = document.getElementById('filiere_ens').value;
            const niveau = document.getElementById('niveau_ens').value;
            const semestre = document.getElementById('simestre_ens').value;
            
            console.log('Chargement des matières avec:', {filiereId, niveau, semestre});
            
            if (filiereId && niveau && semestre) {
                const url = `get_filieres_matieres.php?action=get_matieres&filiere_id=${filiereId}&niveau=${niveau}&semestre=${semestre}`;
                console.log("URL de requête:", url);
                
                fetch(url)
                    .then(response => {
                        console.log("Réponse reçue pour les matières", response);
                        
                        if (!response.ok) {
                            throw new Error(`Erreur réseau: ${response.status} - ${response.statusText}`);
                        }
                        return response.text();
                    })
                    .then(rawText => {
                        console.log("Texte brut reçu pour les matières:", rawText);
                        
                        try {
                            const data = JSON.parse(rawText);
                            console.log("Données JSON des matières parsées:", data);
                            
                            const matiereSelect = document.getElementById('matiere_ens');
                            matiereSelect.innerHTML = '<option value="">Sélectionner une matière</option>';
                            
                            if (Array.isArray(data) && data.length > 0) {
                                data.forEach(matiere => {
                                    const option = document.createElement('option');
                                    option.value = matiere.id_matiere;
                                    option.textContent = matiere.nom_matiere;
                                    matiereSelect.appendChild(option);
                                });
                                console.log(`${data.length} matières chargées`);
                            } else if (data.error) {
                                console.error('Erreur serveur:', data.message);
                            } else {
                                console.log('Aucune matière trouvée pour ces critères');
                            }
                        } catch (error) {
                            console.error("Erreur lors du parsing JSON des matières:", error, "Texte reçu:", rawText);
                        }
                    })
                    .catch(error => {
                        console.error('Erreur de récupération des matières:', error);
                    });
            } else {
                const matiereSelect = document.getElementById('matiere_ens');
                matiereSelect.innerHTML = '<option value="">Sélectionner une matière</option>';
            }
        }
    </script>

    <!-- Edit User Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content editModal">
            <div class="modal-header">
                <h2 class="modal-title">Modifier l'utilisateur</h2>
                <button type="button" class="close-btn" id="closeEditModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editUserForm" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editId">
                    
                    <div class="form-group general-fields">
                        <label for="editNom">Nom</label>
                        <input type="text" id="editNom" name="nom" required>
                    </div>
                    
                    <div class="form-group general-fields">
                        <label for="editPrenom">Prénom</label>
                        <input type="text" id="editPrenom" name="prenom" required>
                    </div>
                    
                    <div class="form-group general-fields">
                        <label for="editEmail">Email</label>
                        <input type="email" id="editEmail" name="email" required>
                    </div>
                    
                    <div class="form-group general-fields">
                        <label for="editMotDePasse">Mot de passe (laisser vide pour ne pas changer)</label>
                        <input type="password" id="editMotDePasse" name="mot_de_passe">
                    </div>
                    
                    <div class="form-group general-fields">
                        <label for="editDateNaissance">Date de naissance</label>
                        <input type="date" id="editDateNaissance" name="date_naissance" required>
                    </div>

                    <!-- Champs spécifiques aux rôles -->
                    <div id="editEtudiantFields" class="role-specific-fields  edit-general-fields">
                        <div class="form-group">
                            <label for="editFiliere">Filière</label>
                            <select id="editFiliere" name="filiere">
                                <option value="">Sélectionner une filière</option>
                                <!-- Les options seront ajoutées dynamiquement -->
                            </select>
                            
                        </div>
                        <div class="form-group>
                            <label for="editNiveauEtudiant">Niveau</label>
                            <select id="editNiveauEtudiant" name="niveau_etudiant">
                                <option value="L1">L1</option>
                                <option value="L2">L2</option>
                                <option value="L3">L3</option>
                            </select>
                        </div>
                    </div>

                    <div id="editEnseignantFields" class="role-specific-fields  edit-general-fields">
                        <div class="form-group">
                            <label for="editTelephone">Téléphone</label>
                            <input type="tel" id="editTelephone" name="telephone">
                        </div>

                        <div class="form-group">
                            <label for="editGrade">Grade</label>
                            <input type="text" id="editGrade" name="grade">
                        </div>
                        
                        <div class="form-group">
                            <label for="editFiliereEns">Filière</label>
                            <select id="editFiliereEns" name="filiere_ens">
                                <option value="">Sélectionner une filière</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="editNiveauEns">Niveau</label>
                            <select id="editNiveauEns" name="niveau_ens">
                                <option value="">Sélectionner un niveau</option>
                                <option value="L1">L1</option>
                                <option value="L2">L2</option>
                                <option value="L3">L3</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="editSemestreEns">Semestre</label>
                            <select id="editSemestreEns" name="simestre_ens">
                                <option value="">Sélectionner un semestre</option>
                                <option value="1">Semestre 1</option>
                                <option value="2">Semestre 2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="editMatiereEns">Matière</label>
                            <select id="editMatiereEns" name="matiere_ens">
                                <option value="">Sélectionner une matière</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="editHeure">Nombre d'heures</label>
                            <input type="number" name="heure" id="editHeure">
                        </div>
                        
                        <div class="form-group">
                            <label for="editStatut">Statut</label>
                            <select id="editStatut" name="statut">
                                <option value="">Sélectionner un statut</option>
                                <option value="Permanent">Permanent</option>
                                <option value="Vacataire">Vacataire</option>
                            </select>
                        </div>
                    </div>

                    <div id="editAssistantFields" class="role-specific-fields  edit-general-fields">
                        <div class="form-group">
                            <label for="editDepartementAss">Département</label>
                            <select id="editDepartementAss" name="departement_ass">
                                <option value="">Sélectionner une departement</option>
                                <!-- Les options seront ajoutées dynamiquement -->
                            </select>
                        </div>
                        <div class="form-group  edit-general-fields">
                            <label for="editNiveauAss">Niveau</label>
                            <select id="editNiveauAss" name="niveau_ass">
                                <option value="L1">L1</option>
                                <option value="L2">L2</option>
                                <option value="L3">L3</option>
                            </select>
                        </div>
                    </div>

                    <div id="editfilieresFields" class="role-specific-fields">
                        <div class="form-group">
                            <label for="editFiliereSelect">Filiere</label>
                            <select id="editFiliereSelect" name="filiere">
                                <option value="">Sélectionner une filière</option>
                                <!-- Les options seront ajoutées dynamiquement -->
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="editFiliereRes">Responsable(Assistant)</label>
                            <select id="editFiliereRes" name="filiere_res">
                                <option value="">Sélectionner un responsable</option>
                                <!-- Les options seront ajoutées dynamiquement -->
                            </select>
                        </div>
                    </div>

                    <!-- Pour les matières -->
                    <div id="editmatieresFields" class="role-specific-fields">
                        <div class="form-group">
                            <label for="editNomMatiere">Nom matiere</label>
                            <input type="text" id="editNomMatiere" name="nom_matiere">
                        </div>
                        
                        <div class="form-group">
                            <label for="editFiliereMat">Filière</label>
                            <select id="editFiliereMat" name="filiere_mat">
                                <option value="">Sélectionner une filière</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="editNiveauMat">Niveau</label>
                            <select id="editNiveauMat" name="niveau_ens">
                                <option value="">Sélectionner un niveau</option>
                                <option value="L1">L1</option>
                                <option value="L2">L2</option>
                                <option value="L3">L3</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="editSemestreMat">Semestre</label>
                            <select id="editSemestreMat" name="simestre_ens">
                                <option value="">Sélectionner un semestre</option>
                                <option value="1">Semestre 1</option>
                                <option value="2">Semestre 2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="editCoefficient">Coefficient</label>
                            <input type="number" id="editCoefficient" name="coefficient">
                        </div>
                    </div>
                                        
                    <div class="form-group">
                        <label for="editRole">Rôle</label>
                        <select id="editRole" name="role" required>
                            <option value="etudiant">Étudiant</option>
                            <option value="enseignant">Enseignant</option>
                            <option value="assistant">Assistant</option>
                            <option value="president">Président de Jury</option>
                            <option value="directeur">Directeur d'Études</option>
                            <option value="filieres">Filiere</option>
                            <option value="matieres">Matiere</option>
                            <option value="doyen">Doyen</option>
                        </select>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="cancelEditBtn">Annuler</button>
                        <button type="submit" class="btn btn-success">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Fonction pour le formulaire d'ajout
            function updateAddRoleFields(role) {
                // Masquer et désactiver tous les champs spécifiques
                document.querySelectorAll('#addUserForm .role-specific-fields').forEach(field => {
                    field.style.display = 'none';
                    const inputs = field.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        input.disabled = true;
                        input.removeAttribute('required');
                    });
                });

                // Masquer et désactiver les champs généraux
                document.querySelectorAll('#addUserForm .general-fields').forEach(field => {
                    field.style.display = 'none';
                    const inputs = field.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        input.disabled = true;
                        input.removeAttribute('required');
                    });
                });
                
                // Afficher et activer les champs correspondants
                switch(role) {
                    case 'filieres':
                        document.getElementById('filieresFields').style.display = 'block';
                        const filieresInputs = document.querySelectorAll('#filieresFields input, #filieresFields select');
                        filieresInputs.forEach(input => {
                            input.disabled = false;
                            if (input.id === 'filiere' || input.id === 'filiere_res') {
                                input.setAttribute('required', 'required');
                            }
                        });
                        break;
                        
                    case 'matieres':
                        document.getElementById('matieresFields').style.display = 'block';
                        const matieresInputs = document.querySelectorAll('#matieresFields input, #matieresFields select');
                        matieresInputs.forEach(input => {
                            input.disabled = false;
                            if (input.id === 'nom_matiere' || input.id === 'filiere_mat' || 
                                input.id === 'niveau_mat' || input.id === 'simestre_mat') {
                                input.setAttribute('required', 'required');
                            }
                        });
                        break;
                        
                    default:
                        // Pour les autres rôles
                        document.querySelectorAll('#addUserForm .general-fields').forEach(field => {
                            field.style.display = 'block';
                            const inputs = field.querySelectorAll('input, select');
                            inputs.forEach(input => {
                                input.disabled = false;
                                if (input.id === 'nom' || input.id === 'prenom' || input.id === 'email' || 
                                    input.id === 'mot_de_passe' || input.id === 'date_naissance') {
                                    input.setAttribute('required', 'required');
                                }
                            });
                        });
                        
                        if (document.getElementById(role + 'Fields')) {
                            document.getElementById(role + 'Fields').style.display = 'block';
                            const roleInputs = document.querySelectorAll(`#${role}Fields input, #${role}Fields select`);
                            roleInputs.forEach(input => {
                                input.disabled = false;
                                // Ajouter les required pour les champs spécifiques si nécessaire
                            });
                        }
                        break;
                }
            }

            // Fonction pour le formulaire de modification
            function updateEditRoleFields(role) {
                // Masquer tous les champs spécifiques d'abord
                document.querySelectorAll('#editUserForm .role-specific-fields').forEach(field => {
                    field.style.display = 'none';
                    const inputs = field.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        input.disabled = true;
                        input.removeAttribute('required');
                    });
                });

                // Gérer l'affichage des champs généraux
                const generalFields = document.querySelectorAll('#editUserForm .general-fields');
                generalFields.forEach(field => {
                    // Afficher les champs généraux seulement pour les rôles utilisateur
                    if (role !== 'filieres' && role !== 'matieres') {
                        field.style.display = 'block';
                        const inputs = field.querySelectorAll('input, select');
                        inputs.forEach(input => {
                            input.disabled = false;
                            if (input.id === 'editNom' || input.id === 'editPrenom' || 
                                input.id === 'editEmail' || input.id === 'editDateNaissance') {
                                input.setAttribute('required', 'required');
                            }
                        });
                    } else {
                        field.style.display = 'none';
                        const inputs = field.querySelectorAll('input, select');
                        inputs.forEach(input => {
                            input.disabled = true;
                            input.removeAttribute('required');
                        });
                    }
                });

                // Afficher les champs spécifiques selon le rôle
                switch(role) {
                    case 'filieres':
                        document.getElementById('editfilieresFields').style.display = 'block';
                        const filieresInputs = document.querySelectorAll('#editfilieresFields input, #editfilieresFields select');
                        filieresInputs.forEach(input => {
                            input.disabled = false;
                            if (input.id === 'editFiliereSelect' || input.id === 'editFiliereRes') {
                                input.setAttribute('required', 'required');
                            }
                        });
                        break;
                        
                    case 'matieres':
                        document.getElementById('editmatieresFields').style.display = 'block';
                        const matieresInputs = document.querySelectorAll('#editmatieresFields input, #editmatieresFields select');
                        matieresInputs.forEach(input => {
                            input.disabled = false;
                            if (input.id === 'editNomMatiere' || input.id === 'editFiliereMat' || 
                                input.id === 'editNiveauMat' || input.id === 'editSemestreMat' || 
                                input.id === 'editCoefficient') {
                                input.setAttribute('required', 'required');
                            }
                        });
                        break;
                        
                    // Les autres cas restent inchangés...
                    case 'etudiant':
                        document.getElementById('editEtudiantFields').style.display = 'block';
                        const etudiantInputs = document.querySelectorAll('#editEtudiantFields input, #editEtudiantFields select');
                        etudiantInputs.forEach(input => {
                            input.disabled = false;
                            if (input.id === 'editFiliere' || input.id === 'editNiveauEtudiant') {
                                input.setAttribute('required', 'required');
                            }
                        });
                        break;
                        
                    case 'enseignant':
                        document.getElementById('editEnseignantFields').style.display = 'block';
                        const enseignantInputs = document.querySelectorAll('#editEnseignantFields input, #editEnseignantFields select');
                        enseignantInputs.forEach(input => {
                            input.disabled = false;
                            // Ajouter required aux champs obligatoires si nécessaire
                        });
                        break;
                        
                    case 'assistant':
                        document.getElementById('editAssistantFields').style.display = 'block';
                        const assistantInputs = document.querySelectorAll('#editAssistantFields input, #editAssistantFields select');
                        assistantInputs.forEach(input => {
                            input.disabled = false;
                            if (input.id === 'editDepartementAss' || input.id === 'editNiveauAss') {
                                input.setAttribute('required', 'required');
                            }
                        });
                        break;
                        
                    default: // Pour directeur, president, etc.
                        // Seuls les champs généraux sont affichés
                        break;
                }
            }

            
            // Écouteurs pour le formulaire d'ajout
            document.getElementById('role')?.addEventListener('change', function() {
                updateAddRoleFields(this.value);
            });
            if (document.getElementById('role')) {
                updateAddRoleFields(document.getElementById('role').value);
            }

            // Écouteurs pour le formulaire de modification
            document.getElementById('editRole')?.addEventListener('change', function() {
                updateEditRoleFields(this.value);
            });
            if (document.getElementById('editRole')) {
                updateEditRoleFields(document.getElementById('editRole').value);
            }

            // Gestion des filières et matières pour les enseignants (identique à la création)
            const editFiliereSelect = document.getElementById('editFiliereEns');
            const editNiveauSelect = document.getElementById('editNiveauEns');
            const editSemestreSelect = document.getElementById('editSemestreEns');
            
            if (editFiliereSelect && editNiveauSelect && editSemestreSelect) {
                editFiliereSelect.addEventListener('change', editGetMatieres);
                editNiveauSelect.addEventListener('change', editGetMatieres);
                editSemestreSelect.addEventListener('change', editGetMatieres);
                
                // Charger les filières
                editGetFilieres();
                editGetFilieresMatiere();
                loadAssistants();
            }
            
            function editGetFilieres() {
                return new Promise((resolve, reject) => {
                    fetch('get_filieres_matieres.php?action=get_filieres')
                        .then(response => response.json())
                        .then(data => {
                            // Remplir le select des enseignants
                            const enseignantSelect = document.getElementById('editFiliereEns');
                            enseignantSelect.innerHTML = '<option value="">Sélectionner une filière</option>';
                            
                            // Remplir le select des filières
                            const filiereSelect = document.getElementById('editFiliereSelect');
                            filiereSelect.innerHTML = '<option value="">Sélectionner une filière</option>';
                            
                            data.forEach(filiere => {
                                const option1 = document.createElement('option');
                                option1.value = filiere.id_filiere;
                                option1.textContent = filiere.nom_filiere;
                                enseignantSelect.appendChild(option1);
                                
                                const option2 = document.createElement('option');
                                option2.value = filiere.id_filiere;
                                option2.textContent = filiere.nom_filiere;
                                filiereSelect.appendChild(option2);
                            });
                            resolve();
                        })
                        .catch(error => {
                            console.error('Erreur lors du chargement des filières:', error);
                            reject(error);
                        });
                });
            }

            // Modifier les fonctions pour utiliser des Promises
            function editGetFilieresMatiere() {
                return new Promise((resolve, reject) => {
                    fetch('get_filieres_matieres.php?action=get_filieres')
                        .then(response => response.json())
                        .then(data => {
                            const select = document.getElementById('editFiliereMat');
                            select.innerHTML = '<option value="">Sélectionner une filière</option>';
                            
                            data.forEach(filiere => {
                                const option = document.createElement('option');
                                option.value = filiere.id_filiere;
                                option.textContent = filiere.nom_filiere;
                                select.appendChild(option);
                            });
                            resolve();
                        })
                        .catch(error => {
                            console.error('Erreur lors du chargement des filières:', error);
                            reject(error);
                        });
                });
            }
            function editGetMatieres() {
                return new Promise((resolve, reject) => {
                    const filiereId = document.getElementById('editFiliereEns').value;
                    const niveau = document.getElementById('editNiveauEns').value;
                    const semestre = document.getElementById('editSemestreEns').value;
                    
                    if (filiereId && niveau && semestre) {
                        const url = `get_filieres_matieres.php?action=get_matieres&filiere_id=${filiereId}&niveau=${niveau}&semestre=${semestre}`;
                        
                        fetch(url)
                            .then(response => response.json())
                            .then(data => {
                                const select = document.getElementById('editMatiereEns');
                                select.innerHTML = '<option value="">Sélectionner une matière</option>';
                                
                                data.forEach(matiere => {
                                    const option = document.createElement('option');
                                    option.value = matiere.id_matiere;
                                    option.textContent = matiere.nom_matiere;
                                    select.appendChild(option);
                                });
                                resolve();
                            })
                            .catch(error => {
                                console.error('Erreur lors du chargement des matières:', error);
                                reject(error);
                            });
                    } else {
                        document.getElementById('editMatiereEns').innerHTML = '<option value="">Sélectionner une matière</option>';
                        resolve();
                    }
                });
            }

            // Fonction pour charger les assistants avec Promise
            function loadAssistants() {
                return new Promise((resolve, reject) => {
                    fetch('get_filieres_matieres.php?action=get_assistants')
                        .then(response => response.json())
                        .then(data => {
                            const select = document.getElementById('editFiliereRes');
                            select.innerHTML = '<option value="">Sélectionner un responsable</option>';
                            
                            data.forEach(assistant => {
                                const option = document.createElement('option');
                                option.value = assistant.id_assistant;
                                option.textContent = assistant.nom_complet;
                                select.appendChild(option);
                            });
                            resolve();
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            reject(error);
                        });
                });
            }

            // Dans votre code JavaScript existant, ajoutez ceci :
            // Remplacer le code actuel du gestionnaire d'événement pour le bouton "Modifier" par celui-ci:

            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    
                    // Détecter le rôle actif en fonction de la structure du tableau
                    let activeRole;
                    
                    // Vérifier la structure du tableau pour déterminer le rôle
                    const tableHeaders = document.querySelector('table thead tr');
                    if (tableHeaders) {
                        const headers = tableHeaders.querySelectorAll('th');
                        const headerText = Array.from(headers).map(th => th.textContent.trim());
                        
                        if (headerText.includes('Nom de la filière')) {
                            activeRole = 'filieres';
                        } else if (headerText.includes('Nom de la matière') && headerText.includes('Coefficient')) {
                            activeRole = 'matieres';
                        } else {
                            // Par défaut, c'est un utilisateur
                            activeRole = document.querySelector('input[name="role"]')?.value || 'etudiant';
                        }
                    }
                    
                    // Récupérer les données selon le rôle
                    let url = 'get_user_data.php?action=get&id=' + id;
                    if (activeRole === 'filieres') {
                        url = 'get_filieres_matieres.php?action=get_filiere&id=' + id;
                    } else if (activeRole === 'matieres') {
                        url = 'get_filieres_matieres.php?action=get_matiere&id=' + id;
                    }
                    
                    console.log("URL de récupération:", url); // Debugging
                    
                    fetch(url)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Erreur réseau: ' + response.status);
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Ajouter le rôle aux données si ce n'est pas déjà fait
                            if (!data.role) {
                                data.role = activeRole;
                            }
                            
                            console.log("Données à remplir:", data); // Debugging
                            
                            // Remplir le formulaire
                            fillEditForm(data);
                            
                            // Afficher la modal
                            document.getElementById('editModal').style.display = 'block';
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            alert("Erreur lors du chargement des données: " + error.message);
                        });
                });
            });

            // Ajouter ces fonctions de débogage pour voir les valeurs récupérées
            // Juste avant le début de la fonction fillEditForm, ajouter:

            function logFormFieldsStatus() {
                console.log("État des champs du formulaire enseignant:");
                console.log("editFiliereEns:", document.getElementById('editFiliereEns').value);
                console.log("editNiveauEns:", document.getElementById('editNiveauEns').value);
                console.log("editSemestreEns:", document.getElementById('editSemestreEns').value);
                console.log("editMatiereEns:", document.getElementById('editMatiereEns').value);
                console.log("editHeure:", document.getElementById('editHeure').value);
                console.log("editStatut:", document.getElementById('editStatut').value);
            }

            // Et dans le cas 'enseignant', après toutes les opérations, ajouter:
            setTimeout(logFormFieldsStatus, 1000);
            
            // Fonction pour remplir le formulaire avec les données de l'utilisateur
            // Modifier la fonction fillEditForm pour corriger la gestion des matières et filières
            // Fonction pour remplir le formulaire de modification
            window.fillEditForm = function(userData) {
                console.log("Données reçues:", userData); // Debugging
                
                // Définir l'ID selon le type d'objet
                if (userData.id_personne) {
                    document.getElementById('editId').value = userData.id_personne;
                } else if (userData.id_filiere) {
                    document.getElementById('editId').value = userData.id_filiere;
                } else if (userData.id_matiere) {
                    document.getElementById('editId').value = userData.id_matiere;
                }
                
                // Définir le rôle
                if (userData.role) {
                    document.getElementById('editRole').value = userData.role;
                }
                
                // Remplir les champs généraux pour les utilisateurs
                if (userData.role !== 'filieres' && userData.role !== 'matieres') {
                    if (userData.nom) document.getElementById('editNom').value = userData.nom;
                    if (userData.prenom) document.getElementById('editPrenom').value = userData.prenom;
                    if (userData.email) document.getElementById('editEmail').value = userData.email;
                    if (userData.dateNaissance) document.getElementById('editDateNaissance').value = userData.dateNaissance;
                }
                
                // Remplir les champs spécifiques selon le rôle
                switch(userData.role) {
                    case 'etudiant':
                        if (userData.filiere) document.getElementById('editFiliere').value = userData.filiere;
                        if (userData.niveau_filiere) document.getElementById('editNiveauEtudiant').value = userData.niveau_filiere;
                        break;
                        
                    case 'enseignant':
                        if (userData.telephone) document.getElementById('editTelephone').value = userData.telephone;
                        if (userData.specialite) document.getElementById('editGrade').value = userData.specialite;
                        if (userData.statut) document.getElementById('editStatut').value = userData.statut;
                        if (userData.nb_heure) document.getElementById('editHeure').value = userData.nb_heure;

                        // Charger les filières d'abord
                        editGetFilieres().then(() => {
                            if (userData.id_filiere) {
                                // Sélectionner la filière
                                const filiereSelect = document.getElementById('editFiliereEns');
                                setTimeout(() => {
                                    filiereSelect.value = userData.id_filiere;
                                    
                                    // Puis sélectionner le niveau
                                    if (userData.niveau_filiere) {
                                        document.getElementById('editNiveauEns').value = userData.niveau_filiere;
                                        
                                        // Puis sélectionner le semestre
                                        if (userData.type_simestre) {
                                            document.getElementById('editSemestreEns').value = userData.type_simestre;
                                            
                                            // Charger les matières après avoir défini filière, niveau et semestre
                                            editGetMatieres().then(() => {
                                                if (userData.id_matiere) {
                                                    setTimeout(() => {
                                                        document.getElementById('editMatiereEns').value = userData.id_matiere;
                                                    }, 300);
                                                }
                                            });
                                        }
                                    }
                                }, 300);
                            }
                        });
                        break;

                    case 'assistant':
                        if (userData.departement) document.getElementById('editDepartementAss').value = userData.departement;
                        if (userData.niveau) document.getElementById('editNiveauAss').value = userData.niveau;
                        break;
                        
                    case 'filieres':
                        // Utiliser la même fonction que pour l'enseignant pour charger les filières
                        editGetFilieres().then(() => {
                            // Sélectionner la filière après le chargement
                            if (userData.id_filiere) {
                                const filiereSelect = document.getElementById('editFiliereSelect');
                                setTimeout(() => {
                                    filiereSelect.value = userData.id_filiere;
                                    
                                    // Charger les assistants après avoir sélectionné la filière
                                    loadAssistants().then(() => {
                                        if (userData.responsable_id) {
                                            document.getElementById('editFiliereRes').value = userData.responsable_id;
                                        }
                                    });
                                }, 300);
                            }
                        });
                        break;
                            
                    case 'matieres':
                        if (userData.nom_matiere) document.getElementById('editNomMatiere').value = userData.nom_matiere;
                        if (userData.coeff) document.getElementById('editCoefficient').value = userData.coeff;
                        
                        // Charger les filières avant de définir les autres valeurs
                        editGetFilieresMatiere().then(() => {
                            setTimeout(() => {
                                if (userData.id_filiere) document.getElementById('editFiliereMat').value = userData.id_filiere;
                                if (userData.niveau_filiere) document.getElementById('editNiveauMat').value = userData.niveau_filiere;
                                if (userData.type_simestre) document.getElementById('editSemestreMat').value = userData.type_simestre;
                            }, 300);
                        });
                        break;
                }
                
                // Mettre à jour l'affichage des champs selon le rôle
                updateEditRoleFields(userData.role);
            };

            // Fonction pour formater la date au format YYYY-MM-DD
            function formatDateForInput(dateString) {
                if (!dateString) return '';
                
                // Si la date est déjà au bon format
                if (dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
                    return dateString;
                }
                
                // Si c'est un timestamp ou autre format
                const date = new Date(dateString);
                if (isNaN(date.getTime())) {
                    console.error('Date invalide:', dateString);
                    return '';
                }
                
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                
                return `${year}-${month}-${day}`;
            }

            // Fonction pour charger les filières (utilisée par tous les rôles)
            function editGetFilieres() {
                return new Promise((resolve, reject) => {
                    fetch('get_filieres_matieres.php?action=get_filieres')
                        .then(response => response.json())
                        .then(data => {
                            // Liste de tous les selects de filières à mettre à jour
                            const filiereSelects = [
                                'editFiliere',         // Étudiant
                                'editFiliereEns',      // Enseignant
                                'editDepartementAss',  // Assistant (département)
                                'editFiliereSelect',   // Filières (admin)
                                'editFiliereMat'       // Matières
                            ];
                            
                            // Mettre à jour tous les selects
                            filiereSelects.forEach(selector => {
                                const select = document.getElementById(selector);
                                if (select) {
                                    select.innerHTML = '<option value="">Sélectionner une filière</option>';
                                    
                                    data.forEach(filiere => {
                                        const option = document.createElement('option');
                                        option.value = filiere.id_filiere;
                                        option.textContent = filiere.nom_filiere;
                                        select.appendChild(option);
                                    });
                                }
                            });
                            
                            resolve();
                        })
                        .catch(error => {
                            console.error('Erreur lors du chargement des filières:', error);
                            reject(error);
                        });
                });
            }

        });
    </script>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Confirmer la suppression</h2>
                <button type="button" class="close-btn" id="closeDeleteModal">&times;</button>
            </div>
            <p id="deleteMessage">Êtes-vous sûr de vouloir supprimer cet utilisateur ?</p>
            <form id="deleteUserForm" method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="role" id="deleteRole">
                <input type="hidden" name="id" id="deleteId">
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="cancelDeleteBtn">Annuler</button>
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Progression Chart - Diagramme en barres
        const progressionCtx = document.getElementById('progressionChart').getContext('2d');
        const progressionChart = new Chart(progressionCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_unique(array_column(isset($progressionData['etudiant']) ? $progressionData['etudiant'] : [], 'mois'))) ?>,
                datasets: [
                    {
                        label: 'Étudiants',
                        data: <?= json_encode(array_column(isset($progressionData['etudiant']) ? $progressionData['etudiant'] : [], 'count')) ?>,
                        backgroundColor: '#3498db',
                        borderColor: '#2980b9',
                        borderWidth: 1
                    },
                    {
                        label: 'Enseignants',
                        data: <?= json_encode(array_column(isset($progressionData['enseignant']) ? $progressionData['enseignant'] : [], 'count')) ?>,
                        backgroundColor: '#2ecc71',
                        borderColor: '#27ae60',
                        borderWidth: 1
                    },
                    {
                        label: 'Assistants',
                        data: <?= json_encode(array_column(isset($progressionData['assistant']) ? $progressionData['assistant'] : [], 'count')) ?>,
                        backgroundColor: '#f39c12',
                        borderColor: '#e67e22',
                        borderWidth: 1
                    },
                    {
                        label: 'Matières',
                        data: <?= json_encode(array_column(isset($progressionData['matieres']) ? $progressionData['matieres'] : [], 'count')) ?>,
                        backgroundColor: '#9b59b6',
                        borderColor: '#8e44ad',
                        borderWidth: 1
                    },
                    {
                        label: 'Filières',
                        data: <?= json_encode(array_column(isset($progressionData['filieres']) ? $progressionData['filieres'] : [], 'count')) ?>,
                        backgroundColor: '#e74c3c',
                        borderColor: '#c0392b',
                        borderWidth: 1
                    },
                    {
                        label: 'Facultés',
                        data: <?= json_encode(array_column(isset($progressionData['facultes']) ? $progressionData['facultes'] : [], 'count')) ?>,
                        backgroundColor: '#1abc9c',
                        borderColor: '#16a085',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Nombre d'inscriptions/ajouts",
                            font: { weight: 'bold' }
                        },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Mois',
                            font: { weight: 'bold' }
                        },
                        grid: { display: false },
                        ticks: {
                            callback: function(value) {
                                const [year, month] = this.getLabelForValue(value).split('-');
                                return `${month}/${year.substr(2)}`; // Format MM/AA
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            padding: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                const [year, month] = context[0].label.split('-');
                                const monthNames = [
                                    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                                    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
                                ];
                                return `${monthNames[parseInt(month)-1]} ${year}`;
                            },
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}`;
                            }
                        }
                    }
                },
                // Configuration spécifique aux barres
                barPercentage: 0.8,
                categoryPercentage: 0.9
            }
        });
    </script>

    <script>
        $(document).ready(function() {
            // Afficher le modal d'ajout
            $("#addUserBtn").click(function() {
                $("#addModal").css("display", "flex");
            });
            
            // Fermer le modal d'ajout
            $("#closeAddModal, #cancelAddBtn").click(function() {
                $("#addModal").hide();
            });
            
            // Gérer le modal de modification
            $(".edit-btn").click(function() {
                const id = $(this).data("id");
                const nom = $(this).data("nom");
                const prenom = $(this).data("prenom");
                const email = $(this).data("email");
                const role = $(this).data("role");
                
                $("#editId").val(id);
                $("#editNom").val(nom);
                $("#editPrenom").val(prenom);
                $("#editEmail").val(email);
                $("#editRole").val(role);
                
                $("#editModal").css("display", "flex");
            });
            
            // Fermer le modal de modification
            $("#closeEditModal, #cancelEditBtn").click(function() {
                $("#editModal").hide();
            });
            
            // Gérer le modal de suppression
            $(".delete-btn").click(function() {
                const id = $(this).data("id");
                const nom = $(this).data("nom");
                const prenom = $(this).data("prenom");
                const role = $(this).data("role");
                
                $("#deleteId").val(id);
                $("#deleteRole").val(role); // l'ajouter dans le champ caché
                // Si prenom vide (filière/matière), afficher uniquement nom
                if (prenom) {
                    $("#deleteMessage").text(`Êtes-vous sûr de vouloir supprimer l'utilisateur ${prenom} ${nom} ?`);
                } else {
                    $("#deleteMessage").text(`Êtes-vous sûr de vouloir supprimer ${nom} ?`);
                }
                $("#deleteModal").css("display", "flex");
            });
            
            // Fermer le modal de suppression
            $("#closeDeleteModal, #cancelDeleteBtn").click(function() {
                $("#deleteModal").hide();
            });
            
            // Toggle pour mobile
            $(".mobile-toggle").click(function() {
                $(".sidebar").toggleClass("active");
            });
            
            // Fermer le sidebar en mode mobile quand on clique sur un lien
            $(".nav-link").click(function() {
                if ($(window).width() <= 768) {
                    $(".sidebar").removeClass("active");
                }
            });
            
            // Stats link (simulé ici, vous pouvez l'implémenter comme une vue AJAX)
            $("#stats-link").click(function(e) {
                e.preventDefault();
                window.location.href = "?role=all";
            });
            
            // Fermer les alerts après 5 secondes
            setTimeout(function() {
                $(".alert").fadeOut('slow');
            }, 5000);
        });

        // Gestion de l'affichage des champs spécifiques
        function toggleRoleFields(role) {
            $('.role-specific-fields').hide();
            switch(role) {
                case 'etudiant':
                    $('#etudiantFields').show();
                    break;
                case 'enseignant':
                    $('#enseignantFields').show();
                    break;
                case 'assistant':
                    $('#assistantFields').show();
                    break;
                case 'filieres':
                $('#filieresFields').show();
                break;
                case 'matieres':
                $('#matieresFields').show();
                break;
                default:
                    $('.role-specific-fields').hide();
            }
        }

        $('#role, #editRole').change(function() {
            toggleRoleFields($(this).val());
        });

        // Au chargement initial
        toggleRoleFields($('#role').val());
    </script>
</body>
</html>
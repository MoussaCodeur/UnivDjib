<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: Connexion.php");
    exit();
}

// Paramètres de connexion à la base de données
$servername = "localhost";
$username = "root";
$password = ""; // Mot de passe vide
$database = "gestioncouruniversitaire";

// Connexion au serveur MySQL
$conn = new mysqli($servername, $username, $password, $database);

// Initialisation des variables de recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$activeRole = isset($_GET['role']) ? $_GET['role'] : 'all';


// Vérification de la connexion
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

// Récupérer l'ID de l'utilisateur depuis la session
$userID = $_SESSION['user_id'];

// Requête pour obtenir les informations de l'utilisateur// Modifier la requête SQL
$sql = "SELECT 
        p.*, 
        e.id_filiere AS etudiant_filiere_id, 
        fe.nom_filiere AS etudiant_filiere_nom, 
        e.niveau_filiere AS etudiant_niveau,
        en.telephone AS enseignant_telephone,
        ens.id_filiere AS enseignant_filiere_id,
        ff.nom_filiere AS enseignant_filiere_nom,
        a.departement AS assistant_departement,
        a.niveau AS assistant_niveau
        FROM personne p
        LEFT JOIN etudiant e ON p.id_personne = e.id_etudiant
        LEFT JOIN filiere fe ON e.id_filiere = fe.id_filiere
        LEFT JOIN enseignant en ON p.id_personne = en.id_enseignant
        LEFT JOIN enseigner ens ON en.id_enseignant = ens.id_enseignant
        LEFT JOIN filiere ff ON ens.id_filiere = ff.id_filiere
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
    
    if ($action == 'create') {
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
        $date_inscription = date('Y-m-d H:i:s');
        $date_naissance = $_POST['date_naissance'];
    
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
                    $filiere = $_POST['filiere'];
                    $niveau = $_POST['niveau_etudiant'];
                    $statut = "En attente";
                    
                    // Récupérer l'ID de la filière à partir du nom
                    $sql_get_filiere = "SELECT id_filiere FROM filiere WHERE nom_filiere = $filiere";
                    $stmt_get_filiere = $conn->prepare($sql_get_filiere);
                    $stmt_get_filiere->bind_param("s", $filiere);
                    $stmt_get_filiere->execute();
                    $result_filiere = $stmt_get_filiere->get_result();
                    
                    if ($result_filiere->num_rows > 0) {
                        $row_filiere = $result_filiere->fetch_assoc();
                        $filiere_id = $row_filiere['id_filiere'];
                    } else {
                        // Si la filière n'existe pas, utiliser une valeur par défaut ou générer une erreur
                        $filiere_id = null;
                    }
                    $stmt_get_filiere->close();
                    
                    $sql_etudiant = "INSERT INTO etudiant (id_etudiant, id_filiere, niveau_filiere, statut) VALUES (?, ?, ?, ?)";
                    $stmt_etudiant = $conn->prepare($sql_etudiant);
                    $stmt_etudiant->bind_param("iiss", $id_personne, $filiere_id, $niveau, $statut);
                    $stmt_etudiant->execute();
                    $stmt_etudiant->close();
                    break;
    
                    case 'enseignant':
                        $telephone = $_POST['telephone'];
                        $specialite = $_POST['grade'];
                        $filiere_id = $_POST['filiere_ens'];
                        $niveau = $_POST['niveau_ens'];
                        $semestre = $_POST['simestre_ens'];
                        $matiere_id = $_POST['matiere_ens'];
                        $statut = $_POST['statut'];
                        $nb_heure = 30; // ou selon votre logique métier
                    
                        // Insertion dans `enseignant`
                        $sql_ens = "INSERT INTO enseignant (id_enseignant, specialite, telephone, statut) 
                                    VALUES (?, ?, ?, ?)";
                        $stmt_ens = $conn->prepare($sql_ens);
                        $stmt_ens->bind_param("isss", $id_personne, $specialite, $telephone, $statut);
                        $stmt_ens->execute();
                        $stmt_ens->close();
                    
                        // Insertion dans `enseigner`
                        $sql_enseigner = "INSERT INTO enseigner (id_enseignant, id_matiere, id_filiere, niveau_filiere, nb_heure, type_semestre) 
                                          VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt_enseigner = $conn->prepare($sql_enseigner);
                        $stmt_enseigner->bind_param("iiisis", $id_personne, $matiere_id, $filiere_id, $niveau, $nb_heure, $semestre);
                        $stmt_enseigner->execute();
                        $stmt_enseigner->close();
                        break;
                    
                
                case 'assistant':
                    $departement = $_POST['departement_ass'];
                    $niveau = $_POST['niveau_ass'];
                    $sql_assistant = "INSERT INTO assistant (id_assistant, departement, niveau) VALUES (?, ?, ?)";
                    $stmt_assistant = $conn->prepare($sql_assistant);
                    $stmt_assistant->bind_param("iss", $id_personne, $departement, $niveau);
                    $stmt_assistant->execute();
                    $stmt_assistant->close();
                    break;
                    
                case 'president':
                    $sql_president = "INSERT INTO president_jury (id_president) VALUES (?)";
                    $stmt_president = $conn->prepare($sql_president);
                    $stmt_president->bind_param("i", $id_personne);
                    $stmt_president->execute();
                    $stmt_president->close();
                    break;
    
                case 'directeur':
                    // Si une table directeur existe, à insérer ici. Sinon, inséré uniquement dans `personne`.
                    break;
            }
    
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=create");
            exit();
        } else {
            $error = "Erreur lors de l'ajout: " . $stmt->error;
        }
        $stmt->close();
    }
    
    
    // Mettre à jour un utilisateur existant
    else if ($action == 'update') {
    $id = $_POST['id'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $date_naissance = $_POST['date_naissance'];

    try {
        // Commencer une transaction
        $conn->begin_transaction();
        
        // Mise à jour du mot de passe si fourni
        if (!empty($_POST['mot_de_passe'])) {
            $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
            $sql = "UPDATE personne SET 
                    prenom=?, nom=?, email=?, date_naissance=?, role=?, mot_de_passe=?
                    WHERE id_personne=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $prenom, $nom, $email, $date_naissance, $role, $mot_de_passe, $id);
        } else {
            // Mise à jour sans mot de passe
            $sql = "UPDATE personne SET 
                    prenom=?, nom=?, email=?, date_naissance=?, role=?
                    WHERE id_personne=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $prenom, $nom, $email, $date_naissance, $role, $id);
        }
        $stmt->execute();
    
        // Vérifier d'abord si l'utilisateur a changé de rôle
        $sql = "SELECT role FROM personne WHERE id_personne = ? AND role != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $id, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Si l'utilisateur a changé de rôle, supprimer les anciennes données spécifiques au rôle
        if ($result->num_rows > 0) {
            $old_role = $result->fetch_assoc()['role'];
            
            // Supprimer les anciennes données de rôle
            switch($old_role) {
                case 'etudiant':
                    $sql = "DELETE FROM etudiant WHERE id_personne = ?";
                    break;
                case 'enseignant':
                    $sql = "DELETE FROM enseignant WHERE id_personne = ?";
                    break;
                case 'assistant':
                    $sql = "DELETE FROM assistant WHERE id_personne = ?";
                    break;
                // Ajouter d'autres cas si nécessaire pour president et directeur
            }
            
            if (isset($sql)) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
        }
    
        // Mise à jour des tables spécifiques selon le rôle
        switch($role) {
            case 'etudiant':
                $filiere = $_POST['filiere'];
                $niveau = $_POST['niveau'];
                
                $sql = "INSERT INTO etudiant (id_personne, filiere, niveau)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        filiere=VALUES(filiere), niveau=VALUES(niveau)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $id, $filiere, $niveau);
                break;
                
            case 'enseignant':
                $telephone = $_POST['telephone'];
                $departement = $_POST['departement'];
                
                $sql = "INSERT INTO enseignant (id_personne, telephone, departement)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        telephone=VALUES(telephone), departement=VALUES(departement)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $id, $telephone, $departement);
                break;
                
            case 'assistant':
                $departement = $_POST['departement'];
                $niveau = $_POST['niveau'] ;
                
                $sql = "INSERT INTO assistant (id_personne, departement, niveau)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        departement=VALUES(departement), niveau=VALUES(niveau)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $id, $departement, $niveau);
                break;
                
            // Ajouter d'autres cas pour president et directeur si nécessaire
        }
        
        // Exécuter la requête spécifique au rôle si définie
        if (isset($sql)) {
            $stmt->execute();
        }
        
        // Valider la transaction
        $conn->commit();
        
        // Rediriger avec un message de succès
        header("Location: ".$_SERVER['PHP_SELF']."?success=update");
        exit();
    
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $conn->rollback();
        
        $error = "Erreur lors de la mise à jour : " . $e->getMessage();
        // Afficher l'erreur ou la logger
    }
    }

    
    // Supprimer un utilisateur
    else if ($action == 'delete') {
        $id = $_POST['id'];
        
        $sql = "DELETE FROM personne WHERE id_personne=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            header("Location: ".$_SERVER['PHP_SELF']."?success=delete");
            exit();
        } else {
            $error = "Erreur lors de la suppression: " . $stmt->error;
        }
        $stmt->close();
    }
}

function loadUsers($conn, $role = null, $page = 1, $perPage = 10, $search = '') {
    $offset = ($page - 1) * $perPage;
    $params = [];
    $types = '';
    
    $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM personne WHERE 1=1";
    
    if ($role && $role != 'all') {
        $sql .= " AND role = ?";
        $params[] = $role;
        $types .= 's';
    }
    
    if (!empty($search)) {
        $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'sss';
    }
    
    $sql .= " ORDER BY nom, prenom LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($sql);
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
    $conn = new mysqli($servername, $username, $password, $database);
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
$perPage = 10;
$result = loadUsers($conn, $activeRole, $page, $perPage, $search);
$users = $result['users'];
$totalUsers = $result['total'];
$totalPages = ceil($totalUsers / $perPage);

// Préparation des statistiques
$roleStats = [
    'etudiant' => ['icon' => 'fa-user-graduate', 'color' => 'blue', 'count' => 0],
    'enseignant' => ['icon' => 'fa-chalkboard-teacher', 'color' => 'green', 'count' => 0],
    'assistant' => ['icon' => 'fa-user-friends', 'color' => 'orange', 'count' => 0],
    'president' => ['icon' => 'fa-user-tie', 'color' => 'red', 'count' => 0],
    'directeur' => ['icon' => 'fa-university', 'color' => 'teal', 'count' => 0],
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
    <title>Interface Administrateur</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #1abc9c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --text-color: #333;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow-sm: 0 2px 5px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 10px rgba(0,0,0,0.12);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
            --radius-sm: 4px;
            --radius-md: 8px;
            --radius-lg: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: var(--text-color);
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--primary-color);
            color: white;
            padding: 1.5rem 1rem;
            position: fixed;
            height: 100%;
            transition: var(--transition);
            z-index: 100;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }

        .sidebar-header {
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .admin-profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .admin-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--secondary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .admin-avatar::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 30%;
            background: rgba(0,0,0,0.2);
        }

        .admin-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .admin-info p {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .nav-list {
            list-style: none;
            margin-top: 2rem;
        }

        .nav-item {
            margin: 0.5rem 0;
            border-radius: var(--radius-md);
            transition: var(--transition);
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.2rem;
            color: white;
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: var(--transition);
        }

        .nav-link i {
            min-width: 1.5rem;
            margin-right: 0.8rem;
            font-size: 1.1rem;
        }

        .nav-item:hover .nav-link,
        .nav-item.active .nav-link {
            background: var(--secondary-color);
            transform: translateX(5px);
        }

        .logout-section {
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 1rem 1.2rem;
            color: white;
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: var(--transition);
            background: rgba(255,255,255,0.05);
        }

        .logout-btn i {
            min-width: 1.5rem;
            margin-right: 0.8rem;
            font-size: 1.1rem;
        }

        .logout-btn:hover {
            background: var(--danger-color);
        }

        /* Main content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            transition: var(--transition);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        /* Cards */
        .card {
            background: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            padding: 1.5rem;
            margin-bottom: 2rem;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--shadow-md);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        /* Table */
        .table-container {
            overflow-x: auto;
            border-radius: var(--radius-md);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            background: var(--primary-color);
            color: white;
        }

        th, td {
            padding: 1rem;
            text-align: left;
        }

        tbody tr {
            border-bottom: 1px solid #eee;
            transition: var(--transition);
        }

        tbody tr:hover {
            background: #f9f9f9;
        }

        /* Actions */
        .actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }

        .btn-success {
            background: var(--accent-color);
            color: white;
        }

        .btn-success:hover {
            background: #27ae60;
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
            box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3);
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
            box-shadow: 0 4px 10px rgba(243, 156, 18, 0.3);
        }

        /* Forms */
        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: var(--radius-md);
            background: #f9f9f9;
            transition: var(--transition);
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2rem;
            width: 100%;
            max-width: 600px;
            box-shadow: var(--shadow-lg);
            animation: scaleIn 0.3s ease;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #aaa;
            transition: var(--transition);
        }

        .close-btn:hover {
            color: var(--danger-color);
        }

        .modal-footer {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes scaleIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        /* Alert messages */
        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .alert-success {
            background: rgba(46, 204, 113, 0.2);
            border-left: 4px solid var(--accent-color);
            color: #27ae60;
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.2);
            border-left: 4px solid var(--danger-color);
            color: #c0392b;
        }

        .alert-warning {
            background: rgba(243, 156, 18, 0.2);
            border-left: 4px solid var(--warning-color);
            color: #e67e22;
        }

        /* Tab content for different user roles */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        /* Badge for role */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-primary {
            background: rgba(52, 152, 219, 0.2);
            color: var(--secondary-color);
        }

        .badge-success {
            background: rgba(46, 204, 113, 0.2);
            color: var(--accent-color);
        }

        .badge-warning {
            background: rgba(243, 156, 18, 0.2);
            color: var(--warning-color);
        }

        .badge-danger {
            background: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
        }

        .badge-info {
            background: rgba(26, 188, 156, 0.2);
            color: var(--info-color);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 250px;
            }
            
            .main-content {
                margin-left: 250px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-toggle {
                display: block;
                position: fixed;
                top: 1rem;
                left: 1rem;
                background: var(--primary-color);
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 999;
                box-shadow: var(--shadow-md);
                cursor: pointer;
            }
        }

        /* Loader */
        .loader {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: var(--secondary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius-md);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-icon.blue {
            background: rgba(52, 152, 219, 0.2);
            color: var(--secondary-color);
        }

        .stat-icon.green {
            background: rgba(46, 204, 113, 0.2);
            color: var(--accent-color);
        }

        .stat-icon.orange {
            background: rgba(243, 156, 18, 0.2);
            color: var(--warning-color);
        }

        .stat-icon.red {
            background: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
        }

        .stat-icon.teal {
            background: rgba(26, 188, 156, 0.2);
            color: var(--info-color);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        /* Profile initials */
        .initials {
            font-size: 1.8rem;
            font-weight: 600;
            color: white;
        }

        /* Nouveaux styles */
        .search-card {
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .search-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .search-container {
            position: relative;
            width: 100%;
        }

        .search-input-group {
            display: flex;
            gap: 8px;
            position: relative;
        }

        .search-input {
            flex: 1;
            width: 320px;
            padding: 14px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #ffffff;
            color: #2d3748;
        }

        .search-input:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
        }

        .search-button {
            display: inline-flex;
            align-items: center;
            padding: 14px 24px;
            background: #4a90e2;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            gap: 8px;
        }

        .search-button:hover {
            background: #357abd;
            transform: translateY(-1px);
        }

        .search-button:active {
            transform: translateY(0);
        }

        .search-icon {
            font-size: 16px;
            transition: transform 0.2s ease;
        }

        .search-text {
            font-weight: 500;
        }

        /* Version mobile */
        @media (max-width: 768px) {
            .search-input-group {
                flex-direction: column;
            }
            
            .search-button {
                width: 100%;
                justify-content: center;
            }
            
            .search-input {
                padding: 12px 16px;
            }
        }

        /* Animation au focus */
        @keyframes input-focus {
            0% { transform: scale(1); }
            50% { transform: scale(1.005); }
            100% { transform: scale(1); }
        }

        .search-input:focus {
            animation: input-focus 0.3s ease;
        }

        .role-specific-fields {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #f9f9f9;
        }

        .role-specific-fields.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
            overflow: hidden;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            flex-shrink: 0;
        }

        .modal-body {
            padding: 0 1.5rem;
            overflow-y: auto;
            flex-grow: 1;
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid #eee;
            margin-top: auto;
            flex-shrink: 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .modal-content {
                max-height: 85vh;
                width: 95%;
            }
            
            .modal-header,
            .modal-footer {
                padding: 1rem;
            }
            
            .modal-body {
                padding: 0 1rem;
            }
        }

        .role-specific-fields {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin: 0.5rem 0;
        }

        .role-specific-fields .form-group:last-child {
            margin-bottom: 0;
        }
    </style>
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
            switch($_GET['success']) {
                case 'create':
                    echo "L'utilisateur a été créé avec succès.";
                    break;
                case 'update':
                    echo "L'utilisateur a été mis à jour avec succès.";
                    break;
                case 'delete':
                    echo "L'utilisateur a été supprimé avec succès.";
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
                <li class="nav-item">
                    <a href="#statistiques" class="nav-link" id="stats-link">
                        <i class="fas fa-chart-bar"></i>
                        Statistiques
                    </a>
                </li>
            </ul>

            <div class="logout-section">
                <a href="deconnexion.php" class="logout-btn">
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
                            default:
                                echo "Tableau de Bord";
                        }
                    ?>
                </h1>
                <?php if ($activeRole != 'all'): ?>
                <button class="btn btn-success" id="addUserBtn">
                    <i class="fas fa-plus"></i>
                    Ajouter <?= $activeRole == 'etudiant' ? 'un étudiant' : 
                               ($activeRole == 'enseignant' ? 'un enseignant' : 
                               ($activeRole == 'assistant' ? 'un assistant' : 
                               ($activeRole == 'president' ? 'un président' : 
                               ($activeRole == 'directeur' ? 'un directeur' : 'un utilisateur')))) ?>
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
                    $conn = new mysqli($servername, $username, $password, $database);
                    
                    $roleStats = [
                        'etudiant' => ['icon' => 'fa-user-graduate', 'color' => 'blue', 'count' => 0],
                        'enseignant' => ['icon' => 'fa-chalkboard-teacher', 'color' => 'green', 'count' => 0],
                        'assistant' => ['icon' => 'fa-user-friends', 'color' => 'orange', 'count' => 0],
                        'president' => ['icon' => 'fa-user-tie', 'color' => 'red', 'count' => 0],
                        'directeur' => ['icon' => 'fa-university', 'color' => 'teal', 'count' => 0],
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
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Email</th>
                                    <th>Date d'inscription</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($users) > 0): ?>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id_personne'] ?></td>
                                        <td><?= htmlspecialchars($user['nom']) ?></td>
                                        <td><?= htmlspecialchars($user['prenom']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= isset($user['date_inscription']) ? date("d/m/Y", strtotime($user['date_inscription'])) : "N/A" ?></td>
                                        <td class="actions">
                                            <button class="btn btn-sm btn-primary edit-btn" 
                                                    data-id="<?= $user['id_personne'] ?>" 
                                                    data-nom="<?= htmlspecialchars($user['nom']) ?>" 
                                                    data-prenom="<?= htmlspecialchars($user['prenom']) ?>" 
                                                    data-email="<?= htmlspecialchars($user['email']) ?>" 
                                                    data-role="<?= $user['role'] ?>">
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-btn" 
                                                    data-id="<?= $user['id_personne'] ?>" 
                                                    data-nom="<?= htmlspecialchars($user['nom']) ?>" 
                                                    data-prenom="<?= htmlspecialchars($user['prenom']) ?>">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center;">Aucun utilisateur trouvé</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <!-- Après le tableau dans la vue spécifique au rôle -->
                        <div class="pagination" style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?role=<?= $activeRole ?>&page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                                class="btn btn-sm <?= $i == $page ? 'btn-primary' : 'btn-secondary' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
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

                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" required>
                </div>
                
                <div class="form-group">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                </div>
                
                <div class="form-group">
                    <label for="date_naissance">Date de naissance</label>
                    <input type="date" id="date_naissance" name="date_naissance" required>
                </div>

                <!-- Champs spécifiques aux rôles -->
                <div id="etudiantFields" class="role-specific-fields">
                    <div class="form-group">
                        <label for="filiere">Filière</label>
                        <input type="text" id="filiere" name="filiere">
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
                        <select name="filiere_ens" id="filiere_ens" required>
                            <option value="">-- Sélectionner une filière --</option>
                            <!-- Options ajoutées dynamiquement -->
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="niveau_ens">Niveau</label>
                        <select id="niveau_ens" name="niveau_ens">
                            <option value="L1">L1</option>
                            <option value="L2">L2</option>
                            <option value="L3">L3</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="simestre_ens">Semestre</label>
                        <select id="simestre_ens" name="simestre_ens">
                            <option value="1">Semestre 1</option>
                            <option value="2">Semestre 2</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="matiere_ens">Matière</label>
                        <select id="matiere_ens" name="matiere_ens">
                            <!-- Options générées dynamiquement par JavaScript -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="statut">Statut</label>
                        <select id="statut" name="statut">
                            <option value="Permanent">Permanent</option>
                            <option value="Vacataire">Vacataire</option>
                        </select>
                    </div>
                </div>

                <div id="assistantFields" class="role-specific-fields">
                    <div class="form-group">
                        <label for="departement_ass">Département</label>
                        <input type="text" id="departement_ass" name="departement_ass">
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
                
                <div class="form-group">
                    <label for="role">Rôle</label>
                    <select id="role" name="role" required>
                        <option value="etudiant" <?= $activeRole == 'etudiant' ? 'selected' : '' ?>>Étudiant</option>
                        <option value="enseignant" <?= $activeRole == 'enseignant' ? 'selected' : '' ?>>Enseignant</option>
                        <option value="assistant" <?= $activeRole == 'assistant' ? 'selected' : '' ?>>Assistant</option>
                        <option value="president" <?= $activeRole == 'president' ? 'selected' : '' ?>>Président de Jury</option>
                        <option value="directeur" <?= $activeRole == 'directeur' ? 'selected' : '' ?>>Directeur d'Études</option>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="cancelAddBtn">Annuler</button>
                    <button type="submit" class="btn btn-success">Enregistrer</button>
                </div>
            </form>

            <!-- Ajouter le script JavaScript à la fin du document -->
            <script src="js/form-dynamic.js"></script>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal" id="editModal">
<div class="modal-content">
    <div class="modal-header">
        <h2 class="modal-title">Modifier l'utilisateur</h2>
        <button type="button" class="close-btn" id="closeEditModal">&times;</button>
    </div>
    <div class="modal-body">
            <form id="addUserForm" method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" required>
                </div>
                
                <div class="form-group">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                </div>
                
                <div class="form-group">
                    <label for="date_naissance">Date de naissance</label>
                    <input type="date" id="date_naissance" name="date_naissance" required>
                </div>

                <!-- Champs spécifiques aux rôles -->
                <div id="etudiantFields" class="role-specific-fields">
                    <div class="form-group">
                        <label for="filiere">Filière</label>
                        <input type="text" id="filiere" name="filiere">
                    </div>
                    <div class="form-group">
                        <label for="niveau">Niveau</label>
                        <select id="niveau" name="niveau">
                            <option value="L1">L1</option>
                            <option value="L2">L2</option>
                            <option value="L3">L3</option>
                        </select>
                    </div>
                </div>

                <div id="enseignantFields" class="role-specific-fields">
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone">
                    </div>
                    <div class="form-group">
                        <label for="departement_ens">Département</label>
                        <input type="text" id="departement_ens" name="departement">
                    </div>
                </div>

                <div id="assistantFields" class="role-specific-fields">
                    <div class="form-group">
                        <label for="departement_ass">Département</label>
                        <input type="text" id="departement_ass" name="departement">
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

            <div class="form-group">
                <label for="editRole">Rôle</label>
                <select id="editRole" name="role" required>
            <?php 
            $roles = [
                'etudiant' => 'Étudiant',
                'enseignant' => 'Enseignant', 
                'assistant' => 'Assistant',
                'president' => 'Président de Jury',
                'directeur' => 'Directeur d\'Études'
            ];
            foreach ($roles as $key => $value): ?>
                <option value="<?= $key ?>" <?= ($key == $role) ? 'selected' : '' ?>>
                    <?= $value ?>
                </option>
            <?php endforeach; ?>
            </select>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="cancelEditBtn">Annuler</button>
                <button type="submit" class="btn btn-success">Mettre à jour</button>
            </div>
        </form>
    </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", function () {
    fetch("get_filieres.php")
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById("filiere_ens");
            data.forEach(filiere => {
                const option = document.createElement("option");
                option.value = filiere.id;
                option.textContent = filiere.nom;
                select.appendChild(option);
            });
        })
        .catch(error => console.error("Erreur lors du chargement des filières :", error));
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const filiereSelect = document.getElementById('filiere_ens');
    const niveauSelect = document.getElementById('niveau_ens');
    const semestreSelect = document.getElementById('simestre_ens');
    const matiereSelect = document.getElementById('matiere_ens');

    // Lorsqu'une des valeurs change
    [filiereSelect, niveauSelect, semestreSelect].forEach(el => {
        el.addEventListener('change', () => {
            const filiereId = filiereSelect.value;
            const niveau = niveauSelect.value;
            const semestre = semestreSelect.value;

            if (filiereId && niveau && semestre) {
                fetch(`get_matieres.php?filiere=${filiereId}&niveau=${niveau}&semestre=${semestre}`)
                    .then(response => response.json())
                    .then(data => {
                        matiereSelect.innerHTML = '';
                        data.forEach(matiere => {
                            const option = document.createElement('option');
                            option.value = matiere.id_matiere;
                            option.textContent = matiere.nom_matiere;
                            matiereSelect.appendChild(option);
                        });
                    });
            }
        });
    });
});
</script>

<script>
    // Gestion des champs dynamiques
    function updateEditFields(role) {
    const dynamicFields = document.getElementById('dynamicFields');
    let html = '';

    switch(role) {
        case 'etudiant':
            html = `
                <div class="role-specific-fields">
                    <div class="form-group">
                        <label for="filiere">Filière</label>
                        <input type="text" id="filiere" name="filiere">
                    </div>
                    <div class="form-group">
                        <label for="niveau">Niveau</label>
                        <select id="niveau" name="niveau">
                            ${generateOptions(['L1', 'L2', 'L3'], '<?= $row['etudiant_niveau'] ?>')}
                        </select>
                    </div>
                </div>`;
            break;
        
        // Ajouter les autres cas...
    }

    dynamicFields.innerHTML = html;
    }

    document.getElementById('editRole').addEventListener('change', function() {
    updateEditFields(this.value);
    });

    // Initialisation
    updateEditFields(document.getElementById('editRole').value);
</script>
    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Confirmer la suppression</h2>
                <button type="button" class="close-btn" id="closeDeleteModal">&times;</button>
            </div>
            <p id="deleteMessage">Êtes-vous sûr de vouloir supprimer cet utilisateur ?</p>
            <form id="deleteUserForm" method="POST">
                <input type="hidden" name="action" value="delete">
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
// Progression Chart
const progressionCtx = document.getElementById('progressionChart').getContext('2d');
const progressionChart = new Chart(progressionCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_unique(array_column($progressionData['etudiant'] ?? [], 'mois'))) ?>,
        datasets: [
            <?php foreach ($roleStats as $role => $data): ?>
            {
                label: '<?= ucfirst($role) ?>s',
                data: <?= json_encode(array_column($progressionData[$role] ?? [], 'count')) ?>,
                borderColor: '<?= [
                    'etudiant' => '#3498db',
                    'enseignant' => '#2ecc71',
                    'assistant' => '#f39c12',
                    'president' => '#e74c3c',
                    'directeur' => '#1abc9c'
                ][$role] ?>',
                backgroundColor: '<?= [
                    'etudiant' => '#3498db33',
                    'enseignant' => '#2ecc7133',
                    'assistant' => '#f39c1233',
                    'president' => '#e74c3c33',
                    'directeur' => '#1abc9c33'
                ][$role] ?>',
                borderWidth: 2,
                pointRadius: 4,
                lineTension: 0.3, // Modification clé ici
                fill: true
            }<?= ($role !== array_key_last($roleStats)) ? ',' : '' ?>
            <?php endforeach; ?>
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        elements: {
            line: {
                tension: 0.3 // Version alternative si besoin
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: "Nombre d'inscriptions",
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
            tooltip: {
                callbacks: {
                    title: function(context) {
                        const [year, month] = context[0].label.split('-');
                        const monthNames = [
                            'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                            'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
                        ];
                        return `${monthNames[parseInt(month)-1]} ${year}`;
                    }
                }
            }
        }
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
            
            $("#deleteId").val(id);
            $("#deleteMessage").text(`Êtes-vous sûr de vouloir supprimer l'utilisateur ${prenom} ${nom} ?`);
            
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
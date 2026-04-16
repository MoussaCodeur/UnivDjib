<?php
// Démarrer la session
require_once '../../../config/session.php';

// La connexion à la base de données
require_once '../../../config/db.php';

// Récupérer l'ID de l'utilisateur depuis la session
$userID = $_SESSION['user_id'];

// Requête pour obtenir les informations de l'utilisateur
$sql = "SELECT id_personne, prenom, nom, email, role FROM personne WHERE id_personne = ?";
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

// REQUETE POUR AFFICHER LA SPECIALITE DE L'ENSEIGNANT
$SpecialiteQuery = "SELECT specialite FROM enseignant e, personne p WHERE e.id_enseignant = p.id_personne AND id_enseignant = ?";
$stmtSpecialite = $conn->prepare($SpecialiteQuery);

if (!$stmtSpecialite) {
    die("Erreur dans la requête SQL : " . $conn->error);
}

// Liage des paramètres
$stmtSpecialite->bind_param("s", $userID);
$stmtSpecialite->execute();
$resultSpecialite = $stmtSpecialite->get_result();

if ($resultSpecialite && $resultSpecialite->num_rows > 0) {
    $rowSpecialite = $resultSpecialite->fetch_assoc();
    $specialite = $rowSpecialite['specialite'];
} else {
    $specialite = "Non spécifiée"; // Valeur par défaut si aucune spécialité n'est trouvée
}

// REQUETE POUR AFFICHER NOMBRE DE MATIERE ENSEIGNER
$NombreMatiereQuery = "SELECT COUNT(DISTINCT id_matiere) AS nombre_matieres FROM enseigner WHERE id_enseignant = ?";
$stmtNombreMatiere = $conn->prepare($NombreMatiereQuery);

if (!$stmtNombreMatiere) {
    die("Erreur dans la requête SQL : " . $conn->error);
}

// Liage des paramètres
$stmtNombreMatiere->bind_param("s", $userID);
$stmtNombreMatiere->execute();
$resultNombreMatiere = $stmtNombreMatiere->get_result();

if ($resultNombreMatiere && $resultNombreMatiere->num_rows > 0) {
    $rowNombreMatiere = $resultNombreMatiere->fetch_assoc();
    $nombre_matieres = $rowNombreMatiere['nombre_matieres'];
} else {
    $nombre_matieres = 0; // Valeur par défaut si aucune matière n'est trouvée
} 
// REQUETE POUR AFFICHER LES NOMS DES MATIERES ENSEIGNEES
$MatiereQuery = "SELECT DISTINCT(m.nom_matiere)
                 FROM matiere m, enseignant e, enseigner en 
                 WHERE m.id_matiere = en.id_matiere
                 AND en.id_enseignant = e.id_enseignant 
                 AND (en.type_semestre = 1 OR en.type_semestre = 2) 
                 AND en.id_enseignant = ?";
$stmtMatiere = $conn->prepare($MatiereQuery);

if (!$stmtMatiere) {
    die("Erreur dans la requête SQL : " . $conn->error);
}

// Liage des paramètres
$stmtMatiere->bind_param("s", $userID);
$stmtMatiere->execute();
$resultMatiere = $stmtMatiere->get_result();

$matieres = []; // Initialiser un tableau pour stocker les noms des matières

if ($resultMatiere && $resultMatiere->num_rows > 0) {
    while ($rowMatiere = $resultMatiere->fetch_assoc()) {
        $matieres[] = $rowMatiere['nom_matiere']; // Ajouter chaque matière au tableau
    }
} else {
    $matieres[] = "Aucune matière enseignée"; // Valeur par défaut si aucune matière n'est trouvée
}

// Fermer le statement
$stmtMatiere->close();
// REQUETE POUR AFFICHER LE NOMBRE TOTAL D'HEURES ENSEIGNÉES
$TotalHeuresQuery = "SELECT SUM(nb_heure) AS total_heures FROM enseigner WHERE id_enseignant = ?";
$stmtTotalHeures = $conn->prepare($TotalHeuresQuery);

if (!$stmtTotalHeures) {
    die("Erreur dans la requête SQL : " . $conn->error);
}

// Liage des paramètres
$stmtTotalHeures->bind_param("s", $userID);
$stmtTotalHeures->execute();
$resultTotalHeures = $stmtTotalHeures->get_result();

if ($resultTotalHeures && $resultTotalHeures->num_rows > 0) {
    $rowTotalHeures = $resultTotalHeures->fetch_assoc();
    $total_heures = $rowTotalHeures['total_heures'];
} else {
    $total_heures = 0; // Valeur par défaut si aucune heure n'est trouvée
}


// Traitement du formulaire de changement de mot de passe
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Vérifier si les mots de passe correspondent
    if ($new_password === $confirm_password) {
        // Hacher le nouveau mot de passe
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Mettre à jour le mot de passe dans la base de données
        $updatePasswordQuery = "UPDATE personne SET mot_de_passe = ? WHERE id_personne = ?";
        $stmtUpdatePassword = $conn->prepare($updatePasswordQuery);

        if (!$stmtUpdatePassword) {
            die("Erreur dans la requête SQL : " . $conn->error);
        }

        $stmtUpdatePassword->bind_param("ss", $hashed_password, $userID);
        
        // Vérifier si l'exécution de la requête est réussie
        if ($stmtUpdatePassword->execute()) {
            echo "<script>alert('Mot de passe mis à jour avec succès.');</script>";
        } else {
            echo "<script>alert('Erreur lors de la mise à jour du mot de passe.');</script>";
        }

        $stmtUpdatePassword->close();
    } else {
        // Si les mots de passe ne correspondent pas
        echo "<script>alert('Les mots de passe ne correspondent pas. Veuillez vérifier et réessayer.');</script>";
    }
}


// Affichage du message (si nécessaire)
if (isset($message)) {
    echo $message;
}

$stmt->close();
$stmtSpecialite->close();
$stmtNombreMatiere->close();
$stmtTotalHeures->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Profil Enseignant - Universite De Djibouti</title>
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
    <link rel="stylesheet" href="../../../public/assets/css/profile_enseignant.css">
</head>
<body>
    <div class="dashboard">
        <aside class="left-side">
            <div class="logo">
                <img src="../../../public/assets/img/U-remove.png" alt="Logo U-Digital">
            </div>
            <nav>
                <ul>
                    <a href="acceuil_enseignant.php"><li><i class="bi-house"></i><span>Accueil</span></li></a>
                    <a href="profile_enseignant.php"><li><i class="bi-person"></i><span>Profil</span></li></a>
                    <a href="deposer_cours.php"><li><i class="bi-book"></i><span>Dépôts cours</span></li></a>
                    <a href="enseignant_planning.php"><li><i class="bi-calendar"></i><span>Consulter planning</span></li></a>
                    <a href="deposer_notes.php"><li><i class="bi-journal"></i><span>Dépôts notes</span></li></a>
                    <div class="deconnection">
                        <a href="../../../config/logout.php"><i class="bi-box-arrow-right"></i><span>Déconnexion</span></a>
                    </div>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header>
                <div class="notification"><?php echo $userID; ?> - <?php echo $role; ?></div>
            </header>

            <div class="profile">
                <div class="welcome">
                    <div>
                        <h2>Personnaliser votre profil</h2>
                        <p>Consultez votre profil | Changez votre mot de passe en un seul clic</p>
                    </div>
                    <img src="../../../public/assets/img/U-remove.png" alt="User">
                </div>

                <div class="categorie">
                    <div class="cat1">
                        <label>ID : <?php echo $userID; ?></label>
                        <label>Specialite : <?php echo $specialite; ?></label>
                    </div>
    
                    <div class="cat1">
                        <label>Nom : <?php echo $nom; ?></label>
                        <label>Prénom : <?php echo $prenom; ?></label>
                    </div>
    
                    <div class="cat1">
                        <label>Email : <?php echo $email; ?></label>
                    </div>
                    <div class="cat1">
                    <label>Nom de la matière enseignée : </label>
                        <select name="matiere_enseignee" id="matiere_enseignee">
                            <?php
                            if (!empty($matieres)) {
                                foreach ($matieres as $matiere) {
                                    echo "<option value='$matiere'>$matiere</option>";
                                }
                            } else {
                                echo "<option value=''>Aucune matière enseignée</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="password">
                    <label>Modifier votre mot de passe</label>
                    <button type="submit" id="showPasswordForm">Cliquez ici</button>
                </div>

                <div class="change-password">
                    <div id="passwordForm">
                        <form method="post" action="profile_enseignant.php">
                            <h2>Veuillez modifier vos mots de passe</h2>
                            <label>Nouveau mot de passe :</label>
                            <input type="password" name="new_password" required>
                            <label>Confirmer le mot de passe :</label>
                            <input type="password" name="confirm_password" required>
                            <div class="div-boutton">
                                <button type="button" class="retourner" id="hidePasswordForm">Retourner</button>
                                <button type="submit" class="button" name="change_password">Valider</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Overlay pour le flou -->
                <div id="overlay"></div>

                <div class="statistique">
                    <div>
                        <p>Nombre de matières enseignées</p>
                        <button class="matiere_statis"><?php echo $nombre_matieres; ?></button>
                    </div>
                    <div>
                        <p>Nombre d'heures</p>
                        <button class="matiere_heure"><?php echo $total_heures; ?></button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../../public/assets/js/passwordForm.js"></script>
</body>
</html>
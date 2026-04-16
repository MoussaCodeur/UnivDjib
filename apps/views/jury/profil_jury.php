<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';

$userID = $_SESSION['user_id'];


// Requête pour obtenir les informations de l'utilisateur
$sql = "SELECT id_personne, prenom, nom, email, role, image_profile FROM personne WHERE id_personne = ?";
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
    $image_profile = $row['image_profile'] ? 'uploads/' . $row['image_profile'] : '../Images/user2.jpg';
} else {
    die("Utilisateur non trouvé.");
}

// REQUETE POUR COMPTER LE NOMBRE D'ANONYMATS GÉRÉS
$NombreAnonymatsQuery = "SELECT COUNT(*) AS nombre_anonymats FROM anonymat WHERE id_president = ?";
$stmtNombreAnonymats = $conn->prepare($NombreAnonymatsQuery);

if (!$stmtNombreAnonymats) {
    die("Erreur dans la requête SQL : " . $conn->error);
}

// Liage des paramètres
$stmtNombreAnonymats->bind_param("s", $userID);
$stmtNombreAnonymats->execute();
$resultNombreAnonymats = $stmtNombreAnonymats->get_result();

if ($resultNombreAnonymats && $resultNombreAnonymats->num_rows > 0) {
    $rowNombreAnonymats = $resultNombreAnonymats->fetch_assoc();
    $nombre_anonymats = $rowNombreAnonymats['nombre_anonymats'];
} else {
    $nombre_anonymats = 0;
}

// REQUETE POUR COMPTER LE NOMBRE D'ÉVALUATIONS VALIDÉES
$NombreEvaluationsQuery = "SELECT COUNT(*) AS nombre_evaluations FROM evaluer WHERE id_anonymat IN (SELECT id_anonymat FROM anonymat WHERE id_president = ?) AND note IS NOT NULL";
$stmtNombreEvaluations = $conn->prepare($NombreEvaluationsQuery);

if (!$stmtNombreEvaluations) {
    die("Erreur dans la requête SQL : " . $conn->error);
}

// Liage des paramètres
$stmtNombreEvaluations->bind_param("s", $userID);
$stmtNombreEvaluations->execute();
$resultNombreEvaluations = $stmtNombreEvaluations->get_result();

if ($resultNombreEvaluations && $resultNombreEvaluations->num_rows > 0) {
    $rowNombreEvaluations = $resultNombreEvaluations->fetch_assoc();
    $nombre_evaluations = $rowNombreEvaluations['nombre_evaluations'];
} else {
    $nombre_evaluations = 0;
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
            $message = "<div class='alert alert-success'>Mot de passe mis à jour avec succès.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Erreur lors de la mise à jour du mot de passe.</div>";
        }

        $stmtUpdatePassword->close();
    } else {
        // Si les mots de passe ne correspondent pas
        $message = "<div class='alert alert-danger'>Les mots de passe ne correspondent pas. Veuillez vérifier et réessayer.</div>";
    }
}

$stmt->close();
$stmtNombreAnonymats->close();
$stmtNombreEvaluations->close();
$conn->close();

// Obtenir la date et l'heure actuelles
date_default_timezone_set('Europe/Paris');
$dateActuelle = date('d/m/Y');
$heureActuelle = date('H:i');
$jourSemaine = date('l');
$mois = date('F');

// Traduire les noms de jours et mois en français
$joursFrancais = [
    'Monday' => 'Lundi',
    'Tuesday' => 'Mardi',
    'Wednesday' => 'Mercredi',
    'Thursday' => 'Jeudi',
    'Friday' => 'Vendredi',
    'Saturday' => 'Samedi',
    'Sunday' => 'Dimanche'
];

$moisFrancais = [
    'January' => 'janvier',
    'February' => 'février',
    'March' => 'mars',
    'April' => 'avril',
    'May' => 'mai',
    'June' => 'juin',
    'July' => 'juillet',
    'August' => 'août',
    'September' => 'septembre',
    'October' => 'octobre',
    'November' => 'novembre',
    'December' => 'décembre'
];

$jourSemaineFr = $joursFrancais[$jourSemaine];
$moisFr = $moisFrancais[$mois];

// Formatage de la date comme demandé
$date = $jourSemaineFr . ' ' . date('d') . ' ' . $moisFr . ' ' . date('Y');
$heure = $heureActuelle;
$initiales = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1));
$nomComplet = $prenom . " " . $nom;
$roleUtilisateur = "Président du Jury";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Profil president jury - Université de djibouti</title>
    <link rel="stylesheet" href="../../../public/assets/css/profile_president_jury.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
</head>
<body>
    <div class="dashboard">
        <aside class="left-side">
            <div class="logo">
                <img src="../../../public/assets/img/U-remove.png" alt="Logo U-Digital" style="width:150px;height:100px;background:white;border-radius:50%;margin-left:30px">
            </div>
            <nav>
                <ul>
                    <li><a href="acceuil_president_jury.php"><i class="bi bi-house"></i>Accueil</a></li>
                    <li><a href="profil_jury.php"><i class="bi bi-person"></i>Profil</a></li>
                    <li><a href="consulter_notes_president.php"><i class="bi bi-journal"></i>Consultation des notes</a></li>
                    <li><a href="generer_attestations.php"><i class="bi bi-file-text"></i>Attestations</a></li>
                    <li><a href="statistiques.php"><i class="bi bi-bar-chart"></i>Statistiques</a></li>
                    <li class="deconnection"><a href="../../../config/logout.php"><i class="bi bi-box-arrow-right"></i>Déconnexion</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <!-- Nouvel en-tête amélioré -->
            <div class="header-container">
                <div class="date-container">
                    <div class="date-text"><?php echo $date; ?></div>
                    <div class="time-text"><?php echo $heure; ?></div>
                </div>
                <div class="user-container">
                    <div class="user-avatar">
                        <?php echo $initiales; ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo $nomComplet; ?></div>
                        <div class="user-role"><?php echo $roleUtilisateur; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="profile">
                <?php if (isset($message)) echo $message; ?>
                
                <div class="welcome">
                    <div>
                        <h2>Profil du Président du Jury</h2>
                        <p>Consultez et gérez vos informations personnelles</p>
                    </div>
                    <img src="../../../public/assets/img/U-remove.png" alt="Photo de profil">
                </div>

                <div class="categorie">
                    <div class="cat1">
                        <label>ID : <?php echo $userID; ?></label>
                        <label>Rôle : <?php echo $role; ?></label>
                    </div>
    
                    <div class="cat1">
                        <label>Nom : <?php echo $nom; ?></label>
                        <label>Prénom : <?php echo $prenom; ?></label>
                    </div>
    
                    <div class="cat1">
                        <label>Email : <?php echo $email; ?></label>
                        <label>Fonction : Président du Jury</label>
                    </div>

                    <div class="password">
                        <label>Modifier votre mot de passe</label>
                        <button type="submit" id="showPasswordForm">Cliquez ici</button>
                    </div>

                    <div class="statistique">
                        <div>
                            <p>Anonymats gérés</p>
                            <button><?php echo $nombre_anonymats; ?></button>
                        </div>
                        <div>
                            <p>Évaluations validées</p>
                            <button><?php echo $nombre_evaluations; ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulaire de changement de mot de passe -->
            <div id="overlay"></div>
            <div id="passwordForm">
                <div class="change-password">
                    <form method="post" action="profil_jury.php">
                        <h2>Modification du mot de passe</h2>
                        <label for="new_password">Nouveau mot de passe :</label>
                        <input type="password" id="new_password" name="new_password" required>
                        
                        <label for="confirm_password">Confirmer le mot de passe :</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        
                        <div class="div-boutton">
                            <button type="button" id="hidePasswordForm">Retourner</button>
                            <button type="submit" name="change_password">Valider</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('showPasswordForm').addEventListener('click', function() {
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('passwordForm').style.display = 'block';
        });

        document.getElementById('hidePasswordForm').addEventListener('click', function() {
            document.getElementById('overlay').style.display = 'none';
            document.getElementById('passwordForm').style.display = 'none';
        });

        // Fermer le formulaire si on clique en dehors
        document.getElementById('overlay').addEventListener('click', function() {
            document.getElementById('overlay').style.display = 'none';
            document.getElementById('passwordForm').style.display = 'none';
        });
    </script>
</body>
</html>
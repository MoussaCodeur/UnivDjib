<?php
// Démarrer la session
require_once '../../../config/session.php';

// La connexion à la base de données
require_once '../../../config/db.php';

// Inclure PHPMailer
require '../../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Récupérer l'ID de l'enseignant connecté
$id_enseignant = $_SESSION['user_id'];
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Récupérer les infos de l'enseignant
$sql = "SELECT prenom, nom FROM personne WHERE id_personne = ? AND role = 'enseignant'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_enseignant);
$stmt->execute();
$result = $stmt->get_result();
$enseignant = $result->fetch_assoc();
$nom_enseignant = $enseignant['prenom'] . ' ' . $enseignant['nom'];

// Fonction pour envoyer les notifications par email
function envoyerNotificationNotes($conn, $type_note, $id_matiere, $filiere, $niveau, $nom_enseignant) {
    // Récupérer le nom de la matière
    $sql_matiere = "SELECT nom_matiere FROM matiere WHERE id_matiere = ?";
    $stmt_matiere = $conn->prepare($sql_matiere);
    $stmt_matiere->bind_param("i", $id_matiere);
    $stmt_matiere->execute();
    $result_matiere = $stmt_matiere->get_result();
    $matiere = $result_matiere->fetch_assoc();
    $nom_matiere = $matiere['nom_matiere'];
    
    // Récupérer les étudiants concernés
    $sql_etudiants = "
        SELECT p.id_personne, p.email
        FROM personne p
        JOIN etudiant e ON p.id_personne = e.id_etudiant
        JOIN evaluer ev ON e.id_etudiant = ev.id_etudiant
        JOIN filiere f ON f.id_filiere = e.id_filiere
        WHERE f.nom_filiere = ? AND e.niveau_filiere = ? AND ev.id_matiere = ? AND p.role = 'etudiant'";
    $stmt_etudiants = $conn->prepare($sql_etudiants);
    $stmt_etudiants->bind_param("ssi", $filiere, $niveau, $id_matiere);
    $stmt_etudiants->execute();
    $result_etudiants = $stmt_etudiants->get_result();

    // Lien de connexion
    $link = "https://www.gcnu-plateforme.free.nf/apps/views/global/Connexion.php";

    // Parcourir les étudiants concernés
    while ($etudiant = $result_etudiants->fetch_assoc()) {
        $email = $etudiant['email'];

        // Envoyer un email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'gestioncoursuniversitaire@gmail.com';
            $mail->Password = 'jevu knsh qkus oxhf';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Expéditeur et destinataire
            $mail->setFrom('gestioncoursuniversitaire@gmail.com', 'Université de Djibouti');
            $mail->addAddress($email);

            // Contenu du mail différent selon le type de note
            $mail->isHTML(true);
            
            if ($type_note == 'cf') {
                $mail->Subject = "📝 Note finale disponible : {$nom_matiere}";
                $mail->Body = "
                    <!DOCTYPE html>
                    <html lang='fr'>
                    <head>
                        <meta charset='UTF-8'>
                        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                        <title>Note finale disponible</title>
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                line-height: 1.6;
                                color: #333333;
                                background-color: #f9f9f9;
                                margin: 0;
                                padding: 0;
                            }
                            .container {
                                max-width: 600px;
                                margin: 0 auto;
                                padding: 20px;
                                background-color: #ffffff;
                                border-radius: 10px;
                                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
                            }
                            .header {
                                background-color: #28a745;
                                padding: 20px;
                                text-align: center;
                                border-radius: 10px 10px 0 0;
                            }
                            .header h1 {
                                color: #ffffff;
                                margin: 0;
                                font-size: 24px;
                            }
                            .content {
                                padding: 30px 20px;
                            }
                            .info {
                                background-color: #e8f5e9;
                                border-left: 4px solid #28a745;
                                padding: 15px;
                                margin: 20px 0;
                                border-radius: 5px;
                            }
                            .info p {
                                margin: 5px 0;
                            }
                            .action-button {
                                display: inline-block;
                                padding: 12px 24px;
                                background-color: #28a745;
                                color: #ffffff !important;
                                text-decoration: none;
                                border-radius: 5px;
                                font-weight: bold;
                                margin: 20px 0;
                                text-align: center;
                                transition: background-color 0.3s ease;
                            }
                            .action-button:hover {
                                background-color: #218838;
                            }
                            .footer {
                                text-align: center;
                                padding: 20px;
                                font-size: 14px;
                                color: #666666;
                                border-top: 1px solid #eeeeee;
                            }
                            .highlight {
                                color: #28a745;
                                font-weight: bold;
                            }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>Note Finale Disponible</h1>
                            </div>
                            <div class='content'>
                                <p>Cher(e) étudiant(e),</p>
                                
                                <p>Nous vous informons que la note finale de la matière suivante est maintenant disponible sur votre plateforme <strong>Universite de Djibouti</strong>.</p>
                                
                                <div class='info'>
                                    <p><strong>Matière :</strong> <span class='highlight'>{$nom_matiere}</span></p>
                                    <p><strong>Filière :</strong> <span class='highlight'>{$filiere} - {$niveau}</span></p>
                                    <p><strong>Enseignant :</strong> <span class='highlight'>{$nom_enseignant}</span></p>
                                    <p><strong>Date de publication :</strong> <span class='highlight'>" . date('d/m/Y') . "</span></p>
                                </div>
                                
                                <p>Vous pouvez maintenant consulter votre note globale pour cette matière.</p>
                                
                                <p style='text-align: center;'>
                                    <a href='{$link}' class='action-button'>CONSULTER MA NOTE</a>
                                </p>
                                
                                <p>Si vous avez des questions concernant cette note, n'hésitez pas à contacter votre enseignant.</p>
                                
                                <p>Cordialement,<br>
                                <strong>L'équipe Universite de Djibouti</strong></p>
                            </div>
                            
                            <div class='footer'>
                                <p>© " . date('Y') . " Universite de Djibouti - Plateforme Éducative. Tous droits réservés.</p>
                                <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";
            } else {
                $type_note_text = strtoupper($type_note);
                $mail->Subject = "📝 Note de {$type_note_text} disponible : {$nom_matiere}";
                $mail->Body = "
                    <!DOCTYPE html>
                    <html lang='fr'>
                    <head>
                        <meta charset='UTF-8'>
                        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                        <title>Note de {$type_note_text} disponible</title>
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                line-height: 1.6;
                                color: #333333;
                                background-color: #f9f9f9;
                                margin: 0;
                                padding: 0;
                            }
                            .container {
                                max-width: 600px;
                                margin: 0 auto;
                                padding: 20px;
                                background-color: #ffffff;
                                border-radius: 10px;
                                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
                            }
                            .header {
                                background-color: #007bff;
                                padding: 20px;
                                text-align: center;
                                border-radius: 10px 10px 0 0;
                            }
                            .header h1 {
                                color: #ffffff;
                                margin: 0;
                                font-size: 24px;
                            }
                            .content {
                                padding: 30px 20px;
                            }
                            .info {
                                background-color: #f0f7ff;
                                border-left: 4px solid #007bff;
                                padding: 15px;
                                margin: 20px 0;
                                border-radius: 5px;
                            }
                            .info p {
                                margin: 5px 0;
                            }
                            .action-button {
                                display: inline-block;
                                padding: 12px 24px;
                                background-color: #007bff;
                                color: #ffffff !important;
                                text-decoration: none;
                                border-radius: 5px;
                                font-weight: bold;
                                margin: 20px 0;
                                text-align: center;
                                transition: background-color 0.3s ease;
                            }
                            .action-button:hover {
                                background-color: #0056b3;
                            }
                            .footer {
                                text-align: center;
                                padding: 20px;
                                font-size: 14px;
                                color: #666666;
                                border-top: 1px solid #eeeeee;
                            }
                            .highlight {
                                color: #007bff;
                                font-weight: bold;
                            }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>Note de {$type_note_text} Disponible</h1>
                            </div>
                            <div class='content'>
                                <p>Cher(e) étudiant(e),</p>
                                
                                <p>Nous vous informons que la note de {$type_note_text} pour la matière suivante a été déposée par votre enseignant sur votre plateforme <strong>Universite de Djibouti</strong>.</p>
                                
                                <div class='info'>
                                    <p><strong>Matière :</strong> <span class='highlight'>{$nom_matiere}</span></p>
                                    <p><strong>Type de note :</strong> <span class='highlight'>{$type_note_text}</span></p>
                                    <p><strong>Filière :</strong> <span class='highlight'>{$filiere} - {$niveau}</span></p>
                                    <p><strong>Enseignant :</strong> <span class='highlight'>{$nom_enseignant}</span></p>
                                    <p><strong>Date de publication :</strong> <span class='highlight'>" . date('d/m/Y') . "</span></p>
                                </div>
                                
                                <p style='text-align: center;'>
                                    <a href='{$link}' class='action-button'>ACCÉDER À LA PLATEFORME</a>
                                </p>
                                
                                <p>Si vous avez des questions concernant cette note, n'hésitez pas à contacter votre enseignant.</p>
                                
                                <p>Cordialement,<br>
                                <strong>L'équipe Universite de Djibouti</strong></p>
                            </div>
                            
                            <div class='footer'>
                                <p>© " . date('Y') . " Universite de Djibouti - Plateforme Éducative. Tous droits réservés.</p>
                                <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";
            }
            
            // Envoi du mail
            $mail->send();
        } catch (Exception $e) {
            error_log("Erreur lors de l'envoi de l'e-mail à $email : {$mail->ErrorInfo}");
        }
    }
}

// Récupérer les semestres enseignés par l'enseignant connecté
$sql_semestres = "SELECT DISTINCT type_semestre FROM enseigner WHERE id_enseignant = ?";
$stmt = $conn->prepare($sql_semestres);
$stmt->bind_param("i", $id_enseignant);
$stmt->execute();
$result_semestres = $stmt->get_result();
$semestres = [];
while ($row = $result_semestres->fetch_assoc()) {
    $semestres[] = $row['type_semestre'];
}

// Initialiser les variables
$matieres = [];
$etudiants = [];
$filiere_niveau = [];
$formulaire_valide = false;
$current_semestre = '1'; // Par défaut

// Gestion des soumissions de formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['valider_selection'])) {
        // Récupérer les valeurs sélectionnées
        $semestre = $_POST['semestre_et'];
        $id_matiere = $_POST['matier_et'];
        $niveau = $_POST['niveau'];
        $filiere = $_POST['filiere'];

        // Validation des champs
        if (empty($semestre) || empty($id_matiere) || empty($niveau) || empty($filiere)) {
            echo "<script>alert('Veuillez sélectionner tous les champs.');</script>";
        } else {
            // Stocker les valeurs dans des variables de session
            $_SESSION['semestre'] = $semestre;
            $_SESSION['id_matiere'] = $id_matiere;
            $_SESSION['niveau'] = $niveau;
            $_SESSION['filiere'] = $filiere;
            $formulaire_valide = true;
            
            // Déterminer le type de note à afficher
            if (isset($_POST['type_note'])) {
                $_SESSION['type_note'] = $_POST['type_note'];
            }
        }
    }

    if (isset($_POST['deposer_cc'])) {
        $id_matiere = $_SESSION['id_matiere'];
        $niveau = $_SESSION['niveau'];
        $filiere = $_SESSION['filiere'];
        $valid = true;

        foreach ($_POST['notes_cc'] as $id_etudiant => $note_cc) {
            if ($note_cc < 0 || $note_cc > 20) {
                echo "<script>alert('La note CC pour l\'étudiant $id_etudiant est invalide. Les notes doivent être comprises entre 0 et 20.');</script>";
                $valid = false;
                break;
            }
        }

        if ($valid) {
            foreach ($_POST['notes_cc'] as $id_etudiant => $note_cc) {
                $sql_check = "SELECT id_evaluation, cc, tp FROM evaluer WHERE id_etudiant = ? AND id_matiere = ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("ii", $id_etudiant, $id_matiere);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check->num_rows > 0) {
                    $row = $result_check->fetch_assoc();
                    $id_evaluation = $row['id_evaluation'];
                    $date_evaluation = date('Y-m-d');

                    $sql_update = "UPDATE evaluer SET cc = ?, date_evaluation = ? WHERE id_evaluation = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param("dss", $note_cc, $date_evaluation, $id_evaluation);
                    $stmt_update->execute();
                } else {
                    $id_evaluation = uniqid();
                    $date_evaluation = date('Y-m-d');

                    $sql_insert = "INSERT INTO evaluer (id_evaluation, date_evaluation, cc, tp, note, id_matiere, id_etudiant, id_anonymat)
                                   VALUES (?, ?, ?, NULL, NULL, ?, ?, NULL)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bind_param("ssdii", $id_evaluation, $date_evaluation, $note_cc, $id_matiere, $id_etudiant);
                    $stmt_insert->execute();
                }
            }
            
            // Envoyer les notifications après le dépôt des notes
            envoyerNotificationNotes($conn, 'cc', $id_matiere, $filiere, $niveau, $nom_enseignant);
            
            echo "<script>alert('Notes CC déposées avec succès et notifications envoyées !');</script>";
        } else {
            echo "<script>alert('Le dépôt des notes CC a été annulé en raison de notes invalides.');</script>";
        }
    }

    if (isset($_POST['deposer_tp'])) {
        $id_matiere = $_SESSION['id_matiere'];
        $niveau = $_SESSION['niveau'];
        $filiere = $_SESSION['filiere'];
        $valid = true;

        foreach ($_POST['notes_tp'] as $id_etudiant => $note_tp) {
            if ($note_tp < 0 || $note_tp > 20) {
                echo "<script>alert('La note TP pour l\'étudiant $id_etudiant est invalide. Les notes doivent être comprises entre 0 et 20.');</script>";
                $valid = false;
                break;
            }
        }

        if ($valid) {
            foreach ($_POST['notes_tp'] as $id_etudiant => $note_tp) {
                $sql_check = "SELECT id_evaluation, cc, tp FROM evaluer WHERE id_etudiant = ? AND id_matiere = ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("ii", $id_etudiant, $id_matiere);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check->num_rows > 0) {
                    $row = $result_check->fetch_assoc();
                    $id_evaluation = $row['id_evaluation'];
                    $date_evaluation = date('Y-m-d');

                    $sql_update = "UPDATE evaluer SET tp = ?, date_evaluation = ? WHERE id_evaluation = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param("dss", $note_tp, $date_evaluation, $id_evaluation);
                    $stmt_update->execute();
                } else {
                    $id_evaluation = uniqid();
                    $date_evaluation = date('Y-m-d');

                    $sql_insert = "INSERT INTO evaluer (id_evaluation, date_evaluation, cc, tp, note, id_matiere, id_etudiant, id_anonymat)
                                   VALUES (?, ?, NULL, ?, NULL, ?, ?, NULL)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bind_param("ssdii", $id_evaluation, $date_evaluation, $note_tp, $id_matiere, $id_etudiant);
                    $stmt_insert->execute();
                }
            }
            
            // Envoyer les notifications après le dépôt des notes
            envoyerNotificationNotes($conn, 'tp', $id_matiere, $filiere, $niveau, $nom_enseignant);
            
            echo "<script>alert('Notes TP déposées avec succès et notifications envoyées !');</script>";
        } else {
            echo "<script>alert('Le dépôt des notes TP a été annulé en raison de notes invalides.');</script>";
        }
    }

    if (isset($_POST['deposer_cf'])) {
        $id_matiere = $_SESSION['id_matiere'];
        $niveau = $_SESSION['niveau'];
        $filiere = $_SESSION['filiere'];
        
        foreach ($_POST['notes_cf'] as $id_etudiant => $note_cf) {
            $sql = "SELECT id_anonymat FROM evaluer WHERE id_etudiant = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_etudiant);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $id_anonymat = $row['id_anonymat'];

            $sql = "UPDATE anonymat SET CF = ? WHERE id_anonymat = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $note_cf, $id_anonymat);
            $stmt->execute();
        }
        
        // Envoyer les notifications après le dépôt des notes
        envoyerNotificationNotes($conn, 'cf', $id_matiere, $filiere, $niveau, $nom_enseignant);
        
        echo "<script>alert('Notes CF déposées avec succès et notifications envoyées !');</script>";
    }
}

// Si le formulaire est validé, charger les matières et étudiants correspondants
if ($formulaire_valide && isset($_SESSION['semestre']) && isset($_SESSION['id_matiere'])) {
    // Charger les matières pour le semestre sélectionné
    $semestre = $_SESSION['semestre'];
    $sql_matieres = "SELECT m.id_matiere, m.nom_matiere 
                    FROM matiere m
                    JOIN enseigner e ON m.id_matiere = e.id_matiere
                    WHERE e.id_enseignant = ? AND e.type_semestre = ?";
    $stmt = $conn->prepare($sql_matieres);
    $stmt->bind_param("ii", $id_enseignant, $semestre);
    $stmt->execute();
    $result_matieres = $stmt->get_result();
    $matieres = $result_matieres->fetch_all(MYSQLI_ASSOC);

    // Charger les étudiants pour la matière sélectionnée
    $id_matiere = $_SESSION['id_matiere'];
    $niveau = $_SESSION['niveau'];
    $filiere = $_SESSION['filiere'];
    
    $sql_etudiants = "SELECT DISTINCT CONCAT(p.nom, ' ', p.prenom) AS Liste_des_etudiants, 
                             et.id_etudiant, ev.id_anonymat, et.niveau_filiere, f.nom_filiere
                      FROM personne p
                      JOIN etudiant et ON p.id_personne = et.id_etudiant 
                      JOIN filiere f ON f.id_filiere = et.id_filiere 
                      JOIN evaluer ev ON et.id_etudiant = ev.id_etudiant 
                      JOIN matiere m ON ev.id_matiere = m.id_matiere 
                      JOIN enseigner en ON m.id_matiere = en.id_matiere 
                      WHERE en.id_enseignant = ?
                      AND m.id_matiere = ?
                      AND et.niveau_filiere = ?
                      AND f.nom_filiere = ?";
    
    $stmt = $conn->prepare($sql_etudiants);
    $stmt->bind_param("iiss", $id_enseignant, $id_matiere, $niveau, $filiere);
    $stmt->execute();
    $result_etudiants = $stmt->get_result();
    $etudiants = $result_etudiants->fetch_all(MYSQLI_ASSOC);
}

// Récupérer les filières et niveaux disponibles
$sql_filieres = "SELECT DISTINCT f.nom_filiere FROM etudiant e , filiere f where f.id_filiere = e.id_filiere ";
$result_filieres = $conn->query($sql_filieres);
$filieres = $result_filieres->fetch_all(MYSQLI_ASSOC);

$sql_niveaux = "SELECT DISTINCT niveau_filiere FROM etudiant";
$result_niveaux = $conn->query($sql_niveaux);
$niveaux = $result_niveaux->fetch_all(MYSQLI_ASSOC);

// Fermer la connexion
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Déposer Notes - Université de Djibouti</title>
    <link rel="stylesheet" href="../../../public/assets/css/deposer_notes.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
</head>
<body>
    <div class="dashboard">
        <!-- Barre latérale moderne -->
        <aside class="left-side">
            <div class="logo">
                <img src="../../../public/assets/img/U-remove.png" alt="Logo U-Digital">
            </div>
            <nav>
                <ul>
                    <a href="acceuil_enseignant.php"><li><i class="bi-house"></i><span>Accueil</span></li></a>
                    <a href="profile_enseignant.php"><li><i class="bi-person"></i><span>Profil</span></li></a>
                    <a href="deposer_cours.php"><li><i class="bi-book"></i><span>Dépôt des cours</span></li></a>
                    <a href="enseignant_planning.php"><li><i class="bi-calendar"></i><span>Consulter le planning</span></li></a>
                    <a href="#"><li><i class="bi-journal"></i><span>Dépôt des notes</span></li></a>
                    <a href="consulter_note.php"><li><i class="bi-question-circle"></i><span>Consulter les notes</span></li></a>
                    <div class="deconnection">
                        <a href="../../../config/logout.php"><i class="bi-box-arrow-right"></i><span>Déconnexion</span></a>
                    </div>
                </ul>
            </nav>
        </aside>

        <!-- Contenu principal élégant -->
        <main class="main-content">
            <header>
                <div class="notification"><?php echo $id_enseignant; ?> - <?php echo $role; ?></div>
            </header>

             <!-- Nouvel indicateur de chargement premium -->
            <div id="loadingOverlay" class="loading-overlay">
                <div class="loader-container">
                    <div class="loader"></div>
                    <div class="loader"></div>
                    <div class="loader"></div>
                    <img src="../../../public/assets/img/U-remove.png" alt="Logo Université" class="loader-logo">
                </div>
                <div class="loading-text">Dépôt en cours</div>
                <div class="progress-bar">
                    <div class="progress"></div>
                </div>
            </div>

            <!-- Overlay -->
            <div id="overlay"></div>

            <!-- Formulaire de sélection -->
            <form id="form-selection" class="form-container" method="POST" action="" style="<?php echo $formulaire_valide ? 'display: none;' : 'display: none;' ?>">
                <h2>Sélectionnez les informations</h2>

                <div class="form-row">
                    <label class="form-label">Filière</label>
                    <select name="filiere" required>
                        <option value="" disabled selected>Choisir une filière</option>
                        <?php foreach ($filieres as $fil) {
                            echo "<option value='" . $fil['nom_filiere'] . "'>" . $fil['nom_filiere'] . "</option>";
                        } ?>
                    </select>
                </div>

                <div class="form-row">
                    <label class="form-label">Niveau</label>
                    <select name="niveau" required>
                        <option value="" disabled selected>Choisir un niveau</option>
                        <?php foreach ($niveaux as $niv) {
                            echo "<option value='" . $niv['niveau_filiere'] . "'>" . $niv['niveau_filiere'] . "</option>";
                        } ?>
                    </select>
                </div>

                <div class="form-row">
                    <label class="form-label">Semestre</label>
                    <select name="semestre_et" id="semestre_et" onchange="chargerMatieres(this.value, window.idEnseignant)">
                        <option value="" disabled selected>Choisir un semestre</option>
                        <?php foreach ($semestres as $sem) {
                            echo "<option value='$sem'>$sem</option>";
                        } ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Matière</label>
                    <select name="matier_et" id="matiere_select" required>
                        <option value="" disabled selected>Choisir une matière</option>
                        <?php
                        if (!empty($matieres)) {
                            foreach ($matieres as $matiere) {
                                $selected = (isset($_POST['matier_et']) && $_POST['matier_et'] === $matiere['id_matiere']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($matiere['id_matiere']) . "' $selected>" . htmlspecialchars($matiere['nom_matiere']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <input type="hidden" name="type_note" id="type_note" value="">
                
                <div class="button-container">
                    <button type="button" onclick="hideUploadForm()">Retourner</button>
                    <button type="submit" name="valider_selection">Valider</button>
                </div>
            </form>

            <!-- Section "Déposer vos notes" -->
            <div class="deposer-cours">
                <h2>Déposer vos notes</h2>
                <ul>
                    <li>
                        <img src="../../../public/assets/img/logo_etudiant_1.png" alt="Partager le cc">
                        <strong>NOTES CC</strong>
                        <div class="button-group">
                            <button id="btn-notes-cc" onclick="showUploadForm('cc')">Cliquer ici</button>
                        </div>
                    </li>
                    <li>
                        <img src="../../../public/assets/img/logo_etudiant_2.png" alt="Partager le TP">
                        <strong>NOTES TP</strong>
                        <div class="button-group">
                            <button id="btn-notes-tp" onclick="showUploadForm('tp')">Cliquer ici</button>
                        </div>
                    </li>
                    <li>
                        <img src="../../../public/assets/img/logo_etudiant_3.png" alt="Publier le cf">
                        <strong>NOTES CF</strong>
                        <div class="button-group">
                            <button id="btn-notes-cf" onclick="showUploadForm('cf')">Cliquer ici</button>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Formulaire CC -->
            <form id="form-cc" class="form-container" method="POST" action="" 
                  style="<?php echo ($formulaire_valide && isset($_SESSION['type_note']) && $_SESSION['type_note'] === 'cc') ? 'display: block;' : 'display: none;'; ?>"
                  onsubmit="showLoading()">
                <h2>Saisir les notes CC</h2>
                <div class="info-section">
                    <p><strong>Filière:</strong> <?php echo isset($_SESSION['filiere']) ? htmlspecialchars($_SESSION['filiere']) : '' ?></p>
                    <p><strong>Niveau:</strong> <?php echo isset($_SESSION['niveau']) ? htmlspecialchars($_SESSION['niveau']) : '' ?></p>
                </div>
                
                <div class="table-container">
                    <table class="notes-table">
                        <thead>
                            <tr>
                                <th>ID Etudiant</th>
                                <th>Nom</th>
                                <th>Note CC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($etudiants as $etudiant) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($etudiant['id_etudiant']) ?></td>
                                    <td><?= htmlspecialchars($etudiant['Liste_des_etudiants']) ?></td>
                                    <td>
                                        <div class="input-wrapper">
                                            <input type="number" name="notes_cc[<?= $etudiant['id_etudiant'] ?>]" 
                                                placeholder="0-20" min="0" max="20" step="0.1"
                                                class="note-input">
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="button-container">
                    <button type="button" class="retourner" onclick="hideUploadForm()">Retourner</button>
                    <button type="submit" class="button" name="deposer_cc">Déposer le CC</button>
                </div>
            </form>

            <!-- Formulaire TP -->
            <form id="form-tp" class="form-container" method="POST" action="" 
                  style="<?php echo ($formulaire_valide && isset($_SESSION['type_note']) && $_SESSION['type_note'] === 'tp') ? 'display: block;' : 'display: none;'; ?>"
                  onsubmit="showLoading()">
                <h2>Saisir les notes TP</h2>
                <div class="info-section">
                    <p><strong>Filière:</strong> <?php echo isset($_SESSION['filiere']) ? htmlspecialchars($_SESSION['filiere']) : '' ?></p>
                    <p><strong>Niveau:</strong> <?php echo isset($_SESSION['niveau']) ? htmlspecialchars($_SESSION['niveau']) : '' ?></p>
                </div>
                
                <div class="table-container">
                    <table class="notes-table">
                        <thead>
                            <tr>
                                <th>ID Etudiant</th>
                                <th>Nom</th>
                                <th>Note TP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($etudiants as $etudiant) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($etudiant['id_etudiant']) ?></td>
                                    <td><?= htmlspecialchars($etudiant['Liste_des_etudiants']) ?></td>
                                    <td>
                                        <div class="input-wrapper">
                                            <input type="number" name="notes_tp[<?= $etudiant['id_etudiant'] ?>]" 
                                                placeholder="0-20" min="0" max="20" step="0.1"
                                                class="note-input">
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="button-container">
                    <button type="button" class="retourner" onclick="hideUploadForm()">Retourner</button>
                    <button type="submit" class="button" name="deposer_tp">Déposer le TP</button>
                </div>
            </form>

            <!-- Formulaire CF -->
            <form id="form-cf" class="form-container" method="POST" action="" 
                  style="<?php echo ($formulaire_valide && isset($_SESSION['type_note']) && $_SESSION['type_note'] === 'cf') ? 'display: block;' : 'display: none;'; ?>"
                  onsubmit="showLoading()">
                <h2>Saisir les notes CF</h2>
                <div class="info-section">
                    <p><strong>Filière:</strong> <?php echo isset($_SESSION['filiere']) ? htmlspecialchars($_SESSION['filiere']) : '' ?></p>
                    <p><strong>Niveau:</strong> <?php echo isset($_SESSION['niveau']) ? htmlspecialchars($_SESSION['niveau']) : '' ?></p>
                </div>
                
                <div class="table-container">
                    <table class="notes-table">
                        <thead>
                            <tr>
                                <th>Id Anonymat</th>
                                <th>Note CF</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($etudiants as $etudiant) : ?>
                                <tr>
                                    <td><?= isset($etudiant['id_anonymat']) ? htmlspecialchars($etudiant['id_anonymat']) : 'Non assigné' ?></td>
                                    <td>
                                        <div class="input-wrapper">
                                            <input type="number" name="notes_cf[<?= $etudiant['id_etudiant'] ?>]" 
                                                placeholder="0-20" min="0" max="20" step="0.1"
                                                class="note-input">
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="button-container">
                    <button type="button" class="retourner" onclick="hideUploadForm()">Retourner</button>
                    <button type="submit" class="button" name="deposer_cf">Déposer le CF</button>
                </div>
            </form>
            
            <script>
                <?php if (isset($_POST['deposer_cc']) || isset($_POST['deposer_tp']) || isset($_POST['deposer_cf'])): ?>
                    window.hideLoadingAfterSubmit = true;
                <?php endif; ?>

                // Passage de l'id enseignant au JS
                window.idEnseignant = <?php echo json_encode($id_enseignant); ?>;
            </script>
            <script src="../../../public/assets/js/deposer_notes_enseignant.js"></script>

        </main>
    </div>
</body>
</html>
<?php

// Démarrer la session
require_once '../../../config/session.php';

// La connexion à la base de données
require_once '../../../config/db.php';

// Envoyer un email à chaque étudiant concerné
require '../../../vendor/autoload.php'; // Inclure l'autoloader de Composer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Récupérer les informations de l'utilisateur connecté
$id_personne = $_SESSION['user_id'];
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Récupérer les infos de l'enseignant
$sql = "SELECT prenom, nom FROM personne WHERE id_personne = ? AND role = 'enseignant'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_personne);
$stmt->execute();
$result = $stmt->get_result();
$enseignant = $result->fetch_assoc();
$nom_enseignant = $enseignant['prenom'] . ' ' . $enseignant['nom'];

// Récupérer les filières enseignées par l'enseignant connecté
$sql_filieres = "
                SELECT DISTINCT f.nom_filiere
                FROM etudiant e
                JOIN evaluer ev ON e.id_etudiant = ev.id_etudiant
                JOIN enseigner en ON ev.id_matiere = en.id_matiere
                JOIN filiere f ON f.id_filiere = en.id_filiere
                WHERE en.id_enseignant = ? ";
$stmt_filieres = $conn->prepare($sql_filieres);
$stmt_filieres->bind_param("i", $id_personne);
$stmt_filieres->execute();
$result_filieres = $stmt_filieres->get_result();
$filieres = [];
while ($row = $result_filieres->fetch_assoc()) {
    $filieres[] = $row['nom_filiere'];
}

// Initialiser les variables pour les niveaux et matières
$niveaux = [];
$matieres = [];

// Récupérer les niveaux enseignés pour une filière donnée
if (isset($_POST['filiere'])) {
    $filiere = $_POST['filiere']; // Récupérer la filière sélectionnée
    $sql_niveaux = "
        SELECT DISTINCT e.niveau_filiere
        FROM etudiant e
        JOIN evaluer ev ON e.id_etudiant = ev.id_etudiant
        JOIN enseigner en ON ev.id_matiere = en.id_matiere
        JOIN filiere f ON f.id_filiere = e.id_filiere
        WHERE en.id_enseignant = ? AND f.nom_filiere = ?;
    ";
    $stmt_niveaux = $conn->prepare($sql_niveaux);
    $stmt_niveaux->bind_param("is", $id_personne, $filiere);
    $stmt_niveaux->execute();
    $result_niveaux = $stmt_niveaux->get_result();
    while ($row = $result_niveaux->fetch_assoc()) {
        $niveaux[] = $row['niveau_filiere'];
    }
}

// Récupérer les semestres enseignés par l'enseignant
$sql_semestres = "
    SELECT DISTINCT type_semestre
    FROM enseigner
    WHERE id_enseignant = ?
";
$stmt_semestres = $conn->prepare($sql_semestres);
$stmt_semestres->bind_param("i", $id_personne);
$stmt_semestres->execute();
$result_semestres = $stmt_semestres->get_result();
$semestres = [];
while ($row = $result_semestres->fetch_assoc()) {
    $semestres[] = $row['type_semestre'];
}

// Récupérer les matières enseignées pour un semestre donné
if (isset($_POST['semestre'])) {
    $semestre = $_POST['semestre']; // Récupérer le semestre sélectionné
    $sql_matieres = "
        SELECT m.nom_matiere, m.id_matiere
        FROM matiere m
        JOIN enseigner en ON m.id_matiere = en.id_matiere
        WHERE en.id_enseignant = ? AND en.type_semestre = ?
    ";
    $stmt_matieres = $conn->prepare($sql_matieres);
    $stmt_matieres->bind_param("ii", $id_personne, $semestre);
    $stmt_matieres->execute();
    $result_matieres = $stmt_matieres->get_result();
    while ($row = $result_matieres->fetch_assoc()) {
        $matieres[] = $row;
    }
}

// Traitement du formulaire de dépôt de fichiers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    // Récupérer les données du formulaire
    $filiere = $_POST['filiere'];
    $niveau = $_POST['niveau'];
    $semestre = $_POST['semestre'];
    $id_matiere = $_POST['matiere'];
    $type_ressource = $_POST['type_ressource']; // Récupéré depuis le bouton cliqué

    // Récupérer le nom de la matière
    $sql_matiere = "SELECT nom_matiere FROM matiere WHERE id_matiere = ?";
    $stmt_matiere = $conn->prepare($sql_matiere);
    $stmt_matiere->bind_param("i", $id_matiere);
    $stmt_matiere->execute();
    $result_matiere = $stmt_matiere->get_result();
    $matiere = $result_matiere->fetch_assoc();
    $nom_matiere = $matiere['nom_matiere'];

    // Chemin de stockage des fichiers
    $uploadDir = '../students/uploads/';

    // Parcourir les fichiers uploadés
    foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
        $fileName = basename($_FILES['files']['name'][$key]);
        $filePath = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Déplacer le fichier vers le dossier de stockage
        if (move_uploaded_file($tmp_name, $filePath)) {
            // Générer un ID unique pour la ressource
            $id_ressource = uniqid();

            // Insérer la ressource dans la table Ressource
            $sql_insert_ressource = "
                INSERT INTO ressource (id_ressource, id_enseignant, type, chemin_fichier, id_matiere, date_depot)
                VALUES (?, ?, ?, ?, ?, NOW())
            ";
            $stmt_insert_ressource = $conn->prepare($sql_insert_ressource);
            $stmt_insert_ressource->bind_param("sissi", $id_ressource, $id_personne, $type_ressource, $filePath, $id_matiere);
            $stmt_insert_ressource->execute();

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

            // Parcourir les étudiants concernés
            while ($etudiant = $result_etudiants->fetch_assoc()) {
                $id_etudiant = $etudiant['id_personne'];
                $email = $etudiant['email'];

                // Insérer dans la table Recevoir_Ressources
                $sql_insert_recevoir = "
                    INSERT INTO recevoir_ressources (id_etudiant, id_ressource)
                    VALUES (?, ?)
                ";
                $stmt_insert_recevoir = $conn->prepare($sql_insert_recevoir);
                $stmt_insert_recevoir->bind_param("is", $id_etudiant, $id_ressource);
                $stmt_insert_recevoir->execute();

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

                    // Lien de connexion
                    $link = "https://www.gcnu-plateforme.free.nf/apps/views/global/Connexion.php";

                    // Contenu du mail
                    $mail->isHTML(true);
                
                    $mail->Subject = "📚 Nouvelle ressource disponible : {$type_ressource} de {$nom_matiere}";
                    $mail->Body = "
                        <!DOCTYPE html>
                        <html lang='fr'>
                        <head>
                            <meta charset='UTF-8'>
                            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                            <title>Nouvelle ressource disponible</title>
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
                                .resource-info {
                                    background-color: #f0f7ff;
                                    border-left: 4px solid #007bff;
                                    padding: 15px;
                                    margin: 20px 0;
                                    border-radius: 5px;
                                }
                                .resource-info p {
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
                                .footer img {
                                    width: 100px;
                                    margin-bottom: 10px;
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
                                    <h1>Nouvelle Ressource Pédagogique</h1>
                                </div>
                                <div class='content'>
                                    <p>Bonjour,</p>
                                    
                                    <p>Nous sommes heureux de vous informer qu'une nouvelle ressource pédagogique est disponible sur votre plateforme <strong>Universite de Djibouti</strong>.</p>
                                    
                                    <div class='resource-info'>
                                        <p><strong>Type de ressource :</strong> <span class='highlight'>{$type_ressource}</span></p>
                                        <p><strong>Matière :</strong> <span class='highlight'>{$nom_matiere}</span></p>
                                        <p><strong>Filière :</strong> <span class='highlight'>{$filiere} - {$niveau}</span></p>
                                        <p><strong>Professeur :</strong> <span class='highlight'>{$nom_enseignant}</span></p>
                                        <p><strong>Date de publication :</strong> <span class='highlight'>" . date('d/m/Y') . "</span></p>
                                    </div>
                                    
                                    <p>Ne manquez pas cette opportunité d'enrichir vos connaissances et d'améliorer votre parcours académique.</p>
                                    
                                    <p style='text-align: center;'>
                                        <a href='{$link}' class='action-button'>ACCÉDER À LA RESSOURCE</a>
                                    </p>
                                    
                                    <p>Si vous avez des questions concernant cette ressource, n'hésitez pas à contacter votre enseignant ou l'administration.</p>
                                    
                                    <p>Bonne étude !</p>
                                    
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
                    // Envoi du mail
                    $mail->send();
                } catch (Exception $e) {
                    echo "<script>alert('Erreur lors de l'envoi de l'e-mail à $email : {$mail->ErrorInfo}');</script>";
                }
            }

            echo "<script>alert('Fichier déposé avec succès et notifications envoyées.');</script>";
        } else {
            echo "<script>alert('Erreur lors du téléversement du fichier.');</script>";
        }
    }
}
?>

<!DOCTYPE html>  
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Déposer Cours - Université de Djibouti</title>
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
     <link rel="stylesheet" href="../../../public/assets/css/deposer_cours.css">
    <style>
       
    </style>
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
                    <a href="deposer_cours.php"><li><i class="bi-book"></i><span>Dépôts des cours</span></li></a>
                    <a href="enseignant_planning.php"><li><i class="bi-calendar"></i><span>Consulter le planning</span></li></a>
                    <a href="deposer_notes.php"><li><i class="bi-journal"></i><span>Dépôts du notes</span></li></a>
                    <div class="deconnection">
                        <a href="../../../config/logout.php"><i class="bi-box-arrow-right"></i><span>Déconnexion</span></a>
                    </div>
                </ul>
            </nav>
        </aside>

        <!-- Contenu principal élégant -->
        <main class="main-content">
            <!-- Indicateur de chargement premium -->
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

            <header>
                <div class="notification"><?php echo $id_personne; ?> - <?php echo $role; ?></div>
            </header>

            <!-- Section "Déposer vos ressources" -->
            <div class="deposer-cours">
                <h2>Déposer vos ressources</h2>
                <ul>
                    <li>
                        <img src="../../../public/assets/img/logo_etudiant_1.png" alt="Partager les cours">
                        <strong>Partager les cours</strong>
                        <div class="button-group">
                            <button onclick="showUploadForm('cours')">Cliquer ici</button>
                        </div>
                    </li>
                    <li>
                        <img src="../../../public/assets/img/logo_etudiant_2.png" alt="Partager les TD">
                        <strong>Partager les TD</strong>
                        <div class="button-group">
                            <button onclick="showUploadForm('td')">Cliquer ici</button>
                        </div>
                    </li>
                    <li>
                        <img src="../../../public/assets/img/logo_etudiant_3.png" alt="Publier les TP">
                        <strong>Publier les TP</strong>
                        <div class="button-group">
                            <button onclick="showUploadForm('tp')">Cliquer ici</button>
                        </div>
                    </li>
                </ul>
            </div>
        </main>
    </div>

    <!-- Overlay -->
    <div id="overlay"></div>

    <!-- Formulaire de dépôt de fichiers -->
    <div id="uploadFormContainer">
        <h2>Dépôt de fichiers</h2>
        <form id="uploadForm" action="" method="POST" enctype="multipart/form-data" onsubmit="showLoading()">
            <div>
                <label for="fileInput">Sélectionnez vos fichiers :</label>
                <input type="file" id="fileInput" name="files[]" multiple>
                <input type="hidden" name="type_ressource" id="type_ressource">
            </div>

            <div>
                <label for="filiere">Filière :</label>
                <select id="filiere" name="filiere" required>
                    <option value="">Sélectionnez une filière</option>
                    <?php foreach ($filieres as $f): ?>
                        <option value="<?= $f ?>"><?= $f ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="niveau">Niveau :</label>
                <select id="niveau" name="niveau" required>
                    <option value="L1">L1</option>
                    <option value="L2">L2</option>
                    <option value="L3">L3</option>
                </select>
            </div>

            <div>
                <label for="semestre">Semestre :</label>
                <select id="semestre" name="semestre" required>
                    <option value="">Sélectionnez un semestre</option>
                    <?php foreach ($semestres as $s): ?>
                        <option value="<?= $s ?>">Semestre <?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="matiere">Matière :</label>
                <select id="matiere" name="matiere" required>
                    <option value="">Sélectionnez d'abord un semestre</option>
                    <?php if (!empty($matieres)): ?>
                        <?php foreach ($matieres as $m): ?>
                            <option value="<?= $m['id_matiere'] ?>"><?= $m['nom_matiere'] ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="div-boutton">
                <button type="button" class="retourner" onclick="hideUploadForm()">Retourner</button>
                <button type="submit" class="button" name="change_password">Déposer le fichier</button>
            </div>
        </form>
    </div>
    
    <script>
    <?php if(isset($_POST['change_password'])): ?>
        window.hideLoadingAfterSubmit = true;
    <?php endif; ?>
    </script>
    <script src="../../../public/assets/js/deposer_cours_enseignant.js"></script>

</body>
</html>
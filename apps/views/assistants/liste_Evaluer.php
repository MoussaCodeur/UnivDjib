<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';

require '../../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Récupération des informations de l'assistant
$stmt = $conn->prepare("SELECT f.id_filiere, a.niveau 
                      FROM filiere f
                      JOIN assistant a ON f.responsable_id = a.id_assistant
                      WHERE a.id_assistant = ?");
if (!$stmt) {
    die("Erreur de préparation : " . $conn->error);
}

$stmt->bind_param("i", $_SESSION['user_id']);
if (!$stmt->execute()) {
    die("Erreur d'exécution : " . $stmt->error);
}

$result = $stmt->get_result();
$assistant = $result->fetch_assoc();
$stmt->close();

if (!$assistant) {
    die("Assistant non trouvé");
}

// Récupération des départements
$departements = [];
$result = $conn->query("SELECT id_filiere, nom_filiere FROM filiere f
JOIN assistant a ON a.id_assistant = f.responsable_id
WHERE a.departement = f.nom_filiere");
if ($result) {
    $departements = $result->fetch_all(MYSQLI_ASSOC);
}

// Gestion des filtres
$selected_departement = $assistant['id_filiere'];
$selected_niveau = explode(',', $assistant['niveau'])[0];
$selected_semestre = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['departement'])) {
    $selected_departement = $conn->real_escape_string($_POST['departement']);
    $selected_niveau = $conn->real_escape_string($_POST['niveau']);
    $selected_semestre = (int)$_POST['semestre'];
}

// Récupération des étudiants et matières
$students = [];
$matieres = [];

if ($selected_departement && $selected_niveau && $selected_semestre) {
    // Requête pour les étudiants
    $stmt = $conn->prepare("SELECT e.id_etudiant, p.nom, p.prenom
                          FROM etudiant e
                          JOIN personne p ON e.id_etudiant = p.id_personne
                          WHERE e.id_filiere = ? AND e.niveau_filiere = ?
                          ORDER BY p.nom, p.prenom");
    if (!$stmt) {
        die("Erreur de préparation : " . $conn->error);
    }
    $stmt->bind_param("ss", $selected_departement, $selected_niveau);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Requête pour les matières
    $stmt = $conn->prepare("SELECT m.id_matiere, m.nom_matiere
                          FROM matiere m
                          WHERE m.id_filiere = ?
                          AND m.niveau_filiere = ?
                          AND m.type_simestre = ?");
    if (!$stmt) {
        die("Erreur de préparation : " . $conn->error);
    }
    $stmt->bind_param("ssi", $selected_departement, $selected_niveau, $selected_semestre);
    $stmt->execute();
    $matieres = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Traitement des évaluations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['evaluations'])) {
    $evaluationsParMatiere = [];

    foreach ($_POST['evaluations'] as $id_etudiant => $matieres) {
        foreach ($matieres as $id_matiere => $valeur) {
            // Vérification existence évaluation
            $stmt = $conn->prepare("SELECT id_evaluation FROM evaluer 
                                  WHERE id_etudiant = ? AND id_matiere = ?");
            $stmt->bind_param("ss", $id_etudiant, $id_matiere);
            $stmt->execute();
            $exists = $stmt->get_result()->num_rows > 0;
            $stmt->close();
            
            if (!$exists) {
                try {
                    // Insertion anonymat
                    $id_anonymat = mt_rand(100000, 999999);
                    $stmt = $conn->prepare("INSERT INTO anonymat (id_anonymat, id_president) VALUES (?, 220001400)");
                    $stmt->bind_param("i", $id_anonymat);
                    $stmt->execute();
                    $stmt->close();

                    // Insertion évaluation
                    $id_evaluation = uniqid();
                    $stmt = $conn->prepare("INSERT INTO evaluer 
                                          (id_evaluation, id_etudiant, id_matiere, id_anonymat, date_evaluation)
                                          VALUES (?, ?, ?, ?, NOW())");
                    $stmt->bind_param("sssi", $id_evaluation, $id_etudiant, $id_matiere, $id_anonymat);
                    $stmt->execute();
                    $stmt->close();

                    // Récupération info étudiant
                    $stmt = $conn->prepare("SELECT nom, prenom FROM personne WHERE id_personne = ?");
                    $stmt->bind_param("s", $id_etudiant);
                    $stmt->execute();
                    $etudiant = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    // Récupération info matière
                    $stmt = $conn->prepare("SELECT m.nom_matiere, p.email, p.nom AS nom_enseignant, p.prenom 
                                          FROM matiere m
                                          JOIN enseigner e ON m.id_matiere = e.id_matiere
                                          JOIN enseignant en ON e.id_enseignant = en.id_enseignant
                                          JOIN personne p ON en.id_enseignant = p.id_personne
                                          WHERE m.id_matiere = ?");
                    $stmt->bind_param("s", $id_matiere);
                    $stmt->execute();
                    $matiere_info = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if ($matiere_info && $etudiant) {
                        $evaluationsParMatiere[$id_matiere]['enseignant'] = $matiere_info;
                        $evaluationsParMatiere[$id_matiere]['etudiants'][] = $etudiant;
                    }

                } catch (mysqli_sql_exception $e) {
                    die("Erreur base de données : " . $e->getMessage());
                }
            }
        }
    }

    // Envoi des emails avec CSV
    foreach ($evaluationsParMatiere as $id_matiere => $data) {
        try {
            $temp_file = tempnam(sys_get_temp_dir(), 'eval_') . '.csv';
            $fp = fopen($temp_file, 'w');
            fputcsv($fp, ['Nom', 'Prénom', 'Date d\'évaluation']);
            
            foreach ($data['etudiants'] as $etudiant) {
                fputcsv($fp, [$etudiant['nom'], $etudiant['prenom'], date('d/m/Y')]);
            }
            fclose($fp);

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'gestioncoursuniversitaire@gmail.com';
            $mail->Password = 'jevu knsh qkus oxhf';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom('gestioncoursuniversitaire@gmail.com', 'Université de Djibouti');
            $mail->addAddress($data['enseignant']['email'], $data['enseignant']['prenom'] . ' ' . $data['enseignant']['nom']);
            $mail->addAttachment($temp_file, 'Liste_Etudiants_Evalues_' . $data['enseignant']['nom_matiere'] . '.csv');

            $mail->Body = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Étudiants Évalués</title>
</head>
<body style="margin: 0; padding: 0; font-family: \'Segoe UI\', Tahoma, Arial, sans-serif; background-color: #f5f5f5; color: #333333; line-height: 1.6;">
    <!-- En-tête -->
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 650px; background-color: #ffffff; margin-top: 20px; box-shadow: 0 0 20px rgba(0,0,0,0.1);">
        <!-- Logo et Bannière -->
        <tr>
            <td align="center" bgcolor="#003366" style="padding: 25px 0;">
            </td>
        </tr>
        
        <!-- Titre Principal -->
        <tr>
            <td bgcolor="#f9f9f9" style="padding: 20px 30px; border-bottom: 3px solid #007bff;">
                <h2 style="margin: 0; color: #003366; font-size: 22px; font-weight: 600;">Liste des Étudiants Évalués</h2>
                <p style="margin: 5px 0 0 0; font-size: 18px; color: #007bff;">' . $data['enseignant']['nom_matiere'] . '</p>
            </td>
        </tr>
        
        <!-- Contenu Principal -->
        <tr>
            <td bgcolor="#ffffff" style="padding: 30px 30px 20px 30px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <!-- Salutation -->
                    <tr>
                        <td style="padding-bottom: 20px; color: #333333; font-size: 16px;">
                            <p style="margin: 0;">Bonjour <strong style="color: #003366;">' . $data['enseignant']['prenom'] . '</strong>,</p>
                        </td>
                    </tr>
                    
                    <!-- Message Principal -->
                    <tr>
                        <td style="padding-bottom: 30px; color: #333333; font-size: 16px; line-height: 1.6;">
                            <p>Nous avons le plaisir de vous transmettre ci-joint la liste complète des étudiants évalués pour votre matière :</p>
                            
                            <p style="background-color: #f5f7fa; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0;">
                                <span style="font-weight: 600; color: #003366; font-size: 17px;">' . $data['enseignant']['nom_matiere'] . '</span>
                            </p>
                            
                            <p>Ce document recense un total de <span style="font-weight: 600; color: #007bff;">' . count($data['etudiants']) . '</span> étudiant' . (count($data['etudiants']) > 1 ? "s" : "") . ' ayant participé à l\'évaluation.</p>
                            
                            <p>Ces données ont été soigneusement compilées par notre service pédagogique et sont désormais disponibles pour votre consultation.</p>
                            
                            <p>Si vous avez des questions ou besoin d\'informations complémentaires concernant ces évaluations, n\'hésitez pas à nous contacter.</p>
                        </td>
                    </tr>
                    
                    <!-- Bouton d\'action -->
                    <tr>
                        <td align="center" style="padding: 20px 0 30px 0;">
                            <table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" bgcolor="#007bff" style="border-radius: 4px;">
                                        <a href="https://www.gcnu-plateforme.free.nf/apps/views/global/Connexion.php" target="_blank" style="padding: 12px 28px; border-radius: 4px; color: #ffffff; display: inline-block; font-size: 16px; font-weight: 600; text-decoration: none;">ACCÉDER À MON ESPACE</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <!-- Information Complémentaire -->
        <tr>
            <td bgcolor="#f5f7fa" style="padding: 20px 30px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="color: #555555; font-size: 15px; line-height: 1.5;">
                            <p style="margin: 0;"><span style="color: #003366; font-weight: 600;">⚠️ Information importante :</span> Veuillez conserver ce document dans vos archives pédagogiques pour référence ultérieure.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <!-- Signature -->
        <tr>
            <td bgcolor="#ffffff" style="padding: 30px 30px 20px 30px; border-top: 1px solid #eeeeee;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="color: #555555; font-size: 15px;">
                            <p style="margin: 0;">Cordialement,</p>
                            <p style="margin: 8px 0 0 0; color: #003366; font-weight: 600;">Le Service Pédagogique</p>
                            <p style="margin: 5px 0 0 0; font-size: 14px;">Département des Études</p>
                            <p style="margin: 5px 0 0 0; font-size: 14px;">Université de Djibouti</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <!-- Pied de page -->
        <tr>
            <td bgcolor="#003366" style="padding: 15px 30px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="color: #ffffff; font-size: 13px; text-align: center;">
                            <p style="margin: 0;">&copy; ' . date("Y") . ' Université de Djibouti. Tous droits réservés.</p>
                            <p style="margin: 5px 0 0 0; font-size: 12px; color: #cccccc;">Ce message est généré automatiquement, merci de ne pas y répondre.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
$mail->isHTML(true);
$mail->Subject = 'Liste des Étudiants Évalués - ' . $data['enseignant']['nom_matiere'];
            $mail->send();
            unlink($temp_file);
            $_SESSION['message'] = "Fichier CSV envoyé à {$data['enseignant']['email']}";

        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
            if (file_exists($temp_file)) {
                unlink($temp_file);
            }
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

?>
<!DOCTYPE html>  
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Évaluation des étudiants</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/listes_evaluations.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
</head>
<body>
    <div class="dashboard">
        <!-- Mobile Menu Toggle -->
        <button class="menu-toggle" id="menuToggle">
            <i class="bi bi-list"></i>
        </button>

        <!-- Overlay for mobile menu -->
        <div class="mobile-overlay" id="mobileOverlay"></div>

        <!-- Barre latérale -->
        <aside class="sidebar" id="sidebar">
            <a href="profil_assistant.php" style="text-decoration: none; color: inherit;">
                <div class="sidebar-header">
                    <img src="../../../public/assets/img/U-remove.png" alt="Logo Université" class="sidebar-logo">
                    <h2>Assistant Panel</h2>
                </div>
            </a>
            
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="acceuil_assistant.php"><i class="bi bi-house-door"></i> <span>Accueil</span></a>
                    </li>
                    <li>
                        <a href="profile_assistant.php"><i class="bi bi-person"></i> <span>Profil</span></a>
                    </li>
                    <li>
                        <a href="gestion_planning.php"><i class="bi bi-calendar-week"></i> <span>Gestion Planning</span></a>
                    </li>
                    <li class="active">
                        <a href="liste_Evaluer.php"><i class="bi bi-list-check"></i> <span>Évaluations</span></a>
                    </li>
                </ul>
                
                <div class="logout-section">
                    <a href="../../../config/logout.php" class="logout-btn">
                        <i class="bi bi-box-arrow-right"></i> <span>Déconnexion</span>
                    </a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <div class="container">
                <h1>Évaluation des étudiants</h1>
                
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> <?= $_SESSION['message'] ?>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <i class="bi bi-exclamation-circle"></i> <?= $_SESSION['error'] ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-funnel"></i> Filtres de sélection
                    </div>
                    <div class="card-body">
                        <form method="post" class="filter-form" id="filterForm">
                            <div class="form-group">
                                <label for="departement">Département:</label>
                                <select id="departement" name="departement" required>
                                    <?php foreach ($departements as $d): ?>
                                    <option value="<?= $d['id_filiere'] ?>" <?= $d['id_filiere'] == $selected_departement ? 'selected' : '' ?>>
                                        <?= $d['nom_filiere'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="niveau">Niveau:</label>
                                <select id="niveau" name="niveau" required>
                                    <?php foreach (explode(',', $assistant['niveau']) as $n): ?>
                                    <option value="<?= $n ?>" <?= $n === $selected_niveau ? 'selected' : '' ?>>
                                        <?= $n ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="semestre">Semestre:</label>
                                <select id="semestre" name="semestre" required>
                                    <option value="1" <?= $selected_semestre === 1 ? 'selected' : '' ?>>Semestre 1</option>
                                    <option value="2" <?= $selected_semestre === 2 ? 'selected' : '' ?>>Semestre 2</option>
                                </select>
                            </div>

                            <div class="form-group btn-action">
                                <button type="submit" class="btn" id="filterBtn">
                                    <i class="bi bi-search"></i> Afficher
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (!empty($students) && !empty($matieres)): ?>
                <form method="post" id="evaluationForm">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th class="fixed-column">ID</th>
                                    <th class="fixed-column">Étudiant</th>
                                    <?php foreach ($matieres as $matiere): ?>
                                    <th><?= $matiere['nom_matiere'] ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="fixed-column">
                                        <?= htmlspecialchars($student['id_etudiant']) ?>
                                    </td>
                                    <td class="fixed-column">
                                        <?= htmlspecialchars($student['prenom']) . ' ' . htmlspecialchars($student['nom']) ?>
                                    </td>
                                    <?php foreach ($matieres as $matiere): 
                                        $stmt = $conn->prepare("
                                            SELECT id_evaluation FROM evaluer 
                                            WHERE id_etudiant = ? AND id_matiere = ?
                                        ");
                                        $stmt->bind_param("ss", $student['id_etudiant'], $matiere['id_matiere']);
                                        $stmt->execute();
                                        $stmt->store_result();
                                        $evalue = $stmt->num_rows > 0;
                                        $stmt->close();
                                    ?>
                                    <td>
                                        <div class="checkbox-container">
                                            <label class="custom-checkbox">
                                                <input type="checkbox" 
                                                       name="evaluations[<?= $student['id_etudiant'] ?>][<?= $matiere['id_matiere'] ?>]"
                                                       <?= $evalue ? 'checked disabled' : '' ?>>
                                                <span class="checkmark"></span>
                                            </label>
                                        </div>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="page-actions">
                        <button type="submit" class="btn" id="submitEvalBtn">
                            <i class="bi bi-save"></i> Enregistrer les évaluations
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Dark Mode Toggle -->
    <button class="theme-toggle" id="themeToggle">
        <i class="bi bi-moon"></i>
    </button>

    <!-- Overlay de chargement -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Traitement en cours...</div>
            <div class="progress-bar">
                <div class="progress" id="uploadProgress"></div>
            </div>
        </div>
    </div>

    <!-- jQuery si nécessaire -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Vos scripts -->
    <script src="../../../public/assets/js/theme.js"></script>
    <script src="../../../public/assets/js/liste_evaluation_loading.js"></script>
    <script src="../../../public/assets/js/table_interaction.js"></script>
</body>
</html>
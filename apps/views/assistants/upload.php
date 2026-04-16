<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion à la base de données
require_once '../../../config/db.php';

require '../../../vendor/autoload.php'; // Inclure l'autoloader de Composer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fonction pour afficher le message de succès popup
function showSuccessPopup($message) {
    echo "
    <div id='successPopup' class='popup-overlay'>
        <div class='popup-content'>
            <div class='popup-header'>
                <i class='fas fa-check-circle'></i>
                <h3>Opération réussie</h3>
            </div>
            <div class='popup-body'>
                <p>$message</p>
            </div>
            <div class='popup-footer'>
                <button id='closePopup' class='btn-primary'>OK</button>
            </div>
        </div>
    </div>
    
    <style>
    .popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .popup-content {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        width: 100%;
        max-width: 450px;
        animation: popIn 0.3s ease-out forwards;
    }
    
    @keyframes popIn {
        0% { transform: scale(0.8); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .popup-header {
        background-color: #f8f9fa;
        padding: 20px;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        text-align: center;
        border-bottom: 1px solid #eee;
    }
    
    .popup-header i {
        font-size: 48px;
        color: #28a745;
        margin-bottom: 10px;
        display: block;
    }
    
    .popup-header h3 {
        margin: 0;
        color: #333;
        font-size: 20px;
    }
    
    .popup-body {
        padding: 25px 20px;
        text-align: center;
        font-size: 16px;
        color: #555;
    }
    
    .popup-footer {
        padding: 15px 20px;
        text-align: center;
        border-top: 1px solid #eee;
    }
    
    .btn-primary {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 25px;
        border-radius: 4px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .btn-primary:hover {
        background-color: #0069d9;
    }
    </style>
    
    <script>
    document.getElementById('closePopup').addEventListener('click', function() {
        document.getElementById('successPopup').style.display = 'none';
        // Redirection après fermeture
        window.location.href = 'gestion_planning.php';
    });
    </script>
    ";
}

try {
    $id_assistant = (int)$_SESSION['user_id'];
    $type = isset($_POST['type']) ? htmlspecialchars($_POST['type']) : '';

    if (!in_array($type, ['enseignant', 'etudiant'])) {
        throw new Exception("Type de destinataire invalide");
    }

    // Ajoutez ces lignes après la récupération du $type
    $type_planning = isset($_POST['type_planning']) ? htmlspecialchars($_POST['type_planning']) : '';
    $semestre = isset($_POST['semestre']) ? htmlspecialchars($_POST['semestre']) : '';

    // Validation des nouvelles valeurs
    if (!in_array($type_planning, ['cours', 'examen', 'autres'])) {
        throw new Exception("Type de planning invalide");
    }

    if (!in_array($semestre, ['1', '2'])) {
        throw new Exception("Semestre invalide");
    }

    // Vérification du fichier
    if (!isset($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
        $error_message = "Erreur lors du transfert du fichier";
        if (isset($_FILES['fichier']['error'])) {
            switch ($_FILES['fichier']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message = "Le fichier est trop volumineux";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_message = "Le fichier n'a été que partiellement téléchargé";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_message = "Aucun fichier n'a été téléchargé";
                    break;
            }
        }
        throw new Exception($error_message);
    }

    $file_name = $_FILES['fichier']['name'];
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        throw new Exception("Seuls les fichiers PDF sont autorisés");
    }

    if ($_FILES['fichier']['size'] > 10 * 1024 * 1024) {
        throw new Exception("Le fichier est trop volumineux (max 10 Mo)");
    }

    // Déterminer le dossier de destination en fonction du type
    $upload_dir = ($type === 'enseignant') ? '../teachers/uploads/' : '../students/uploads/';
    
    // Créer le dossier s'il n'existe pas
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_path = $upload_dir . uniqid() . '_' . basename($file_name);
    if (!move_uploaded_file($_FILES['fichier']['tmp_name'], $file_path)) {
        throw new Exception("Échec de l'enregistrement du fichier");
    }

    // Récupération des données de l'assistant
    $stmt = $conn->prepare("SELECT p.nom, p.prenom, a.departement, a.niveau 
                            FROM assistant a , personne p 
                            WHERE p.id_personne = a.id_assistant 
                            AND id_assistant = ?;");
    $stmt->bind_param("i", $id_assistant);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Assistant non trouvé");
    }
    $assistant = $result->fetch_assoc();
    $departement = $assistant['departement'];
    $niveaux_autorises = explode(',', $assistant['niveau']);
    $nom_complet_assistant = $assistant['prenom'] . ' ' . $assistant['nom'];

    $sql_check = "SELECT COUNT(*) FROM filiere WHERE responsable_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $id_assistant);
    $stmt_check->execute();
    $count = $stmt_check->get_result()->fetch_row()[0];

    if ($count === 0) {
        throw new Exception("Aucune filière attribuée à cet assistant");
    }

    $conn->begin_transaction();
    $date_actuelle = date("d/m/Y");

    // Avant la préparation de l'email, vous pouvez ajouter :
    $types_planning_traduits = [
        'cours' => 'Planning de Cours',
        'examen' => 'Planning d\'Examen',
        'autres' => 'Planning Divers'
    ];

    $type_planning_libelle = isset($types_planning_traduits[$type_planning]) ? $types_planning_traduits[$type_planning] : $type_planning;

    // Préparation du modèle d'email HTML professionnel
    $email_template = '<!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title>Université de Djibouti - Notification Planning</title>
                        </head>
                        <body style="margin: 0; padding: 0; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5;">
                            <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin-top: 20px;">
                                <tr>
                                <td align="center" bgcolor="#003366" style="padding: 20px 0;">
                                    </td>
                                </tr>
                                <tr>
                                    <td bgcolor="#ffffff" style="padding: 40px 30px; border-left: 1px solid #dddddd; border-right: 1px solid #dddddd;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td style="color: #003366; font-size: 24px; font-weight: bold;">
                                                    Notification De Planning
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 20px 0; color: #333333; font-size: 16px; line-height: 24px;">
                                                    <p>Cher(e) {ROLE},</p>
                                                    <p>Nous vous informons qu\'un nouveau planning a été mis à votre disposition par la Direction des Études.</p>
                                                    <p><strong>Informations :</strong></p>
                                                    <ul style="padding-left: 20px;">
                                                        <li>Envoyer par Assistant : <strong>{NomAssistant}</strong></li>
                                                        <li>Département : <strong>{DEPARTEMENT}</strong></li>
                                                        <li>Type de planning : <strong>{TYPE_PLANNING}</strong></li>
                                                        <li>Semestre : <strong>{SEMESTRE}</strong></li>
                                                        {NIVEAU_INFO}
                                                        <li>Date de publication : <strong>{DATE}</strong></li>
                                                    </ul>
                                                    <p>Ce document contient des informations importantes concernant votre emploi du temps et vos obligations académiques. Nous vous invitons à le consulter dans les plus brefs délais.</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="padding: 30px 0;">
                                                    <table border="0" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td align="center" bgcolor="#007bff" style="border-radius: 4px;">
                                                                <a href="{LINK}" target="_blank" style="padding: 15px 25px; border-radius: 4px; color: #ffffff; display: inline-block; font-size: 16px; font-weight: bold; text-decoration: none;">ACCÉDER À MON ESPACE</a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td bgcolor="#f9f9f9" style="padding: 20px 30px; border-left: 1px solid #dddddd; border-right: 1px solid #dddddd; border-bottom: 1px solid #dddddd;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td style="color: #555555; font-size: 14px;">
                                                    <p style="margin: 0;">Cordialement,</p>
                                                    <p style="margin: 5px 0 0 0;"><strong>Direction des Études</strong></p>
                                                    <p style="margin: 5px 0 0 0;">Université de Djibouti</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 15px; text-align: center; font-size: 12px; color: #777777;">
                                        <p style="margin: 0;">Ce message est généré automatiquement, merci de ne pas y répondre.</p>
                                        <p style="margin: 5px 0 0 0;">© 2025 Université de Djibouti. Tous droits réservés.</p>
                                    </td>
                                </tr>
                            </table>
                        </body>
                        </html>';

    $link = "https://www.gcnu-plateforme.free.nf/apps/views/global/Connexion.php";

    if ($type === 'enseignant') {
        $id_enseignant = filter_input(INPUT_POST, 'id_enseignant', FILTER_VALIDATE_INT);
        if (!$id_enseignant) {
            throw new Exception("ID enseignant invalide");
        }

        $stmt = $conn->prepare("SELECT id_personne, email FROM personne WHERE id_personne = ? AND role = 'enseignant'");
        $stmt->bind_param("i", $id_enseignant);
        $stmt->execute();
        $result_email = $stmt->get_result();
        if ($result_email->num_rows === 0) {
            throw new Exception("Enseignant introuvable");
        }
        $enseignant = $result_email->fetch_assoc();
        $destinataire = $enseignant['email'];

        /// Pour les enseignants
        $stmt = $conn->prepare("INSERT INTO planning (id_assistant, id_personne, chemin_planning, date_depot, role_personne, departement, type_planning, type_semestre) VALUES (?, ?, ?, NOW(), 'enseignant', ?, ?, ?)");
        $stmt->bind_param("iissss", $id_assistant, $id_enseignant, $file_path, $departement, $type_planning, $semestre);
        $stmt->execute();

        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Erreur lors de l'insertion du planning");
        }

        // Pour les enseignants
        $email_content = str_replace(
            ['{ROLE}', '{DEPARTEMENT}', '{DATE}', '{LINK}', '{TYPE_PLANNING}', '{SEMESTRE}', '{NIVEAU_INFO}', '{NomAssistant}'],
            ['Enseignant', $departement, $date_actuelle, $link, 
            ucfirst($type_planning), 
            'Semestre ' . $semestre,
            '',
            $nom_complet_assistant], // Ajout du nom complet de l'assistant ici
            $email_template
        );
        

        // Envoi email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'gestioncoursuniversitaire@gmail.com';
            $mail->Password = 'jevu knsh qkus oxhf';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('gestioncoursuniversitaire@gmail.com', 'Université de Djibouti');
            $mail->addAddress($destinataire);
            $mail->isHTML(true);
            $mail->Subject = 'Nouveau planning disponible - Université de Djibouti';
            $mail->Body = $email_content;
            $mail->send();
            
            showSuccessPopup("Le planning a été envoyé avec succès à l'enseignant et une notification par email lui a été transmise.");
        } catch (Exception $e) {
            error_log("Erreur d'envoi mail : " . $mail->ErrorInfo);
            showSuccessPopup("Le planning a été envoyé à l'enseignant, mais l'email n'a pas pu être envoyé.");
        }
    }

    elseif ($type === 'etudiant') {
        $niveau = isset($_POST['niveau']) ? htmlspecialchars($_POST['niveau']) : '';
        if (!in_array($niveau, $niveaux_autorises)) {
            throw new Exception("Niveau non autorisé pour cet assistant");
        }
    
        $sql = "SELECT e.id_etudiant, p.email 
                FROM etudiant e
                INNER JOIN personne p ON e.id_etudiant = p.id_personne
                INNER JOIN filiere f ON e.id_filiere = f.id_filiere
                INNER JOIN assistant a ON f.responsable_id = a.id_assistant
                WHERE e.niveau_filiere = ? AND a.id_assistant = ?";
    
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $niveau, $id_assistant);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows === 0) {
            throw new Exception("Aucun étudiant trouvé pour le niveau '$niveau' dans le département '$departement'");
        }
    
        // Correction ici - préparez la requête avec tous les champs nécessaires
        $insert_stmt = $conn->prepare("INSERT INTO planning (id_assistant, id_personne, chemin_planning, date_depot, role_personne, departement, type_planning, type_semestre) VALUES (?, ?, ?, NOW(), 'etudiant', ?, ?, ?)");
        
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'gestioncoursuniversitaire@gmail.com';
        $mail->Password = 'jevu knsh qkus oxhf';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->setFrom('gestioncoursuniversitaire@gmail.com', 'Université de Djibouti');
        $mail->isHTML(true);
        $mail->Subject = 'Nouveau planning disponible - Université de Djibouti';
    
        $count_success = 0;
        $email_failures = 0;
    
        // Pour les étudiants
        $email_content = str_replace(
            ['{ROLE}', '{DEPARTEMENT}', '{DATE}', '{LINK}', '{TYPE_PLANNING}', '{SEMESTRE}', '{NIVEAU_INFO}', '{NomAssistant}'],
            ['Étudiant', $departement, $date_actuelle, $link, 
             $type_planning_libelle, 
             'Semestre ' . $semestre,
             '<li>Niveau : <strong>' . $niveau . '</strong></li>',
             $nom_complet_assistant], 
            $email_template
        );
    
        while ($etudiant = $result->fetch_assoc()) {
            // Correction ici - liez tous les paramètres nécessaires
            $insert_stmt->bind_param("iissss", 
                $id_assistant, 
                $etudiant['id_etudiant'], 
                $file_path, 
                $departement, 
                $type_planning, 
                $semestre);
            
            $insert_stmt->execute();
    
            if ($insert_stmt->affected_rows > 0) {
                $mail->clearAddresses();
                $mail->addAddress($etudiant['email']);
                $mail->Body = $email_content;
                try {
                    $mail->send();
                    $count_success++;
                } catch (Exception $e) {
                    $email_failures++;
                    error_log("Échec d'envoi à " . $etudiant['email'] . " : " . $mail->ErrorInfo);
                }
            }
        }
    
        if ($email_failures > 0) {
            showSuccessPopup("$count_success planning(s) ont été envoyés avec succès aux étudiants. Cependant, $email_failures email(s) n'ont pas pu être délivrés.");
        } else {
            showSuccessPopup("$count_success planning(s) ont été envoyés avec succès aux étudiants et les notifications par email ont été transmises.");
        }
    }

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    echo "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
}
?>
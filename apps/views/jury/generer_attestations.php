<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';

$userID = $_SESSION['user_id'];

$sql = "SELECT id_personne, prenom, nom, email, role FROM personne WHERE id_personne = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $prenom = $user['prenom'];
    $nom = $user['nom'];
    $role = $user['role'];
    $nom_complet = $prenom . ' ' . $nom;
} else {
    header("Location: Connexion.php");
    exit();
}

// Vérifier que l'utilisateur est bien un président du jury
if ($role !== 'president_jury') {
    header("Location: acces_refuse.php");
    exit();
}

// Récupérer toutes les filières disponibles
$filieres = array();
$sql_filieres = "SELECT f.id_filiere, f.nom_filiere FROM filiere f ORDER BY f.nom_filiere";
$result_filieres = $conn->query($sql_filieres);
while ($row = $result_filieres->fetch_assoc()) {
    $filieres[$row['id_filiere']] = $row['nom_filiere'];
}

// Récupérer les paramètres de filtre
$filiere_selected = isset($_GET['filiere']) ? intval($_GET['filiere']) : 0;
$niveau_selected = isset($_GET['niveau']) ? $_GET['niveau'] : '';

// Récupérer les niveaux disponibles pour la filière sélectionnée
$niveaux = array();
if ($filiere_selected > 0) {
    $sql_niveaux = "SELECT DISTINCT niveau_filiere FROM etudiant WHERE id_filiere = ? ORDER BY niveau_filiere";
    $stmt = $conn->prepare($sql_niveaux);
    $stmt->bind_param("i", $filiere_selected);
    $stmt->execute();
    $result_niveaux = $stmt->get_result();
    
    while ($row = $result_niveaux->fetch_assoc()) {
        $niveaux[] = $row['niveau_filiere'];
    }
}

// Récupérer les étudiants selon les filtres
$etudiants = array();
if ($filiere_selected > 0 && !empty($niveau_selected)) {
    $sql_etudiants = "SELECT p.id_personne, p.prenom, p.nom, p.email, f.nom_filiere, e.niveau_filiere 
                      FROM personne p 
                      JOIN etudiant e ON p.id_personne = e.id_etudiant 
                      JOIN filiere f ON e.id_filiere = f.id_filiere
                      WHERE e.id_filiere = ? AND e.niveau_filiere = ?
                      ORDER BY p.nom, p.prenom";
    $stmt = $conn->prepare($sql_etudiants);
    $stmt->bind_param("is", $filiere_selected, $niveau_selected);
    $stmt->execute();
    $result_etudiants = $stmt->get_result();
    
    while ($row = $result_etudiants->fetch_assoc()) {
        $etudiants[] = $row;
    }
}

// Traitement de la génération d'attestation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generer_attestation'])) {
    $id_etudiant = intval($_POST['id_etudiant']);
    
    // Récupérer les informations de l'étudiant
    $sql_etudiant = "SELECT p.prenom, p.nom, f.nom_filiere, e.niveau_filiere 
                     FROM personne p 
                     JOIN etudiant e ON p.id_personne = e.id_etudiant 
                     JOIN filiere f ON e.id_filiere = f.id_filiere
                     WHERE p.id_personne = ?";
    $stmt = $conn->prepare($sql_etudiant);
    $stmt->bind_param("i", $id_etudiant);
    $stmt->execute();
    $result_etudiant = $stmt->get_result();
    
    if ($result_etudiant->num_rows > 0) {
        $etudiant = $result_etudiant->fetch_assoc();
        
        // Créer le contenu HTML de l'attestation avec l'image en arrière-plan
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Attestation de scolarité</title>
            <style>
                @page {
                    size: A4;
                    margin: 0;
                }
                body { 
                    font-family: "Times New Roman", serif; 
                    margin: 0;
                    padding: 0;
                    position: relative;
                }
                .watermark {
                    position: absolute;
                    opacity: 0.1;
                    width: 100%;
                    height: 100%;
                    background-image: url("Images/ud.jpg");
                    background-repeat: no-repeat;
                    background-position: center;
                    background-size: contain;
                    z-index: -1;
                }
                .content {
                    padding: 3cm;
                    position: relative;
                    z-index: 1;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 40px;
                }
                .university { 
                    font-weight: bold; 
                    font-size: 24px;
                    margin-bottom: 10px;
                    text-decoration: underline;
                }
                .title { 
                    font-size: 20px; 
                    margin-bottom: 30px;
                    font-weight: bold;
                }
                .text { 
                    line-height: 1.6; 
                    margin-bottom: 20px;
                    text-align: justify;
                }
                .student-info { 
                    margin-left: 40px; 
                    margin-bottom: 20px;
                }
                .student-info p {
                    margin: 10px 0;
                }
                .date { 
                    margin-top: 40px;
                    text-align: right;
                }
                .signature { 
                    margin-top: 80px;
                    text-align: right;
                }
                .signature p:first-child {
                    margin-bottom: 50px;
                }
                .no-print { 
                    display: none; 
                }
                .border {
                    border: 2px solid #000;
                    padding: 20px;
                    margin: 20px;
                }
            </style>
        </head>
        <body>
            <div class="watermark"></div>
            
            <div class="content">
                <div class="border">
                    <div class="header">
                        <div class="university">Université de Djibouti</div>
                        <div class="title">ATTESTATION DE SCOLARITÉ</div>
                    </div>
                    
                    <div class="text">
                        Je soussigné(e), Président du jury de l\'Université de Djibouti, certifie que :
                    </div>
                    
                    <div class="student-info">
                        <p><strong>Nom :</strong> '.htmlspecialchars($etudiant['nom']).'</p>
                        <p><strong>Prénom :</strong> '.htmlspecialchars($etudiant['prenom']).'</p>
                        <p><strong>Filière :</strong> '.htmlspecialchars($etudiant['nom_filiere']).'</p>
                        <p><strong>Niveau :</strong> '.htmlspecialchars($etudiant['niveau_filiere']).'</p>
                    </div>
                    
                    <div class="text">
                        est régulièrement inscrit(e) dans notre établissement pour l\'année universitaire '.date('Y').'-'.(date('Y')+1).'.
                    </div>
                    
                    <div class="date">
                        <p>Fait à Djibouti, le '.date('d/m/Y').'</p>
                    </div>
                    
                    <div class="signature">
                        <p>Le Président du jury,</p>
                        <p>'.htmlspecialchars($nom_complet).'</p>
                    </div>
                </div>
            </div>
            
            <div class="no-print" style="text-align: center; margin-top: 30px; position: fixed; bottom: 20px; width: 100%;">
                <button onclick="window.print()" style="padding: 10px 20px; background-color: #1a5276; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    <i class="bi bi-printer"></i> Imprimer cette attestation
                </button>
                <button onclick="window.close()" style="padding: 10px 20px; background-color: #7f8c8d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                    <i class="bi bi-x-circle"></i> Fermer
                </button>
            </div>
        </body>
        </html>';
        
        // Enregistrer en session pour affichage
        $_SESSION['attestation_generee'] = array(
            'nom' => $etudiant['nom'],
            'prenom' => $etudiant['prenom'],
            'filiere' => $etudiant['nom_filiere'],
            'niveau' => $etudiant['niveau_filiere'],
            'html' => $html
        );
        
        $message_success = "Attestation générée avec succès pour ".$etudiant['prenom']." ".$etudiant['nom'];
    } else {
        $message_erreur = "Étudiant non trouvé";
    }
}

// Gestion de l'affichage de l'attestation
if (isset($_GET['afficher_attestation']) && isset($_SESSION['attestation_generee'])) {
    $attestation = $_SESSION['attestation_generee'];
    header('Content-Type: text/html');
    echo $attestation['html'];
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générer des attestations - Président du jury</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../../public/assets/css/generer_attestation.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">

    <style>
        
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Barre latérale gauche -->
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

        <!-- Contenu principal -->
        <main class="main-content">
            <header>
                <div class="profile">
                    <span><?php echo htmlspecialchars($nom_complet); ?> - Président du jury</span>
                </div>
            </header>

            <!-- Affichage des messages d'erreur ou de succès -->
            <?php if (!empty($message_erreur)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo $message_erreur; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($message_success)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?php echo $message_success; ?>
                </div>
            <?php endif; ?>

            <!-- Filtres de sélection -->
            <div class="filters-container">
                <h2>Filtrer les étudiants</h2>
                <form action="" method="GET">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="filiere">Filière</label>
                            <select name="filiere" id="filiere" onchange="this.form.submit()">
                                <option value="">-- Sélectionnez une filière --</option>
                                <?php foreach ($filieres as $id => $nom): ?>
                                    <option value="<?php echo $id; ?>" <?php echo ($id == $filiere_selected) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($nom); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if ($filiere_selected > 0): ?>
                            <div class="filter-group">
                                <label for="niveau">Niveau</label>
                                <select name="niveau" id="niveau" onchange="this.form.submit()">
                                    <option value="">-- Sélectionnez un niveau --</option>
                                    <?php foreach ($niveaux as $niveau): ?>
                                        <option value="<?php echo htmlspecialchars($niveau); ?>" <?php echo ($niveau === $niveau_selected) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($niveau); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Liste des étudiants -->
            <?php if (!empty($etudiants)): ?>
                <div class="etudiants-container">
                    <h2>Liste des étudiants</h2>
                    <p class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Sélectionnez un étudiant pour générer son attestation.
                    </p>
                    
                    <table class="etudiants-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom complet</th>
                                <th>Email</th>
                                <th>Filière</th>
                                <th>Niveau</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($etudiants as $etudiant): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($etudiant['id_personne']); ?></td>
                                    <td><?php echo htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($etudiant['email']); ?></td>
                                    <td><?php echo htmlspecialchars($etudiant['nom_filiere']); ?></td>
                                    <td><?php echo htmlspecialchars($etudiant['niveau_filiere']); ?></td>
                                    <td>
                                        <form method="POST" action="">
                                            <input type="hidden" name="id_etudiant" value="<?php echo $etudiant['id_personne']; ?>">
                                            <button type="submit" name="generer_attestation" class="btn-generer">
                                                <i class="bi bi-file-earmark-text"></i> Générer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($filiere_selected > 0 && !empty($niveau_selected)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Aucun étudiant trouvé pour cette filière et ce niveau.
                </div>
            <?php endif; ?>

            <!-- Affichage de l'attestation générée -->
            <?php if (isset($_SESSION['attestation_generee'])): 
                $attestation = $_SESSION['attestation_generee']; ?>
                <div class="attestation-container">
                    <h3>Attestation générée</h3>
                    <p>
                        <strong>Étudiant:</strong> <?php echo htmlspecialchars($attestation['prenom'] . ' ' . $attestation['nom']); ?><br>
                        <strong>Filière:</strong> <?php echo htmlspecialchars($attestation['filiere']); ?><br>
                        <strong>Niveau:</strong> <?php echo htmlspecialchars($attestation['niveau']); ?>
                    </p>
                    
                    <a href="?afficher_attestation=1" target="_blank" class="btn-telecharger">
                        <i class="bi bi-eye"></i> Afficher l'attestation
                    </a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
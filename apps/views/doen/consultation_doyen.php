<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';

$userID = $_SESSION['user_id'];


// Initialiser les variables pour éviter les erreurs
$prenom = '';
$nom = '';
$email = '';
$role = '';
$image_profile = 'profile1.jpg';
$nom_complet = '';

// Vérifier si l'utilisateur est bien un directeur d'études
try {
    $sql = "SELECT id_personne, prenom, nom, email, role, image_profile FROM personne 
            WHERE id_personne = ? AND role = 'directeur'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $prenom = $user['prenom'] ?? '';
        $nom = $user['nom'] ?? '';
        $email = $user['email'] ?? '';
        $role = $user['role'] ?? '';
        $image_profile = $user['image_profile'] ?? 'profile1.jpg';
        $nom_complet = trim($prenom . ' ' . $nom);
    } 
} catch (Exception $e) {
    die("Impossible de vérifier les permissions de l'utilisateur.");
}

// Récupérer toutes les filières (le doyen a accès à tout)
try {
    $sql_filieres = "SELECT id_filiere, nom_filiere FROM filiere ORDER BY nom_filiere";
    $result_filieres = $conn->query($sql_filieres);
    $filieres = $result_filieres->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Impossible de récupérer la liste des filières.");
}

// Récupérer tous les niveaux disponibles
$niveaux = ['L1', 'L2', 'L3'];

// Récupérer tous les semestres
$semestres = [1, 2];

// Paramètres de filtrage
$id_filiere_selected = isset($_GET['filiere']) ? intval($_GET['filiere']) : (isset($filieres[0]['id_filiere']) ? $filieres[0]['id_filiere'] : 0);
$niveau_selected = isset($_GET['niveau']) ? $_GET['niveau'] : 'L1';
$semestre_selected = isset($_GET['semestre']) ? intval($_GET['semestre']) : 1;

// Récupérer les matières selon les filtres
$matieres = [];
if ($id_filiere_selected > 0) {
    try {
        $sql_matieres = "SELECT m.id_matiere, m.nom_matiere, m.coeff, 
                        CONCAT(p.prenom, ' ', p.nom) as nom_enseignant
                        FROM matiere m
                        JOIN enseigner e ON m.id_matiere = e.id_matiere
                        JOIN personne p ON e.id_enseignant = p.id_personne
                        WHERE m.id_filiere = ? 
                        AND m.niveau_filiere = ?
                        AND m.type_simestre = ?
                        GROUP BY m.id_matiere
                        ORDER BY m.nom_matiere";
        $stmt = $conn->prepare($sql_matieres);
        $stmt->bind_param("isi", $id_filiere_selected, $niveau_selected, $semestre_selected);
        $stmt->execute();
        $result_matieres = $stmt->get_result();
        $matieres = $result_matieres->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        $message_erreur = "Impossible de récupérer les matières.";
    }
}

// ID de matière sélectionnée
$id_matiere_selected = isset($_GET['matiere']) ? intval($_GET['matiere']) : (isset($matieres[0]['id_matiere']) ? $matieres[0]['id_matiere'] : 0);

// Variables pour la matière sélectionnée
$nom_matiere_selected = '';
$nom_filiere_selected = '';
$coeff_matiere = '';
$nom_enseignant = '';

if ($id_matiere_selected > 0) {
    foreach ($matieres as $matiere) {
        if ($matiere['id_matiere'] == $id_matiere_selected) {
            $nom_matiere_selected = $matiere['nom_matiere'];
            $coeff_matiere = $matiere['coeff'];
            $nom_enseignant = $matiere['nom_enseignant'];
            break;
        }
    }
    
    // Récupérer le nom de la filière
    foreach ($filieres as $filiere) {
        if ($filiere['id_filiere'] == $id_filiere_selected) {
            $nom_filiere_selected = $filiere['nom_filiere'];
            break;
        }
    }
}

// Récupérer les étudiants et leurs notes pour la matière sélectionnée
$etudiants = [];
if ($id_matiere_selected > 0) {
    try {
        $sql_etudiants = "SELECT p.id_personne, p.nom, p.prenom, 
                          e.id_etudiant, ev.cc, ev.tp, ev.id_anonymat, a.CF,
                          (ev.cc * 0.2 + ev.tp * 0.3 + IFNULL(a.CF, 0) * 0.5) as moyenne
                          FROM personne p
                          JOIN etudiant e ON p.id_personne = e.id_etudiant 
                          JOIN evaluer ev ON e.id_etudiant = ev.id_etudiant 
                          LEFT JOIN anonymat a ON a.id_anonymat = ev.id_anonymat 
                          WHERE ev.id_matiere = ?
                          AND e.id_filiere = ?
                          AND e.niveau_filiere = ?
                          ORDER BY p.nom, p.prenom";
        
        $stmt = $conn->prepare($sql_etudiants);
        $stmt->bind_param("iis", $id_matiere_selected, $id_filiere_selected, $niveau_selected);
        $stmt->execute();
        $result_etudiants = $stmt->get_result();
        
        while ($row = $result_etudiants->fetch_assoc()) {
            $etudiants[] = [
                'id_etudiant' => $row['id_etudiant'],
                'nom_complet' => $row['prenom'] . ' ' . $row['nom'],
                'cc' => $row['cc'],
                'tp' => $row['tp'],
                'CF' => $row['CF'],
                'moyenne' => $row['moyenne'],
                'id_anonymat' => $row['id_anonymat']
            ];
        }
    } catch (Exception $e) {
        $message_erreur = "Impossible de récupérer la liste des étudiants.";
    }
}

// Calcul des statistiques
$stats = [
    'nb_etudiants' => count($etudiants),
    'moyenne_cc' => 0,
    'moyenne_tp' => 0,
    'moyenne_cf' => 0,
    'moyenne_generale' => 0,
    'nb_admis' => 0,
    'taux_reussite' => 0
];

if (count($etudiants) > 0) {
    $sum_cc = 0;
    $sum_tp = 0;
    $sum_cf = 0;
    $sum_moyenne = 0;
    $nb_cf = 0;
    
    foreach ($etudiants as $etudiant) {
        if (!is_null($etudiant['cc'])) $sum_cc += $etudiant['cc'];
        if (!is_null($etudiant['tp'])) $sum_tp += $etudiant['tp'];
        if (!is_null($etudiant['CF'])) {
            $sum_cf += $etudiant['CF'];
            $nb_cf++;
        }
        if (!is_null($etudiant['moyenne'])) {
            $sum_moyenne += $etudiant['moyenne'];
            if ($etudiant['moyenne'] >= 10) $stats['nb_admis']++;
        }
    }
    
    $stats['moyenne_cc'] = $sum_cc / count($etudiants);
    $stats['moyenne_tp'] = $sum_tp / count($etudiants);
    $stats['moyenne_cf'] = $nb_cf > 0 ? $sum_cf / $nb_cf : null;
    $stats['moyenne_generale'] = $sum_moyenne / count($etudiants);
    $stats['taux_reussite'] = ($stats['nb_admis'] / count($etudiants)) * 100;
}

// Gestion de l'export CSV
if (isset($_POST['telecharger_csv']) && $id_matiere_selected > 0 && !empty($etudiants)) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=notes_' . $nom_matiere_selected . '_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // En-tête CSV
    fputcsv($output, ['ID Étudiant', 'Nom complet', 'Note CC', 'Note TP', 'Note CF', 'Moyenne', 'Statut'], ';');
    
    // Données
    foreach ($etudiants as $etudiant) {
        $statut = 'Incomplet';
        if (!is_null($etudiant['moyenne'])) {
            $statut = $etudiant['moyenne'] >= 10 ? 'Admis' : 'Non admis';
        }
        
        fputcsv($output, [
            $etudiant['id_etudiant'],
            $etudiant['nom_complet'],
            !is_null($etudiant['cc']) ? number_format($etudiant['cc'], 2) : 'N/A',
            !is_null($etudiant['tp']) ? number_format($etudiant['tp'], 2) : 'N/A',
            !is_null($etudiant['CF']) ? number_format($etudiant['CF'], 2) : 'N/A',
            !is_null($etudiant['moyenne']) ? number_format($etudiant['moyenne'], 2) : 'N/A',
            $statut
        ], ';');
    }
    
    fclose($output);
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation des notes doyen - Université de djibouti</title>
    <link rel="stylesheet" href="../../../public/assets/css/consulter_notes_doyen.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../../../public/assets/img/U-remove.png" alt="Logo U-Digital" style="width:150px;height:100px;background:white;border-radius:50%;margin-left:30px">
            </div>
            
            <nav class="sidebar-menu">
                <div class="menu-item">
                    <a href="accueil_doyen.php">
                        <i class="bi bi-house-door"></i>
                        <span>Accueil</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="profile_doyen.php">
                        <i class="bi bi-person"></i>
                        <span>Profil</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="consultation_doyen.php" class="active">
                        <i class="bi bi-journal-text"></i>
                        <span>Consultation</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="statistiques_doyen.php">
                        <i class="bi bi-bar-chart"></i>
                        <span>Statistiques</span>
                    </a>
                </div>
                <div class="menu-item deconnection">
                    <a href="../../../config/logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Déconnexion</span>
                    </a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="date-info">
                    <?php 
                    $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
                    $mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                    
                    $jour_semaine = $jours[date('w')];
                    $jour_mois = date('d');
                    $mois_annee = $mois[date('n') - 1];
                    $annee = date('Y');
                    $heure = date('H:i');
                    
                    echo ucfirst($jour_semaine) . ' ' . $jour_mois . ' ' . $mois_annee . ' ' . $annee . ' - ' . $heure;
                    ?>
                </div>
                <div class="profile">
                    <div class="profile-info">
                        <div class="profile-name"><?php echo htmlspecialchars($nom_complet); ?></div>
                        <div class="profile-role">Doyen</div>
                    </div>
                    <div class="profile-avatar">
                        <?php 
                        $initiales = '';
                        if (!empty($prenom)) $initiales .= strtoupper(substr($prenom, 0, 1));
                        if (!empty($nom)) $initiales .= strtoupper(substr($nom, 0, 1));
                        echo htmlspecialchars($initiales);
                        ?>
                    </div>
                </div>
            </header>

            <div class="filters-container">
                <h2>Filtres de consultation</h2>
                <form class="filter-form" method="GET">
                    <div class="form-group">
                        <label for="filiere">Filière</label>
                        <select name="filiere" id="filiere">
                            <?php foreach ($filieres as $filiere): ?>
                                <option value="<?php echo $filiere['id_filiere']; ?>" <?php echo ($filiere['id_filiere'] == $id_filiere_selected) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($filiere['nom_filiere']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="niveau">Niveau</label>
                        <select name="niveau" id="niveau">
                            <?php foreach ($niveaux as $niveau): ?>
                                <option value="<?php echo $niveau; ?>" <?php echo ($niveau == $niveau_selected) ? 'selected' : ''; ?>>
                                    <?php echo $niveau; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="semestre">Semestre</label>
                        <select name="semestre" id="semestre">
                            <?php foreach ($semestres as $semestre): ?>
                                <option value="<?php echo $semestre; ?>" <?php echo ($semestre == $semestre_selected) ? 'selected' : ''; ?>>
                                    Semestre <?php echo $semestre; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="bi bi-funnel"></i> Appliquer
                    </button>
                </form>
            </div>

            <?php if (!empty($matieres)): ?>
                <div class="filters-container">
                    <h3>Sélectionner une matière</h3>
                    <form class="filter-form" method="GET">
                        <input type="hidden" name="filiere" value="<?php echo $id_filiere_selected; ?>">
                        <input type="hidden" name="niveau" value="<?php echo $niveau_selected; ?>">
                        <input type="hidden" name="semestre" value="<?php echo $semestre_selected; ?>">
                        
                        <div class="form-group">
                            <select name="matiere" id="matiere">
                                <?php foreach ($matieres as $matiere): ?>
                                    <option value="<?php echo $matiere['id_matiere']; ?>" <?php echo ($matiere['id_matiere'] == $id_matiere_selected) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($matiere['nom_matiere']); ?> (<?php echo htmlspecialchars($matiere['nom_enseignant']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn">
                            <i class="bi bi-eye"></i> Afficher
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (!empty($etudiants) && $id_matiere_selected > 0): ?>
                <div class="matiere-info">
                    <h2><?php echo htmlspecialchars($nom_matiere_selected); ?></h2>
                    <div class="matiere-meta">
                        <span>Filière: <strong><?php echo htmlspecialchars($nom_filiere_selected); ?></strong></span>
                        <span>Niveau: <strong><?php echo htmlspecialchars($niveau_selected); ?></strong></span>
                        <span>Semestre: <strong><?php echo $semestre_selected; ?></strong></span>
                        <span>Coefficient: <strong><?php echo $coeff_matiere; ?></strong></span>
                        <span>Enseignant: <strong><?php echo htmlspecialchars($nom_enseignant); ?></strong></span>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Nombre d'étudiants</h3>
                        <div class="stat-value"><?php echo $stats['nb_etudiants']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Moyenne CC</h3>
                        <div class="stat-value"><?php echo number_format($stats['moyenne_cc'], 2); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Moyenne TP</h3>
                        <div class="stat-value"><?php echo number_format($stats['moyenne_tp'], 2); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Moyenne CF</h3>
                        <div class="stat-value">
                            <?php echo !is_null($stats['moyenne_cf']) ? number_format($stats['moyenne_cf'], 2) : 'N/A'; ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <h3>Moyenne Générale</h3>
                        <div class="stat-value"><?php echo number_format($stats['moyenne_generale'], 2); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Taux de réussite</h3>
                        <div class="stat-value"><?php echo number_format($stats['taux_reussite'], 1); ?>%</div>
                    </div>
                </div>

                <div class="actions-bar">
                    <form method="POST" action="generer_pdf.php" target="_blank">
                        <input type="hidden" name="matiere" value="<?php echo $id_matiere_selected; ?>">
                        <button type="submit" class="btn">
                            <i class="bi bi-file-earmark-pdf"></i> Exporter en PDF
                        </button>
                    </form>
                    <form method="POST" action="">
                        <input type="hidden" name="matiere" value="<?php echo $id_matiere_selected; ?>">
                        <button type="submit" name="telecharger_csv" class="btn">
                            <i class="bi bi-file-earmark-excel"></i> Exporter en CSV
                        </button>
                    </form>
                </div>

                <div class="notes-table-container">
                    <table class="notes-table">
                        <thead>
                            <tr>
                                <th>ID Étudiant</th>
                                <th>Nom complet</th>
                                <th>Note CC</th>
                                <th>Note TP</th>
                                <th>Note CF</th>
                                <th>Moyenne</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($etudiants as $etudiant): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($etudiant['id_etudiant']); ?></td>
                                    <td><?php echo htmlspecialchars($etudiant['nom_complet']); ?></td>
                                    <td><?php echo !is_null($etudiant['cc']) ? number_format($etudiant['cc'], 2) : 'N/A'; ?></td>
                                    <td><?php echo !is_null($etudiant['tp']) ? number_format($etudiant['tp'], 2) : 'N/A'; ?></td>
                                    <td><?php echo !is_null($etudiant['CF']) ? number_format($etudiant['CF'], 2) : 'N/A'; ?></td>
                                    <td><?php echo !is_null($etudiant['moyenne']) ? number_format($etudiant['moyenne'], 2) : 'N/A'; ?></td>
                                    <td class="<?php echo (!is_null($etudiant['moyenne']) && $etudiant['moyenne'] >= 10 ? 'admis' : 'non-admis'); ?>">
                                        <?php 
                                        if (is_null($etudiant['moyenne'])) {
                                            echo 'Incomplet';
                                        } else {
                                            echo $etudiant['moyenne'] >= 10 ? 'Admis' : 'Non admis';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($id_matiere_selected > 0): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Aucun étudiant trouvé pour cette matière avec les filtres sélectionnés.
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';

$userID = $_SESSION['user_id'];

// Récupérer les informations de l'utilisateur
$sql = "SELECT id_personne, prenom, nom, email, role FROM personne WHERE id_personne = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $prenom = $user['prenom'];
    $nom = $user['nom'];
    $email = $user['email'];
    $role = $user['role'];
    $nom_complet = $prenom . ' ' . $nom;
} else {
    die("Utilisateur non trouvé");
}

// Récupérer toutes les filières disponibles
$filieres = [];
$sql_filieres = "SELECT id_filiere, nom_filiere FROM filiere ORDER BY nom_filiere";
$result_filieres = $conn->query($sql_filieres);
if ($result_filieres) {
    while ($row = $result_filieres->fetch_assoc()) {
        $filieres[] = $row;
    }
}

// Initialiser les variables de filtre
$id_filiere_selected = isset($_GET['filiere']) ? intval($_GET['filiere']) : 0;
$niveau_selected = isset($_GET['niveau']) ? $_GET['niveau'] : '';
$semestre_selected = isset($_GET['semestre']) ? intval($_GET['semestre']) : 0;

// Récupérer les matières filtrées
$matieres = [];
if ($id_filiere_selected > 0 && !empty($niveau_selected) && $semestre_selected > 0) {
    $sql_matieres = "SELECT m.id_matiere, m.nom_matiere, m.coeff, 
                    CONCAT(p.prenom, ' ', p.nom) AS nom_enseignant,
                    e.id_enseignant,
                    ens.type_semestre
                    FROM matiere m
                    JOIN enseigner ens ON m.id_matiere = ens.id_matiere
                    JOIN enseignant e ON ens.id_enseignant = e.id_enseignant
                    JOIN personne p ON e.id_enseignant = p.id_personne
                    WHERE ens.id_filiere = ? 
                    AND m.niveau_filiere = ?
                    AND ens.type_semestre = ?
                    ORDER BY m.nom_matiere";
    
    $stmt = $conn->prepare($sql_matieres);
    $stmt->bind_param("isi", $id_filiere_selected, $niveau_selected, $semestre_selected);
    $stmt->execute();
    $result_matieres = $stmt->get_result();
    
    while ($row = $result_matieres->fetch_assoc()) {
        $matieres[] = $row;
    }
    
    if (empty($matieres)) {
        $message_info = "Aucune matière n'est disponible pour les critères sélectionnés.";
    }
}

// ID de matière par défaut ou sélectionnée
$id_matiere_selected = isset($_GET['matiere']) ? intval($_GET['matiere']) : (isset($matieres[0]['id_matiere']) ? $matieres[0]['id_matiere'] : 0);

// Variables pour stocker les informations de la matière sélectionnée
$date_debut = null;
$date_fin = null;
$semestre = null;
$nom_enseignant = null;
$id_enseignant = null;
$nom_filiere = null;
$niveau = null;
$coeff = null;

// Récupérer les informations spécifiques à la matière sélectionnée
if ($id_matiere_selected > 0) {
    foreach ($matieres as $matiere) {
        if ($matiere['id_matiere'] == $id_matiere_selected) {
            $semestre = $matiere['type_semestre'];
            $nom_enseignant = $matiere['nom_enseignant'];
            $id_enseignant = $matiere['id_enseignant'];
            $coeff = $matiere['coeff'];
            break;
        }
    }
    
    // Déterminer les dates du semestre
    $date_debut = ($semestre == 1) ? '2024-09-01' : '2025-02-01';
    $date_fin = ($semestre == 1) ? '2025-01-30' : '2025-05-30';
    
    // Récupérer le nom de la filière et le niveau
    $sql_filiere_info = "SELECT nom_filiere FROM filiere WHERE id_filiere = ?";
    $stmt = $conn->prepare($sql_filiere_info);
    $stmt->bind_param("i", $id_filiere_selected);
    $stmt->execute();
    $result_filiere_info = $stmt->get_result();
    if ($result_filiere_info->num_rows > 0) {
        $filiere_info = $result_filiere_info->fetch_assoc();
        $nom_filiere = $filiere_info['nom_filiere'];
    }
}

// Récupérer les étudiants et leurs notes pour la matière sélectionnée
$etudiants = [];
if ($id_matiere_selected > 0 && $id_filiere_selected > 0 && !empty($niveau_selected)) {
    try {
        $sql_etudiants = "SELECT p.id_personne, CONCAT(p.nom, ' ', p.prenom) AS nom_complet, 
                          et.id_etudiant, ev.cc, ev.tp, ev.id_anonymat, a.CF
                          FROM personne p
                          JOIN etudiant et ON p.id_personne = et.id_etudiant 
                          JOIN evaluer ev ON et.id_etudiant = ev.id_etudiant 
                          JOIN matiere m ON ev.id_matiere = m.id_matiere 
                          LEFT JOIN anonymat a ON a.id_anonymat = ev.id_anonymat 
                          WHERE m.id_matiere = ?
                          AND m.id_filiere = ?
                          AND m.niveau_filiere = ?
                          ORDER BY p.nom, p.prenom";
        $stmt = $conn->prepare($sql_etudiants);
        $stmt->bind_param("iis", $id_matiere_selected, $id_filiere_selected, $niveau_selected);
        $stmt->execute();
        $result_etudiants = $stmt->get_result();
        
        while ($row = $result_etudiants->fetch_assoc()) {
            $etudiants[] = $row;
        }
        
        if (empty($etudiants)) {
            $message_info = "Aucun étudiant n'est inscrit à cette matière dans cette filière et ce niveau.";
        }
    } catch (Exception $e) {
        $message_erreur = "Impossible de récupérer la liste des étudiants.";
    }
}

// Traitement des formulaires de validation des notes
$message_success = "";
$message_erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation des notes CC
    if (isset($_POST['valider_cc'])) {
        $conn->begin_transaction();
        try {
            foreach ($_POST['notes_cc'] as $id_etudiant => $note_cc) {
                $note_validee = $note_cc === "" ? NULL : floatval($note_cc);
                
                if ($note_validee !== NULL && ($note_validee < 0 || $note_validee > 20)) {
                    throw new Exception("La note CC doit être entre 0 et 20 pour l'étudiant ID $id_etudiant.");
                }
                
                $sql_update = "UPDATE evaluer SET cc = ? WHERE id_etudiant = ? AND id_matiere = ?";
                $stmt = $conn->prepare($sql_update);
                $stmt->bind_param("dii", $note_validee, $id_etudiant, $id_matiere_selected);
                $stmt->execute();
            }
            
            $conn->commit();
            $message_success = "Les notes CC ont été validées avec succès.";
        } catch (Exception $e) {
            $conn->rollback();
            $message_erreur = $e->getMessage();
        }
    }
    
    // Validation des notes TP
    if (isset($_POST['valider_tp'])) {
        $conn->begin_transaction();
        try {
            foreach ($_POST['notes_tp'] as $id_etudiant => $note_tp) {
                $note_validee = $note_tp === "" ? NULL : floatval($note_tp);
                
                if ($note_validee !== NULL && ($note_validee < 0 || $note_validee > 20)) {
                    throw new Exception("La note TP doit être entre 0 et 20 pour l'étudiant ID $id_etudiant.");
                }
                
                $sql_update = "UPDATE evaluer SET tp = ? WHERE id_etudiant = ? AND id_matiere = ?";
                $stmt = $conn->prepare($sql_update);
                $stmt->bind_param("dii", $note_validee, $id_etudiant, $id_matiere_selected);
                $stmt->execute();
            }
            
            $conn->commit();
            $message_success = "Les notes TP ont été validées avec succès.";
        } catch (Exception $e) {
            $conn->rollback();
            $message_erreur = $e->getMessage();
        }
    }
    
    // Validation des notes CF
    if (isset($_POST['valider_cf'])) {
        $conn->begin_transaction();
        try {
            foreach ($_POST['notes_cf'] as $id_anonymat => $note_cf) {
                $note_validee = $note_cf === "" ? NULL : floatval($note_cf);
                
                if ($note_validee !== NULL && ($note_validee < 0 || $note_validee > 20)) {
                    throw new Exception("La note CF doit être entre 0 et 20 pour l'anonymat #$id_anonymat.");
                }
                
                // Vérification supplémentaire pour s'assurer que l'anonymat appartient bien à la matière sélectionnée
                $sql_verif = "SELECT COUNT(*) as count FROM evaluer WHERE id_anonymat = ? AND id_matiere = ?";
                $stmt_verif = $conn->prepare($sql_verif);
                $stmt_verif->bind_param("ii", $id_anonymat, $id_matiere_selected);
                $stmt_verif->execute();
                $result_verif = $stmt_verif->get_result();
                $verif = $result_verif->fetch_assoc();
                
                if ($verif['count'] == 0) {
                    throw new Exception("L'anonymat #$id_anonymat n'appartient pas à cette matière.");
                }
                
                $sql_update = "UPDATE anonymat SET CF = ? WHERE id_anonymat = ?";
                $stmt = $conn->prepare($sql_update);
                $stmt->bind_param("di", $note_validee, $id_anonymat);
                $stmt->execute();
            }
            
            $conn->commit();
            $message_success = "Les notes CF ont été validées avec succès.";
        } catch (Exception $e) {
            $conn->rollback();
            $message_erreur = $e->getMessage();
        }
    }
    
    // Génération des fichiers CSV séparés
    if (isset($_POST['telecharger_cc']) || isset($_POST['telecharger_tp']) || isset($_POST['telecharger_cf']) || isset($_POST['telecharger_toutes'])) {
        try {
            $type_notes = '';
            $nom_fichier = '';
            $contenu_csv = '';
            
            if (isset($_POST['telecharger_cc'])) {
                $type_notes = 'CC';
                $nom_fichier = 'notes_cc_' . $id_matiere_selected . '_' . date('Y-m-d') . '.csv';
                $contenu_csv = "ID Étudiant;Nom complet;Note CC\n";
                
                foreach ($etudiants as $etudiant) {
                    $contenu_csv .= sprintf(
                        "%s;%s;%s\n",
                        $etudiant['id_etudiant'],
                        $etudiant['nom_complet'],
                        $etudiant['cc'] ?? ''
                    );
                }
            }
            elseif (isset($_POST['telecharger_tp'])) {
                $type_notes = 'TP';
                $nom_fichier = 'notes_tp_' . $id_matiere_selected . '_' . date('Y-m-d') . '.csv';
                $contenu_csv = "ID Étudiant;Nom complet;Note TP\n";
                
                foreach ($etudiants as $etudiant) {
                    $contenu_csv .= sprintf(
                        "%s;%s;%s\n",
                        $etudiant['id_etudiant'],
                        $etudiant['nom_complet'],
                        $etudiant['tp'] ?? ''
                    );
                }
            }
            elseif (isset($_POST['telecharger_cf'])) {
                $type_notes = 'CF';
                $nom_fichier = 'notes_cf_' . $id_matiere_selected . '_' . date('Y-m-d') . '.csv';
                $contenu_csv = "ID Anonymat;Note CF\n";
                
                foreach ($etudiants as $etudiant) {
                    $contenu_csv .= sprintf(
                        "%s;%s\n",
                        $etudiant['id_anonymat'] ?? 'Non assigné',
                        $etudiant['CF'] ?? ''
                    );
                }
            }
            elseif (isset($_POST['telecharger_toutes'])) {
                $type_notes = 'TOUTES';
                $nom_fichier = 'notes_completes_' . $id_matiere_selected . '_' . date('Y-m-d') . '.csv';
                $contenu_csv = "ID Étudiant;Nom complet;Note CC;Note TP;ID Anonymat;Note CF\n";
                
                foreach ($etudiants as $etudiant) {
                    $contenu_csv .= sprintf(
                        "%s;%s;%s;%s;%s;%s\n",
                        $etudiant['id_etudiant'],
                        $etudiant['nom_complet'],
                        $etudiant['cc'] ?? '',
                        $etudiant['tp'] ?? '',
                        $etudiant['id_anonymat'] ?? 'Non assigné',
                        $etudiant['CF'] ?? ''
                    );
                }
            }
            
            // Envoyer les en-têtes pour forcer le téléchargement
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $nom_fichier);
            echo $contenu_csv;
            exit();
            
        } catch (Exception $e) {
            $message_erreur = "Erreur lors de la génération du fichier : " . $e->getMessage();
        }
    }
}

// Fermer les ressources
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation des notes - Président de jury</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../../public/assets/css/consulter_notes_president_jury.css">
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
                    <li class="deconnection"><a href="deconnexion.php"><i class="bi bi-box-arrow-right"></i>Déconnexion</a></li></ul>
            </nav>
        </aside>

        <!-- Contenu principal -->
        <main class="main-content">
            <header>
                
                <div class="profile">
                    <span><?php echo htmlspecialchars($nom_complet); ?> - Président de jury</span>
                </div>
            </header>

            <!-- Affichage des messages d'erreur, de succès ou d'info -->
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

            <?php if (!empty($message_info)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> <?php echo $message_info; ?>
                </div>
            <?php endif; ?>

            <!-- Section de filtrage -->
            <div class="filter-section">
                <h2>Filtrer les matières</h2>
                <form action="" method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="filiere">Filière</label>
                        <select name="filiere" id="filiere" required>
                            <option value="">Sélectionnez une filière</option>
                            <?php foreach ($filieres as $filiere): ?>
                                <option value="<?php echo $filiere['id_filiere']; ?>" <?php echo ($filiere['id_filiere'] == $id_filiere_selected) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($filiere['nom_filiere']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="niveau">Niveau</label>
                        <select name="niveau" id="niveau" required>
                            <option value="">Sélectionnez un niveau</option>
                            <option value="L1" <?php echo ($niveau_selected == 'L1') ? 'selected' : ''; ?>>Licence 1</option>
                            <option value="L2" <?php echo ($niveau_selected == 'L2') ? 'selected' : ''; ?>>Licence 2</option>
                            <option value="L3" <?php echo ($niveau_selected == 'L3') ? 'selected' : ''; ?>>Licence 3</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="semestre">Semestre</label>
                        <select name="semestre" id="semestre" required>
                            <option value="">Sélectionnez un semestre</option>
                            <option value="1" <?php echo ($semestre_selected == 1) ? 'selected' : ''; ?>>Semestre 1</option>
                            <option value="2" <?php echo ($semestre_selected == 2) ? 'selected' : ''; ?>>Semestre 2</option>
                        </select>
                    </div>
                    
                    <button type="submit">Appliquer les filtres</button>
                </form>
            </div>

            <!-- Sélecteur de matière (uniquement si des filtres sont appliqués) -->
            <?php if ($id_filiere_selected > 0 && !empty($niveau_selected) && $semestre_selected > 0): ?>
                <div class="matiere-selector">
                    <h2>Sélectionner une matière</h2>
                    <form action="" method="GET">
                        <input type="hidden" name="filiere" value="<?php echo $id_filiere_selected; ?>">
                        <input type="hidden" name="niveau" value="<?php echo $niveau_selected; ?>">
                        <input type="hidden" name="semestre" value="<?php echo $semestre_selected; ?>">
                        
                        <select name="matiere" id="matiere" required>
                            <option value="">Sélectionnez une matière</option>
                            <?php foreach ($matieres as $matiere): ?>
                                <option value="<?php echo $matiere['id_matiere']; ?>" <?php echo ($matiere['id_matiere'] == $id_matiere_selected) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($matiere['nom_matiere']); ?> - <?php echo htmlspecialchars($matiere['nom_enseignant']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Afficher les notes</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (!empty($etudiants) && $id_matiere_selected > 0): ?>
                <!-- Information sur la période et l'enseignant -->
                <div class="info-card">
                    <h3>Période d'évaluation</h3>
                    <p>
                        Semestre <?php echo $semestre; ?> : 
                        du <?php echo date('d/m/Y', strtotime($date_debut)); ?> 
                        au <?php echo date('d/m/Y', strtotime($date_fin)); ?>
                    </p>
                </div>

                <div class="info-card">
                    <h3>Enseignant responsable</h3>
                    <p>
                        <strong>Nom :</strong> <?php echo htmlspecialchars($nom_enseignant); ?><br>
                        <strong>ID :</strong> <?php echo htmlspecialchars($id_enseignant); ?>
                    </p>
                </div>

                <!-- Statistiques rapides -->
                <div class="stats-container">
                    <div class="stat-card">
                        <h3>Nombre d'étudiants</h3>
                        <div class="stat-value"><?php echo count($etudiants); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Moyenne CC</h3>
                        <div class="stat-value">
                            <?php 
                                $somme_cc = 0;
                                $nb_notes_cc = 0;
                                foreach ($etudiants as $etudiant) {
                                    if (!is_null($etudiant['cc'])) {
                                        $somme_cc += $etudiant['cc'];
                                        $nb_notes_cc++;
                                    }
                                }
                                echo $nb_notes_cc > 0 ? number_format($somme_cc / $nb_notes_cc, 2) : 'N/A';
                            ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <h3>Moyenne TP</h3>
                        <div class="stat-value">
                            <?php 
                                $somme_tp = 0;
                                $nb_notes_tp = 0;
                                foreach ($etudiants as $etudiant) {
                                    if (!is_null($etudiant['tp'])) {
                                        $somme_tp += $etudiant['tp'];
                                        $nb_notes_tp++;
                                    }
                                }
                                echo $nb_notes_tp > 0 ? number_format($somme_tp / $nb_notes_tp, 2) : 'N/A';
                            ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <h3>Moyenne CF</h3>
                        <div class="stat-value">
                            <?php 
                                $somme_cf = 0;
                                $nb_notes_cf = 0;
                                foreach ($etudiants as $etudiant) {
                                    if (!is_null($etudiant['CF'])) {
                                        $somme_cf += $etudiant['CF'];
                                        $nb_notes_cf++;
                                    }
                                }
                                echo $nb_notes_cf > 0 ? number_format($somme_cf / $nb_notes_cf, 2) : 'N/A';
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Information sur la classe -->
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    Filière: <strong><?php echo htmlspecialchars($nom_filiere); ?></strong> | 
                    Niveau: <strong><?php echo htmlspecialchars($niveau_selected); ?></strong> | 
                    Coefficient: <strong><?php echo htmlspecialchars($coeff); ?></strong>
                </div>

                <!-- Boutons de téléchargement -->
                <div class="download-buttons">
                    <form method="POST" action="">
                        <button type="submit" name="telecharger_cc" class="primary">
                            <i class="bi bi-download"></i> Télécharger CC (CSV)
                        </button>
                    </form>
                    <form method="POST" action="">
                        <button type="submit" name="telecharger_tp" class="primary">
                            <i class="bi bi-download"></i> Télécharger TP (CSV)
                        </button>
                    </form>
                    <form method="POST" action="">
                        <button type="submit" name="telecharger_cf" class="primary">
                            <i class="bi bi-download"></i> Télécharger CF (CSV)
                        </button>
                    </form>
                    <form method="POST" action="">
                        <button type="submit" name="telecharger_toutes" class="info">
                            <i class="bi bi-download"></i> Télécharger toutes les notes (CSV)
                        </button>
                    </form>
                </div>

                <!-- Onglets pour les différents types de notes -->
                <div class="tabs">
                    <button class="tab active" onclick="openTab(event, 'tab-cc')">Notes CC</button>
                    <button class="tab" onclick="openTab(event, 'tab-tp')">Notes TP</button>
                    <button class="tab" onclick="openTab(event, 'tab-cf')">Notes CF</button>
                </div>

                <!-- Affichage des notes CC - Nouvelle version avec formulaire -->
<div id="tab-cc" class="tab-content active">
    <form method="POST" action="">
        <h2>Notes de Contrôle Continu (CC)</h2>
        <table class="notes-table">
            <thead>
                <tr>
                    <th>ID Étudiant</th>
                    <th>Nom complet</th>
                    <th>Note CC</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($etudiants as $etudiant): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($etudiant['id_etudiant']); ?></td>
                        <td><?php echo htmlspecialchars($etudiant['nom_complet']); ?></td>
                        <td>
                            <input type="number" name="notes_cc[<?php echo $etudiant['id_etudiant']; ?>]" 
                                   value="<?php echo !is_null($etudiant['cc']) ? htmlspecialchars($etudiant['cc']) : ''; ?>" 
                                   step="0.01" min="0" max="20" placeholder="Note CC">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="action-buttons">
            <button type="submit" name="valider_cc" class="success">
                <i class="bi bi-check-circle"></i> Valider les modifications CC
            </button>
        </div>
    </form>
</div>

<!-- Affichage des notes TP - Nouvelle version avec formulaire -->
<div id="tab-tp" class="tab-content">
    <form method="POST" action="">
        <h2>Notes de Travaux Pratiques (TP)</h2>
        <table class="notes-table">
            <thead>
                <tr>
                    <th>ID Étudiant</th>
                    <th>Nom complet</th>
                    <th>Note TP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($etudiants as $etudiant): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($etudiant['id_etudiant']); ?></td>
                        <td><?php echo htmlspecialchars($etudiant['nom_complet']); ?></td>
                        <td>
                            <input type="number" name="notes_tp[<?php echo $etudiant['id_etudiant']; ?>]" 
                                   value="<?php echo !is_null($etudiant['tp']) ? htmlspecialchars($etudiant['tp']) : ''; ?>" 
                                   step="0.01" min="0" max="20" placeholder="Note TP">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="action-buttons">
            <button type="submit" name="valider_tp" class="success">
                <i class="bi bi-check-circle"></i> Valider les modifications TP
            </button>
        </div>
    </form>
</div>

                <!-- Formulaire des notes CF (avec possibilité de validation) -->
                <div id="tab-cf" class="tab-content">
                    <form method="POST" action="">
                        <h2>Notes de Contrôle Final (CF)</h2>
                        <p class="alert alert-info">
                            <i class="bi bi-info-circle"></i> En tant que président de jury, vous pouvez modifier les notes CF si nécessaire.
                        </p>
                        <table class="notes-table">
                            <thead>
                                <tr>
                                    <th>ID Anonymat</th>
                                    <th>Note CF</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($etudiants as $etudiant): ?>
                                    <tr>
                                        <td><?php echo !empty($etudiant['id_anonymat']) ? htmlspecialchars($etudiant['id_anonymat']) : '<span class="text-muted">Non assigné</span>'; ?></td>
                                        
                                        <td>
                                            <?php if (!empty($etudiant['id_anonymat'])): ?>
                                                <input type="number" name="notes_cf[<?php echo $etudiant['id_anonymat']; ?>]" 
                                                    value="<?php echo !is_null($etudiant['CF']) ? htmlspecialchars($etudiant['CF']) : ''; ?>" 
                                                    step="0.01" min="0" max="20" placeholder="Note CF">
                                            <?php else: ?>
                                                <span class="text-muted">Numéro d'anonymat requis</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="action-buttons">
                            <button type="submit" name="valider_cf" class="success">
                                <i class="bi bi-check-circle"></i> Valider les modifications CF
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Fonction pour gérer les onglets
        function openTab(evt, tabId) {
            // Masquer tous les contenus d'onglets
            const tabContents = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }

            // Désactiver tous les boutons d'onglets
            const tabs = document.getElementsByClassName("tab");
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }

            // Afficher le contenu de l'onglet sélectionné et activer le bouton
            document.getElementById(tabId).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        // Fonction pour valider les notes (entre 0 et 20)
        function validerNote(input) {
            const value = parseFloat(input.value);
            
            // Si le champ est vide, c'est autorisé (pas de note)
            if (input.value === "") {
                input.classList.remove("invalid");
                return true;
            }
            
            // Vérifier si la valeur est un nombre entre 0 et 20
            if (isNaN(value) || value < 0 || value > 20) {
                input.classList.add("invalid");
                alert("La note doit être un nombre entre 0 et 20.");
                return false;
            } else {
                input.classList.remove("invalid");
                return true;
            }
        }

        // Ajouter la validation à tous les champs de note
        document.addEventListener("DOMContentLoaded", function() {
            const noteInputs = document.querySelectorAll('input[type="number"]');
            
            noteInputs.forEach(input => {
                input.addEventListener("change", function() {
                    validerNote(this);
                });
            });

            // Valider tous les champs avant soumission du formulaire
            const forms = document.querySelectorAll("form");
            forms.forEach(form => {
                form.addEventListener("submit", function(e) {
                    let valid = true;
                    const inputs = this.querySelectorAll('input[type="number"]');
                    
                    inputs.forEach(input => {
                        if (!input.disabled && !validerNote(input)) {
                            valid = false;
                        }
                    });
                    
                    if (!valid) {
                        e.preventDefault();
                        alert("Veuillez corriger les notes invalides avant de soumettre.");
                    }
                });
            });
        });
    </script>
</body>
</html>
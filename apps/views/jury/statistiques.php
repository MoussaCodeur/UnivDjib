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

// Récupérer les statistiques globales
$stats = array();

// 1. Statistiques par filière
$sql_filieres = "SELECT f.nom_filiere, COUNT(*) as count FROM etudiant e , filiere f where e.id_filiere = f.id_filiere GROUP BY f.nom_filiere";
$result_filieres = $conn->query($sql_filieres);
$stats['par_filiere'] = array();
if ($result_filieres && $result_filieres->num_rows > 0) {
    while ($row = $result_filieres->fetch_assoc()) {
        $stats['par_filiere'][$row['nom_filiere']] = $row['count'];
    }
}

// 2. Statistiques par niveau
$sql_niveaux = "SELECT niveau_filiere, COUNT(*) as count FROM etudiant GROUP BY niveau_filiere ORDER BY niveau_filiere";
$result_niveaux = $conn->query($sql_niveaux);
$stats['par_niveau'] = array();
if ($result_niveaux && $result_niveaux->num_rows > 0) {
    while ($row = $result_niveaux->fetch_assoc()) {
        $stats['par_niveau'][$row['niveau_filiere']] = $row['count'];
    }
}

// 3. Statistiques par semestre (basé sur les matières) - CORRECTION APPLIQUÉE ICI
$sql_semestres = "SELECT 
                    CASE 
                        WHEN m.id_matiere BETWEEN 1 AND 6 THEN 'Semestre 1'
                        WHEN m.id_matiere BETWEEN 7 AND 12 THEN 'Semestre 2'
                        WHEN m.id_matiere BETWEEN 13 AND 18 THEN 'Semestre 3'
                        WHEN m.id_matiere BETWEEN 19 AND 24 THEN 'Semestre 4'
                        WHEN m.id_matiere BETWEEN 25 AND 30 THEN 'Semestre 5'
                        ELSE 'Semestre 6'
                    END as semestre,
                    COUNT(DISTINCT e.id_etudiant) as count
                  FROM evaluer ev
                  JOIN etudiant e ON ev.id_etudiant = e.id_etudiant
                  JOIN matiere m ON ev.id_matiere = m.id_matiere
                  GROUP BY semestre";
$result_semestres = $conn->query($sql_semestres);
$stats['par_semestre'] = array();
if ($result_semestres && $result_semestres->num_rows > 0) {
    while ($row = $result_semestres->fetch_assoc()) {
        $stats['par_semestre'][$row['semestre']] = $row['count'];
    }
}

// 4. Meilleurs étudiants par filière (moyenne générale)
$sql_top_etudiants = "SELECT 
                        f.nom_filiere,
                        p.prenom, 
                        p.nom, 
                        AVG(ev.note) as moyenne_generale
                      FROM evaluer ev
                      JOIN etudiant e ON ev.id_etudiant = e.id_etudiant
                      JOIN filiere f ON f.id_filiere = e.id_filiere
                      JOIN personne p ON e.id_etudiant = p.id_personne
                      WHERE ev.note IS NOT NULL
                      GROUP BY e.id_etudiant, f.nom_filiere, p.prenom, p.nom
                      ORDER BY f.nom_filiere, moyenne_generale DESC";
$result_top = $conn->query($sql_top_etudiants);
$stats['top_etudiants'] = array();
if ($result_top && $result_top->num_rows > 0) {
    while ($row = $result_top->fetch_assoc()) {
        if (!isset($stats['top_etudiants'][$row['nom_filiere']])) {
            $stats['top_etudiants'][$row['nom_filiere']] = array();
        }
        if (count($stats['top_etudiants'][$row['nom_filiere']]) < 5) {
            $stats['top_etudiants'][$row['nom_filiere']][] = array(
                'prenom' => $row['prenom'],
                'nom' => $row['nom'],
                'moyenne' => round($row['moyenne_generale'], 2)
            );
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - Président du jury</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/statistiques_president_jury.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
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
                    <li><a href="profile_president.php"><i class="bi bi-person"></i>Profil</a></li>
                    <li><a href="consulter_notes_president.php"><i class="bi bi-journal"></i>Consultation</a></li>
                    <li><a href="generer_attestations.php"><i class="bi bi-file-text"></i>Attestations</a></li>
                    <li><a href="statistiques.php" class="active"><i class="bi bi-bar-chart"></i>Statistiques</a></li>
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

            <h1>Statistiques académiques</h1>

            <!-- 1. Statistiques par filière -->
            <h2 class="section-title">Répartition par filière</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="chart-container">
                        <canvas id="filiereChart"></canvas>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Détails par filière</h3>
                    <ul>
                        <?php foreach ($stats['par_filiere'] as $filiere => $count): ?>
                            <li><?php echo htmlspecialchars($filiere); ?>: <?php echo $count; ?> étudiants</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- 2. Statistiques par niveau -->
            <h2 class="section-title">Répartition par niveau</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="chart-container">
                        <canvas id="niveauChart"></canvas>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Détails par niveau</h3>
                    <ul>
                        <?php foreach ($stats['par_niveau'] as $niveau => $count): ?>
                            <li><?php echo htmlspecialchars($niveau); ?>: <?php echo $count; ?> étudiants</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- 3. Statistiques par semestre -->
            <h2 class="section-title">Répartition par semestre</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="chart-container">
                        <canvas id="semestreChart"></canvas>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Détails par semestre</h3>
                    <ul>
                        <?php foreach ($stats['par_semestre'] as $semestre => $count): ?>
                            <li><?php echo htmlspecialchars($semestre); ?>: <?php echo $count; ?> étudiants</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- 4. Meilleurs étudiants par filière -->
            <h2 class="section-title"></h2>
            <div class="top-students">
                <?php foreach ($stats['top_etudiants'] as $filiere => $etudiants): ?>
                    <div class="stat-card">
                        <h3>Top 5 - <?php echo htmlspecialchars($filiere); ?></h3>
                        <table style="width:100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background-color: #f5f5f5;">
                                    <th style="padding: 10px; text-align: left;">#</th>
                                    <th style="padding: 10px; text-align: left;">Étudiant</th>
                                    <th style="padding: 10px; text-align: left;">Moyenne</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($etudiants as $index => $etudiant): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px;"><?php echo ($index + 1); ?></td>
                                    <td style="padding: 10px;"><?php echo htmlspecialchars($etudiant['prenom'] . ' ' . htmlspecialchars($etudiant['nom'])); ?></td>
                                    <td style="padding: 10px;">
                                        <span class="badge"><?php echo htmlspecialchars($etudiant['moyenne']); ?>/20</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <script>
        // Chart.js configuration
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Chart des filières
            const filiereCtx = document.getElementById('filiereChart').getContext('2d');
            new Chart(filiereCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_keys($stats['par_filiere'])); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($stats['par_filiere'])); ?>,
                        backgroundColor: [
                            '#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Répartition des étudiants par filière'
                        }
                    }
                }
            });

            // 2. Chart des niveaux
            const niveauCtx = document.getElementById('niveauChart').getContext('2d');
            new Chart(niveauCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_keys($stats['par_niveau'])); ?>,
                    datasets: [{
                        label: "Nombre d'étudiants",
                        data: <?php echo json_encode(array_values($stats['par_niveau'])); ?>,
                        backgroundColor: '#007bff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Répartition des étudiants par niveau'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // 3. Chart des semestres
            const semestreCtx = document.getElementById('semestreChart').getContext('2d');
            new Chart(semestreCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_keys($stats['par_semestre'])); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($stats['par_semestre'])); ?>,
                        backgroundColor: [
                            '#ff6384', '#36a2eb', '#ffce56', '#4bc0c0', '#9966ff', '#ff9f40'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Répartition des étudiants par semestre'
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
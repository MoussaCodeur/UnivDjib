<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';

// Récupération des informations utilisateur
$userID = $_SESSION['user_id'];
$sql = "SELECT id_personne, prenom, nom, email, role FROM personne WHERE id_personne = ?";
$stmt = $conn->prepare($sql);
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

// Récupération des statistiques
$stats = [
    'total_etudiants' => 0,
    'total_enseignants' => 0,
    'total_matieres' => 0,
    'taux_reussite' => 0,
    'moyenne_generale' => 0,
    'filieres' => []
];

// Statistiques générales
$sql = "SELECT COUNT(*) as total FROM etudiant";
$result = $conn->query($sql);
$stats['total_etudiants'] = $result->fetch_assoc()['total'];

$sql = "SELECT COUNT(*) as total FROM enseignant";
$result = $conn->query($sql);
$stats['total_enseignants'] = $result->fetch_assoc()['total'];

$sql = "SELECT COUNT(*) as total FROM matiere";
$result = $conn->query($sql);
$stats['total_matieres'] = $result->fetch_assoc()['total'];

// Statistiques par filière
$sql = "SELECT f.id_filiere, f.nom_filiere, 
        COUNT(DISTINCT e.id_etudiant) as nb_etudiants,
        COUNT(DISTINCT m.id_matiere) as nb_matieres,
        AVG(ev.note) as moyenne
        FROM filiere f
        LEFT JOIN etudiant e ON f.id_filiere = e.id_filiere
        LEFT JOIN matiere m ON f.id_filiere = m.id_filiere
        LEFT JOIN evaluer ev ON m.id_matiere = ev.id_matiere
        GROUP BY f.id_filiere";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $stats['filieres'][] = $row;
}

// Calcul du taux de réussite global
$sql = "SELECT 
        SUM(CASE WHEN (ev.cc * 0.4 + ev.tp * 0.2 + IFNULL(a.CF, 0) * 0.4) >= 10 THEN 1 ELSE 0 END) as admis,
        COUNT(*) as total
        FROM evaluer ev
        LEFT JOIN anonymat a ON a.id_anonymat = ev.id_anonymat";
$result = $conn->query($sql);
$data = $result->fetch_assoc();
$stats['taux_reussite'] = ($data['total'] > 0) ? round(($data['admis'] / $data['total']) * 100, 2) : 0;

// Date et heure
$mois_fr = ['', 'jan', 'fév', 'mar', 'avr', 'mai', 'juin', 'juil', 'août', 'sep', 'oct', 'nov', 'déc'];
$jours_fr = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$jour_semaine = $jours_fr[date('w')];
$jour = date('d');
$mois = $mois_fr[date('n')];
$annee = date('Y');
$date = "$jour_semaine $jour $mois $annee";
$heure = date('H:i');

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques doyen - Université de djibouti</title>
    <link rel="stylesheet" href="../../../public/assets/css/statistiques_doyen.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../../../public/assets/img/U-remove.png" alt="Logo U-Digital" style="width:150px;height:100px;background:white;border-radius:50%;margin-left:30px">
        </div>
        
        <div class="sidebar-menu">
            <ul>
                <li>
                    <a href="accueil_doyen.php">
                        <i class="bi-house"></i>
                        <span>Accueil</span>
                    </a>
                </li>
                <li>
                    <a href="profile_doyen.php">
                        <i class="bi-person"></i>
                        <span>Profil</span>
                    </a>
                </li>
                <li>
                    <a href="consultation_doyen.php">
                        <i class="bi-people"></i>
                        <span>Consultation</span>
                    </a>
                </li>
                <li>
                    <a href="statistiques_doyen.php" class="active">
                        <i class="bi-bar-chart"></i>
                        <span>Statistiques</span>
                    </a>
                </li>
                <li>
                    <a href="../../../config/logout.php">
                        <i class="bi-box-arrow-right"></i>
                        <span>Déconnexion</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="header">
            <h1 class="page-title">Tableau de Bord - Statistiques</h1>
            <div class="date-time"><?php echo "$date $heure"; ?></div>
            <div class="user-info">
                <div><?php echo "$nom $prenom"; ?></div>
                <div class="user-avatar"><?php echo strtoupper(substr($nom, 0, 1) . substr($prenom, 0, 1)); ?></div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-title">Étudiants Inscrits</div>
                <div class="stat-value primary"><?php echo $stats['total_etudiants']; ?></div>
                <div class="stat-desc">Nombre total d'étudiants</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-title">Enseignants</div>
                <div class="stat-value success"><?php echo $stats['total_enseignants']; ?></div>
                <div class="stat-desc">Enseignants permanents et vacataires</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-title">Matières</div>
                <div class="stat-value warning"><?php echo $stats['total_matieres']; ?></div>
                <div class="stat-desc">Matières enseignées</div>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-title">Taux de Réussite</div>
                <div class="stat-value danger"><?php echo $stats['taux_reussite']; ?>%</div>
                <div class="stat-desc">Moyenne globale de réussite</div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-section">
            <div class="chart-container">
                <h2 class="chart-title">Répartition des Étudiants par Filière</h2>
                <canvas id="filiereChart"></canvas>
            </div>
            
            <div class="chart-container">
                <h2 class="chart-title">Performances par Filière</h2>
                <canvas id="moyenneChart"></canvas>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-container">
            <h2 class="chart-title">Détails des Filières</h2>
            <table>
                <thead>
                    <tr>
                        <th>Filière</th>
                        <th>Étudiants</th>
                        <th>Matières</th>
                        <th>Moyenne</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['filieres'] as $filiere): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($filiere['nom_filiere']); ?></td>
                            <td><?php echo $filiere['nb_etudiants']; ?></td>
                            <td><?php echo $filiere['nb_matieres']; ?></td>
                            <td><?php echo isset($filiere['moyenne']) ? number_format($filiere['moyenne'], 2) : 'N/A'; ?></td>
                            <td>
                                <?php if (isset($filiere['moyenne'])): ?>
                                    <span class="badge <?php echo ($filiere['moyenne'] >= 10) ? 'badge-success' : (($filiere['moyenne'] >= 8) ? 'badge-warning' : 'badge-danger'); ?>">
                                        <?php echo ($filiere['moyenne'] >= 10) ? 'Excellent' : (($filiere['moyenne'] >= 8) ? 'Moyen' : 'À améliorer'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
    // Graphique de répartition par filière (Doughnut)
    const filiereCtx = document.getElementById('filiereChart').getContext('2d');
    const filiereChart = new Chart(filiereCtx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php foreach ($stats['filieres'] as $filiere): ?>
                    '<?php echo $filiere["nom_filiere"]; ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach ($stats['filieres'] as $filiere): ?>
                        <?php echo $filiere["nb_etudiants"]; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: [
                    '#3498DB', '#2ECC71', '#F39C12', '#E74C3C', '#9B59B6', '#1ABC9C',
                    '#34495E', '#16A085', '#27AE60', '#2980B9', '#8E44AD', '#C0392B'
                ],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.raw || 0;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} étudiants (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });

    // Graphique des performances par filière (Bar)
    const moyenneCtx = document.getElementById('moyenneChart').getContext('2d');
    const moyenneChart = new Chart(moyenneCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php foreach ($stats['filieres'] as $filiere): ?>
                    '<?php echo $filiere["nom_filiere"]; ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Moyenne /20',
                data: [
                    <?php foreach ($stats['filieres'] as $filiere): ?>
                        <?php echo isset($filiere['moyenne']) ? number_format($filiere['moyenne'], 2) : 0; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: '#3498DB',
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 20,
                    grid: {
                        drawBorder: false
                    },
                    ticks: {
                        stepSize: 2
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Moyenne: ${context.raw}/20`;
                        }
                    }
                }
            }
        }
    });
</script>
</body>
</html>
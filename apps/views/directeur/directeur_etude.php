<?php
// Démarrer la session
require_once '../../../config/session.php';

// Connexion a la base de donnees
require_once '../../../config/db.php';

// Récupérer l'ID de l'utilisateur depuis la session
$user_id = $_SESSION['user_id'];

$role_query = $conn->prepare("SELECT role FROM personne WHERE id_personne = ?");
$role_query->bind_param("i", $user_id);
$role_query->execute();
$role_result = $role_query->get_result()->fetch_assoc();

// Récupération des données initiales
$filieres = $conn->query("SELECT * FROM filiere")->fetch_all(MYSQLI_ASSOC);
$niveaux = ['L1', 'L2', 'L3'];
$semestres = ['1', '2'];
$results = [];
$stats = [
    'total' => 0,
    'moyenne_generale' => 0,
    'taux_reussite' => 0
];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filiere = $conn->real_escape_string($_POST['filiere']);
    $niveau = $conn->real_escape_string($_POST['niveau']);
    $semestre = $conn->real_escape_string($_POST['semestre']);

    // Requête des étudiants avec notes
    $query = $conn->prepare("
        SELECT e.id_etudiant, p.nom, p.prenom, m.nom_matiere, ev.note 
        FROM etudiant e
        JOIN personne p ON e.id_etudiant = p.id_personne
        LEFT JOIN evaluer ev ON e.id_etudiant = ev.id_etudiant
        LEFT JOIN matiere m ON ev.id_matiere = m.id_matiere 
            AND m.type_simestre = ?
        WHERE e.id_filiere = ? AND e.niveau_filiere = ?
    ");
    $query->bind_param("sis", $semestre, $filiere, $niveau);
    $query->execute();
    $result = $query->get_result();

    // Organisation des données
    while ($row = $result->fetch_assoc()) {
        $id = $row['id_etudiant'];
        if (!isset($results[$id])) {
            $results[$id] = [
                'id' => $id,
                'nom' => $row['nom'],
                'prenom' => $row['prenom'],
                'notes' => [],
                'moyenne' => 0
            ];
        }
        if ($row['nom_matiere']) {
            $results[$id]['notes'][$row['nom_matiere']] = $row['note'];
        }
    }

    // Calcul des statistiques
    $stats['total'] = count($results);
    $notes_flat = [];
    foreach ($results as $student) {
        if (!empty($student['notes'])) {
            $notes_flat = array_merge($notes_flat, array_values($student['notes']));
        }
    }
    
    // Éviter division par zéro pour les moyennes
    if (count($notes_flat) > 0) {
        $stats['moyenne_generale'] = array_sum($notes_flat) / count($notes_flat);
        $stats['taux_reussite'] = (count(array_filter($notes_flat, fn($n) => $n >= 10)) / count($notes_flat)) * 100;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Directeur</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/directeur_etude.css">
    <link rel="icon" type="image/x-icon" href="../../../Universite.ico">
    
</head>
<body class="bg-gray-50 text-gray-800 transition-all">
    <!-- Overlay pour mobile -->
    <div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="flex">
        <!-- Sidebar - toujours visible sur desktop, caché sur mobile -->
        <aside class="sidebar bg-indigo-900 text-white p-6 shadow-xl w-72">
            <div class="flex items-center justify-between mb-10">
                <div class="flex items-center space-x-3">
                    <svg class="h-8 w-8 text-indigo-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                        <path d="M6 12v5c0 2 1 3 3 3h6c2 0 3-1 3-3v-5"/>
                    </svg>
                    <h2 class="text-2xl font-bold tracking-tight">Université</h2>
                </div>
                <button class="md:hidden text-2xl focus:outline-none" onclick="toggleSidebar()">×</button>
            </div>
            
            <div class="space-y-1">
                <a href="#" class="flex items-center p-3 bg-indigo-800 rounded-lg transition-all">
                    <i class="fas fa-chart-line w-6 text-center mr-3 text-indigo-300"></i>
                    <span>Tableau de bord</span>
                </a>
                <a href="profil_directeur.php" class="flex items-center p-3 hover:bg-indigo-800 rounded-lg transition-all">
                    <i class="fas fa-user w-6 text-center mr-3 text-indigo-300"></i>
                    <span>Profil</span>
                </a>
            </div>
            
            <div class="absolute bottom-6 left-6 right-6">
                <a href="../../../config/logout.php" class="flex items-center p-3 text-indigo-200 hover:bg-indigo-800 rounded-lg transition-all">
                    <i class="fas fa-sign-out-alt w-6 text-center mr-3"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6 lg:p-8 overflow-hidden">
            <!-- Mobile menu button - visible uniquement sur mobile -->
            <div class="md:hidden mb-6">
                <button onclick="toggleSidebar()" class="p-2 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 focus:outline-none transition-all">
                    <i class="fas fa-bars text-lg"></i>
                </button>
            </div>

            <!-- Header -->
            <div class="mb-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-1">Tableau de Bord</h1>
                        <p class="text-gray-600">Bienvenue, <?= htmlspecialchars($_SESSION['nom'] ?? 'Directeur') ?></p>
                    </div>
                    <div class="flex space-x-2">
                        <button id="dark-mode-toggle" class="p-2 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-800 focus:outline-none transition-all">
                            <i class="fas fa-moon"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="card bg-white p-6 rounded-xl shadow-md border border-gray-100 transition-all hover:shadow-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-gray-500 text-sm font-medium">Étudiants</h3>
                        <span class="p-2 rounded-full bg-indigo-50 text-indigo-600">
                            <i class="fas fa-users"></i>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-indigo-600"><?= $stats['total'] ?></p>
                    <p class="text-sm text-gray-500 mt-2">Nombre total d'étudiants</p>
                </div>
                <div class="card bg-white p-6 rounded-xl shadow-md border border-gray-100 transition-all hover:shadow-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-gray-500 text-sm font-medium">Moyenne Générale</h3>
                        <span class="p-2 rounded-full bg-green-50 text-green-600">
                            <i class="fas fa-chart-line"></i>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-green-600"><?= number_format($stats['moyenne_generale'], 2) ?></p>
                    <p class="text-sm text-gray-500 mt-2">Sur 20 points</p>
                </div>
                <div class="card bg-white p-6 rounded-xl shadow-md border border-gray-100 transition-all hover:shadow-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-gray-500 text-sm font-medium">Taux Réussite</h3>
                        <span class="p-2 rounded-full bg-purple-50 text-purple-600">
                            <i class="fas fa-trophy"></i>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-purple-600"><?= number_format($stats['taux_reussite'], 1) ?>%</p>
                    <p class="text-sm text-gray-500 mt-2">Note >= 10/20</p>
                </div>
            </div>

            <!-- Graphique (si des données sont disponibles) -->
            <?php if (!empty($results)): ?>
            <div class="card bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
                <h3 class="text-lg font-semibold mb-4">Répartition des notes</h3>
                <div class="h-64">
                    <canvas id="notesChart"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <form method="post" class="form-card bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8 transition-all">
                <h3 class="text-lg font-semibold mb-4">Générer un rapport</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="filiere" class="block text-sm font-medium text-gray-700 mb-1">Filière</label>
                        <select id="filiere" name="filiere" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-base" required>
                            <option value="">Choisir une filière</option>
                            <?php foreach ($filieres as $filiere): ?>
                            <option value="<?= $filiere['id_filiere'] ?>"><?= htmlspecialchars($filiere['nom_filiere']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="niveau" class="block text-sm font-medium text-gray-700 mb-1">Niveau</label>
                        <select id="niveau" name="niveau" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-base" required>
                            <option value="">Choisir un niveau</option>
                            <?php foreach ($niveaux as $niv): ?>
                            <option value="<?= $niv ?>"><?= $niv ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="semestre" class="block text-sm font-medium text-gray-700 mb-1">Semestre</label>
                        <select id="semestre" name="semestre" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-base" required>
                            <option value="">Choisir un semestre</option>
                            <?php foreach ($semestres as $sem): ?>
                            <option value="<?= $sem ?>">Semestre <?= $sem ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="mt-6 w-full md:w-auto bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <i class="fas fa-file-alt mr-2"></i> Générer le Rapport
                </button>
            </form>

            <!-- Résultats -->
            <?php if (!empty($results)): ?>
            <div class="card bg-white rounded-xl shadow-md border border-gray-100 overflow-x-auto transition-all mb-8">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold">Résultats des étudiants</h3>
                    <p class="text-sm text-gray-500 mt-1">Liste des étudiants et leurs notes</p>
                </div>
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="p-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Étudiant</th>
                            <?php if (!empty($results)): ?>
                                <?php $first_student = reset($results); ?>
                                <?php if (!empty($first_student['notes'])): ?>
                                    <?php foreach (array_keys($first_student['notes']) as $matiere): ?>
                                        <th class="p-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= htmlspecialchars($matiere) ?></th>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                            <th class="p-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Moyenne</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($results as $etudiant): ?>
                        <tr class="hover:bg-gray-50 transition-all">
                            <td class="p-4 whitespace-nowrap"><?= $etudiant['id'] ?></td>
                            <td class="p-4 whitespace-nowrap font-medium"><?= htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']) ?></td>
                            <?php 
                            // S'assurer que l'étudiant a des notes
                            if (!empty($etudiant['notes'])):
                                foreach ($etudiant['notes'] as $note): 
                            ?>
                            <td class="p-4 whitespace-nowrap">
                                <span class="px-2 py-1 rounded-full text-sm 
                                    <?= $note >= 16 ? 'bg-green-100 text-green-800' : 
                                       ($note >= 10 ? 'bg-yellow-100 text-yellow-800' : 
                                       'bg-red-100 text-red-800') ?>">
                                    <?= $note ?? '-' ?>
                                </span>
                            </td>
                            <?php 
                                endforeach;
                            endif;
                            ?>
                            <td class="p-4 whitespace-nowrap font-medium">
                                <?php
                                // Fix division by zero error
                                $note_count = count($etudiant['notes']);
                                if ($note_count > 0) {
                                    echo number_format(array_sum($etudiant['notes']) / $note_count, 2);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('sidebar-active');
            document.getElementById('sidebar-overlay').classList.toggle('active');
            
            // Empêcher le défilement du body quand le menu est ouvert sur mobile
            document.body.classList.toggle('overflow-hidden', document.querySelector('.sidebar').classList.contains('sidebar-active'));
        }
        
        // Dark mode toggle
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        
        // Check for dark mode preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
            darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        }
        
        // Toggle dark mode
        darkModeToggle.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            if (document.documentElement.classList.contains('dark')) {
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            } else {
                darkModeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            }
        });
        
        // Listen for system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
            if (event.matches) {
                document.documentElement.classList.add('dark');
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            } else {
                document.documentElement.classList.remove('dark');
                darkModeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            }
        });
        
        // Chart for note distribution (if data exists)
        <?php if (!empty($results)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Prepare data for chart
            const notes = [];
            <?php 
            foreach ($results as $etudiant): 
                if (!empty($etudiant['notes'])):
                    foreach ($etudiant['notes'] as $note): 
            ?>
                notes.push(<?= $note ?>);
            <?php 
                    endforeach;
                endif;
            endforeach; 
            ?>
            
            // Group notes into ranges for chart
            const ranges = {
                '0-5': 0,
                '5-10': 0,
                '10-15': 0,
                '15-20': 0
            };
            
            notes.forEach(note => {
                if (note < 5) ranges['0-5']++;
                else if (note < 10) ranges['5-10']++;
                else if (note < 15) ranges['10-15']++;
                else ranges['15-20']++;
            });
            
            // Create chart
            const ctx = document.getElementById('notesChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(ranges),
                    datasets: [{
                        label: 'Distribution des notes',
                        data: Object.values(ranges),
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.6)', // Red for 0-5
                            'rgba(245, 158, 11, 0.6)', // Orange for 5-10
                            'rgba(16, 185, 129, 0.6)', // Green for 10-15
                            'rgba(37, 99, 235, 0.6)'  // Blue for 15-20
                        ],
                        borderColor: [
                            'rgba(239, 68, 68, 1)',
                            'rgba(245, 158, 11, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(37, 99, 235, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>
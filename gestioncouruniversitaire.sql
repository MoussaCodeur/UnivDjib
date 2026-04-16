-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Hôte : sql204.byetcluster.com
-- Généré le :  Dim 25 mai 2025 à 04:17
-- Version du serveur :  10.6.19-MariaDB
-- Version de PHP :  7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `if0_38952482_gestioncouruniversitaire`
--

-- --------------------------------------------------------

--
-- Structure de la table `anonymat`
--

CREATE TABLE `anonymat` (
  `id_anonymat` int(11) NOT NULL,
  `id_president` int(11) DEFAULT NULL,
  `CF` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `anonymat`
--

INSERT INTO `anonymat` (`id_anonymat`, `id_president`, `CF`) VALUES
(108998, 220001400, '7'),
(134324, 220001400, '12'),
(148127, 220001400, '13'),
(161052, 220001400, NULL),
(161394, 220001400, '9'),
(174583, 220001400, '16'),
(187240, 220001400, NULL),
(199526, 220001400, '6'),
(200174, 220001400, '12'),
(207164, 220001400, '15'),
(222020, 220001400, '10'),
(224140, 220001400, '8'),
(229944, 220001400, '12'),
(253035, 220001400, '5'),
(260246, 220001400, '13'),
(265713, 220001400, '11'),
(270592, 220001400, '14'),
(271097, 220001400, '13'),
(284884, 220001400, '9'),
(301542, 220001400, '6'),
(333547, 220001400, '7'),
(343906, 220001400, '4'),
(372807, 220001400, '12'),
(389991, 220001400, '13'),
(404688, 220001400, '3'),
(420224, 220001400, '13'),
(423836, 220001400, '8'),
(425109, 220001400, '12'),
(439776, 220001400, '12'),
(453688, 220001400, '7'),
(458943, 220001400, '12'),
(460572, 220001400, '10'),
(461125, 220001400, '16'),
(479907, 220001400, '11'),
(484620, 220001400, '12'),
(506154, 220001400, '11'),
(514728, 220001400, '6'),
(516749, 220001400, '13'),
(526986, 220001400, '10'),
(529919, 220001400, '5'),
(546560, 220001400, '8'),
(550551, 220001400, '19'),
(568395, 220001400, '7'),
(577270, 220001400, '9'),
(589075, 220001400, '4'),
(590615, 220001400, '11'),
(597015, 220001400, '15'),
(597822, 220001400, '14'),
(601290, 220001400, '13'),
(605403, 220001400, NULL),
(639450, 220001400, '12'),
(654064, 220001400, '2'),
(657530, 220001400, '9'),
(660268, 220001400, '8'),
(667215, 220001400, '10'),
(676107, 220001400, '6'),
(679097, 220001400, NULL),
(704669, 220001400, '12'),
(705016, 220001400, '7'),
(706943, 220001400, '5'),
(714559, 220001400, '13'),
(728042, 220001400, NULL),
(728509, 220001400, '6'),
(736398, 220001400, NULL),
(739239, 220001400, '12'),
(744840, 220001400, '4'),
(747969, 220001400, '12'),
(771333, 220001400, '11'),
(783543, 220001400, '10'),
(786710, 220001400, '2'),
(806304, 220001400, '8'),
(820778, 220001400, '11'),
(829102, 220001400, NULL),
(832935, 220001400, NULL),
(865611, 220001400, '11'),
(866793, 220001400, NULL),
(877859, 220001400, NULL),
(882906, 220001400, '9'),
(909204, 220001400, '14'),
(913677, 220001400, '11'),
(928147, 220001400, '7'),
(931440, 220001400, '18'),
(933411, 220001400, '5'),
(949030, 220001400, '5'),
(953496, 220001400, '5'),
(963037, 220001400, '9'),
(963116, 220001400, '6'),
(966433, 220001400, '8'),
(989473, 220001400, NULL),
(990953, 220001400, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `assistant`
--

CREATE TABLE `assistant` (
  `id_assistant` int(11) NOT NULL,
  `departement` varchar(100) DEFAULT NULL,
  `niveau` set('L1','L2','L3') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `assistant`
--

INSERT INTO `assistant` (`id_assistant`, `departement`, `niveau`) VALUES
(220001401, 'Informatique', 'L3'),
(240001433, 'Mathématique ', 'L3');

-- --------------------------------------------------------

--
-- Structure de la table `directeur_etude`
--

CREATE TABLE `directeur_etude` (
  `id_directeur` int(11) NOT NULL,
  `departement` varchar(255) NOT NULL DEFAULT 'Informatique'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `enseignant`
--

CREATE TABLE `enseignant` (
  `id_enseignant` int(11) NOT NULL,
  `specialite` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `statut` enum('permanent','vacataire') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `enseignant`
--

INSERT INTO `enseignant` (`id_enseignant`, `specialite`, `telephone`, `statut`) VALUES
(220001402, 'Master 2', '77201234', 'vacataire'),
(220001403, 'Master 2', '77205678', 'permanent'),
(220001404, 'Doctorat', '77203456', 'permanent'),
(220001405, 'Master 2', '77207890', 'permanent'),
(220001406, 'Master 2', '77201123', 'permanent'),
(220001407, 'Doctorat', '77204567', 'permanent'),
(220001408, 'Master 2', '77209876', 'permanent'),
(220001409, 'Master 2', '77203210', 'permanent'),
(220001411, 'Docteur', '77207865', 'permanent'),
(220001412, 'Master 2', '77209812', 'permanent'),
(240001438, 'Master 2', '77454870', 'vacataire');

-- --------------------------------------------------------

--
-- Structure de la table `enseigner`
--

CREATE TABLE `enseigner` (
  `id_enseignant` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `id_filiere` int(11) NOT NULL DEFAULT 1,
  `niveau_filiere` enum('L1','L2','L3','') NOT NULL DEFAULT 'L1',
  `nb_heure` int(11) DEFAULT NULL,
  `type_semestre` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `enseigner`
--

INSERT INTO `enseigner` (`id_enseignant`, `id_matiere`, `id_filiere`, `niveau_filiere`, `nb_heure`, `type_semestre`) VALUES
(220001402, 36, 1, 'L3', 5, 2),
(220001403, 29, 1, 'L3', 12, 1),
(220001403, 35, 1, 'L3', 8, 2),
(220001404, 27, 1, 'L3', 8, 1),
(220001405, 25, 1, 'L3', 8, 1),
(220001406, 26, 1, 'L3', 8, 1),
(220001406, 31, 1, 'L3', 8, 2),
(220001407, 30, 1, 'L3', 2, 1),
(220001407, 37, 1, 'L3', 3, 2),
(220001408, 28, 1, 'L3', 9, 1),
(220001409, 32, 1, 'L3', 4, 2),
(220001411, 33, 1, 'L3', 7, 2),
(220001412, 34, 1, 'L3', 6, 2),
(240001438, 36, 1, 'L3', 12, 2);

-- --------------------------------------------------------

--
-- Structure de la table `etudiant`
--

CREATE TABLE `etudiant` (
  `id_etudiant` int(11) NOT NULL,
  `id_filiere` int(11) NOT NULL DEFAULT 1,
  `niveau_filiere` enum('L1','L2','L3','') NOT NULL DEFAULT 'L3',
  `statut` varchar(50) DEFAULT 'inscrit'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `etudiant`
--

INSERT INTO `etudiant` (`id_etudiant`, `id_filiere`, `niveau_filiere`, `statut`) VALUES
(220001415, 1, 'L3', 'En attente'),
(220001416, 1, 'L3', 'inscrit'),
(220001417, 1, 'L3', 'En attente'),
(220001418, 1, 'L3', 'En attente'),
(240001435, 1, 'L3', 'En attente'),
(240001437, 1, 'L3', 'En attente');

-- --------------------------------------------------------

--
-- Structure de la table `evaluer`
--

CREATE TABLE `evaluer` (
  `id_evaluation` varchar(255) NOT NULL,
  `date_evaluation` date DEFAULT NULL,
  `cc` float DEFAULT NULL,
  `tp` float DEFAULT NULL,
  `note` float DEFAULT NULL,
  `id_matiere` int(11) DEFAULT NULL,
  `id_etudiant` int(11) DEFAULT NULL,
  `id_anonymat` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `evaluer`
--

INSERT INTO `evaluer` (`id_evaluation`, `date_evaluation`, `cc`, `tp`, `note`, `id_matiere`, `id_etudiant`, `id_anonymat`) VALUES
('682543eaa63dd', '2025-05-20', 12, 12, 11, 35, 220001418, 372807),
('682543eaa8ffe', '2025-05-20', 18, 19, 12, 35, 220001417, 654064),
('682543eaac134', '2025-05-20', 14, 10, 12, 35, 220001416, 933411),
('682543eaad757', '2025-05-20', 10, 19, 15, 35, 220001415, 389991),
('68254503763eb', '2025-05-15', 4, NULL, 10, 31, 220001418, 301542),
('6825450377626', '2025-05-15', 15, 18, 10, 32, 220001418, 660268),
('682545037930c', '2025-05-15', 8, NULL, 11, 33, 220001418, 963116),
('682545037aceb', '2025-05-15', 19, NULL, 12, 34, 220001418, 479907),
('682545037be67', '2025-05-15', 19, 18, 11, 36, 220001418, 343906),
('682545037d23c', '2025-05-15', 8, NULL, 11, 37, 220001418, 568395),
('682545037e3b1', '2025-05-15', 16, NULL, 8, 31, 220001417, 270592),
('682545037fcf3', '2025-05-15', 16, 12, 9, 32, 220001417, 705016),
('6825450381df4', '2025-05-15', 3, NULL, 11, 33, 220001417, 224140),
('6825450383383', '2025-05-15', 11, NULL, 12, 34, 220001417, 423836),
('6825450384714', '2025-05-15', 11, 12, 7, 36, 220001417, 590615),
('6825450385c6d', '2025-05-15', 12, NULL, 11, 37, 220001417, 207164),
('6825450386df0', '2025-05-15', 12, NULL, 12, 31, 220001416, 265713),
('6825450387cfe', '2025-05-15', 12, 17, 16, 32, 220001416, 786710),
('682545038aa97', '2025-05-15', 16, NULL, 12, 33, 220001416, 148127),
('682545038c697', '2025-05-15', 12, NULL, 4, 34, 220001416, 420224),
('682545038e3f2', '2025-05-15', 16, 18, 3, 36, 220001416, 161394),
('682545038febd', '2025-05-15', 19, NULL, 8, 37, 220001416, 589075),
('6825450392968', '2025-05-15', 12, 18, 9, 31, 220001415, 966433),
('6825450393d88', '2025-05-15', 11, 12, 19, 32, 220001415, 546560),
('6825450395c3d', '2025-05-15', 12, NULL, 12, 33, 220001415, 253035),
('6825450397904', '2025-05-15', 12, NULL, 2, 34, 220001415, 222020),
('682545039a4a0', '2025-05-15', 12, 19, 9, 36, 220001415, 333547),
('682545039c3e2', '2025-05-15', 17, NULL, 9, 37, 220001415, 744840),
('6825719db438c', '2025-05-15', 12, 12, 18, 25, 220001418, 953496),
('6825719db67cd', '2025-05-15', 16, 14, 12, 26, 220001418, 453688),
('6825719db77bd', '2025-05-15', 3, 20, 15, 27, 220001418, 639450),
('6825719db8f47', '2025-05-15', 18, NULL, 19, 28, 220001418, 460572),
('6825719dbb0ef', '2025-05-19', 11, NULL, 14, 29, 220001418, 404688),
('6825719dbc077', '2025-05-15', 18, NULL, 12, 30, 220001418, 529919),
('6825719dbe41a', '2025-05-15', 10, 12, 11, 25, 220001417, 597015),
('6825719dbf400', '2025-05-15', 12, 12, 12, 26, 220001417, 928147),
('6825719dc0338', '2025-05-15', 12, 19, 16, 27, 220001417, 199526),
('6825719dc2710', '2025-05-15', 13, 14, 11, 28, 220001417, 601290),
('6825719dc36e6', '2025-05-19', 14, 18, 18, 29, 220001417, 909204),
('6825719dc4644', '2025-05-15', 12, NULL, 9, 30, 220001417, 706943),
('6825719dc73fe', '2025-05-15', 15, 12, 4, 25, 220001415, 657530),
('6825719dc9e0d', '2025-05-15', 18, 18, 10, 26, 220001415, 714559),
('6825719dcbb9c', '2025-05-15', 18, 12, 12, 27, 220001415, 200174),
('6825719dce0ca', '2025-05-15', 12, 11, 18, 28, 220001415, 771333),
('6825719dd003f', '2025-05-19', 12, 15, 9, 29, 220001415, 882906),
('6825719dd24b4', '2025-05-15', 12, NULL, 20, 30, 220001415, 667215),
('682680bd8179d', '2025-05-19', 9, 16, 12, 29, 220001416, 739239),
('682ada20c397e', '2025-05-19', 12, 17, NULL, 27, 220001416, 458943),
('682ae984a0f6d', '2025-05-19', 18, 19, NULL, 26, 220001416, 704669),
('682d3bcb950d0', '2025-05-20', 12, 17, NULL, 25, 240001435, 229944),
('682d3bcb96045', '2025-05-20', 12, 12, NULL, 26, 240001435, 865611),
('682d3bcb96fff', '2025-05-20', 13, 9, NULL, 27, 240001435, 931440),
('682d3bcb98030', '2025-05-20', 14, 10, NULL, 28, 240001435, 439776),
('682d3bcb99012', '2025-05-20', 12, 13, NULL, 29, 240001435, 506154),
('682d3bcb99ec3', '2025-05-20', 18, NULL, NULL, 30, 240001435, 425109),
('682d3bea31225', '2025-05-20', 19, NULL, NULL, 31, 240001435, 963037),
('682d3bea327b8', '2025-05-20', 12, 15, NULL, 32, 240001435, 134324),
('682d3bea33c49', '2025-05-20', 19, NULL, NULL, 33, 240001435, 820778),
('682d3bea34ef6', '2025-05-20', 12, NULL, NULL, 34, 240001435, 271097),
('682d3bea362d1', '2025-05-20', 12, 16, NULL, 35, 240001435, 913677),
('682d3bea373b7', '2025-05-20', 12, 12, NULL, 36, 240001435, 526986),
('682d3bea3855a', '2025-05-20', 18, 14, NULL, 37, 240001435, 174583),
('682d4a3c90bd0', '2025-05-20', NULL, NULL, NULL, 25, 240001437, 550551),
('682d4a3c91ae1', '2025-05-20', NULL, NULL, NULL, 26, 240001437, 866793),
('682d4a3c9273c', '2025-05-20', NULL, NULL, NULL, 27, 240001437, 990953),
('682d4a3c93903', '2025-05-20', NULL, NULL, NULL, 28, 240001437, 728042),
('682d4a3c947a3', '2025-05-20', NULL, NULL, NULL, 29, 240001437, 679097),
('682d4a3c95584', '2025-05-20', NULL, NULL, NULL, 30, 240001437, 989473),
('682d4a714a5da', '2025-05-20', NULL, NULL, NULL, 31, 240001437, 877859),
('682d4a714b2a4', '2025-05-20', NULL, NULL, NULL, 32, 240001437, 832935),
('682d4a714bd91', '2025-05-20', NULL, NULL, NULL, 33, 240001437, 605403),
('682d4a714c883', '2025-05-20', NULL, NULL, NULL, 34, 240001437, 736398),
('682d4a714d445', '2025-05-20', 10, 10, NULL, 35, 240001437, 829102),
('682d4a714e140', '2025-05-20', NULL, NULL, NULL, 36, 240001437, 187240),
('682d4a714eecc', '2025-05-20', NULL, NULL, NULL, 37, 240001437, 161052);

-- --------------------------------------------------------

--
-- Structure de la table `filiere`
--

CREATE TABLE `filiere` (
  `id_filiere` int(11) NOT NULL,
  `nom_filiere` varchar(100) NOT NULL,
  `responsable_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `filiere`
--

INSERT INTO `filiere` (`id_filiere`, `nom_filiere`, `responsable_id`) VALUES
(1, 'Informatique', 220001401),
(2, 'Mathématique ', 240001433);

-- --------------------------------------------------------

--
-- Structure de la table `matiere`
--

CREATE TABLE `matiere` (
  `id_matiere` int(11) NOT NULL,
  `nom_matiere` varchar(100) DEFAULT NULL,
  `coeff` float DEFAULT NULL,
  `id_filiere` int(11) NOT NULL DEFAULT 1,
  `niveau_filiere` enum('L1','L2','L3','') NOT NULL DEFAULT 'L3',
  `type_simestre` varchar(255) NOT NULL DEFAULT '2'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `matiere`
--

INSERT INTO `matiere` (`id_matiere`, `nom_matiere`, `coeff`, `id_filiere`, `niveau_filiere`, `type_simestre`) VALUES
(1, 'Logiques et Arithmetiques', 6, 1, 'L1', '1'),
(2, 'Logiques et Arithmetiques', 6, 1, 'L1', '1'),
(3, 'Introduction a l\'Algorithmique', 6, 1, 'L1', '1'),
(4, 'Representation des donnees en machine', 6, 1, 'L1', '1'),
(5, 'Anglais I', 3, 1, 'L1', '1'),
(6, 'Outils informatiques', 3, 1, 'L1', '1'),
(7, 'Analyse pour informatique 2', 6, 1, 'L1', '2'),
(8, 'Algebre lineaire et reduction des endomorphismes', 6, 1, 'L1', '2'),
(9, 'Introduction aux Systemes d\'Exploitation', 5, 1, 'L1', '2'),
(10, 'Programmation procedurale', 6, 1, 'L1', '2'),
(11, 'Introduction aux reseaux informatiques', 6, 1, 'L1', '2'),
(12, 'Anglais II', 3, 1, 'L1', '2'),
(13, 'Probabilités & Statistiques', 5, 1, 'L2', '1'),
(14, 'Architectures des ordinateurs I', 5, 1, 'L2', '1'),
(15, 'Structures des donnees lineaires', 6, 1, 'L2', '1'),
(16, 'Reseaux et Protocoles', 6, 1, 'L2', '1'),
(17, 'Bases de donnees', 5, 1, 'L2', '1'),
(18, 'Anglais III', 3, 1, 'L2', '1'),
(19, 'Administration des Bases de donnees', 6, 1, 'L2', '2'),
(20, 'Introduction a la programmation orientee objet', 6, 1, 'L2', '2'),
(21, 'Systèmes d\'Exploitation', 5, 1, 'L2', '2'),
(22, 'Structures des donnees arborescentes', 5, 1, 'L2', '2'),
(23, 'Programmation Web', 5, 1, 'L2', '2'),
(24, 'Anglais IV', 3, 1, 'L2', '2'),
(25, 'Architectures des ordinateurs II', 6, 1, 'L3', '1'),
(26, 'Algorithme de Graphes', 5, 1, 'L3', '1'),
(27, 'Programmation et Conception Orientee Objet', 6, 1, 'L3', '1'),
(28, 'Gestion des projets', 5, 1, 'L3', '1'),
(29, 'Bases de donnees avancees', 5, 1, 'L3', '1'),
(30, 'Anglais V', 3, 1, 'L3', '1'),
(31, 'Intelligence Artificielle', 5, 1, 'L3', '2'),
(32, 'Administration des systemes', 5, 1, 'L3', '2'),
(33, 'FTI', 4, 1, 'L3', '2'),
(34, 'Langages et Compilation', 4, 1, 'L3', '2'),
(35, 'Genie Logiciel', 4, 1, 'L3', '2'),
(36, 'Projet Tutore', 5, 1, 'L3', '2'),
(37, 'Anglais VI', 3, 1, 'L3', '2');

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `fichier_joint` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `messages`
--

INSERT INTO `messages` (`id`, `topic_id`, `user_id`, `content`, `created_at`, `fichier_joint`) VALUES
(19, 12, 240001435, 'Bonjours cher Rachid', '2025-05-21 02:37:57', NULL),
(20, 12, 220001415, 'OUI Moussa', '2025-05-21 02:38:29', NULL),
(21, 12, 240001435, 'J\'ai une question a propos de projet tutore', '2025-05-21 03:54:47', NULL),
(22, 12, 220001415, 'demande je suis la pour vous repondre', '2025-05-21 03:55:51', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

CREATE TABLE `password_resets` (
  `id_personne` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expiry` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `password_resets`
--

INSERT INTO `password_resets` (`id_personne`, `email`, `token`, `expiry`) VALUES
(220001418, 'ayanamina359@gmail.com', '62c3af9452550d9163de31591750d0c4ddf901a7c353854723c65a4950c756f86ae52af9a7f40f79abf662cc05a0df7d1387', '2025-05-19 16:08:18'),
(240001438, 'idmanidman1@gmail.com', '23c9e200694b1b7db25d0463a9ed2aa59baa97b5f42d6e852522cee3499e6373e80fc1f51376f80f41d52e5e8c21dbfff5eb', '2025-05-24 16:23:37');

-- --------------------------------------------------------

--
-- Structure de la table `personne`
--

CREATE TABLE `personne` (
  `id_personne` int(11) NOT NULL,
  `prenom` varchar(50) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mot_de_passe` varchar(255) DEFAULT NULL,
  `image_profile` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `date_inscription` date DEFAULT NULL,
  `dateNaissance` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `personne`
--

INSERT INTO `personne` (`id_personne`, `prenom`, `nom`, `email`, `mot_de_passe`, `image_profile`, `role`, `date_inscription`, `dateNaissance`) VALUES
(220001400, 'Mohamed', 'Yacin', 'mohamed.yacin@outlook.fr', '$2y$10$76L0em7hr/LqyslgCW0uEOKx8ZwuEEnqAdGTqKCLejM18I6qv3h6G', 'img1.png', 'president_jury', '2025-03-06', NULL),
(220001401, 'Djibril', 'Moussa', 'djibril.moussa@gmail.com', '12345678', 'profile3.jpg', 'assistant', '2025-01-17', '1997-02-12'),
(220001402, 'Idriss', 'Soumeya', 'idriss.soumeya@gmail.com', '1234567890', 'profile2.jpg', 'enseignant', '2025-01-16', '1998-02-12'),
(220001403, 'Beille', 'Souleiman', 'moussaadendoualehdev2@gmail.com', '123456789', 'profile2.jpg', 'enseignant', '2025-01-23', NULL),
(220001404, 'Aden', 'Ismael', 'hajimohamedamin34@gmail.com', '1234567890', 'profile1.jpg', 'enseignant', '2025-01-21', NULL),
(220001405, 'Ahmed', 'Hamadou', 'ahmed.hamadou@yahoo.fr', '1234567890', 'profile2.jpg', 'enseignant', '2025-01-22', NULL),
(220001406, 'Mahamoud', 'Mahfoud', 'mahamoud.mahfoud@outlook.com', 'ba7518ae6576885c79519e014fafa8e506b637301f7029d3ce26bb311f9596fd', 'profile3.jpg', 'enseignant', '2025-01-23', NULL),
(220001407, 'Abdillahi', 'Mohamed', 'somali.mohamed@gmail.com', '1a80fed5b95d0fe382cd97635e7f4e178aefb4874cee9744b4c095c26231be27', 'profile4.jpg', 'enseignant', '2025-01-24', NULL),
(220001408, 'qalib', 'Yahya', 'fatima.mahmoud@live.fr', 'c3f79b1a0dcb04ae9bb74069bbd21f6a0a2f980623ba604a99b231ad5c83d37a', 'profile5.jpg', 'enseignant', '2025-01-25', NULL),
(220001409, 'Abdallah', 'Ali', 'aden.said@gmail.com', '234567890', 'profile6.jpg', 'enseignant', '2025-01-26', NULL),
(220001411, 'Assoweh', 'Houssein', 'hassan.mohamed@gmail.com', 'd05b92f6e0813e612be3724ceee5489b8c26c4812acf0e60ef67b85a16dd6bc8', 'profile8.jpg', 'enseignant', '2025-01-28', NULL),
(220001412, 'Alaoui Othmane', 'Yazidi', 'lina.omar@yahoo.com', 'd05b92f6e0813e612be3724ceee5489b8c26c4812acf0e60ef67b85a16dd6bc8', 'profile9.jpg', 'enseignant', '2025-01-29', NULL),
(220001415, 'Djibril', 'Rachid', 'moussaadendoualeh2@gmail.com ', '12345678', 'img1.png', 'etudiant', '2025-02-05', '2004-02-12'),
(220001416, 'Omar', 'Moussa', 'omarmeiraneh123@gmail.com', '12345678', 'img2.png', 'etudiant', '2025-03-06', '2003-01-01'),
(220001417, 'Abdi', 'Mohamed', 'mohamedabdidaher9@gmail.com', '$2y$10$8riznp88cRia3zF4TONYkeK3sFcHPtjYTj.nZ.0jAcQdsJe5EE5e2', 'profile1.jpg', 'etudiant', '2025-01-15', '2003-02-12'),
(220001418, 'Yacin', 'Amina', 'ayanamina359@gmail.com', '12345678', 'profile2.jpg', 'etudiant', '2025-01-20', '2004-12-20'),
(240001404, 'Moussa', 'admin', 'moussaadendoualeh2@gmail.com ', 'GestionCourUniversitaire24', 'img.png', 'admin', '2025-03-06', '2025-03-06'),
(240001417, 'Yacin', 'Mohamed', 'mohamedyacin@gmail.com', '$2y$10$Nj96CQpkHFtu/TeN9LTC/ePqEpYb39nPd4NkRwBjoIX4jC7gOQX/S', 'img.png', 'president', '2025-05-04', '2004-02-21'),
(240001418, 'YACIN', 'AHMED ', 'asia@gmail.com', '12345678', 'img.png', 'directeur', '2025-05-04', '2004-02-21'),
(240001430, 'saida', 'chireh', 'saidachireh@gmail.com', '$2y$10$mgp6B3an709KaxHjTH78GuM.Wyzaak49/E3LVxgKuS/mkl1bq1TQm', 'profile.png', 'doyen', '2025-02-04', '2025-03-06'),
(240001433, 'Ahmed', 'Marwo', 'Marwo@gmail.com', '$2y$10$/KJd/LqM/5wOvqBtaoGbfu1fTSGbpOTVUrvW5QcvUgkst8vT4Vw7u', 'img.png', 'assistant', '2025-05-20', '2009-02-12'),
(240001435, 'Moussa', 'Aden Doualeh', 'moussaaden2023@gmail.com', '$2y$10$hOlNbZlCuG9yh.s2YVLwZeZF7Jogn5VlLN4ILkkfwU.x3tIPeGheC', 'img.png', 'etudiant', '2025-05-20', '2005-02-12'),
(240001437, 'Farah Ali', 'Ali', 'moussaadendoualehassis@gmail.com', '$2y$10$os5kAFwQIJVVi5gJR.CauuMIw0cMDkWDubCYXGHyQoTYcyT8rwbpO', 'img.png', 'etudiant', '2025-05-20', '2005-02-12'),
(240001438, 'IDMAN', 'Idman', 'idmanidman1@gmail.com', '$2y$10$YU9zIInCsDt9nPB82H8.8.mvwqhKSRJsqKxueXwDyJNddjE.EXVaa', 'img.png', 'enseignant', '2025-05-20', '1998-02-12');

-- --------------------------------------------------------

--
-- Structure de la table `planning`
--

CREATE TABLE `planning` (
  `id_planning` int(11) NOT NULL,
  `id_assistant` int(11) NOT NULL,
  `id_personne` int(11) NOT NULL,
  `departement` varchar(50) NOT NULL,
  `chemin_planning` varchar(255) DEFAULT NULL,
  `date_depot` date DEFAULT NULL,
  `role_personne` varchar(255) NOT NULL,
  `type_planning` enum('cours','examen','autres','') NOT NULL,
  `type_semestre` enum('1','2') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `planning`
--

INSERT INTO `planning` (`id_planning`, `id_assistant`, `id_personne`, `departement`, `chemin_planning`, `date_depot`, `role_personne`, `type_planning`, `type_semestre`) VALUES
(62, 220001401, 220001403, 'Informatique', '../teachers/uploads/682569996c7ca_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-15', 'enseignant', 'cours', '1'),
(63, 220001401, 220001414, 'Informatique', '../students/uploads/68256aacc5f45_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-15', 'etudiant', 'examen', '2'),
(64, 220001401, 220001415, 'Informatique', '../students/uploads/68256aacc5f45_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-15', 'etudiant', 'examen', '2'),
(65, 220001401, 220001416, 'Informatique', '../students/uploads/68256aacc5f45_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-15', 'etudiant', 'examen', '2'),
(66, 220001401, 220001417, 'Informatique', '../students/uploads/68256aacc5f45_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-15', 'etudiant', 'examen', '2'),
(67, 220001401, 220001418, 'Informatique', '../students/uploads/68256aacc5f45_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-15', 'etudiant', 'examen', '2'),
(68, 220001401, 220001419, 'Informatique', '../students/uploads/68256aacc5f45_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-15', 'etudiant', 'examen', '2'),
(69, 220001401, 220001420, 'Informatique', '../students/uploads/68256aacc5f45_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-15', 'etudiant', 'examen', '2'),
(70, 220001401, 220001421, 'Informatique', '../students/uploads/68256aacc5f45_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-15', 'etudiant', 'examen', '2'),
(71, 220001401, 220001422, 'Informatique', '../students/uploads/68256aacc5f45_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-15', 'etudiant', 'examen', '2'),
(72, 220001401, 220001423, 'Informatique', '../students/uploads/68256aacc5f45_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-15', 'etudiant', 'examen', '2'),
(73, 220001401, 220001424, 'Informatique', '../students/uploads/68256aacc5f45_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-15', 'etudiant', 'examen', '2'),
(74, 220001401, 220001403, 'Informatique', '../teachers/uploads/68256c38c8ca6_Emploi_du_temps_L3_INFOR_S1.pdf', '2025-05-15', 'enseignant', 'cours', '1'),
(75, 220001401, 220001409, 'Informatique', '../teachers/uploads/68256c5f1f0a7_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-15', 'enseignant', 'cours', '1'),
(76, 220001401, 220001403, 'Informatique', '../teachers/uploads/68256de383487_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-15', 'enseignant', 'autres', '2'),
(78, 220001401, 220001403, 'Informatique', '../teachers/uploads/6826623499a7a_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-15', 'enseignant', 'examen', '1'),
(79, 220001401, 220001414, 'Informatique', '../students/uploads/682663c014dcf_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-15', 'etudiant', 'cours', '2'),
(80, 220001401, 220001415, 'Informatique', '../students/uploads/682663c014dcf_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-15', 'etudiant', 'cours', '2'),
(81, 220001401, 220001416, 'Informatique', '../students/uploads/682663c014dcf_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-15', 'etudiant', 'cours', '2'),
(82, 220001401, 220001417, 'Informatique', '../students/uploads/682663c014dcf_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-15', 'etudiant', 'cours', '2'),
(83, 220001401, 220001418, 'Informatique', '../students/uploads/682663c014dcf_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-15', 'etudiant', 'cours', '2'),
(84, 220001401, 220001419, 'Informatique', '../students/uploads/682663c014dcf_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-15', 'etudiant', 'cours', '2'),
(85, 220001401, 220001421, 'Informatique', '../students/uploads/682663c014dcf_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-15', 'etudiant', 'cours', '2'),
(86, 220001401, 220001422, 'Informatique', '../students/uploads/682663c014dcf_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-15', 'etudiant', 'cours', '2'),
(87, 220001401, 220001423, 'Informatique', '../students/uploads/682663c014dcf_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-15', 'etudiant', 'cours', '2'),
(88, 220001401, 220001424, 'Informatique', '../students/uploads/682663c014dcf_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-15', 'etudiant', 'cours', '2'),
(89, 220001401, 220001403, 'Informatique', '../teachers/uploads/682ad72418a54_Page_Garde_U-Digital 2 (3).pdf', '2025-05-19', 'enseignant', 'examen', '2'),
(90, 220001401, 220001414, 'Informatique', '../students/uploads/682ad8f8a80e7_Cours_FTI_5_L3.pdf', '2025-05-19', 'etudiant', 'cours', '2'),
(91, 220001401, 220001415, 'Informatique', '../students/uploads/682ad8f8a80e7_Cours_FTI_5_L3.pdf', '2025-05-19', 'etudiant', 'cours', '2'),
(92, 220001401, 220001416, 'Informatique', '../students/uploads/682ad8f8a80e7_Cours_FTI_5_L3.pdf', '2025-05-19', 'etudiant', 'cours', '2'),
(93, 220001401, 220001417, 'Informatique', '../students/uploads/682ad8f8a80e7_Cours_FTI_5_L3.pdf', '2025-05-19', 'etudiant', 'cours', '2'),
(94, 220001401, 220001418, 'Informatique', '../students/uploads/682ad8f8a80e7_Cours_FTI_5_L3.pdf', '2025-05-19', 'etudiant', 'cours', '2'),
(95, 220001401, 220001414, 'Informatique', '../students/uploads/682ae836d9659_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-19', 'etudiant', 'autres', '2'),
(96, 220001401, 220001415, 'Informatique', '../students/uploads/682ae836d9659_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-19', 'etudiant', 'autres', '2'),
(97, 220001401, 220001416, 'Informatique', '../students/uploads/682ae836d9659_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-19', 'etudiant', 'autres', '2'),
(98, 220001401, 220001417, 'Informatique', '../students/uploads/682ae836d9659_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-19', 'etudiant', 'autres', '2'),
(99, 220001401, 220001418, 'Informatique', '../students/uploads/682ae836d9659_Emploi_du_temps_L3_INFOR_(1).pdf', '2025-05-19', 'etudiant', 'autres', '2'),
(100, 220001401, 220001415, 'Informatique', '../students/uploads/682cf7a282b98_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(101, 220001401, 220001416, 'Informatique', '../students/uploads/682cf7a282b98_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(102, 220001401, 220001417, 'Informatique', '../students/uploads/682cf7a282b98_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(103, 220001401, 220001418, 'Informatique', '../students/uploads/682cf7a282b98_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(104, 220001401, 240001434, 'Informatique', '../students/uploads/682cf7a282b98_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(105, 220001401, 220001415, 'Informatique', '../students/uploads/682d3f869bd80_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(106, 220001401, 220001416, 'Informatique', '../students/uploads/682d3f869bd80_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(107, 220001401, 220001417, 'Informatique', '../students/uploads/682d3f869bd80_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(108, 220001401, 220001418, 'Informatique', '../students/uploads/682d3f869bd80_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(109, 220001401, 240001435, 'Informatique', '../students/uploads/682d3f869bd80_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(110, 220001401, 220001415, 'Informatique', '../students/uploads/682d45da948e5_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(111, 220001401, 220001416, 'Informatique', '../students/uploads/682d45da948e5_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(112, 220001401, 220001417, 'Informatique', '../students/uploads/682d45da948e5_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(113, 220001401, 220001418, 'Informatique', '../students/uploads/682d45da948e5_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(114, 220001401, 240001435, 'Informatique', '../students/uploads/682d45da948e5_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(115, 220001401, 240001436, 'Informatique', '../students/uploads/682d45da948e5_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(116, 220001401, 220001403, 'Informatique', '../teachers/uploads/682d460519041_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'enseignant', 'cours', '2'),
(117, 220001401, 220001403, 'Informatique', '../teachers/uploads/682d49c7797da_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'enseignant', 'cours', '2'),
(118, 220001401, 220001415, 'Informatique', '../students/uploads/682d4b9fe9fed_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(119, 220001401, 220001416, 'Informatique', '../students/uploads/682d4b9fe9fed_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(120, 220001401, 220001417, 'Informatique', '../students/uploads/682d4b9fe9fed_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(121, 220001401, 220001418, 'Informatique', '../students/uploads/682d4b9fe9fed_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(122, 220001401, 240001435, 'Informatique', '../students/uploads/682d4b9fe9fed_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(123, 220001401, 240001437, 'Informatique', '../students/uploads/682d4b9fe9fed_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'etudiant', 'cours', '2'),
(124, 220001401, 220001403, 'Informatique', '../teachers/uploads/682d756e1d9ac_Emploi_du_temps_L3_INFOR_S2.pdf', '2025-05-20', 'enseignant', 'cours', '2');

-- --------------------------------------------------------

--
-- Structure de la table `president_jury`
--

CREATE TABLE `president_jury` (
  `id_president` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `president_jury`
--

INSERT INTO `president_jury` (`id_president`) VALUES
(220001400),
(240001417);

-- --------------------------------------------------------

--
-- Structure de la table `recevoir_ressources`
--

CREATE TABLE `recevoir_ressources` (
  `id_etudiant` int(11) NOT NULL,
  `id_ressource` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `recevoir_ressources`
--

INSERT INTO `recevoir_ressources` (`id_etudiant`, `id_ressource`) VALUES
(220001415, '682d006e051ca'),
(220001415, '682d038b2d4f7'),
(220001415, '682d3e96ba6f5'),
(220001415, '682d4ac4ef543'),
(220001416, '682d006e051ca'),
(220001416, '682d038b2d4f7'),
(220001416, '682d3e96ba6f5'),
(220001416, '682d4ac4ef543'),
(220001417, '682d006e051ca'),
(220001417, '682d038b2d4f7'),
(220001417, '682d3e96ba6f5'),
(220001417, '682d4ac4ef543'),
(220001418, '682d006e051ca'),
(220001418, '682d038b2d4f7'),
(220001418, '682d3e96ba6f5'),
(220001418, '682d4ac4ef543'),
(240001435, '682d3e96ba6f5'),
(240001435, '682d4ac4ef543'),
(240001437, '682d4ac4ef543');

-- --------------------------------------------------------

--
-- Structure de la table `ressource`
--

CREATE TABLE `ressource` (
  `id_ressource` varchar(50) NOT NULL,
  `id_enseignant` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `chemin_fichier` varchar(255) DEFAULT NULL,
  `date_depot` date DEFAULT NULL,
  `id_matiere` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `ressource`
--

INSERT INTO `ressource` (`id_ressource`, `id_enseignant`, `type`, `chemin_fichier`, `date_depot`, `id_matiere`) VALUES
('682d006e051ca', 220001403, 'td', '../students/uploads/bda_cours1a.pdf', '2025-05-20', 35),
('682d038b2d4f7', 220001403, 'cours', '../students/uploads/bda_cours1a.pdf', '2025-05-20', 35),
('682d3e96ba6f5', 220001403, 'cours', '../students/uploads/bda_cours1a.pdf', '2025-05-20', 35),
('682d4ac4ef543', 220001403, 'cours', '../students/uploads/Chapitre-II-UML.ppt', '2025-05-20', 35);

-- --------------------------------------------------------

--
-- Structure de la table `topics`
--

CREATE TABLE `topics` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `topics`
--

INSERT INTO `topics` (`id`, `user_id`, `title`, `created_at`) VALUES
(12, 220001415, 'Preparation du projet tutore', '2025-05-15 08:17:13');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `anonymat`
--
ALTER TABLE `anonymat`
  ADD PRIMARY KEY (`id_anonymat`),
  ADD KEY `id_president` (`id_president`);

--
-- Index pour la table `assistant`
--
ALTER TABLE `assistant`
  ADD PRIMARY KEY (`id_assistant`);

--
-- Index pour la table `directeur_etude`
--
ALTER TABLE `directeur_etude`
  ADD KEY `id_directeur` (`id_directeur`);

--
-- Index pour la table `enseignant`
--
ALTER TABLE `enseignant`
  ADD PRIMARY KEY (`id_enseignant`);

--
-- Index pour la table `enseigner`
--
ALTER TABLE `enseigner`
  ADD PRIMARY KEY (`id_enseignant`,`id_matiere`),
  ADD KEY `id_matiere` (`id_matiere`),
  ADD KEY `id_filiere` (`id_filiere`);

--
-- Index pour la table `etudiant`
--
ALTER TABLE `etudiant`
  ADD PRIMARY KEY (`id_etudiant`),
  ADD KEY `id_filiere` (`id_filiere`);

--
-- Index pour la table `evaluer`
--
ALTER TABLE `evaluer`
  ADD PRIMARY KEY (`id_evaluation`),
  ADD KEY `id_etudiant` (`id_etudiant`),
  ADD KEY `id_matiere` (`id_matiere`),
  ADD KEY `id_anonymat` (`id_anonymat`);

--
-- Index pour la table `filiere`
--
ALTER TABLE `filiere`
  ADD PRIMARY KEY (`id_filiere`),
  ADD UNIQUE KEY `nom_filiere` (`nom_filiere`),
  ADD KEY `responsable_id` (`responsable_id`);

--
-- Index pour la table `matiere`
--
ALTER TABLE `matiere`
  ADD PRIMARY KEY (`id_matiere`),
  ADD KEY `id_filiere` (`id_filiere`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `messages_ibfk_2` (`user_id`);

--
-- Index pour la table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `fk_personne_password_resets` (`id_personne`);

--
-- Index pour la table `personne`
--
ALTER TABLE `personne`
  ADD PRIMARY KEY (`id_personne`);

--
-- Index pour la table `planning`
--
ALTER TABLE `planning`
  ADD PRIMARY KEY (`id_planning`,`id_assistant`,`id_personne`),
  ADD KEY `id_assistant` (`id_assistant`),
  ADD KEY `id_personne` (`id_personne`);

--
-- Index pour la table `president_jury`
--
ALTER TABLE `president_jury`
  ADD PRIMARY KEY (`id_president`);

--
-- Index pour la table `recevoir_ressources`
--
ALTER TABLE `recevoir_ressources`
  ADD PRIMARY KEY (`id_etudiant`,`id_ressource`),
  ADD KEY `recevoir_ressources_ibfk_2` (`id_ressource`);

--
-- Index pour la table `ressource`
--
ALTER TABLE `ressource`
  ADD PRIMARY KEY (`id_ressource`,`id_enseignant`),
  ADD KEY `id_enseignant` (`id_enseignant`),
  ADD KEY `ressource_ibfk_2` (`id_matiere`);

--
-- Index pour la table `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topics_ibfk_1` (`user_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `filiere`
--
ALTER TABLE `filiere`
  MODIFY `id_filiere` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pour la table `personne`
--
ALTER TABLE `personne`
  MODIFY `id_personne` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=240001439;

--
-- AUTO_INCREMENT pour la table `planning`
--
ALTER TABLE `planning`
  MODIFY `id_planning` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT pour la table `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `anonymat`
--
ALTER TABLE `anonymat`
  ADD CONSTRAINT `anonymat_ibfk_1` FOREIGN KEY (`id_president`) REFERENCES `president_jury` (`id_president`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `assistant`
--
ALTER TABLE `assistant`
  ADD CONSTRAINT `assistant_ibfk_1` FOREIGN KEY (`id_assistant`) REFERENCES `personne` (`id_personne`);

--
-- Contraintes pour la table `directeur_etude`
--
ALTER TABLE `directeur_etude`
  ADD CONSTRAINT `directeur_etude_ibfk_1` FOREIGN KEY (`id_directeur`) REFERENCES `personne` (`id_personne`);

--
-- Contraintes pour la table `enseignant`
--
ALTER TABLE `enseignant`
  ADD CONSTRAINT `enseignant_ibfk_1` FOREIGN KEY (`id_enseignant`) REFERENCES `personne` (`id_personne`) ON DELETE CASCADE;

--
-- Contraintes pour la table `enseigner`
--
ALTER TABLE `enseigner`
  ADD CONSTRAINT `enseigner_ibfk_1` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignant` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `enseigner_ibfk_2` FOREIGN KEY (`id_matiere`) REFERENCES `matiere` (`id_matiere`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `enseigner_ibfk_3` FOREIGN KEY (`id_filiere`) REFERENCES `filiere` (`id_filiere`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `etudiant`
--
ALTER TABLE `etudiant`
  ADD CONSTRAINT `etudiant_ibfk_1` FOREIGN KEY (`id_etudiant`) REFERENCES `personne` (`id_personne`),
  ADD CONSTRAINT `etudiant_ibfk_2` FOREIGN KEY (`id_filiere`) REFERENCES `filiere` (`id_filiere`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `evaluer`
--
ALTER TABLE `evaluer`
  ADD CONSTRAINT `evaluer_ibfk_1` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiant` (`id_etudiant`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `evaluer_ibfk_2` FOREIGN KEY (`id_matiere`) REFERENCES `matiere` (`id_matiere`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `evaluer_ibfk_3` FOREIGN KEY (`id_anonymat`) REFERENCES `anonymat` (`id_anonymat`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `filiere`
--
ALTER TABLE `filiere`
  ADD CONSTRAINT `filiere_ibfk_1` FOREIGN KEY (`responsable_id`) REFERENCES `personne` (`id_personne`);

--
-- Contraintes pour la table `matiere`
--
ALTER TABLE `matiere`
  ADD CONSTRAINT `matiere_ibfk_1` FOREIGN KEY (`id_filiere`) REFERENCES `filiere` (`id_filiere`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `personne` (`id_personne`) ON DELETE CASCADE;

--
-- Contraintes pour la table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_personne_password_resets` FOREIGN KEY (`id_personne`) REFERENCES `personne` (`id_personne`) ON DELETE CASCADE;

--
-- Contraintes pour la table `president_jury`
--
ALTER TABLE `president_jury`
  ADD CONSTRAINT `fk_president_jury_personne` FOREIGN KEY (`id_president`) REFERENCES `personne` (`id_personne`),
  ADD CONSTRAINT `president_jury_ibfk_1` FOREIGN KEY (`id_president`) REFERENCES `personne` (`id_personne`);

--
-- Contraintes pour la table `recevoir_ressources`
--
ALTER TABLE `recevoir_ressources`
  ADD CONSTRAINT `recevoir_ressources_ibfk_1` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiant` (`id_etudiant`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `recevoir_ressources_ibfk_2` FOREIGN KEY (`id_ressource`) REFERENCES `ressource` (`id_ressource`) ON DELETE CASCADE;

--
-- Contraintes pour la table `ressource`
--
ALTER TABLE `ressource`
  ADD CONSTRAINT `ressource_ibfk_1` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignant` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ressource_ibfk_2` FOREIGN KEY (`id_matiere`) REFERENCES `matiere` (`id_matiere`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `topics`
--
ALTER TABLE `topics`
  ADD CONSTRAINT `topics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `personne` (`id_personne`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

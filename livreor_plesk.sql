-- phpMyAdmin SQL Dump (Plesk-friendly)
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- This dump does NOT create or select a database. Import it into the database you already selected/created in Plesk.
--
-- Server version: 8.x / MariaDB 10.x compatible
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Safely drop existing tables (child first), without needing DB-level privileges
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `commentaires`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `utilisateurs`;
SET FOREIGN_KEY_CHECKS=1;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Table: utilisateurs
-- --------------------------------------------------------

CREATE TABLE `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `login` varchar(191) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `date_inscription` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_derniere_connexion` timestamp NULL DEFAULT NULL,
  `statut` enum('actif','inactif','suspendu') DEFAULT 'actif',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_login` (`login`),
  KEY `idx_date_inscription` (`date_inscription`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data for utilisateurs (password hash = 'password')
INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `login`, `mot_de_passe`, `date_inscription`, `statut`) VALUES
(1, 'Admin', 'Système', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', CURRENT_TIMESTAMP, 'actif'),
(2, 'Dupont', 'Jean', 'jean.dupont', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', CURRENT_TIMESTAMP, 'actif'),
(3, 'Martin', 'Marie', 'marie.martin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', CURRENT_TIMESTAMP, 'actif'),
(4, 'Durand', 'Pierre', 'pierre.durand', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', CURRENT_TIMESTAMP, 'actif');

-- --------------------------------------------------------
-- Table: commentaires
-- --------------------------------------------------------

CREATE TABLE `commentaires` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `commentaire` text NOT NULL,
  `id_utilisateur` int NOT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NULL DEFAULT NULL,
  `statut` enum('public','prive','modere') DEFAULT 'public',
  `note_appreciation` tinyint(1) DEFAULT NULL CHECK ((`note_appreciation` between 1 and 5)),
  PRIMARY KEY (`id`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `idx_date_creation` (`date_creation`),
  KEY `idx_statut` (`statut`),
  CONSTRAINT `commentaires_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data for commentaires
INSERT INTO `commentaires` (`id`, `titre`, `commentaire`, `id_utilisateur`, `date_creation`, `note_appreciation`) VALUES
(1, 'Excellente expérience !', 'J''ai vraiment apprécié ma visite. L''accueil était chaleureux et le service impeccable. Je recommande vivement !', 2, CURRENT_TIMESTAMP, 5),
(2, 'Très satisfaite', 'Un grand merci à toute l''équipe pour ce moment agréable. Tout était parfait, de l''accueil au service.', 3, CURRENT_TIMESTAMP, 4),
(3, 'Bonne découverte', 'Première visite et très bonne impression. J''ai hâte de revenir pour découvrir d''autres aspects.', 4, CURRENT_TIMESTAMP, 4),
(4, 'Test du livre d''or', 'Voici l''inauguration du livre d''or rénové avec une nouvelle interface moderne !', 1, CURRENT_TIMESTAMP, 5),
(5, 'Interface moderne', 'J''adore la nouvelle interface, elle est beaucoup plus intuitive et agréable à utiliser.', 2, CURRENT_TIMESTAMP, 5);

-- --------------------------------------------------------
-- Table: categories (optional)
-- --------------------------------------------------------

CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text,
  `couleur` varchar(7) DEFAULT '#3498db',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`),
  KEY `idx_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data for categories
INSERT INTO `categories` (`id`, `nom`, `description`, `couleur`) VALUES
(1, 'Témoignage', 'Messages de témoignages et retours d''expérience', '#2ecc71'),
(2, 'Remerciement', 'Messages de remerciements et gratitude', '#f39c12'),
(3, 'Suggestion', 'Suggestions d''amélioration et idées', '#9b59b6'),
(4, 'Général', 'Messages généraux sans catégorie spécifique', '#3498db'),
(5, 'Événement', 'Messages liés à des événements particuliers', '#e74c3c');

-- --------------------------------------------------------
-- Table: sessions (optional, not used by PHP sessions in this app)
-- --------------------------------------------------------

CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `utilisateur_id` int NOT NULL,
  `data` text,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_expiration` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_utilisateur` (`utilisateur_id`),
  KEY `idx_expiration` (`date_expiration`),
  CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto-increments
ALTER TABLE `utilisateurs` AUTO_INCREMENT=5;
ALTER TABLE `commentaires` AUTO_INCREMENT=6;
ALTER TABLE `categories` AUTO_INCREMENT=6;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

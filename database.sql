-- ============================================================
-- Leitor Social - Banco de Dados
-- Versão: 1.0
-- Descrição: Estrutura completa para o projeto Leitor Social
-- ============================================================

-- ============================================================
-- Configuração inicial
-- ============================================================
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- Criação do banco de dados (se não existir)
-- ============================================================
CREATE DATABASE IF NOT EXISTS `leitor_social` 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `leitor_social`;

-- ============================================================
-- Tabela: users (Usuários)
-- ============================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(255) NOT NULL UNIQUE,
    `password_hash` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir usuário de teste (senha: 123456)
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`) VALUES
(1, 'Usuário Teste', 'teste@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ============================================================
-- Tabela: books (Livros)
-- ============================================================
DROP TABLE IF EXISTS `books`;
CREATE TABLE `books` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `google_book_id` varchar(255) NOT NULL UNIQUE,
    `title` varchar(500) NOT NULL,
    `authors` text DEFAULT NULL,
    `description` text DEFAULT NULL,
    `thumbnail` varchar(500) DEFAULT NULL,
    `published_date` varchar(50) DEFAULT NULL,
    `tags` varchar(255) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `google_book_id` (`google_book_id`),
    KEY `idx_tags` (`tags`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir livros de exemplo
INSERT INTO `books` (`id`, `google_book_id`, `title`, `authors`, `description`, `thumbnail`, `published_date`, `tags`) VALUES
(1, 'iO5pApw2JycC', 'The Ivory Tower and Harry Potter', 'Lana A. Whited', 'Now available in paper, The Ivory Tower and Harry Potter is the first book-length analysis of J. K. Rowling\'s work from a broad range of perspectives within literature, folklore, psychology, sociology, and popular culture.', 'http://books.google.com/books/content?id=iO5pApw2JycC&printsec=frontcover&img=1&zoom=1&edge=curl&source=gbs_api', '2004', 'Fantasia, Literatura'),
(2, 'NFTNEAAAQBAJ', 'Robôs e Inteligência Artificial Nas Telas', 'Lívia de Pádua Nóbrega', 'O livro Robôs e Inteligência Artificial nas telas: Tecnociência, Imaginário e Política na ficção nasce de diversos incômodos.', 'http://books.google.com/books/content?id=NFTNEAAAQBAJ&printsec=frontcover&img=1&zoom=1&edge=curl&source=gbs_api', '2023-07-26', 'Ficção Científica, Tecnologia');

-- ============================================================
-- Tabela: user_books (Relação usuário-livro)
-- ============================================================
DROP TABLE IF EXISTS `user_books`;
CREATE TABLE `user_books` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `book_id` int(11) NOT NULL,
    `status` enum('want_to_read','reading','finished') DEFAULT 'want_to_read',
    `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `review` text DEFAULT NULL,
    `started_at` date DEFAULT NULL,
    `finished_at` date DEFAULT NULL,
    `current_page` int(11) DEFAULT 0,
    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_book` (`user_id`,`book_id`),
    KEY `book_id` (`book_id`),
    CONSTRAINT `user_books_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `user_books_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir relação de exemplo (usuário 1 com livro 1, status "Lendo")
INSERT INTO `user_books` (`user_id`, `book_id`, `status`, `rating`, `review`, `started_at`, `finished_at`) VALUES
(1, 1, 'reading', 5, 'Excelente livro! Recomendo a todos.', '2025-01-01', NULL);

-- ============================================================
-- Tabela: groups (Clubes de leitura)
-- ============================================================
DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `is_private` tinyint(1) DEFAULT 0,
    `join_code` varchar(20) DEFAULT NULL UNIQUE,
    `cover_image` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `join_code` (`join_code`),
    KEY `created_by` (`created_by`),
    CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir clube de exemplo
INSERT INTO `groups` (`id`, `name`, `description`, `created_by`, `join_code`) VALUES
(1, 'Clube de Ficção Científica', 'Grupo para discutir obras de ficção científica e fantasia.', 1, 'ABC12345');

-- ============================================================
-- Tabela: group_members (Membros dos clubes)
-- ============================================================
DROP TABLE IF EXISTS `group_members`;
CREATE TABLE `group_members` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `group_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `role` enum('admin','moderator','member') DEFAULT 'member',
    `joined_at` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_group_user` (`group_id`,`user_id`),
    KEY `idx_group_members_user` (`user_id`),
    KEY `idx_group_members_group` (`group_id`),
    CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir membro de exemplo (admin)
INSERT INTO `group_members` (`group_id`, `user_id`, `role`) VALUES
(1, 1, 'admin');

-- ============================================================
-- Tabela: group_books (Livros do clube)
-- ============================================================
DROP TABLE IF EXISTS `group_books`;
CREATE TABLE `group_books` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `group_id` int(11) NOT NULL,
    `book_id` int(11) NOT NULL,
    `added_by` int(11) NOT NULL,
    `added_at` timestamp NULL DEFAULT current_timestamp(),
    `status` enum('pending','reading','finished') DEFAULT 'pending',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_group_book` (`group_id`,`book_id`),
    KEY `book_id` (`book_id`),
    KEY `added_by` (`added_by`),
    KEY `idx_group_books_group` (`group_id`),
    CONSTRAINT `group_books_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `group_books_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
    CONSTRAINT `group_books_ibfk_3` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir livro no clube de exemplo
INSERT INTO `group_books` (`group_id`, `book_id`, `added_by`, `status`) VALUES
(1, 2, 1, 'pending');

-- ============================================================
-- Tabela: group_discussions (Discussões do clube)
-- ============================================================
DROP TABLE IF EXISTS `group_discussions`;
CREATE TABLE `group_discussions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `group_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `book_id` int(11) DEFAULT NULL,
    `message` text NOT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `book_id` (`book_id`),
    KEY `idx_group_discussions_group` (`group_id`),
    CONSTRAINT `group_discussions_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `group_discussions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `group_discussions_ibfk_3` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir discussão de exemplo
INSERT INTO `group_discussions` (`group_id`, `user_id`, `message`) VALUES
(1, 1, 'Alguém já leu este livro? Estou adorando!');

-- ============================================================
-- Tabela: recommendations_cache (Cache de recomendações)
-- ============================================================
DROP TABLE IF EXISTS `recommendations_cache`;
CREATE TABLE `recommendations_cache` (
    `user_id` int(11) NOT NULL,
    `recommendations` json NOT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Fim do script
-- ============================================================
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

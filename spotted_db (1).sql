-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Tempo de geração: 06/05/2026 às 21:51
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `spotted_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `comentarios`
--

CREATE TABLE `comentarios` (
  `id` int(11) NOT NULL,
  `id_mensagem` int(11) NOT NULL,
  `comentario` text NOT NULL,
  `data_comentario` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_nome` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `pref_vibe_comentario` varchar(20) DEFAULT NULL,
  `pref_cor_borda` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `comentarios`
--

INSERT INTO `comentarios` (`id`, `id_mensagem`, `comentario`, `data_comentario`, `usuario_nome`, `parent_id`, `pref_vibe_comentario`, `pref_cor_borda`) VALUES
(139, 65, ' oi', '2026-04-26 04:12:19', 'A Presença  - Official aaaaaaa', NULL, 'vibe-neon', '#70cde4'),
(142, 65, '@APresença-Officialaaaaaaa  teste', '2026-04-26 04:15:48', 'A Presença  - Official aaaaaaa', 140, 'vibe-light', '#70cde4'),
(143, 65, ' teste', '2026-04-26 04:16:12', 'A Presença  - Official aaaaaaa', NULL, 'vibe-glass', '#e576c7'),
(144, 65, ' teste', '2026-04-26 04:16:42', 'A Presença  - Official aaaaaaa', NULL, 'vibe-light', '#3ef44a'),
(145, 65, '@APresença-Officialaaaaaaa  ', '2026-04-26 04:19:07', 'A Presença  - Official aaaaaaa', 140, 'vibe-glass', '#70cde4'),
(146, 33, ' Testando as cores no celular pra ver como é que fica a bagaça , aparentemente tá dando certo ( lá ele ) e tudo tá minutinho. Agora vou enviar !', '2026-04-26 16:55:32', 'A Presença  - Official aaaaaaa', NULL, 'vibe-glass', '#a80707'),
(147, 33, ' Testando solar', '2026-04-26 16:56:07', 'A Presença  - Official aaaaaaa', NULL, 'vibe-light', '#ff0000'),
(148, 33, ' Testando engengrau', '2026-04-26 16:56:53', 'A Presença  - Official aaaaaaa', NULL, 'vibe-dark', '#ff0000'),
(149, 33, ' Testando Neon', '2026-04-26 16:57:12', 'A Presença  - Official aaaaaaa', NULL, 'vibe-neon', '#00ff00'),
(150, 33, '@APresença-Officialaaaaaaa  respondendo no celular ', '2026-04-26 16:57:34', 'A Presença  - Official aaaaaaa', 149, 'vibe-glass', '#70cde4'),
(151, 69, ' Oie', '2026-04-26 17:22:45', 'A Presença  - Official aaaaaaa', NULL, 'vibe-light', '#ffffff'),
(152, 64, ' @apresenca_fev responde euuuu', '2026-04-26 18:04:11', 'test123', NULL, 'vibe-neon', '#ea5834'),
(153, 64, '@test123  Que foooii', '2026-04-26 18:05:30', 'A Presença  - Official aaaaaaa', 152, 'vibe-glass', '#70cde4'),
(154, 64, 'tá respondido', '2026-04-26 18:06:03', 'test123', 152, 'vibe-glass', '#70cde4'),
(155, 64, ' apresenca_fev mano ', '2026-04-26 18:07:54', 'test123', NULL, 'vibe-glass', '#70cde4'),
(156, 64, ' @apresenca_fev oi manooo', '2026-04-26 18:08:06', 'test123', NULL, 'vibe-glass', '#70cde4'),
(163, 87, '@ teste123', '2026-04-27 00:11:58', 'A Presença  - Official aaaaaaa', NULL, 'vibe-glass', '#70cde4'),
(164, 87, ' @test123', '2026-04-27 00:13:57', 'A Presença  - Official aaaaaaa', NULL, 'vibe-glass', '#70cde4'),
(165, 87, '@APresença-Officialaaaaaaa oi', '2026-04-27 12:57:41', 'aaaaaaaaaaaaaaaaaaaaaaaaa', 163, 'vibe-glass', '#70cde4'),
(166, 87, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', '2026-04-27 13:31:41', 'aaaaaaaaaaaaaaaaaaaaaaaaa', NULL, 'vibe-glass', '#70cde4'),
(167, 87, '@APresença-Officialaaaaaaa oi', '2026-04-28 12:46:02', 'test123', 163, 'vibe-glass', '#de3902'),
(168, 86, '@apresenca_fev', '2026-04-28 12:48:00', 'test123', NULL, 'vibe-dark', '#f93939'),
(169, 86, '@test123 que foi', '2026-04-28 12:51:49', 'aaaaaaaaaaaaaaaaaaaaaaaaa', 168, 'vibe-dark', '#f22626'),
(170, 88, '@apresença_fev responde desgraça', '2026-04-28 14:02:08', 'test123', NULL, 'vibe-glass', '#80f060'),
(171, 88, '@test123 que foooi', '2026-04-28 15:04:13', 'aaaaaaaaaaaaaaaaaaaaaaaaa', 170, 'vibe-neon', '#f22626'),
(172, 88, '@test123 fala estrupicio', '2026-04-28 15:05:06', 'aaaaaaaaaaaaaaaaaaaaaaaaa', 170, 'vibe-neon', '#f22626'),
(173, 85, '@presenca_fev me respondee', '2026-04-28 16:05:28', 'test123', NULL, 'vibe-glass', '#51ccf5'),
(174, 88, '@test123 o que você quer inferno kkkkk', '2026-04-28 16:46:42', 'aaaaaaaaaaaaaaaaaaaaaaaaa', 170, 'vibe-neon', '#f22626'),
(175, 88, 'Fala @apresenca_fev', '2026-04-28 17:43:54', 'test123', 170, 'vibe-glass', '#51ccf5'),
(176, 89, '@test', '2026-04-29 03:46:16', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#f22626'),
(177, 64, '@test123', '2026-04-29 12:47:39', 'Leonardo Florindo Alves R', 152, 'vibe-neon', '#f22626'),
(178, 89, '@LeonardoFlorindoAlvesR oi', '2026-04-29 13:52:13', 'test123', 176, 'vibe-neon', '#1288af'),
(179, 89, '@LeonardoFlorindoAlvesR oi', '2026-04-29 13:52:18', 'test123', 176, 'vibe-neon', '#1288af'),
(180, 89, 'oie', '2026-04-29 14:33:15', 'test123', NULL, 'vibe-glass', '#1288af'),
(181, 89, 'oie', '2026-04-29 14:33:18', 'test123', NULL, 'vibe-glass', '#1288af'),
(182, 89, 'oiii', '2026-04-29 14:34:16', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#f22626'),
(183, 89, 'oiii', '2026-04-29 14:35:30', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#f22626'),
(184, 89, 'oi', '2026-04-29 14:40:43', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#f22626'),
(185, 89, 'teste de 302', '2026-04-29 14:48:57', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#f22626'),
(186, 89, 'teste de 302', '2026-04-29 14:49:36', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#f22626'),
(187, 89, 'oi', '2026-04-29 15:01:15', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#f22626'),
(188, 89, 'TESTE AJAX FUNFOU AEEEEE P*&#*$$', '2026-04-29 15:01:39', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#f22626'),
(189, 90, 'OI AJAX TESTE ANONIMO', '2026-04-29 15:04:26', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#f22626'),
(190, 90, 'OIÊ', '2026-04-29 15:06:01', 'test123', NULL, 'vibe-glass', '#1288af'),
(191, 90, 'OIEEE', '2026-04-29 15:06:30', 'test123', NULL, 'vibe-glass', '#1288af'),
(192, 90, 'oi', '2026-04-29 15:27:07', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#f22626'),
(193, 90, 'oio', '2026-04-29 15:27:08', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#f22626'),
(194, 90, 'oioioioi', '2026-04-29 15:27:10', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#f22626'),
(195, 90, 'ooooooooooooooo', '2026-04-29 15:27:12', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#f22626'),
(196, 90, 'oooooooooooooooooo', '2026-04-29 15:27:14', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#f22626'),
(197, 90, '@LeonardoFlorindoAlvesR respondendo eu mesmo jkjjjj', '2026-04-29 15:27:23', 'Leonardo Florindo Alves R', 189, 'vibe-neon', '#f22626'),
(198, 90, '@test123 i', '2026-04-29 15:27:55', 'Leonardo Florindo Alves R', 191, 'vibe-neon', '#f22626'),
(199, 90, '@test123 oi', '2026-04-29 15:28:07', 'Leonardo Florindo Alves R', 190, 'vibe-neon', '#f22626'),
(200, 90, '@test123  que foi estrupcio', '2026-04-29 15:29:40', 'Leonardo Florindo Alves R', 190, 'vibe-neon', '#f22626'),
(201, 88, 'oioiii', '2026-04-29 15:33:39', 'Leonardo Florindo Alves R', NULL, 'vibe-dark', '#f22626'),
(202, 90, 'oi', '2026-04-29 15:33:58', 'test123', NULL, 'vibe-glass', '#1288af'),
(203, 90, 'oioi', '2026-04-29 15:34:25', 'test123', NULL, 'vibe-glass', '#1288af'),
(204, 88, '@LeonardoFlorindoAlvesR oi', '2026-04-29 15:35:46', 'test123', 201, 'vibe-glass', '#1288af'),
(205, 88, '@LeonardoFlorindoAlvesR oi', '2026-04-29 15:36:20', 'test123', 201, 'vibe-glass', '#1288af'),
(206, 88, '@LeonardoFlorindoAlvesR oi', '2026-04-29 15:37:59', 'test123', 201, 'vibe-glass', '#1288af'),
(207, 88, '@test123 oi', '2026-04-29 15:38:46', 'test123', 170, 'vibe-glass', '#1288af'),
(208, 70, 'oi', '2026-04-29 16:37:39', NULL, NULL, 'vibe-glass', '#70cde4'),
(209, 90, '@test123 oi', '2026-04-29 22:03:43', 'Leonardo Florindo Alves R', 190, 'vibe-neon', '#f22626'),
(210, 90, '@LeonardoFlorindoAlvesR oi', '2026-04-29 22:06:57', 'test123', 192, 'vibe-glass', '#1288af'),
(211, 90, '@test123 oiê', '2026-04-30 00:29:55', 'Leonardo Florindo Alves R', 202, 'vibe-neon', '#f22626'),
(212, 90, '@test123 oi', '2026-04-30 00:30:30', 'Leonardo Florindo Alves R', 202, 'vibe-neon', '#f22626'),
(213, 90, '@test123 oi', '2026-05-01 14:25:29', 'Leonardo Florindo Alves R', 203, 'vibe-light', '#ee2f2f'),
(214, 90, 'oii', '2026-05-01 20:17:57', 'Leonardo Florindo Alves R', NULL, 'vibe-light', '#ee2f2f'),
(215, 68, 'oii', '2026-05-02 14:18:14', 'Leonardo Florindo Alves R', NULL, 'vibe-light', '#ee2f2f'),
(216, 90, '@LeonardoFlorindoAlvesR oi', '2026-05-02 14:19:41', 'test123', 189, 'vibe-dark', '#af7612'),
(217, 90, '@test123 oii', '2026-05-02 14:20:49', 'Leonardo Florindo Alves R', 191, 'vibe-glass', '#ee2f2f'),
(218, 89, 'oi', '2026-05-02 23:51:55', 'Leonardo Florindo Alves R', NULL, 'vibe-glass', '#ee2f2f'),
(219, 89, '@test123 oi\r\n', '2026-05-02 23:52:11', 'Leonardo Florindo Alves R', 181, 'vibe-glass', '#ee2f2f'),
(220, 89, 'aaaa', '2026-05-03 01:13:05', 'test123', NULL, 'vibe-light', '#e81717'),
(221, 89, '@LeonardoFlorindoAlvesR a', '2026-05-03 01:13:35', 'test123', 176, 'vibe-dark', '#af7612'),
(222, 91, 'oiiii', '2026-05-03 02:49:22', 'Leonardo Florindo Alves R', NULL, 'vibe-dark', '#ee2f2f'),
(223, 91, '@test', '2026-05-03 02:49:30', 'Leonardo Florindo Alves R', NULL, 'vibe-dark', '#ee2f2f'),
(224, 92, 'oi', '2026-05-04 02:52:21', 'Leonardo Florindo Alves R', NULL, 'vibe-dark', '#e62828'),
(225, 94, 'oiii', '2026-05-05 22:07:38', 'Leonardo Florindo Alves R', NULL, 'vibe-light', '#ea3939'),
(226, 94, 'oi', '2026-05-05 22:07:44', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#ea3939'),
(227, 94, 'oi', '2026-05-05 22:07:55', 'Leonardo Florindo Alves R', NULL, 'vibe-glass', '#ea3939'),
(228, 94, 'OI', '2026-05-06 06:15:05', 'Leonardo Florindo Alves R', 225, 'vibe-light', '#ea3939'),
(229, 94, 'OI', '2026-05-06 06:15:24', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#ea3939'),
(230, 94, 'OI', '2026-05-06 06:15:39', 'Leonardo Florindo Alves R', NULL, 'vibe-glass', '#ea3939'),
(231, 94, 'oi', '2026-05-06 15:53:29', 'Leonardo Florindo Alves R', NULL, 'vibe-dark', '#e01515'),
(232, 90, 'oi', '2026-05-06 16:15:24', 'Leonardo Florindo Alves R', 189, 'vibe-dark', '#ea3939'),
(233, 95, 'Oi', '2026-05-06 17:23:41', 'Leonardo Florindo Alves R', NULL, 'vibe-light', '#ea3939'),
(234, 95, 'Oi ', '2026-05-06 17:24:54', 'Leonardo Florindo Alves R', NULL, 'vibe-dark', '#ff0000'),
(235, 89, 'oi', '2026-05-06 18:43:52', 'Leonardo Florindo Alves R', NULL, 'vibe-dark', '#ea3939'),
(236, 89, 'iu', '2026-05-06 18:43:58', 'Leonardo Florindo Alves R', NULL, 'vibe-light', '#ea3939'),
(237, 89, 'oi', '2026-05-06 18:44:05', 'Leonardo Florindo Alves R', NULL, 'vibe-neon', '#ea3939'),
(238, 89, 'oi', '2026-05-06 18:44:27', 'Leonardo Florindo Alves R', NULL, 'vibe-glass', '#ea3939'),
(239, 90, 'oi', '2026-05-06 19:01:02', 'Leonardo Florindo Alves R', NULL, 'vibe-light', '#d61f1f');

-- --------------------------------------------------------

--
-- Estrutura para tabela `curtidas`
--

CREATE TABLE `curtidas` (
  `id` int(11) NOT NULL,
  `mensagem_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_reacao` varchar(50) NOT NULL,
  `data_reacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `curtidas`
--

INSERT INTO `curtidas` (`id`, `mensagem_id`, `usuario_id`, `tipo_reacao`, `data_reacao`) VALUES
(690, 31, 16, 'perplecto', '2026-05-05 19:10:37'),
(692, 88, 16, 'amei', '2026-05-05 19:39:15'),
(730, 88, 1, 'amei', '2026-05-06 17:45:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens`
--

CREATE TABLE `mensagens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `mensagem` text NOT NULL,
  `categoria` varchar(50) DEFAULT 'anonimo',
  `subcategoria` varchar(20) DEFAULT NULL,
  `data_post` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `mensagens`
--

INSERT INTO `mensagens` (`id`, `usuario_id`, `mensagem`, `categoria`, `subcategoria`, `data_post`, `status`) VALUES
(1, 1, 'Alguém sabe quem era a menina de moletom azul hoje no bloco 4? Ela tinha um cabelo meio loiro ondulado', 'elogio', NULL, '2026-04-01 04:54:02', 'ativo'),
(25, 1, 'Alguém vai pra VG as 15h? queria uma carona, racho a gasosa', 'caronas', NULL, '2026-04-06 10:48:48', 'ativo'),
(26, 1, 'quando tem monitoria de calculo 1?', 'academico', NULL, '2026-04-07 07:37:29', 'ativo'),
(29, 1, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'anonimo', NULL, '2026-04-08 06:07:50', 'ativo'),
(31, 16, '@apresenca_fev ', 'comunidade', NULL, '2026-04-08 07:23:13', 'ativo'),
(32, 1, 'bora futzinho? @apresenca_fev', 'esportes', NULL, '2026-04-09 02:48:41', 'ativo'),
(33, 1, 'Bora um Fifinha na casa do @test?', 'games', NULL, '2026-04-09 04:48:07', 'ativo'),
(61, 16, '@apresenca_fev tá aí?', 'comunidade', '', '2026-04-14 18:22:31', 'ativo'),
(62, 1, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'elogio', '', '2026-04-21 07:46:49', 'ativo'),
(63, 1, 'teste', 'elogio', '', '2026-04-22 08:50:47', 'ativo'),
(64, 1, 'perdi teste', 'perdidos', 'perdi', '2026-04-22 08:52:17', 'ativo'),
(65, 1, 'oi', 'perdidos', 'achei', '2026-04-22 10:40:49', 'ativo'),
(66, 1, 'teste categoria esportes', 'esportes', '', '2026-04-23 15:52:39', 'ativo'),
(67, 1, 'teste categoria academica', 'academico', '', '2026-04-23 15:55:49', 'ativo'),
(68, 1, 'teste categoria caronas', 'caronas', '', '2026-04-23 15:56:42', 'ativo'),
(69, 1, 'teste categoria ACABAAAAAAAAAAA', 'acaba-pelo-amor-de-deus', '', '2026-04-23 15:57:15', 'ativo'),
(70, 1, 'Achei a dignidade que me sobrou ', 'perdidos', 'achei', '2026-04-26 17:36:01', 'ativo'),
(71, 1, 'aaaaa', 'comunidade', '', '2026-04-26 18:09:00', 'ativo'),
(72, 1, 'aaaaaaa', 'elogio', '', '2026-04-26 18:09:06', 'ativo'),
(73, 1, 'aaaa', 'comunidade', '', '2026-04-26 18:09:13', 'ativo'),
(74, 1, 'ssssssssss', 'anonimo', '', '2026-04-26 18:09:20', 'ativo'),
(75, 1, 'aaaaaaaaaaaaaaaaa', 'anonimo', '', '2026-04-26 18:09:26', 'ativo'),
(76, 1, 'aaaaaaaaaaaaaaaaaaaaaaaa', 'anonimo', '', '2026-04-26 18:09:29', 'ativo'),
(77, 1, 'aaaaaaaaaaaaaaaaaaaaaa', 'ranco', '', '2026-04-26 18:09:35', 'ativo'),
(78, 1, 'aaaaaaa', 'acaba-pelo-amor-de-deus', '', '2026-04-26 18:09:40', 'ativo'),
(79, 1, 'ssssssssssssa', 'games', '', '2026-04-26 18:09:48', 'ativo'),
(80, 1, 'sssssssssssaaaaaas', 'caronas', '', '2026-04-26 18:09:58', 'ativo'),
(81, 1, 'sssssssssss', 'acaba-pelo-amor-de-deus', '', '2026-04-26 18:10:04', 'ativo'),
(82, 1, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'comunidade', '', '2026-04-26 18:10:10', 'ativo'),
(83, 1, 'ssssssssssssssssssssssss', 'academico', '', '2026-04-26 18:10:16', 'ativo'),
(84, 1, 'ddddddddddddddddddddddddd', 'comunidade', '', '2026-04-26 18:10:22', 'ativo'),
(85, 1, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'acaba-pelo-amor-de-deus', '', '2026-04-26 18:10:29', 'ativo'),
(86, 1, 'ssssssssssssssssssssss', 'academico', '', '2026-04-26 18:10:38', 'ativo'),
(87, 1, 'ddddddddddddddddddddddddd', 'elogio', '', '2026-04-26 18:10:44', 'ativo'),
(88, 16, '@apresenca_fev', 'academico', '', '2026-04-28 12:49:10', 'ativo'),
(89, 1, '@test123', 'comunidade', '', '2026-04-28 16:06:03', 'ativo'),
(90, 1, 'teste AJAX  ( não é o da liga da justiça)', 'anonimo', '', '2026-04-29 15:04:03', 'ativo'),
(91, 1, 'oi', 'anonimo', '', '2026-05-03 00:40:16', 'ativo'),
(92, 1, 'Oi', 'perdidos', 'achei', '2026-05-03 02:53:58', 'ativo'),
(93, 1, 'perdi o meu nariz', 'perdidos', 'perdi', '2026-05-03 19:16:57', 'ativo'),
(94, 1, 'axchei seu nariz', 'perdidos', 'achei', '2026-05-03 19:17:24', 'ativo'),
(95, 1, 'Oi', 'anonimo', '', '2026-05-06 17:23:17', 'ativo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `mensagem` text NOT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `notificacoes`
--

INSERT INTO `notificacoes` (`id`, `usuario_id`, `post_id`, `mensagem`, `lida`, `data_criacao`) VALUES
(18, 16, 54, 'A Presença  - Official  mencionou você em um post!', 1, '2026-04-14 05:16:38'),
(19, 16, 55, 'A Presença  - Official  mencionou você em um post!', 1, '2026-04-14 05:23:11'),
(20, 1, 56, 'test mencionou você em um post!', 1, '2026-04-14 16:45:32'),
(21, 16, 56, 'A Presença  - Official  mencionou você em um comentário!', 1, '2026-04-14 16:54:11'),
(22, 1, 58, 'test mencionou você em um post!', 1, '2026-04-14 16:57:51'),
(23, 1, 59, 'test mencionou você em um post!', 1, '2026-04-14 16:58:36'),
(24, 1, 54, 'test mencionou você em um comentário!', 1, '2026-04-14 17:03:33'),
(25, 16, 60, 'A Presença  - Official  mencionou você em um post!', 1, '2026-04-14 17:19:58'),
(26, 1, 61, 'test mencionou você em um post!', 1, '2026-04-14 18:22:31'),
(27, 1, 60, 'test123 mencionou você em um comentário!', 1, '2026-04-20 16:09:16'),
(28, 1, 60, 'test123 mencionou você em um comentário!', 1, '2026-04-20 16:12:05'),
(29, 1, 60, 'test123 mencionou você em um comentário!', 1, '2026-04-20 16:12:36'),
(30, 1, 60, 'test123 mencionou você em um comentário!', 1, '2026-04-20 16:33:35'),
(31, 1, 33, 'test123 mencionou você em um comentário!', 1, '2026-04-20 17:25:06'),
(32, 16, 61, '@A Presença  - Official aaaaaaa mencionou você em um comentário!', 1, '2026-04-20 18:38:37'),
(33, 16, 61, '@A Presença  - Official aaaaaaa mencionou você em um comentário!', 1, '2026-04-20 19:24:40'),
(34, 16, 33, '@apresenca_fev mencionou você em um comentário!', 1, '2026-04-22 08:49:38'),
(35, 1, 64, '@test123 mencionou você em um comentário!', 1, '2026-04-26 18:04:11'),
(36, 16, 64, '@apresenca_fev mencionou você em um comentário!', 1, '2026-04-26 18:05:30'),
(37, 1, 64, '@test123 mencionou você em um comentário!', 1, '2026-04-26 18:08:06'),
(38, 16, 87, '@apresenca_fev mencionou você em um comentário!', 1, '2026-04-27 00:13:57'),
(39, 1, 86, '@test123 mencionou você em um comentário!', 1, '2026-04-28 12:48:00'),
(40, 1, 88, 'test123 mencionou você em um post!', 1, '2026-04-28 12:49:10'),
(41, 16, 86, '@apresenca_fev mencionou você em um comentário!', 1, '2026-04-28 12:51:49'),
(42, 16, 88, '@apresenca_fev mencionou você em um comentário!', 1, '2026-04-28 15:04:13'),
(43, 16, 88, '@apresenca_fev mencionou você em um comentário!', 1, '2026-04-28 15:05:06'),
(44, 16, 89, 'aaaaaaaaaaaaaaaaaaaaaaaaa mencionou você em um post!', 1, '2026-04-28 16:06:03'),
(45, 16, 88, '@apresenca_fev mencionou você em um comentário!', 1, '2026-04-28 16:46:42'),
(46, 1, 88, '@test123 mencionou você em um comentário!', 1, '2026-04-28 17:43:54'),
(47, 16, 64, '@apresenca_fev mencionou você em um comentário!', 1, '2026-04-29 12:47:39'),
(48, 1, 89, '@test123 comentou no seu post!', 1, '2026-04-29 14:33:15'),
(49, 1, 89, '@test123 comentou no seu post!', 1, '2026-04-29 14:33:18'),
(50, 1, 90, '@test123 comentou no seu post!', 1, '2026-04-29 15:06:01'),
(51, 1, 90, '@test123 comentou no seu post!', 1, '2026-04-29 15:06:30'),
(52, 16, 90, '@apresenca_fev mencionou você em um comentário!', 1, '2026-04-29 15:27:55'),
(53, 16, 90, '@apresenca_fev mencionou você em um comentário!', 1, '2026-04-29 15:28:07'),
(54, 16, 90, '@apresenca_fev mencionou você em um comentário!', 1, '2026-04-29 15:29:40'),
(55, 16, 88, '@Leonardo Florindo Alves R comentou no seu post!', 1, '2026-04-29 15:33:39'),
(56, 1, 90, '@test123 comentou no seu post!', 1, '2026-04-29 15:33:58'),
(57, 1, 90, '@test123 comentou no seu post!', 1, '2026-04-29 15:34:25'),
(58, 1, 70, '@Visitante comentou no seu post!', 1, '2026-04-29 16:37:39'),
(59, 16, 90, '@Leonardo Florindo Alves R mencionou você em um comentário!', 1, '2026-04-29 22:03:43'),
(60, 1, 90, '@test123 comentou no seu post!', 1, '2026-04-29 22:06:57'),
(61, 16, 90, '@Leonardo Florindo Alves R mencionou você em um comentário!', 1, '2026-04-30 00:29:55'),
(62, 16, 90, '@Leonardo Florindo Alves R mencionou você em um comentário!', 1, '2026-04-30 00:30:30'),
(63, 16, 90, '@Leonardo Florindo Alves R mencionou você em um comentário!', 1, '2026-05-01 14:25:29'),
(64, 1, 90, '@test123 comentou no seu post!', 1, '2026-05-02 14:19:41'),
(65, 16, 90, '@Leonardo Florindo Alves R mencionou você em um comentário!', 1, '2026-05-02 14:20:49'),
(66, 16, 89, '@Leonardo Florindo Alves R mencionou você em um comentário!', 1, '2026-05-02 23:52:11'),
(67, 1, 89, '@test123 comentou no seu post!', 1, '2026-05-03 01:13:05'),
(68, 1, 89, '@test123 comentou no seu post!', 1, '2026-05-03 01:13:35');

-- --------------------------------------------------------

--
-- Estrutura para tabela `seguidores`
--

CREATE TABLE `seguidores` (
  `id` int(11) NOT NULL,
  `id_seguidor` int(11) NOT NULL,
  `id_seguido` int(11) NOT NULL,
  `data_seguida` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `seguidores`
--

INSERT INTO `seguidores` (`id`, `id_seguidor`, `id_seguido`, `data_seguida`) VALUES
(46, 16, 1, '2026-04-28 12:48:47');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `foto` varchar(255) NOT NULL DEFAULT 'default.jpg',
  `bio` text DEFAULT NULL,
  `capa` varchar(255) DEFAULT 'default_capa.jpg',
  `atletica_id` varchar(50) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `token` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 0,
  `pref_vibe_comentario` varchar(20) DEFAULT 'padrao',
  `pref_cor_borda` varchar(7) DEFAULT '#70cde4',
  `pref_vibe_padrao` varchar(20) DEFAULT 'vibe-glass',
  `pref_cor_padrao` varchar(7) DEFAULT '#70cde4',
  `pref_swipe` tinyint(1) DEFAULT 0,
  `pref_bolhas` int(11) DEFAULT 1,
  `ultima_atividade` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `username`, `email`, `senha`, `foto`, `bio`, `capa`, `atletica_id`, `data_cadastro`, `token`, `ativo`, `pref_vibe_comentario`, `pref_cor_borda`, `pref_vibe_padrao`, `pref_cor_padrao`, `pref_swipe`, `pref_bolhas`, `ultima_atividade`) VALUES
(1, 'Leonardo Florindo Alves R', 'apresenca_fevaaaaaaa', '87826@unifev.edu.br', '$2y$10$zg7pxV31ML6NyIUBxZ4AX.c8mlgRoarhdnFnKqNtMZfTGvOUCG7P6', 'user_1_1777855196.jpg', 'Criador e é também idealizador do Projeto \" A Fenda \"', 'capa_1.jpg', 'direito', '2026-04-01 03:14:47', 'Leo_Idealizador', 1, 'padrao', '#70cde4', 'vibe-dark', '#ea3939', 0, 1, '2026-05-06 19:51:33'),
(16, 'test123', 'test123', '15505@unifev.edu.br', '$2y$10$bctURxSC7BgFjw9WeaTBNug53yW5muOotty8H.ZX1YaDY0jhB75Ly', 'user_16_1776620960.jpg', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'capa_16.jpg', 'eng-mecanica', '2026-04-03 03:42:31', NULL, 1, 'padrao', '#70cde4', 'vibe-dark', '#d79119', 1, 1, '2026-05-06 19:51:40');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_mensagem` (`id_mensagem`);

--
-- Índices de tabela `curtidas`
--
ALTER TABLE `curtidas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mensagem_id` (`mensagem_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `seguidores`
--
ALTER TABLE `seguidores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_seguidor` (`id_seguidor`,`id_seguido`),
  ADD KEY `fk_seguido` (`id_seguido`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=240;

--
-- AUTO_INCREMENT de tabela `curtidas`
--
ALTER TABLE `curtidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=735;

--
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT de tabela `seguidores`
--
ALTER TABLE `seguidores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`id_mensagem`) REFERENCES `mensagens` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `curtidas`
--
ALTER TABLE `curtidas`
  ADD CONSTRAINT `curtidas_ibfk_1` FOREIGN KEY (`mensagem_id`) REFERENCES `mensagens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `curtidas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `seguidores`
--
ALTER TABLE `seguidores`
  ADD CONSTRAINT `fk_seguido` FOREIGN KEY (`id_seguido`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seguidor` FOREIGN KEY (`id_seguidor`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

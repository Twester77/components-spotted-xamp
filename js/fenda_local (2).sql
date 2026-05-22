-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Tempo de geração: 22/05/2026 às 22:56
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
-- Banco de dados: `fenda_local`
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
(255, 97, 'Aoba ', '2026-05-09 15:26:11', 'Aluno', NULL, 'vibe-dark', '#000000'),
(256, 97, '@Aluno aobaaa', '2026-05-09 15:46:59', 'A Presença  - Official ', 255, 'vibe-dark', '#c02716'),
(309, 105, 'teste', '2026-05-20 23:18:02', 'test', NULL, 'vibe-neon', '#37bfe1'),
(310, 105, 'responder', '2026-05-21 10:57:02', 'test', 309, 'vibe-light', '#e3ff0f');

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
(890, 108, 1, 'amei', '2026-05-15 09:47:16'),
(1007, 105, 27, 'amei', '2026-05-19 17:43:17'),
(1055, 100, 1, 'amei', '2026-05-20 06:46:09'),
(1070, 104, 1, 'amei', '2026-05-20 07:20:05');

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens`
--

CREATE TABLE `mensagens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `mensagem` text NOT NULL,
  `imagem_url` varchar(255) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT 'anonimo',
  `subcategoria` varchar(20) DEFAULT NULL,
  `data_post` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `mensagens`
--

INSERT INTO `mensagens` (`id`, `usuario_id`, `mensagem`, `imagem_url`, `categoria`, `subcategoria`, `data_post`, `status`) VALUES
(97, 1, '+== [ INITIAL_BOOT_COMPLETED ] ==+\r\n\"Seja bem-vindo à Fenda, Habitante! \r\nVocê acaba de zarpar no ecossistema oficial da nossa feira. Sinta-se em casa para explorar os cards, reagir às fofocas e deixar sua marca e seu recado. \r\nAviso da Tripulação: Este é um ambiente de interatividade da comunidade. Ajuste sua Vibe, respeite os outros tripulantes e, acima de tudo: divirta-se! \"', NULL, 'comunidade', '', '2026-05-08 15:32:32', 'ativo'),
(99, 1, 'aaaa', NULL, 'elogio', '', '2026-05-10 07:31:59', 'ativo'),
(100, 1, 'aaa', NULL, 'caronas', '', '2026-05-10 07:32:05', 'ativo'),
(101, 1, 'aaaa', NULL, 'esportes', '', '2026-05-10 07:32:11', 'ativo'),
(102, 1, 'aaa', NULL, 'games', '', '2026-05-10 07:32:17', 'ativo'),
(103, 1, 'aa', NULL, 'academico', '', '2026-05-10 07:32:28', 'ativo'),
(104, 1, 'aa', NULL, 'acaba-pelo-amor-de-deus', '', '2026-05-10 07:32:46', 'ativo'),
(105, 1, 'aa', NULL, 'ranco', '', '2026-05-10 07:32:57', 'ativo'),
(106, 16, 'aaaa', NULL, 'anonimo', '', '2026-05-11 07:17:00', 'ativo'),
(108, 16, 'aaaaa', NULL, 'elogio', '', '2026-05-11 07:17:10', 'ativo'),
(109, 16, 'aaaaa', NULL, 'acaba-pelo-amor-de-deus', '', '2026-05-11 07:17:15', 'ativo'),
(110, 16, 'aaaaa', NULL, 'caronas', '', '2026-05-11 07:17:20', 'ativo'),
(111, 16, 'aaaaa', NULL, 'esportes', '', '2026-05-11 07:17:26', 'ativo'),
(112, 16, 'aaaaaa', NULL, 'games', '', '2026-05-11 07:17:31', 'ativo'),
(114, 16, 'aaaaa', NULL, 'academico', '', '2026-05-11 07:17:42', 'ativo'),
(115, 16, 'aaaaa', NULL, 'ranco', '', '2026-05-11 07:17:49', 'ativo'),
(117, 16, 'aaaaa', NULL, 'caronas', '', '2026-05-11 07:18:00', 'ativo'),
(120, 16, 'aaaa', NULL, 'esportes', '', '2026-05-11 07:18:19', 'ativo'),
(122, 16, 'aaaa', NULL, 'comunidade', '', '2026-05-11 07:18:29', 'ativo'),
(123, 16, 'aaaa', NULL, 'elogio', '', '2026-05-11 07:18:33', 'ativo'),
(124, 16, 'aaaaa', NULL, 'acaba-pelo-amor-de-deus', '', '2026-05-11 07:18:38', 'ativo'),
(125, 16, 'aaaa', NULL, 'ranco', '', '2026-05-11 07:18:46', 'ativo'),
(126, 16, 'aaaaa', NULL, 'caronas', '', '2026-05-11 07:18:51', 'ativo'),
(134, 27, 'perdi teste', NULL, 'perdidos', 'perdi', '2026-05-21 13:34:33', 'ativo'),
(135, 27, 'achei teste', NULL, 'perdidos', 'achei', '2026-05-21 13:35:02', 'ativo');

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
(77, 1, 97, '@Aluno comentou no seu post!', 1, '2026-05-09 15:26:11'),
(78, 26, 132, '@apresenca_fev mencionou você em um post!', 0, '2026-05-18 20:24:17'),
(79, 1, 133, '@teste1 mencionou você em um post!', 1, '2026-05-18 22:15:25'),
(80, 27, 133, '@Aluno comentou no seu post!', 1, '2026-05-18 22:16:13'),
(81, 27, 128, '@Aluno mencionou você em um comentário!', 1, '2026-05-19 04:05:49'),
(82, 1, 128, '@test comentou no seu post!', 1, '2026-05-19 04:38:45'),
(83, 1, 105, '@test comentou no seu post!', 1, '2026-05-20 23:18:02'),
(84, 1, 105, '@test comentou no seu post!', 1, '2026-05-21 10:57:02');

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
(53, 1, 26, '2026-05-18 20:24:41');

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
(1, 'Aluno', 'apresenca_fev', '87826@unifev.edu.br', '$2y$10$zg7pxV31ML6NyIUBxZ4AX.c8mlgRoarhdnFnKqNtMZfTGvOUCG7P6', 'user_1_1779386114.jpg', 'Gosto de programar Meu titulo quebra-layout alert(\'XSS de Banco\');', 'capa_1_1779386114.png', 'ads', '2026-04-01 03:14:47', 'Leo_Idealizador', 1, 'padrao', '#70cde4', 'vibe-light', '#ff2929', 1, 1, '2026-05-22 20:55:21'),
(19, 'toc', NULL, 'toc.toc.quenhe@gmail.com', '$2y$10$7sXeb/93mW1ULgWjX2WGn.f28zA2RzwLPOvWHeQomJ9u59NMWTNcy', 'default.jpg', NULL, 'default_capa.jpg', 'eng-mecanica', '2026-05-08 23:36:05', 'b03a3662378dfc24171e6a2d26350ad18c7d4996a00dc421acea1cc754d56406', 1, 'padrao', '#70cde4', 'vibe-glass', '#00aaff', 0, 1, '2026-05-08 23:36:05'),
(20, 'Aluno', NULL, 'joaofervitor05@gmail.com', '$2y$10$yu9HBY2Cka7L9gkT6qp9Lu.gQibr2.CQPMHVfbd7UZ00z11mAUk.m', 'default.jpg', NULL, 'default_capa.jpg', 'eng-comp', '2026-05-09 14:57:35', 'd74c76d2087c874759f9d41c898100de459b10884bd602b09774fb68dc3c7337', 1, 'padrao', '#70cde4', 'vibe-dark', '#000000', 0, 1, '2026-05-09 15:27:04'),
(26, 'Teste', 'teste', 'hermanoteu9507@outlook.com', '12345678', 'default.jpg', NULL, 'default_capa.jpg', 'eng-comp', '2026-05-16 13:26:13', '98fb7ec46bdf9668f8928f2283acf0ec34a52fcabaee4fd6c9ceb309d93e0e72', 1, 'padrao', '#70cde4', 'vibe-light', '#ff8c2e', 0, 1, '2026-05-16 13:26:13'),
(27, 'test', 'teste1', 'leoflorindo54@gmail.com', '$2y$10$S5yUkhKRvt6Ftfuc.8j.GOpn0cykXKyoiJoDirr9pBW/VCmXes5Mu', 'default_feminino.jpg', '', 'default_capa_feminino.jpg', 'eng-comp', '2026-05-18 21:58:18', '320e012ca56675e4acecc94d79d830b57955467bba859c54dc75f9d945a76f84', 1, 'padrao', '#70cde4', 'vibe-neon', '#ff2e2e', 1, 1, '2026-05-21 16:03:25');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=311;

--
-- AUTO_INCREMENT de tabela `curtidas`
--
ALTER TABLE `curtidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1076;

--
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT de tabela `seguidores`
--
ALTER TABLE `seguidores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

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

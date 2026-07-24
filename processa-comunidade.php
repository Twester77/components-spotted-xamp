<?php
/**
 * processa-comunidade.php – Cria ou edita uma comunidade
 * 
 * Ações:
 * - Criar: POST com nome, slug, descricao, capa
 * - Editar: POST com id, nome, slug, descricao, capa
 * 
 * Redireciona para lista-comunidades.php ou comunidade.php?id=X
 */

require_once __DIR__ . '/auth_check.php';
require_once 'includes/upload_engine.php';
include_once __DIR__ . '/fenda_debug.php';

fenda_log('🔵 INÍCIO processa-comunidade.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: lista-comunidades.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$modo = isset($_POST['id']) ? 'editar' : 'criar';
$id = $modo === 'editar' ? (int)$_POST['id'] : 0;

// ============================================================
// 1. CAPTURA DOS DADOS
// ============================================================
$nome = trim($_POST['nome'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');

// Validação básica
if (empty($nome) || strlen($nome) < 3) {
    $_SESSION['erro_comunidade'] = 'O nome deve ter pelo menos 3 caracteres.';
    header("Location: " . ($modo === 'editar' ? "editar-comunidade.php?id=$id" : "criar-comunidade.php"));
    exit();
}

if (empty($slug) || !preg_match('/^[a-z0-9-]+$/', $slug) || strlen($slug) < 3) {
    $_SESSION['erro_comunidade'] = 'O slug deve conter apenas letras minúsculas, números e hífens.';
    header("Location: " . ($modo === 'editar' ? "editar-comunidade.php?id=$id" : "criar-comunidade.php"));
    exit();
}

// ============================================================
// 2. VERIFICAÇÃO DE SLUG DUPLICADO
// ============================================================
$sql_check = "SELECT id FROM comunidades WHERE slug = ?";
if ($modo === 'editar') {
    $sql_check .= " AND id != ?";
}
$stmt_check = $conn->prepare($sql_check);
if ($modo === 'editar') {
    $stmt_check->bind_param("si", $slug, $id);
} else {
    $stmt_check->bind_param("s", $slug);
}
$stmt_check->execute();
$res_check = $stmt_check->get_result();
if ($res_check->num_rows > 0) {
    $_SESSION['erro_comunidade'] = 'Este slug já está em uso. Escolha outro.';
    header("Location: " . ($modo === 'editar' ? "editar-comunidade.php?id=$id" : "criar-comunidade.php"));
    exit();
}
$stmt_check->close();

// ============================================================
// 3. PROCESSAMENTO DA CAPA (upload para B2)
// ============================================================
$capa_nome = null;

// 🔥 LOG: exibe o que foi enviado
fenda_log('🔵 [UPLOAD] FILES recebidos: ' . print_r($_FILES, true));

if (isset($_FILES['capa']) && $_FILES['capa']['error'] === 0) {
    fenda_log('🔵 [UPLOAD] Arquivo capa recebido: ' . $_FILES['capa']['name'] . ' (' . $_FILES['capa']['size'] . ' bytes)');
    
    // 🔥 Usa a função processarUploadSeguro (que já faz upload para B2)
    // Retorna apenas o nome do arquivo (ex: comunidade_123_abc.webp)
    $capa_nome = processarUploadSeguro($_FILES['capa'], 'uploads', 'comunidade', 2 * 1024 * 1024, $usuario_id);
    
    if ($capa_nome === false) {
        fenda_log('🔴 [UPLOAD] Falha ao processar upload da capa.');
        $_SESSION['erro_comunidade'] = 'Erro ao enviar a capa. Verifique o tamanho (máx 2MB) e formato.';
        header("Location: " . ($modo === 'editar' ? "editar-comunidade.php?id=$id" : "criar-comunidade.php"));
        exit();
    } else {
        fenda_log('🟢 [UPLOAD] Capa enviada com sucesso para B2: ' . $capa_nome);
        // 🔥 IMPORTANTE: Salvar APENAS o nome do arquivo (sem prefixo)
        // A exibição usará obterUrlImagem() para gerar a URL assinada
    }
} else {
    fenda_log('🔵 [UPLOAD] Nenhuma capa enviada ou erro no upload (código: ' . ($_FILES['capa']['error'] ?? 'N/A') . ')');
}

// ============================================================
// 4. INSERÇÃO OU ATUALIZAÇÃO
// ============================================================
if ($modo === 'criar') {
    // Busca a capa padrão se não enviou (nome do arquivo padrão, sem caminho)
    if ($capa_nome === null) {
        $capa_nome = 'default_comunidade.webp';
        fenda_log('🔵 [UPLOAD] Usando capa padrão: ' . $capa_nome);
    }
    
    $sql = "INSERT INTO comunidades (nome, slug, descricao, capa, criador_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $nome, $slug, $descricao, $capa_nome, $usuario_id);
    
    if ($stmt->execute()) {
        $nova_id = $conn->insert_id;
        
        // Adiciona o criador como membro (admin)
        $stmt_membro = $conn->prepare("INSERT INTO comunidade_membros (comunidade_id, usuario_id, papel) VALUES (?, ?, 'admin')");
        $stmt_membro->bind_param("ii", $nova_id, $usuario_id);
        $stmt_membro->execute();
        $stmt_membro->close();
        
        fenda_log('🟢 Comunidade criada: ID ' . $nova_id . ' por usuário ' . $usuario_id);
        $_SESSION['sucesso_comunidade'] = 'Comunidade criada com sucesso!';
        header("Location: comunidade.php?id=$nova_id");
        exit();
    } else {
        fenda_log('🔴 Erro ao criar comunidade: ' . $stmt->error);
        $_SESSION['erro_comunidade'] = 'Erro ao criar comunidade: ' . $stmt->error;
        header("Location: criar-comunidade.php");
        exit();
    }
    $stmt->close();
    
} else { // Editar
    // Verifica se o usuário é admin/criador da comunidade
    $stmt_check = $conn->prepare("SELECT criador_id FROM comunidades WHERE id = ?");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    $com = $res_check->fetch_assoc();
    $stmt_check->close();
    
    if (!$com || ($com['criador_id'] != $usuario_id)) {
        // Verifica se é moderador (opcional)
        $stmt_mod = $conn->prepare("SELECT papel FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ? AND papel IN ('admin', 'moderador')");
        $stmt_mod->bind_param("ii", $id, $usuario_id);
        $stmt_mod->execute();
        $res_mod = $stmt_mod->get_result();
        if ($res_mod->num_rows === 0) {
            $_SESSION['erro_comunidade'] = 'Você não tem permissão para editar esta comunidade.';
            header("Location: comunidade.php?id=$id");
            exit();
        }
        $stmt_mod->close();
    }
    
    // Monta a query de atualização
    $sql = "UPDATE comunidades SET nome = ?, slug = ?, descricao = ?";
    $params = [$nome, $slug, $descricao];
    $types = "sss";
    
    if ($capa_nome !== null) {
        $sql .= ", capa = ?";
        $params[] = $capa_nome;
        $types .= "s";
        fenda_log('🔵 [UPLOAD] Atualizando capa para: ' . $capa_nome);
    } else {
        fenda_log('🔵 [UPLOAD] Mantendo capa atual (sem alteração).');
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $id;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        fenda_log('🟢 Comunidade editada: ID ' . $id . ' por usuário ' . $usuario_id);
        $_SESSION['sucesso_comunidade'] = 'Comunidade atualizada com sucesso!';
        header("Location: comunidade.php?id=$id");
        exit();
    } else {
        fenda_log('🔴 Erro ao editar comunidade: ' . $stmt->error);
        $_SESSION['erro_comunidade'] = 'Erro ao atualizar comunidade: ' . $stmt->error;
        header("Location: editar-comunidade.php?id=$id");
        exit();
    }
    $stmt->close();
}
?>
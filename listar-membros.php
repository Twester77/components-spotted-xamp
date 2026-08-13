<?php
/**
 * listar-membros.php – Endpoint para listar membros de uma comunidade (com paginação)
 * 
 * Parâmetros:
 * - comunidade_id (int, via GET, obrigatório)
 * - offset (int, opcional, padrão 0)
 * - status (string, opcional: 'todos', 'ativos', 'banidos' – padrão 'todos')
 * - busca (string, opcional: termo para buscar em username ou nome)
 * 
 * Retorno: HTML com a lista de membros + estado de paginação.
 * 
 * 🔒 Segurança:
 * - Apenas membros da comunidade podem visualizar a lista.
 * - Prepared statements com bind_param para evitar SQL Injection.
 * - Limite fixo de 20 membros por requisição.
 * 
 * 🌊 MARÉ – INSTÂNCIA #DS-2026-08-12
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/upload_engine.php';

header('Content-Type: text/html; charset=utf-8');

$comunidade_id = isset($_GET['comunidade_id']) ? (int)$_GET['comunidade_id'] : 0;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'todos';
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$limite = 20;

$usuario_id = $_SESSION['usuario_id'];

if ($comunidade_id <= 0) {
    echo '<p style="text-align:center; color:#ff6b6b; padding:20px;">ID da comunidade inválido.</p>';
    exit;
}

// ============================================================
// 1. VERIFICA SE O USUÁRIO É MEMBRO DA COMUNIDADE
// ============================================================
$stmt_check = $conn->prepare("SELECT 1 FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ? AND status = 'ativo'");
$stmt_check->bind_param("ii", $comunidade_id, $usuario_id);
$stmt_check->execute();
$res_check = $stmt_check->get_result();
$is_membro = $res_check->num_rows > 0;
$stmt_check->close();

if (!$is_membro) {
    echo '<p style="text-align:center; color:#ff6b6b; padding:20px;">Você não tem permissão para ver esta lista.</p>';
    exit;
}

// ============================================================
// 2. BUSCA O PAPEL DO USUÁRIO NA COMUNIDADE (para ações)
// ============================================================
$stmt_role = $conn->prepare("SELECT papel FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ? AND status = 'ativo'");
$stmt_role->bind_param("ii", $comunidade_id, $usuario_id);
$stmt_role->execute();
$res_role = $stmt_role->get_result();
$user_role = $res_role->fetch_assoc()['papel'] ?? 'membro';
$stmt_role->close();

$is_admin = in_array($user_role, ['admin', 'criador']);
$is_criador = ($user_role === 'criador');

// ============================================================
// 3. PREPARA OS FILTROS PARA A QUERY
// ============================================================
$filtro_status = '';
if ($status === 'ativos') {
    $filtro_status = "AND cm.status = 'ativo'";
} elseif ($status === 'banidos') {
    $filtro_status = "AND cm.status = 'banido'";
} // 'todos' não adiciona filtro

$busca_like = '%' . $busca . '%';

// ============================================================
// 4. QUERY PRINCIPAL COM PREPARED STATEMENTS
// ============================================================
$sql = "SELECT 
            cm.usuario_id,
            cm.papel,
            cm.status,
            cm.data_entrada,
            u.username,
            u.nome,
            u.foto
        FROM comunidade_membros cm
        INNER JOIN usuarios u ON cm.usuario_id = u.id
        WHERE cm.comunidade_id = ?
        $filtro_status
        AND (u.username LIKE ? OR u.nome LIKE ?)
        ORDER BY 
            FIELD(cm.status, 'banido') DESC,
            FIELD(cm.papel, 'criador', 'admin', 'membro'),
            cm.data_entrada ASC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("issii", $comunidade_id, $busca_like, $busca_like, $limite, $offset);
$stmt->execute();
$res = $stmt->get_result();

// ============================================================
// 5. CONTAGEM TOTAL (para exibir no rodapé e controle de paginação)
// ============================================================
$sql_count = "SELECT COUNT(*) as total FROM comunidade_membros cm
              INNER JOIN usuarios u ON cm.usuario_id = u.id
              WHERE cm.comunidade_id = ? $filtro_status
              AND (u.username LIKE ? OR u.nome LIKE ?)";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("iss", $comunidade_id, $busca_like, $busca_like);
$stmt_count->execute();
$res_count = $stmt_count->get_result();
$total = $res_count->fetch_assoc()['total'] ?? 0;
$stmt_count->close();

$carregados = $offset + $res->num_rows;
$tem_mais = ($total > $carregados);

if ($res->num_rows === 0) {
    echo '<p style="text-align:center; color:#aaa; padding:20px;">Nenhum membro encontrado.</p>';
    exit;
}

// ============================================================
// 6. OBTÉM URLs DAS IMAGENS VIA B2 (COM CACHE)
// ============================================================
try {
    $b2 = B2Client::getInstance();
} catch (Exception $e) {
    $b2 = null;
}

// ============================================================
// 7. RENDERIZA A LISTA DE MEMBROS
// ============================================================
echo '<div class="membros-lista" data-total="' . $total . '" data-carregados="' . $carregados . '" data-offset="' . ($offset + $limite) . '">';

while ($membro = $res->fetch_assoc()) {
    $avatar = !empty($membro['foto']) ? (obterUrlImagem($membro['foto'], $b2, true) ?? 'uploads/ui/default_masculino.webp') : 'uploads/ui/default_masculino.webp';
    $data = date('d/m/Y', strtotime($membro['data_entrada']));
    $is_self = ($membro['usuario_id'] == $usuario_id);
    $is_criador_alvo = ($membro['papel'] === 'criador');
    $is_banido = ($membro['status'] === 'banido');
    
    $papel_classe = match($membro['papel']) {
        'criador' => 'papel-criador',
        'admin' => 'papel-admin',
        default => 'papel-membro'
    };
    
    $acoes = '';
    if ($is_admin && !$is_self && !$is_criador_alvo) {
        if ($is_banido) {
            $acoes .= '<button class="btn-acao-membro btn-banir" data-usuario="' . $membro['usuario_id'] . '" data-acao="desbanir" title="Desbanir">🔓 Desbanir</button>';
        } else {
            $acoes .= '<button class="btn-acao-membro btn-banir" data-usuario="' . $membro['usuario_id'] . '" data-acao="banir" title="Banir">🔒 Banir</button>';
        }
        $acoes .= '<button class="btn-acao-membro btn-remover" data-usuario="' . $membro['usuario_id'] . '" title="Remover">Remover 🗑️</button>';
    }
    
    if ($is_criador && !$is_self && !$is_criador_alvo) {
        if ($membro['papel'] === 'admin') {
            $acoes .= '<button class="btn-acao-membro btn-rebaixar" data-usuario="' . $membro['usuario_id'] . '" title="Rebaixar para membro">Rebaixar ⬇️</button>';
        } else {
            $acoes .= '<button class="btn-acao-membro btn-promover" data-usuario="' . $membro['usuario_id'] . '" title="Promover a admin">Promover ⬆️</button>';
        }
    }
    
    $classe_banido = $is_banido ? 'membro-banido' : '';
    
    echo '<div class="membro-item ' . $classe_banido . '" data-usuario="' . $membro['usuario_id'] . '">';
    echo '  <div class="info-membro">';
    echo '    <img src="' . htmlspecialchars($avatar) . '" class="avatar-membro" onerror="this.src=\'uploads/ui/default_masculino.webp\'">';
    echo '    <div>';
    echo '      <span class="nome-membro">@' . htmlspecialchars($membro['username']) . '</span>';
    if ($is_banido) {
        echo '      <span class="status-banido">(banido)</span>';
    }
    echo '      <div class="data-membro">' . $data . '</div>';
    echo '    </div>';
    echo '  </div>';
    echo '  <div class="acoes-membro">';
    echo '    <span class="papel-badge ' . $papel_classe . '">' . strtoupper($membro['papel']) . '</span>';
    echo '    ' . $acoes . '';
    echo '  </div>';
    echo '</div>';
}

echo '</div>';

// 🔥 SE HOUVER MAIS MEMBROS, EXIBE O BOTÃO "CARREGAR MAIS"
if ($tem_mais) {
    echo '<div class="carregar-mais-membros" style="text-align:center; padding:12px 0;">';
    echo '  <button class="btn-carregar-mais-membros" data-offset="' . ($offset + $limite) . '" style="background:rgba(255,188,0,0.1); border:1px solid rgba(255,188,0,0.2); border-radius:30px; padding:6px 20px; color:#ffbc00; font-weight:600; cursor:pointer; transition:0.2s; font-family:inherit;">';
    echo '    <i class="fas fa-chevron-down"></i> Carregar mais (' . ($total - $carregados) . ' restantes)';
    echo '  </button>';
    echo '</div>';
}

$stmt->close();
?>
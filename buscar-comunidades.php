<?php
/**
 * buscar-comunidades.php – Endpoint JSON para autocomplete de comunidades
 * 
 * 🔒 SEGURANÇA: verificação de sessão manual (sem modificar auth_check)
 * 🛡️ BLINDADO: sem saída HTML, apenas JSON puro.
 * 
 * 🔧 CORREÇÃO ONDINA – INSTÂNCIA #DS-2026-08-17
 *    "Substituição de obterUrlImagem() por obterUrlComFallback() para fallback centralizado
 *     e adição de fallback seguro para erros de B2."
 * - Ondina
 */

error_reporting(0);
ini_set('display_errors', 0);

ob_start(); // Limpa qualquer saída acidental

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/upload_engine.php';

// ============================================================
// 🛡️ VERIFICAÇÃO DE SESSÃO (MANUAL, SEM REDIRECIONAMENTO)
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

// ============================================================
// 🔍 EXECUÇÃO DA BUSCA
// ============================================================
$termo = isset($_GET['q']) ? trim($_GET['q']) : '';
$limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 8;

if (strlen($termo) < 2) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Busca comunidades
$termo_like = '%' . $conn->real_escape_string($termo) . '%';
$sql = "SELECT id, nome, descricao, capa 
        FROM comunidades 
        WHERE nome LIKE ? OR descricao LIKE ?
        ORDER BY 
            CASE 
                WHEN nome LIKE ? THEN 1 
                ELSE 2 
            END,
            data_criacao DESC
        LIMIT ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro ao preparar consulta']);
    exit;
}

$stmt->bind_param("sssi", $termo_like, $termo_like, $termo_like, $limite);
$stmt->execute();
$res = $stmt->get_result();

$resultados = [];
while ($row = $res->fetch_assoc()) {
    // 🔥 CAPA VIA B2 COM FALLBACK CENTRALIZADO (substitui obterUrlImagem)
    $capa_nome = !empty($row['capa']) ? $row['capa'] : 'default_comunidade.webp';
    try {
        $b2 = B2Client::getInstance();
        // 🔥 SUBSTITUIÇÃO AQUI: obterUrlImagem → obterUrlComFallback
        $capa_url = obterUrlComFallback($capa_nome, 'uploads/ui/default_comunidade.webp', $b2, true);
    } catch (Exception $e) {
        error_log("[BUSCAR-COMUNIDADES] Erro ao obter capa para comunidade {$row['id']}: " . $e->getMessage());
        $capa_url = 'uploads/ui/default_comunidade.webp';
    }

    $descricao = htmlspecialchars($row['descricao'] ?? '');
    $descricao_resumida = mb_strlen($descricao) > 60 ? mb_substr($descricao, 0, 60) . '...' : $descricao;

    $resultados[] = [
        'id' => (int)$row['id'],
        'nome' => htmlspecialchars($row['nome']),
        'descricao' => $descricao_resumida,
        'capa' => $capa_url,
        'url' => 'comunidade.php?id=' . $row['id']
    ];
}

$stmt->close();

//  LIMPA O BUFFER E GARANTE QUE SÓ O JSON SAIA
ob_clean();
header('Content-Type: application/json');
echo json_encode($resultados);
exit;
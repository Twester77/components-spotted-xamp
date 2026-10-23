<?php
/**
 * includes/cascata-helper.php
 * Funções auxiliares para renderização da árvore de comentários (Modo Cascata).
 */

if (!function_exists('renderizarArvoreCascata')) {
    /**
     * Renderiza recursivamente a árvore de comentários.
     * 
     * @param array $comentarios Array com todos os comentários (flat)
     * @param int|null $parent_id ID do pai atual (null para raiz)
     * @param int $nivel Nível atual de profundidade (0 = raiz)
     * @param object|null $b2 Instância do B2Client para pegar imagens
     * @return string HTML gerado
     */
    function renderizarArvoreCascata($comentarios, $parent_id = null, $nivel = 0, $b2 = null) {
        $html = '';
        $nivel_maximo_visual = 2; // Trava visual: níveis 3+ se comportam como nível 2

        foreach ($comentarios as $c) {
            // Verifica se o comentário atual é filho do pai que estamos iterando
            $is_filho = ($parent_id === null && empty($c['parent_id'])) || 
                        ($parent_id !== null && $c['parent_id'] == $parent_id);
            
            if (!$is_filho) continue;

            // Trava visual (achatamento)
            $nivel_visual = min($nivel, $nivel_maximo_visual);
            $classe_nivel = 'nivel-' . $nivel_visual;
            
            // Verifica se tem filhos pra adicionar a classe .has-children (útil pro CSS)
            $tem_filhos = false;
            foreach ($comentarios as $possivel_filho) {
                if ($possivel_filho['parent_id'] == $c['id']) {
                    $tem_filhos = true;
                    break;
                }
            }

            // Sanitização de preferências
            $vibe = sanitizarVibe($c['pref_vibe_comentario'] ?? 'vibe-glass');
            $cor_borda = sanitizarCorBorda($c['pref_cor_borda'] ?? '#70cde4');
            
            // Verificando se é meu comentário
            $id_autor = $c['id_usuario'] ?? $c['usuario_id'] ?? 0;
            $sou_eu = (isset($_SESSION['usuario_id']) && $id_autor == $_SESSION['usuario_id']) ? 'meu-comentario' : '';
            $classe_filho = !empty($c['parent_id']) ? "comentario-filho" : "";
            $estilo_filho = $classe_filho ? "var(--cor-borda-glow);" : "";

            // Trecho de resposta
            $trecho_resposta = '';
            if (!empty($c['parent_id']) && !empty($c['parent_comentario'])) {
                $texto_puro = strip_tags($c['parent_comentario']);
                $texto_cortado = mb_substr($texto_puro, 0, 50);
                $trecho_resposta = mb_strlen($texto_puro) > 50 ? $texto_cortado . '...' : $texto_cortado;
            }

            // Início do HTML do comentário
            $html .= '
            <div class="comentario-linha ' . $classe_nivel . ' ' . ($tem_filhos ? 'has-children' : '') . '">';
            
            // O Card do Comentário
            $html .= '
                <div class="comentario-item ' . $vibe . ' ' . $classe_filho . ' ' . $sou_eu . '" id="comentario-' . $c['id'] . '" style="--cor-borda-glow: ' . $cor_borda . '; ' . $estilo_filho . '">
                    <div class="comentario-meta">
                        <strong class="comentario-autor">' . (!empty($c['usuario_nome']) ? "@" . htmlspecialchars($c['usuario_nome']) : "👤 Anônimo") . '</strong>
                    </div>';
            
            // Indicador de resposta
            if (!empty($c['parent_id'])) {
                $html .= '
                    <div class="indicador-resposta" onclick="event.stopPropagation(); irParaMensagem(' . $c['parent_id'] . ')">
                        <i class="fas fa-reply"></i> <small>' . htmlspecialchars($trecho_resposta) . '</small>
                    </div>';
            }

            // Texto
            $html .= '<p class="comentario-texto">' . nl2br(formatarMencoes($c['comentario'])) . '</p>';

            // Anexos (Imagens/GIFs)
            $anexos_exibicao = null;
            if (!empty($c['anexos'])) {
                $anexos_exibicao = json_decode($c['anexos'], true);
            }
            
            if (!empty($anexos_exibicao) && is_array($anexos_exibicao)) {
                $html .= '<div class="comentario-media-wrapper-grid">';
                foreach ($anexos_exibicao as $anexo) {
                    if ($anexo['tipo'] === 'imagem' && !empty($anexo['caminho'])) {
                        $img_url = obterUrlComFallback($anexo['caminho'], 'comentarios/' . htmlspecialchars($anexo['caminho']), $b2, true);
                        $html .= '<div class="comentario-media-item"><img src="' . htmlspecialchars($img_url) . '" class="comentario-img" loading="lazy" onerror="this.style.display=\'none\'"></div>';
                    } elseif ($anexo['tipo'] === 'gif' && !empty($anexo['url'])) {
                        $html .= '<div class="comentario-media-item"><img src="' . htmlspecialchars($anexo['url']) . '" class="comentario-img gif-externo" loading="lazy"></div>';
                    }
                }
                $html .= '</div>';
            } elseif (!empty($c['imagem_url'])) {
                // Fallback para imagem única antiga
                $img_comentario = $c['imagem_url'];
                if (!filter_var($img_comentario, FILTER_VALIDATE_URL)) {
                    $img_comentario = obterUrlComFallback($c['imagem_url'], 'comentarios/' . htmlspecialchars($c['imagem_url']), $b2, true);
                }
                $html .= '<div class="comentario-media-wrapper"><img src="' . htmlspecialchars($img_comentario) . '" class="comentario-img ' . (filter_var($c['imagem_url'], FILTER_VALIDATE_URL) ? 'gif-externo' : '') . '" loading="lazy" onerror="this.style.display=\'none\'"></div>';
            }

            // Rodapé (Data)
            $html .= '
                    <div class="comentario-rodape">
                        <span class="comentario-data">' . exibirDataHoraBrasil($c['data_comentario'], 'H:i') . '</span>
                    </div>
                </div>'; // Fim .comentario-item

            // Botão de ações (⋯)
            $html .= '
                <button type="button" id="comentario-acoes-' . $c['id'] . '" class="comentario-menu-toggle" aria-label="Ações do comentário" aria-haspopup="true" aria-expanded="false" aria-controls="comentario-menu-' . $c['id'] . '" title="Ações do comentário">
                    <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                </button>';

            // RECURSÃO: Busca os filhos deste comentário
            $html .= renderizarArvoreCascata($comentarios, $c['id'], $nivel + 1, $b2);
            
            $html .= '</div>'; // Fim .comentario-linha
        }

        return $html;
    }
}
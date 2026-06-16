<?php 
include_once __DIR__ . '/../conexao.php';

if (isset($_SESSION['usuario_id'])):
    $meu_id_toolbar = $_SESSION['usuario_id'];
    // Buscamos quem você segue
    $sql_seguindo = "SELECT u.nome, u.username, u.ultima_atividade 
                     FROM usuarios u 
                     INNER JOIN seguidores s ON u.id = s.id_seguido 
                     WHERE s.id_seguidor = '$meu_id_toolbar' 
                     LIMIT 8";
    $res_toolbar = mysqli_query($conn, $sql_seguindo);
?>
    <!-- Adicionado role e label para identificar a barra lateral de ferramentas -->
    <div id="fenda-toolbar" class="toolbar-fechada" role="region" aria-label="Painel de Controle Lateral">
    
    <button type="button" 
            class="toolbar-trigger" 
            onclick="toggleToolbar()" 
            aria-label="Abrir Painel de Controle"
            aria-expanded="false"> <span id="trigger-icon" aria-hidden="true">🧭</span>
    </button>

        <div class="toolbar-content">
            <h3 style=" font-family: 'JetBrains Mono', monospace; margin-bottom: 15px; text-align: center;">Navegação</h3>

            <div class="control-item">
                <button type="button" onclick="window.location.href='feed.php'" class="btn-feed-toolbar">
                     IR PRO FEED
                </button>
            </div>

            <div class="setup-section">
                <div class="control-item">
                    <button type="button" onclick="window.location.href='perfil.php'" class="btn-perfil-toolbar">
                        CONFIG.PERFIL
                    </button>
                </div>

                <div class="control-item">
                    <button type="button" onclick="toggleHackerMode()" id="hacker-toggle-lateral">
                        MODO_TERMINAL
                    </button>
                </div>

                <div class="control-item">
                    <label style="font-size: 0.8rem; color: #00ffcc;">Sons da Fenda:</label>
                    <div class="btns-mini">
                        <!-- aria-labels adicionados para traduzir o significado de cada emoji ao leitor de áudio -->
                        <button type="button" onclick="mudarSomAmbiente('chuva')" aria-label="Ativar som ambiente de chuva">🌧️</button>
                        <button type="button" onclick="mudarSomAmbiente('oceano')" aria-label="Ativar som ambiente de oceano">🌊</button>
                        <button type="button" onclick="mudarSomAmbiente('off')" aria-label="Desativar som ambiente">🔇</button>
                    </div>
                </div>
            </div>

            <hr style="border: 0.5px solid rgba(0, 255, 204, 0.2); margin: 15px 0;">

            <div class="status-section">
                <label style="color: #00ffcc; font-weight: bold; font-size: 0.9rem; margin-bottom: 10px; display: block;">Seguindo:</label>
                <div id="lista-amigos-toolbar">
                    <?php
                    if (mysqli_num_rows($res_toolbar) > 0):
                        while ($amigo = mysqli_fetch_assoc($res_toolbar)):
                            // Cálculo Online/Offline
                            $ultima = strtotime($amigo['ultima_atividade']);
                            $agora  = time();
                            $diferenca = $agora - $ultima;
                            $status_classe = ($diferenca <= 300) ? 'dot-online' : 'dot-offline';
                            $status_texto = ($diferenca <= 300) ? 'Disponível' : 'Indisponível';
                    ?>
                            <!-- aria-label informa o status online/offline do usuário antes do nome -->
                            <div class="status-user" aria-label="<?php echo $status_texto; ?>: <?php echo htmlspecialchars($amigo['nome']); ?>">
                                <span class="status-dot <?php echo $status_classe; ?>" aria-hidden="true"></span>
                                <a href="ver-perfil.php?user=<?php echo $amigo['username']; ?>" style="color: #fff; text-decoration: none;">
                                    <?php echo htmlspecialchars($amigo['nome']); ?>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="status-user" style="font-size: 0.8rem; opacity: 0.6;">
                            Nenhum tripulante seguido.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
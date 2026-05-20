<?php if (isset($_SESSION['usuario_id'])):
    $meu_id_toolbar = $_SESSION['usuario_id'];
    // Buscamos quem você segue
    $sql_seguindo = "SELECT u.nome, u.username, u.ultima_atividade 
                     FROM usuarios u 
                     INNER JOIN seguidores s ON u.id = s.id_seguido 
                     WHERE s.id_seguidor = '$meu_id_toolbar' 
                     LIMIT 8";
    $res_toolbar = mysqli_query($conn, $sql_seguindo);
?>
    <div id="fenda-toolbar" class="toolbar-fechada">
        <div class="toolbar-trigger" onclick="toggleToolbar()">
            <span id="trigger-icon">🧭</span>
        </div>

        <div class="toolbar-content">
            <h3 style="color: #00ffcc; font-family: 'JetBrains Mono', monospace; font-size: 1.1rem; margin-bottom: 15px; text-align: center;">Navegação</h3>

            <div class="control-item">
                <button onclick="window.location.href='feed.php'" class="btn-feed-toolbar">
                    🏠 IR PARA O FEED
                </button>
            </div>

            <div class="setup-section">
                <div class="control-item">
                    <button onclick="window.location.href='perfil.php'" class="btn-perfil-toolbar">
                        👤 CONFIG. PERFIL
                    </button>
                </div>

                <div class="control-item">
                    <button onclick="toggleHackerMode()" id="hacker-toggle-lateral">
                        MODO_TERMINAL
                    </button>
                </div>

                <div class="control-item">
                    <label style="font-size: 0.8rem; color: #00ffcc;">Sons da Fenda:</label>
                    <div class="btns-mini">
                        <button onclick="mudarSomAmbiente('chuva')">🌧️</button>
                        <button onclick="mudarSomAmbiente('oceano')">🌊</button>
                        <button onclick="mudarSomAmbiente('off')">🔇</button>
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
                    ?>
                            <div class="status-user">
                                <span class="status-dot <?php echo $status_classe; ?>"></span>
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
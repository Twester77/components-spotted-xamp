<?php if (isset($_SESSION['usuario_id'])): 
    // Buscamos os habitantes que você segue e o "visto por último" deles[cite: 4, 7]
    $meu_id_toolbar = $_SESSION['usuario_id'];
    $sql_seguindo = "SELECT u.nome, u.username, u.ultima_atividade 
                     FROM usuarios u 
                     INNER JOIN seguidores s ON u.id = s.id_seguido 
                     WHERE s.id_seguidor = '$meu_id_toolbar' 
                     LIMIT 5";
    $res_toolbar = mysqli_query($conn, $sql_seguindo);
?>
<div id="fenda-toolbar" class="toolbar-fechada">
    <div class="toolbar-trigger" onclick="toggleToolbar()">
        <span id="trigger-icon">🧭</span>
    </div>

    <div class="toolbar-content">
        <h3> Navegação </h3>
        
        <div class="setup-section">
            <div class="control-item">
                <button onclick="window.location.href='perfil.php'" class="btn-perfil-toolbar">
                    👤 MEU PERFIL
                </button>
            </div>

            <div class="control-item">
                <button onclick="toggleHackerMode()" id="hacker-toggle-lateral">
                    MODO_TERMINAL
                </button>
            </div>
            
            <div class="control-item">
                <label>Sons da Fenda:</label>
                <div class="btns-mini">
                    <button onclick="mudarSomAmbiente('chuva')">🌧️</button>
                    <button onclick="mudarSomAmbiente('oceano')">🌊</button>
                    <button onclick="mudarSomAmbiente('off')">🔇</button>
                </div>
            </div>
        </div>

        <hr style="border: 0.5px solid #004d40; margin: 15px 0;">

        <div class="status-section">
            <label> Seguindo: </label>
            <div id="lista-amigos-toolbar">
                <?php 
                if (mysqli_num_rows($res_toolbar) > 0):
                    while ($amigo = mysqli_fetch_assoc($res_toolbar)): 
                        // CÁLCULO DE STATUS ONLINE (5 MINUTOS)[cite: 4]
                        $ultima = strtotime($amigo['ultima_atividade']);
                        $agora  = time();
                        $diferenca_segundos = $agora - $ultima;

                        // Se a atividade foi nos últimos 300 segundos, tá online[cite: 4]
                        $status_classe = ($diferenca_segundos <= 300) ? 'dot-online' : 'dot-offline';
                ?>
                    <div class="status-user">
                        <span class="status-dot <?php echo $status_classe; ?>"></span> 
                        <?php echo htmlspecialchars($amigo['nome']); ?>
                    </div>
                <?php 
                    endwhile; 
                else:
                ?>
                    <div class="status-user" style="font-size: 11px; opacity: 0.6;">
                        Nenhum tripulante seguido.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
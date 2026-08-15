<?php
echo "=== VARIÁVEIS DE AMBIENTE DA FENDA ===\n\n";

$vars = [
    // Banco de dados (TiDB)
    'DB_HOST',
    'DB_USER',
    'DB_PASS',
    'DB_NAME',
    'DB_PORT',
    'ENVIRONMENT',
    
    // Supabase
    'SUPABASE_URL',
    'SUPABASE_ANON_KEY',
    
    // Backblaze B2
    'B2_KEY_ID',
    'B2_APPLICATION_KEY',
    'B2_BUCKET_NAME',
    'B2_BUCKET_ID',
    
    // Resend (e-mail)
    'RESEND_KEY',
    
    // Cloudflare Turnstile
    'TURNSTILE_SECRET_KEY',
    
    // Sessão
    'SESSION_COOKIE_DOMAIN'
];

foreach ($vars as $var) {
    $value = getenv($var);
    if ($value !== false && $value !== '') {
        // Oculta valores sensíveis para não expor no navegador
        $sensivel = strpos($var, 'PASS') !== false || 
                    strpos($var, 'KEY') !== false || 
                    strpos($var, 'SECRET') !== false ||
                    strpos($var, 'TOKEN') !== false;
        
        if ($sensivel) {
            echo "✅ $var: DEFINIDO (oculto)\n";
        } else {
            echo "✅ $var: $value\n";
        }
    } else {
        echo "❌ $var: NÃO DEFINIDO\n";
    }
}
?>
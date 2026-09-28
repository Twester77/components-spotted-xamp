<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNOSTICO DE IP ===\n\n";

echo "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'VAZIO') . "\n";
echo "HTTP_X_FORWARDED_FOR: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'VAZIO') . "\n";
echo "HTTP_X_REAL_IP: " . ($_SERVER['HTTP_X_REAL_IP'] ?? 'VAZIO') . "\n";
echo "HTTP_X_VERCEL_FORWARDED_FOR: " . ($_SERVER['HTTP_X_VERCEL_FORWARDED_FOR'] ?? 'VAZIO') . "\n";
echo "HTTP_CF_CONNECTING_IP: " . ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? 'VAZIO') . "\n";
echo "HTTP_CLIENT_IP: " . ($_SERVER['HTTP_CLIENT_IP'] ?? 'VAZIO') . "\n";
echo "REDIRECT_HTTP_X_FORWARDED_FOR: " . ($_SERVER['REDIRECT_HTTP_X_FORWARDED_FOR'] ?? 'VAZIO') . "\n";

echo "\n=== TODAS AS CHAVES HTTP_* ===\n\n";
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        echo "$key: $value\n";
    }
}

echo "\n=== FIM ===\n";
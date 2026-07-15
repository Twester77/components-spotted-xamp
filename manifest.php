<?php
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// 🔥 FORÇA O NAVEGADOR A NÃO CACHEAR O MANIFESTO
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$manifest = [
    'name' => 'A Fenda - Spotted Universitário',
    'short_name' => 'Fenda',
    'description' => 'O feed mais doido da UNIFEV.',
    'start_url' => $basePath . '/feed.php',
    'display' => 'standalone',
    'display_override' => ['window-controls-overlay'],
    'theme_color' => '#1a1a2e',
    'background_color' => '#0a0a0a',
    'orientation' => 'any',
    'icons' => [
        [
            'src' => $basePath . '/uploads/ui/icon-192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $basePath . '/uploads/ui/icon-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ]
    ],
    'badge' => $basePath . '/uploads/ui/badge-72x72.png',
    'screenshots' => [],
    'categories' => ['social', 'education']
];
echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
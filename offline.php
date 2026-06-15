<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Fenda - Offline</title>
    <link rel="stylesheet" href="/css/root.css">
    <style>
        body {
            background: #0a0a0a;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .offline-card {
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(10px);
            border: 1px solid #ffbc00;
            border-radius: 20px;
            padding: 30px;
            max-width: 400px;
            color: #fff;
        }
        .offline-card i {
            font-size: 4rem;
            color: #ffbc00;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        p {
            margin-bottom: 20px;
            color: #ccc;
        }
        .btn-rec {
            background: #ffbc00;
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-rec:hover {
            background: #ffd966;
        }
    </style>
</head>
<body>
    <div class="offline-card">
        <i class="fas fa-wifi"></i>
        <h1>📡 Fenda Desconectada</h1>
        <p>Parece que você está sem internet.<br>Assim que a conexão voltar, o feed será recarregado automaticamente.</p>
        <button class="btn-rec" onclick="window.location.reload()">🔄 Tentar Novamente</button>
    </div>
    <script>
        // Tenta recarregar quando a conexão voltar
        window.addEventListener('online', () => window.location.reload());
    </script>
</body>
</html>
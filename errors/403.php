<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>403 — Accès interdit</title>
  <style>
    body { font-family: sans-serif; display: flex; flex-direction: column; align-items: center;
           justify-content: center; min-height: 100vh; margin: 0; background: #f5f5f5; color: #333; }
    .code { font-size: 6rem; font-weight: 800; color: #e53e3e; margin-bottom: 0; }
    h2 { margin: 0 0 12px; }
    a { color: #2a9d5c; }
  </style>
</head>
<body>
  <?php http_response_code(403); ?>
  <p class="code">403</p>
  <h2>Accès interdit</h2>
  <p>Vous n'avez pas les droits nécessaires pour accéder à cette page.</p>
  <a href="../login.html">← Retour à la connexion</a>
</body>
</html>

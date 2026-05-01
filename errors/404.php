<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>404 — Page introuvable</title>
  <style>
    body { font-family: sans-serif; display: flex; flex-direction: column; align-items: center;
           justify-content: center; min-height: 100vh; margin: 0; background: #f5f5f5; color: #333; }
    .code { font-size: 6rem; font-weight: 800; color: #d4890a; margin-bottom: 0; }
    h2 { margin: 0 0 12px; }
    a { color: #2a9d5c; }
  </style>
</head>
<body>
  <?php http_response_code(404); ?>
  <p class="code">404</p>
  <h2>Page introuvable</h2>
  <p>La page que vous cherchez n'existe pas ou a été déplacée.</p>
  <a href="../index.html">← Retour à l'accueil</a>
</body>
</html>

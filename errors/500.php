<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>500 — Erreur serveur</title>
  <style>
    body { font-family: sans-serif; display: flex; flex-direction: column; align-items: center;
           justify-content: center; min-height: 100vh; margin: 0; background: #f5f5f5; color: #333; }
    .code { font-size: 6rem; font-weight: 800; color: #555; margin-bottom: 0; }
    h2 { margin: 0 0 12px; }
    a { color: #2a9d5c; }
  </style>
</head>
<body>
  <?php http_response_code(500); ?>
  <p class="code">500</p>
  <h2>Erreur interne du serveur</h2>
  <p>Une erreur inattendue s'est produite. Veuillez réessayer plus tard.</p>
  <a href="../index.html">← Retour à l'accueil</a>
</body>
</html>

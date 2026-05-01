<?php
// Déconnexion — vide la session et redirige vers la page de connexion
require_once __DIR__ . '/config.php';
start_session();

// On vide le tableau de session (supprime toutes les données)
$_SESSION = [];

// On supprime aussi le cookie de session dans le navigateur
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, // date dans le passé = supprime le cookie
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// On détruit la session côté serveur
session_destroy();

// On redirige vers la page de connexion
header('Location: ../login.html');
exit;

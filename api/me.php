<?php
// Retourne les infos de l'utilisateur connecté (données fraîches depuis la BDD)
// GET /api/me.php
require_once __DIR__ . '/config.php';

// Vérifie que l'utilisateur est connecté (peu importe le rôle)
$session_user = require_auth();

// Pour l'admin, les données de session suffisent (pas besoin d'aller en BDD)
if ($session_user['role'] === 'admin') {
    json_response(['success' => true, 'user' => $session_user]);
}

// Pour un utilisateur normal : on récupère ses données à jour depuis la BDD
$conn    = get_db();
$user_id = (int) $session_user['id'];

$stmt = mysqli_prepare($conn,
    'SELECT id, nin, nom, prenom, ddn, nom_pere, nom_grand_pere, prenom_mere, nom_mere,
            adresse, email, telephone, statut, date_soumission, date_validation
     FROM utilisateurs WHERE id = ? LIMIT 1'
);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

// Si le compte a été supprimé entre temps
if (!$user) {
    session_destroy();
    json_response(['success' => false, 'error' => 'Compte introuvable.', 'code' => 'UNAUTHENTICATED'], 401);
}

// Si le compte a été bloqué entre temps
if ($user['statut'] === 'bloque') {
    session_destroy();
    json_response(['success' => false, 'error' => 'Compte bloqué.', 'code' => 'BLOCKED'], 403);
}

$user['role']     = 'user';
$_SESSION['user'] = $user; // on met à jour la session avec les données fraîches

json_response(['success' => true, 'user' => $user]);

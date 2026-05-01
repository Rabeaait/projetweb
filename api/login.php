<?php
// Connexion utilisateur ou administrateur

require_once __DIR__ . '/config.php';
start_session(); // démarre la session pour pouvoir stocker l'utilisateur connecté

// On accepte seulement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Méthode non autorisée.'], 405);
}

// On récupère les données envoyées
$data     = get_post_data();
$email    = strtolower(trim($data['email']    ?? '')); //tout mettre en miniscule
$password = $data['password'] ?? '';
$role     = $data['role']     ?? 'user'; // 'admin' ou 'user'

// Email et mot de passe obligatoires
if (!$email || !$password) {
    json_response(['success' => false, 'error' => 'Email et mot de passe requis.'], 400);
}

// ── Connexion administrateur ──
if ($role === 'admin') {
    // Vérification en dur (pas en BDD) — identifiants fixes de l'admin
    if ($email === 'admin@hadj.dz' && $password === 'admin1234') {
        // On crée la session admin
        $_SESSION['user'] = [
            'id'    => 0,
            'role'  => 'admin',
            'email' => $email,
            'nom'   => 'Administrateur'
        ];
        json_response(['success' => true, 'redirect' => 'admin/tirages.html', 'role' => 'admin']);
    }
    json_response(['success' => false, 'error' => 'Identifiants administrateur incorrects.', 'code' => 'INVALID'], 401);
}

// ── Connexion utilisateur normal ──
$conn = get_db();

// On cherche l'utilisateur par email dans la BDD
$stmt = mysqli_prepare($conn, 'SELECT * FROM utilisateurs WHERE email = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

// Vérifie que l'utilisateur existe et que le mot de passe est correct
if (!$user || !password_verify($password, $user['password_hash'])) {
    json_response(['success' => false, 'error' => 'Email ou mot de passe incorrect.', 'code' => 'INVALID'], 401);
}

// Compte en attente de validation admin
if ($user['statut'] === 'attente') {
    json_response(['success' => false, 'error' => 'Votre compte est en attente de validation par un administrateur.', 'code' => 'PENDING'], 403);
}

// Compte bloqué
if ($user['statut'] === 'bloque') {
    json_response(['success' => false, 'error' => 'Votre compte a été bloqué. Contactez l\'administrateur.', 'code' => 'BLOCKED'], 403);
}

// Tout est OK → on crée la session utilisateur (sans le mot de passe hashé)
unset($user['password_hash']); // on ne stocke jamais le mot de passe dans la session
$user['role']     = 'user';
$_SESSION['user'] = $user; // on sauvegarde l'utilisateur dans la session

json_response(['success' => true, 'redirect' => 'user/actualites.html', 'role' => 'user']);

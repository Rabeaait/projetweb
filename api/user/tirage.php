<?php
// Tirage actif et participations d'un utilisateur
// GET  → retourne le tirage actif + l'historique des participations
// POST → inscrit l'utilisateur au tirage actif
require_once __DIR__ . '/../config.php';

// Vérifie que c'est un utilisateur connecté (pas un admin)
$current = require_auth('user');
$conn    = get_db();
$user_id = (int) $current['id'];

// ── GET : données du tirage actif ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // On cherche le tirage en cours (pas terminé), le plus récent
    $res    = mysqli_query($conn,
        "SELECT * FROM tirages WHERE statut != 'termine' ORDER BY date_creation DESC LIMIT 1"
    );
    $tirage = mysqli_fetch_assoc($res);
    mysqli_free_result($res);

    // Pas de tirage actif → on retourne des valeurs vides
    if (!$tirage) {
        json_response(['success' => true, 'tirage' => null, 'participe' => false, 'participations' => []]);
    }

    $tirage_id = (int) $tirage['id'];

    // On compte le nombre total de participants à ce tirage
    $stmt = mysqli_prepare($conn,
        'SELECT COUNT(*) AS nb FROM participations WHERE tirage_id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'i', $tirage_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $tirage['nb_participants'] = (int) mysqli_fetch_assoc($r)['nb']; // on ajoute le nombre au tirage
    mysqli_stmt_close($stmt);

    // On vérifie si cet utilisateur est déjà inscrit au tirage actif
    $stmt = mysqli_prepare($conn,
        'SELECT * FROM participations WHERE user_id = ? AND tirage_id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tirage_id);
    mysqli_stmt_execute($stmt);
    $r      = mysqli_stmt_get_result($stmt);
    $myPart = mysqli_fetch_assoc($r); // null si pas inscrit
    mysqli_stmt_close($stmt);

    // On récupère l'historique des anciens tirages auxquels l'utilisateur a participé
    $stmt = mysqli_prepare($conn,
        'SELECT p.*, t.nom AS tirage_nom, t.date_tirage, t.statut AS tirage_statut
         FROM participations p
         JOIN tirages t ON t.id = p.tirage_id
         WHERE p.user_id = ? AND p.tirage_id != ?
         ORDER BY p.date_inscription DESC'
    );
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tirage_id);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $hist = [];
    while ($row = mysqli_fetch_assoc($res)) $hist[] = $row;
    mysqli_stmt_close($stmt);

    json_response([
        'success'        => true,
        'tirage'         => $tirage,
        'participe'      => (bool) $myPart, // true si inscrit, false sinon
        'participation'  => $myPart ?: null,
        'participations' => $hist
    ]);
}

// ── POST : s'inscrire au tirage actif ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // On récupère le tirage en cours
    $res    = mysqli_query($conn,
        "SELECT * FROM tirages WHERE statut != 'termine' ORDER BY date_creation DESC LIMIT 1"
    );
    $tirage = mysqli_fetch_assoc($res);
    mysqli_free_result($res);

    if (!$tirage) {
        json_response(['success' => false, 'error' => 'Aucun tirage actif pour le moment.'], 400);
    }

    // Vérification que la période d'inscription est ouverte
    $today   = new DateTime(); // date d'aujourd'hui
    $ouv     = new DateTime($tirage['date_ouverture']); // date d'ouverture
    $clot    = new DateTime($tirage['date_cloture']); // date de clôture

    if ($today < $ouv || $today > $clot) {
        json_response(['success' => false, 'error' => 'Les inscriptions sont actuellement fermées.'], 400);
    }

    // Vérification de l'âge (doit avoir au moins 18 ans)
    if (calculate_age($current['ddn']) < 18) {
        json_response(['success' => false, 'error' => 'Vous devez avoir au moins 18 ans pour participer.'], 400);
    }

    $tirage_id = (int) $tirage['id'];

    // Vérification : pas déjà inscrit à ce tirage
    $stmt = mysqli_prepare($conn,
        'SELECT id FROM participations WHERE user_id = ? AND tirage_id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tirage_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $already = mysqli_stmt_num_rows($stmt) > 0; // true si déjà inscrit
    mysqli_stmt_close($stmt);

    if ($already) {
        json_response(['success' => false, 'error' => 'Vous êtes déjà inscrit à ce tirage.'], 409);
    }

    // Inscription : on ajoute une ligne dans la table participations
    $stmt = mysqli_prepare($conn,
        'INSERT INTO participations (user_id, tirage_id) VALUES (?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tirage_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$ok) {
        json_response(['success' => false, 'error' => "Erreur lors de l'inscription."], 500);
    }

    // On envoie une notification de confirmation à l'utilisateur
    $titre = 'Inscription confirmée';
    $msg   = 'Votre inscription au ' . $tirage['nom'] . ' a été enregistrée avec succès.';
    $stmt  = mysqli_prepare($conn,
        'INSERT INTO notifications (user_id, titre, message) VALUES (?, ?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'iss', $user_id, $titre, $msg);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    json_response(['success' => true, 'message' => 'Inscription confirmée ! Vous participez au ' . $tirage['nom'] . '.']);
}

json_response(['success' => false, 'error' => 'Méthode non autorisée.'], 405);

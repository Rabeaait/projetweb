<?php
// Notifications d'un utilisateur
// GET  → retourne la liste des notifications
// POST → marque toutes les notifications comme lues
require_once __DIR__ . '/../config.php';

// Vérifie que c'est un utilisateur connecté
$current = require_auth('user');
$conn    = get_db();
$user_id = (int) $current['id'];

// ── GET : récupérer toutes les notifications de l'utilisateur ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // On récupère les notifications triées du plus récent au plus ancien
    $stmt = mysqli_prepare($conn,
        'SELECT id, titre, message, lu, date_creation
         FROM notifications WHERE user_id = ?
         ORDER BY date_creation DESC'
    );
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $res   = mysqli_stmt_get_result($stmt);
    $notifs = [];
    while ($row = mysqli_fetch_assoc($res)) $notifs[] = $row; // on remplit le tableau
    mysqli_stmt_close($stmt);

    // On compte les notifications non lues (lu = 0)
    $unread = count(array_filter($notifs, fn($n) => !$n['lu']));

    json_response(['success' => true, 'notifications' => $notifs, 'unread_count' => $unread]);
}

// ── POST : marquer toutes les notifications comme lues ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // On met lu = 1 pour toutes les notifications de cet utilisateur
    $stmt = mysqli_prepare($conn,
        'UPDATE notifications SET lu = 1 WHERE user_id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    json_response(['success' => true]);
}

json_response(['success' => false, 'error' => 'Méthode non autorisée.'], 405);

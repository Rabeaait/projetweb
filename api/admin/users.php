<?php
// Gestion des utilisateurs (admin)
// GET  → liste tous les utilisateurs (avec recherche optionnelle)
// POST → bloquer / débloquer / supprimer un utilisateur
require_once __DIR__ . '/../config.php';

// Vérifie que c'est bien un admin
require_auth('admin');
$conn = get_db();

// ── GET : liste des utilisateurs ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $search = trim($_GET['search'] ?? ''); // mot clé de recherche (optionnel)

    if ($search !== '') {
        // Recherche par nom, prénom, email ou NIN
        $like = '%' . $search . '%'; // %mot% = contient "mot" n'importe où
        $stmt = mysqli_prepare($conn,
            'SELECT id, nin, nom, prenom, ddn, email, telephone,
                    statut, date_soumission, date_validation
             FROM utilisateurs
             WHERE nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR nin LIKE ?
             ORDER BY date_soumission DESC'
        );
        mysqli_stmt_bind_param($stmt, 'ssss', $like, $like, $like, $like);
    } else {
        // Pas de recherche → on retourne tous les utilisateurs
        $stmt = mysqli_prepare($conn,
            'SELECT id, nin, nom, prenom, ddn, email, telephone,
                    statut, date_soumission, date_validation
             FROM utilisateurs
             ORDER BY date_soumission DESC'
        );
    }

    mysqli_stmt_execute($stmt);
    $res   = mysqli_stmt_get_result($stmt);
    $users = [];
    while ($row = mysqli_fetch_assoc($res)) $users[] = $row; // on remplit le tableau
    mysqli_stmt_close($stmt);

    // Statistiques : on compte par statut
    $stats = [
        'total'   => count($users),
        'actifs'  => count(array_filter($users, fn($u) => $u['statut'] === 'actif')),
        'attente' => count(array_filter($users, fn($u) => $u['statut'] === 'attente')),
        'bloques' => count(array_filter($users, fn($u) => $u['statut'] === 'bloque'))
    ];

    json_response(['success' => true, 'users' => $users, 'stats' => $stats]);
}

// ── POST : actions sur un utilisateur ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data   = get_post_data();
    $action = trim($data['action'] ?? ''); // 'block', 'unblock' ou 'delete'
    $id     = (int) ($data['id']   ?? 0); // id de l'utilisateur ciblé

    if (!$id) {
        json_response(['success' => false, 'error' => "L'identifiant est requis."], 400);
    }

    // ── Bloquer : passe le statut à 'bloque' ──
    if ($action === 'block') {
        $stmt = mysqli_prepare($conn,
            "UPDATE utilisateurs SET statut = 'bloque' WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        json_response(['success' => true, 'message' => 'Utilisateur bloqué.']);
    }

    // ── Débloquer : passe le statut à 'actif' ──
    if ($action === 'unblock') {
        $stmt = mysqli_prepare($conn,
            "UPDATE utilisateurs SET statut = 'actif' WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        json_response(['success' => true, 'message' => 'Utilisateur débloqué.']);
    }

    // ── Supprimer définitivement le compte ──
    if ($action === 'delete') {
        $stmt = mysqli_prepare($conn, 'DELETE FROM utilisateurs WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        json_response(['success' => true, 'message' => 'Compte supprimé définitivement.']);
    }

    json_response(['success' => false, 'error' => 'Action non reconnue.'], 400);
}

json_response(['success' => false, 'error' => 'Méthode non autorisée.'], 405);

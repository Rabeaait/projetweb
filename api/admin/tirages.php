<?php
// Gestion des tirages (admin)
// GET  → retourne le tirage actif + l'historique
// POST → créer un tirage (action=create) ou lancer le tirage (action=launch)
require_once __DIR__ . '/../config.php';

// Vérifie que c'est bien un admin
require_auth('admin');
$conn = get_db();

// ── GET : tirage actif + historique ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // On cherche le tirage en cours (pas terminé), le plus récent
    $res   = mysqli_query($conn,
        "SELECT * FROM tirages WHERE statut != 'termine' ORDER BY date_creation DESC LIMIT 1"
    );
    $actif = mysqli_fetch_assoc($res);
    mysqli_free_result($res);

    // Si un tirage actif existe, on compte ses participants
    if ($actif) {
        $stmt = mysqli_prepare($conn,
            'SELECT COUNT(*) AS nb FROM participations WHERE tirage_id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'i', $actif['id']);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        $actif['nb_participants'] = (int) mysqli_fetch_assoc($r)['nb'];
        mysqli_stmt_close($stmt);
    }

    // Historique des tirages terminés avec leurs statistiques
    $res     = mysqli_query($conn,
        "SELECT t.*,
                (SELECT COUNT(*) FROM participations p WHERE p.tirage_id = t.id) AS nb_participants,
                (SELECT COUNT(*) FROM participations p WHERE p.tirage_id = t.id AND p.resultat = 'gagnant') AS nb_gagnants_reels
         FROM tirages t
         WHERE t.statut = 'termine'
         ORDER BY t.date_tirage DESC"
    );
    $historique = [];
    while ($row = mysqli_fetch_assoc($res)) $historique[] = $row;
    mysqli_free_result($res);

    // Nombre de demandes d'inscription en attente
    $res     = mysqli_query($conn, "SELECT COUNT(*) AS nb FROM utilisateurs WHERE statut = 'attente'");
    $pending = (int) mysqli_fetch_assoc($res)['nb'];
    mysqli_free_result($res);

    json_response([
        'success'      => true,
        'actif'        => $actif,
        'historique'   => $historique,
        'pendingCount' => $pending
    ]);
}

// ── POST : créer ou lancer un tirage ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data   = get_post_data();
    $action = trim($data['action'] ?? ''); // 'create' ou 'launch'

    // ── Créer un nouveau tirage ──
    if ($action === 'create') {

        // On récupère les données envoyées
        $nb_gagnants    = (int)   ($data['nb_gagnants']    ?? 0);
        $date_tirage    = trim($data['date_tirage']    ?? '');
        $date_ouverture = trim($data['date_ouverture'] ?? '');
        $date_cloture   = trim($data['date_cloture']   ?? '');

        $errors = [];

        // Validation des données
        if ($nb_gagnants < 1)
            $errors[] = 'Le nombre de gagnants est requis (minimum 1).';
        if (!$date_tirage || !$date_ouverture || !$date_cloture)
            $errors[] = 'Toutes les dates sont obligatoires.';
        if ($date_ouverture && $date_cloture && $date_ouverture >= $date_cloture)
            $errors[] = "La date d'ouverture doit être antérieure à la clôture.";
        if ($date_cloture && $date_tirage && $date_cloture >= $date_tirage)
            $errors[] = 'La date de clôture doit être antérieure à la date du tirage.';

        // S'il y a des erreurs → on les renvoie
        if (!empty($errors)) {
            json_response(['success' => false, 'errors' => $errors], 422);
        }

        // On génère le nom automatiquement : "Tirage Hadj 2026"
        $annee = (int) date('Y', strtotime($date_tirage));
        $nom   = 'Tirage Hadj ' . $annee;

        // On insère le tirage dans la BDD
        $stmt = mysqli_prepare($conn,
            'INSERT INTO tirages (nom, annee, date_tirage, date_ouverture, date_cloture, nb_gagnants)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'siissi',
            $nom, $annee, $date_tirage, $date_ouverture, $date_cloture, $nb_gagnants
        );
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if (!$ok) {
            json_response(['success' => false, 'error' => 'Erreur lors de la création.'], 500);
        }

        json_response(['success' => true, 'message' => 'Tirage "' . $nom . '" créé avec succès.']);
    }

    // ── Lancer le tirage au sort ──
    if ($action === 'launch') {

        // On récupère le tirage actif
        $res    = mysqli_query($conn,
            "SELECT * FROM tirages WHERE statut != 'termine' ORDER BY date_creation DESC LIMIT 1"
        );
        $tirage = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        if (!$tirage) {
            json_response(['success' => false, 'error' => 'Aucun tirage actif à lancer.'], 400);
        }

        $tirage_id   = (int) $tirage['id'];
        $nb_gagnants = (int) $tirage['nb_gagnants'];

        // On récupère tous les participants dans un ordre ALÉATOIRE
        $stmt = mysqli_prepare($conn,
            'SELECT user_id FROM participations WHERE tirage_id = ? ORDER BY RAND()'
        );
        mysqli_stmt_bind_param($stmt, 'i', $tirage_id);
        mysqli_stmt_execute($stmt);
        $res  = mysqli_stmt_get_result($stmt);
        $all  = [];
        while ($row = mysqli_fetch_assoc($res)) $all[] = (int) $row['user_id'];
        mysqli_stmt_close($stmt);

        if (empty($all)) {
            json_response(['success' => false, 'error' => 'Aucun participant inscrit à ce tirage.'], 400);
        }

        // Les $nb_gagnants premiers (après mélange aléatoire) sont gagnants
        // array_flip permet de vérifier rapidement avec isset() si un user est gagnant
        $winners_set     = array_flip(array_slice($all, 0, $nb_gagnants));
        $nb_selectionnes = count($winners_set);

        // Pour chaque participant : on enregistre son résultat et on envoie une notification
        foreach ($all as $uid) {
            $resultat = isset($winners_set[$uid]) ? 'gagnant' : 'perdu';

            // Mise à jour du résultat dans la table participations
            $stmt = mysqli_prepare($conn,
                'UPDATE participations SET resultat = ? WHERE user_id = ? AND tirage_id = ?'
            );
            mysqli_stmt_bind_param($stmt, 'sii', $resultat, $uid, $tirage_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Notification personnalisée selon le résultat
            $titre   = isset($winners_set[$uid]) ? '🏆 Félicitations !' : 'Résultats du tirage';
            $message = isset($winners_set[$uid])
                ? 'Vous avez été sélectionné pour le ' . $tirage['nom'] . '. Félicitations !'
                : 'Vous n\'avez pas été sélectionné pour le ' . $tirage['nom']
                  . '. Vos chances augmentent au prochain tirage.';

            // Insertion de la notification en BDD
            $stmt = mysqli_prepare($conn,
                'INSERT INTO notifications (user_id, titre, message) VALUES (?, ?, ?)'
            );
            mysqli_stmt_bind_param($stmt, 'iss', $uid, $titre, $message);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // On marque le tirage comme terminé
        $stmt = mysqli_prepare($conn,
            "UPDATE tirages SET statut = 'termine' WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'i', $tirage_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        json_response([
            'success'   => true,
            'message'   => 'Tirage lancé avec succès.',
            'gagnants'  => $nb_selectionnes,
            'total'     => count($all)
        ]);
    }

    json_response(['success' => false, 'error' => 'Action non reconnue.'], 400);
}

json_response(['success' => false, 'error' => 'Méthode non autorisée.'], 405);

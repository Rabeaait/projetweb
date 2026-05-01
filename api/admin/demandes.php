<?php

require_once __DIR__ . '/../config.php';


require_auth('admin');

// On ouvre la connexion à la base de données
$conn = get_db();


// ── Si c'est une requête GET : on retourne la liste des demandes en attente 
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // On récupère tous les utilisateurs avec statut 'attente', triés du plus ancien au plus récent
    $res = mysqli_query($conn,
        "SELECT id, nin, nom, prenom, ddn, nom_pere, nom_grand_pere,
                prenom_mere, nom_mere, adresse, email, telephone, date_soumission
         FROM utilisateurs
         WHERE statut = 'attente'
         ORDER BY date_soumission ASC"
    );

    $demandes = []; // pour stocker les résultats
    while ($row = mysqli_fetch_assoc($res)) $demandes[] = $row; 
    mysqli_free_result($res); 

    // On renvoie les demandes en JSON
    json_response(['success' => true, 'demandes' => $demandes, 'count' => count($demandes)]);
}


// Si c'est une requête POST : on effectue une action sur une demande 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data   = get_post_data();
    $action = trim($data['action'] ?? ''); // l'action a faire 
    $id     = (int) ($data['id'] ?? 0);   
    // Si pas d'id → erreur
    if (!$id) {
        json_response(['success' => false, 'error' => "L'identifiant de l'utilisateur est requis."], 400);
    }

    // ── Valider : on passe le statut à 'actif' ──
    if ($action === 'validate') {
        $now  = date('Y-m-d H:i:s'); // date et heure actuelles
        $stmt = mysqli_prepare($conn,
            "UPDATE utilisateurs SET statut = 'actif', date_validation = ? WHERE id = ? AND statut = 'attente'"
        );
        mysqli_stmt_bind_param($stmt, 'si', $now, $id); // 's' = string, 'i' = integer
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt); // combien de lignes ont été modifiées
        mysqli_stmt_close($stmt);

        if (!$affected) {
            json_response(['success' => false, 'error' => 'Demande introuvable ou déjà traitée.'], 404);
        }
        json_response(['success' => true, 'message' => 'Compte validé et activé.']);
    }

    // ── Ignorer : on supprime la demande ──
    if ($action === 'ignore') {
        $stmt = mysqli_prepare($conn,
            "DELETE FROM utilisateurs WHERE id = ? AND statut = 'attente'"
        );
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        json_response(['success' => true, 'message' => 'Demande supprimée.']);
    }

    // ── Bloquer : on passe le statut à 'bloque' ──
    if ($action === 'block') {
        $stmt = mysqli_prepare($conn,
            "UPDATE utilisateurs SET statut = 'bloque' WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        json_response(['success' => true, 'message' => 'Utilisateur bloqué.']);
    }

    // Si l'action n'est pas reconnue → erreur
    json_response(['success' => false, 'error' => 'Action non reconnue.'], 400);
}

// Si la méthode HTTP n'est ni GET ni POST → erreur
json_response(['success' => false, 'error' => 'Méthode non autorisée.'], 405);

<?php
// Inscription d'un nouvel utilisateur
// Le frontend envoie les infos du formulaire en POST, on les vérifie et on les enregistre en BDD
require_once __DIR__ . '/config.php';

// On accepte seulement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Méthode non autorisée.'], 405);
}

// On récupère toutes les données envoyées par le formulaire
$data = get_post_data();

$nin            = trim($data['nin']            ?? '');
$nom            = trim($data['nom']            ?? '');
$prenom         = trim($data['prenom']         ?? '');
$ddn            = trim($data['ddn']            ?? '');
$nom_pere       = trim($data['nom_pere']       ?? '');
$nom_grand_pere = trim($data['nom_grand_pere'] ?? '');
$prenom_mere    = trim($data['prenom_mere']    ?? '');
$nom_mere       = trim($data['nom_mere']       ?? '');
$adresse        = trim($data['adresse']        ?? '');
$email          = strtolower(trim($data['email'] ?? ''));
$telephone      = trim($data['telephone']      ?? '');
$password       = $data['password']            ?? '';

// Tableau pour collecter les erreurs de validation
$errors = [];

// Vérification de chaque champ
if (!validate_nin($nin))
    $errors['nin'] = 'Le NIN doit contenir exactement 18 chiffres.';

if (!validate_min_len($nom, 2))
    $errors['nom'] = 'Le nom est obligatoire (min. 2 caractères).';

if (!validate_min_len($prenom, 2))
    $errors['prenom'] = 'Le prénom est obligatoire.';

if (!$ddn)
    $errors['ddn'] = 'La date de naissance est obligatoire.';
elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ddn)) // format attendu : AAAA-MM-JJ
    $errors['ddn'] = 'Format de date invalide.';
elseif (calculate_age($ddn) < 18) // doit avoir au moins 18 ans
    $errors['ddn'] = 'Vous devez avoir au moins 18 ans.';

if (!validate_min_len($nom_pere, 2))
    $errors['nom_pere'] = 'Le prénom du père est obligatoire.';

if (!validate_min_len($nom_grand_pere, 2))
    $errors['nom_grand_pere'] = 'Le prénom du grand-père est obligatoire.';

if (!validate_min_len($prenom_mere, 2))
    $errors['prenom_mere'] = 'Le prénom de la mère est obligatoire.';

if (!validate_min_len($nom_mere, 2))
    $errors['nom_mere'] = 'Le nom de famille de la mère est obligatoire.';

if (!validate_min_len($adresse, 5))
    $errors['adresse'] = "L'adresse est obligatoire.";

if (!validate_email($email))
    $errors['email'] = 'Adresse email non valide.';

if (!validate_phone($telephone))
    $errors['telephone'] = 'Numéro invalide. Format attendu : 05/06/07XXXXXXXX.';

if (!validate_password($password))
    $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères.';

// S'il y a des erreurs → on les renvoie au frontend et on s'arrête
if (!empty($errors)) {
    json_response(['success' => false, 'errors' => $errors], 422);
}

// Vérification que le NIN et l'email ne sont pas déjà utilisés
$conn = get_db();

$stmt = mysqli_prepare($conn, 'SELECT id FROM utilisateurs WHERE nin = ? OR email = ?');
mysqli_stmt_bind_param($stmt, 'ss', $nin, $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
$exists = mysqli_stmt_num_rows($stmt) > 0; // true si un utilisateur existe déjà
mysqli_stmt_close($stmt);

if ($exists) {
    json_response(['success' => false, 'error' => 'Ce NIN ou cet email est déjà utilisé.'], 409);
}

// On hash le mot de passe avant de le stocker (on ne stocke jamais un mot de passe en clair)
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Insertion de l'utilisateur dans la BDD avec statut 'attente'
$stmt = mysqli_prepare($conn,
    'INSERT INTO utilisateurs
       (nin, nom, prenom, ddn, nom_pere, nom_grand_pere, prenom_mere, nom_mere, adresse, email, telephone, password_hash)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
mysqli_stmt_bind_param($stmt, 'ssssssssssss',
    $nin, $nom, $prenom, $ddn, $nom_pere, $nom_grand_pere,
    $prenom_mere, $nom_mere, $adresse, $email, $telephone, $password_hash
);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    json_response(['success' => false, 'error' => "Erreur lors de l'enregistrement."], 500);
}

// Succès : le compte est créé mais en attente de validation admin
json_response(['success' => true, 'message' => 'Demande enregistrée. En attente de validation par un administrateur.']);

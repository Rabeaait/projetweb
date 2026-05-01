<?php
// Script à exécuter UNE SEULE FOIS pour insérer l'utilisateur de démonstration.
// Accéder à : http://localhost/hadj/api/seed.php  (ou l'URL de votre projet)

require_once __DIR__ . '/config.php';

$conn = get_db();

$users = [
    [
        'nin'            => '200300987654321',
        'nom'            => 'Djelouah',
        'prenom'         => 'Manel',
        'ddn'            => '2003-07-22',
        'nom_pere'       => 'Rachid',
        'nom_grand_pere' => 'Salah',
        'prenom_mere'    => 'Nadia',
        'nom_mere'       => 'Benali',
        'adresse'        => '7 rue des Jasmins, Constantine',
        'email'          => 'maneldjelouah@gmail.com',
        'telephone'      => '0770334455',
        'password'       => 'manel1234',
        'statut'         => 'actif',
    ],
];

$inserted = 0;
$skipped  = 0;

foreach ($users as $u) {
    // Vérifier si l'email ou le NIN existe déjà
    $check = mysqli_prepare($conn, 'SELECT id FROM utilisateurs WHERE email = ? OR nin = ?');
    mysqli_stmt_bind_param($check, 'ss', $u['email'], $u['nin']);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);
    $exists = mysqli_stmt_num_rows($check) > 0;
    mysqli_stmt_close($check);

    if ($exists) { $skipped++; continue; }

    $hash = password_hash($u['password'], PASSWORD_BCRYPT);

    $stmt = mysqli_prepare($conn,
        'INSERT INTO utilisateurs
           (nin, nom, prenom, ddn, nom_pere, nom_grand_pere, prenom_mere, nom_mere,
            adresse, email, telephone, password_hash, statut, date_validation)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    mysqli_stmt_bind_param($stmt, 'sssssssssssss',
        $u['nin'], $u['nom'], $u['prenom'], $u['ddn'],
        $u['nom_pere'], $u['nom_grand_pere'], $u['prenom_mere'], $u['nom_mere'],
        $u['adresse'], $u['email'], $u['telephone'], $hash, $u['statut']
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $inserted++;
}

echo "<h3>Seed terminé</h3>";
echo "<p>Insérés : <strong>$inserted</strong> | Déjà existants : <strong>$skipped</strong></p>";
echo "<hr><h4>Identifiants utilisateur</h4>";
echo "<p>Email : <code>maneldjelouah@gmail.com</code><br>Mot de passe : <code>manel1234</code></p>";
echo "<hr><h4>Identifiants administrateur</h4>";
echo "<p>Email : <code>admin@hadj.dz</code><br>Mot de passe : <code>admin1234</code></p>";

<?php

// Infos de connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hadj_lottery');

// Ouvre la connexion à la BDD (une seule fois grâce à static)
function get_db() {
    static $conn = null;
    if ($conn === null) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn) {
            error_log('DB connexion échouée : ' . mysqli_connect_error());
            json_response(['success' => false, 'error' => 'Erreur serveur (base de données).'], 500);
        }
        // Supporte les accents, l'arabe et les emojis
        mysqli_set_charset($conn, 'utf8mb4');
    }
    return $conn;
}

// Envoie une réponse JSON au frontend et arrête le script
function json_response($data, $code = 200) {
    http_response_code($code); // envoie le code HTTP (200, 404, 422...)
    header('Content-Type: application/json; charset=utf-8'); // dit au navigateur que c'est du JSON
    header('Cache-Control: no-store'); // pas de mise en cache
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); // convertit le tableau PHP en JSON
    exit; // arrête le script
}

// Démarre la session PHP de façon sécurisée
function start_session() {
    if (session_status() === PHP_SESSION_NONE) { // seulement si pas déjà démarrée
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict']); // cookie sécurisé
        session_start();
    }
}

// Vérifie que l'utilisateur est connecté (et a le bon rôle si précisé)
function require_auth($role = null) {
    start_session();
    if (empty($_SESSION['user'])) {
        // pas connecté → erreur 401
        json_response(['success' => false, 'error' => 'Non authentifié.', 'code' => 'UNAUTHENTICATED'], 401);
    }
    if ($role !== null && $_SESSION['user']['role'] !== $role) {
        // connecté mais mauvais rôle → erreur 403
        json_response(['success' => false, 'error' => 'Accès interdit.', 'code' => 'FORBIDDEN'], 403);
    }
    return $_SESSION['user']; // retourne les infos de l'utilisateur connecté
}

// Récupère les données envoyées en POST (JSON ou formulaire classique)
function get_post_data() {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($ct, 'application/json') !== false) {
        $raw  = file_get_contents('php://input'); // lit le corps de la requête
        $data = json_decode($raw, true); // convertit le JSON en tableau PHP
        return is_array($data) ? $data : [];
    }
    return $_POST;
}

// NIN : exactement 18 chiffres
function validate_nin($v) {
    return (bool) preg_match('/^\d{18}$/', (string) $v);
}

// Email valide
function validate_email($v) {
    return (bool) filter_var($v, FILTER_VALIDATE_EMAIL);
}

// Téléphone algérien : commence par 05, 06 ou 07 + 8 chiffres
function validate_phone($v) {
    return (bool) preg_match('/^0[567]\d{8}$/', preg_replace('/\s+/', '', (string) $v));
}

// Vérifie que le texte a au moins $n caractères
function validate_min_len($v, $n) {
    return mb_strlen(trim((string) $v), 'UTF-8') >= $n;
}

// Mot de passe : minimum 8 caractères
function validate_password($v) {
    return strlen((string) $v) >= 8;
}

// Calcule l'âge à partir d'une date de naissance
function calculate_age($ddn) {
    if (!$ddn) return 0;
    try {
        $birth = new DateTime($ddn);
        $today = new DateTime();
        return (int) $today->diff($birth)->y; // différence en années
    } catch (Exception $e) {
        return 0;
    }
}

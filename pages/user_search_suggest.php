<?php
session_start();
require_once __DIR__ . '/../data_structures/user_search.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'This endpoint only accepts POST requests.']);
    exit();
}

$prefix = trim($_POST['prefix'] ?? '');
if (empty($prefix)) {
    echo json_encode([]);
    exit();
}

$exclude_self = isset($_POST['exclude_self']) && $_POST['exclude_self'] == '1';
$current_user_email = $_SESSION['email'] ?? null;

$allUsers = get_all_users_flat();

if ($exclude_self && $current_user_email) {
    $filteredUsers = [];
    foreach ($allUsers as $user) {
        if ($user['email'] !== $current_user_email) {
            $filteredUsers[] = $user;
        }
    }
    $allUsers = $filteredUsers;
}

$suggestions = search_users_by_query($prefix, $allUsers, 10);

$results = [];
foreach ($suggestions as $user) {
    $results[] = [
        'name' => $user['username'],
        'email' => $user['email']
    ];
}

header('Content-Type: application/json');
echo json_encode($results);

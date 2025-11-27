<?php
require_once __DIR__ . '/bucketed_user_store.php';

function get_all_users_flat(): array {
    $users = load_users();
    $flatUsers = [];

    foreach ($users as $bucket) {
        foreach ($bucket as $user) {
            if (!empty($user['username']) && !empty($user['email'])) {
                $flatUsers[] = [
                    'username' => $user['username'],
                    'email' => $user['email']
                ];
            }
        }
    }

    return $flatUsers;
}

function search_users_by_query(string $query, array $users, int $limit = 10): array {
    $query = strtolower(trim($query));
    if ($query === '') {
        return [];
    }

    $results = [];
    foreach ($users as $user) {
        $username = strtolower($user['username']);
        $email = strtolower($user['email']);

        if (strpos($username, $query) !== false || strpos($email, $query) !== false) {
            $results[] = $user;
        }

        if (count($results) >= $limit) {
            break;
        }
    }

    return $results;
}

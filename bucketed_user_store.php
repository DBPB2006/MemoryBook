<?php
// data_structures/bucketed_user_store.php
// Email-based bucketed hash map user storage in JSON

function get_users_json_path() {
    return __DIR__ . '/../data/users.json';
}

function load_users() {
    $file = get_users_json_path();
    if (!file_exists($file)) {
        // Initialize 26 buckets (A-Z)
        $buckets = [];
        foreach (range('A', 'Z') as $letter) {
            $buckets[$letter] = [];
        }
        return $buckets;
    }
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    // Ensure all buckets exist
    foreach (range('A', 'Z') as $letter) {
        if (!isset($data[$letter])) {
            $data[$letter] = [];
        }
    }
    return $data;
}

function save_users($users) {
    $file = get_users_json_path();
    file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
}

function get_bucket($email) {
    $first = strtoupper($email[0]);
    return ctype_alpha($first) ? $first : 'A'; // Default to 'A' if not a letter
}

function user_exists($email) {
    $users = load_users();
    $bucket = get_bucket($email);
    return isset($users[$bucket][$email]);
}

function add_user($email, $details) {
    $users = load_users();
    $bucket = get_bucket($email);
    $users[$bucket][$email] = $details;
    save_users($users);
}

function get_user($email) {
    $users = load_users();
    $bucket = get_bucket($email);
    return $users[$bucket][$email] ?? null;
}

function username_exists($username) {
    $users = load_users();
    foreach ($users as $bucket) {
        foreach ($bucket as $user) {
            if (isset($user['username']) && strtolower($user['username']) === strtolower($username)) {
                return true;
            }
        }
    }
    return false;
} 
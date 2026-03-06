<?php

function buildNodesAndIndexMap($usersData) {
    $registeredUsers = [];
    foreach ($usersData as $bucket) {
        foreach ($bucket as $email => $user) {
            $registeredUsers[$email] = $user;
        }
    }
    $nodes = [];
    foreach ($registeredUsers as $email => $user) {
        if (!isset($nodes[$email])) {
            $nodes[$email] = [
                'name' => $user['name'] ?? $user['username'] ?? $email,
                'registered' => true
            ];
        }
        foreach ($user['friends'] as $f) {
            $fEmail = $f['email'];
            if (!isset($nodes[$fEmail])) {
                $nodes[$fEmail] = [
                    'name' => $f['name'] ?? $fEmail,
                    'registered' => isset($registeredUsers[$fEmail])
                ];
            }
        }
    }
    $emails = array_keys($nodes);
    $indexMap = [];
    foreach ($emails as $i => $email) {
        $indexMap[$email] = $i;
    }
    return [$nodes, $emails, $indexMap];
}

//  Build a directed friendship adjacency matrix (0/1).

function buildFriendshipMatrix($nodes, $indexMap, $registeredUsers) {
    $size = count($nodes);
    $matrix = [];
    for ($i = 0; $i < $size; $i++) {
        $matrix[$i] = array_fill(0, $size, 0);
    }
    foreach ($registeredUsers as $email => $user) {
        if (!isset($indexMap[$email])) continue;
        $i = $indexMap[$email];
        foreach ($user['friends'] as $friend) {
            $fEmail = $friend['email'];
            if (isset($indexMap[$fEmail])) {
                $j = $indexMap[$fEmail];
                $matrix[$i][$j] = 1;
            }
        }
    }
    return $matrix;
}

// Build an undirected memory frequency adjacency matrix (0,1,2,...).

function buildMemoryFrequencyMatrix($nodes, $indexMap, $memories) {
    $size = count($nodes);
    $matrix = [];
    for ($i = 0; $i < $size; $i++) {
        $matrix[$i] = array_fill(0, $size, 0);
    }
    foreach ($memories as $memory) {
        $owner = $memory['owner'];
        if (!isset($indexMap[$owner])) continue;
        $i = $indexMap[$owner];
        foreach ($memory['friends'] as $fEmail) {
            if (isset($indexMap[$fEmail])) {
                $j = $indexMap[$fEmail];
                $matrix[$i][$j]++;
                $matrix[$j][$i]++;
            }
        }
    }
    return $matrix;
}

/**
 * BFS-based friend suggestion up to level 2.
 * Returns an array of suggested friend emails.
 */
function getFriendSuggestions($friendshipMatrix, $indexMap, $emails, $startEmail, $friendGraph) {
    $suggestions = [];
    if (!isset($indexMap[$startEmail])) return $suggestions;
    $startIndex = $indexMap[$startEmail];
    $queue = [[$startIndex, 0]];
    $visited = [];
    $alreadyFriends = [$startIndex];
    if (isset($friendGraph[$startEmail])) {
        foreach ($friendGraph[$startEmail] as $fEmail) {
            if (isset($indexMap[$fEmail])) {
                $alreadyFriends[] = $indexMap[$fEmail];
            }
        }
    }
    while (count($queue) > 0) {
        list($node, $level) = array_shift($queue);
        if (in_array($node, $visited)) continue;
        $visited[] = $node;
        if ($level === 2 && !in_array($node, $alreadyFriends)) {
            $suggestions[] = $emails[$node];
        }
        if ($level < 2) {
            for ($i = 0; $i < count($friendshipMatrix[$node]); $i++) {
                if ($friendshipMatrix[$node][$i] === 1 && !in_array($i, $visited)) {
                    $queue[] = [$i, $level + 1];
                }
            }
        }
    }
    return $suggestions;
}

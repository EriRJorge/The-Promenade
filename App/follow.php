<?php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$followerId = getCurrentUserId();
$followedId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

if ($followerId === $followedId) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Cannot follow yourself']);
    exit;
}

$result = toggleFollow($followerId, $followedId);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'following' => $result]);
<?php
include 'session.php';

header('Content-Type: application/json');

$user_id = get_user_id();
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];

    // Удаление всех лайков для поста
    $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed (likes): ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("i", $post_id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Execute failed (likes): ' . $stmt->error]);
        exit();
    }

    // Удаление всех комментариев для поста
    $stmt = $conn->prepare("DELETE FROM comments WHERE post_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed (comments): ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("i", $post_id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Execute failed (comments): ' . $stmt->error]);
        exit();
    }

    // Удаление самого поста
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed (posts): ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("ii", $post_id, $user_id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Execute failed (posts): ' . $stmt->error]);
        exit();
    }

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete post']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>


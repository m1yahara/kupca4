<?php
include 'session.php';

header('Content-Type: application/json');

$user_id = get_user_id();
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Користувач не авторизований.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];

    // Перевіряємо, чи вже поставив користувач лайк на цей пост
    $stmt = $conn->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        // Додаємо лайк
        $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
    } else {
        // Видаляємо лайк
        $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
    }

    // Отримуємо оновлену кількість лайків
    $stmt = $conn->prepare("SELECT COUNT(*) as like_count FROM likes WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $like_count = $result->fetch_assoc()['like_count'];

    // Перевіряємо, чи поставив користувач лайк на пост після змін
    $stmt = $conn->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $user_liked = $stmt->get_result()->num_rows > 0;

    echo json_encode(['success' => true, 'like_count' => $like_count, 'user_liked' => $user_liked]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Невірний запит.']);
exit();
?>


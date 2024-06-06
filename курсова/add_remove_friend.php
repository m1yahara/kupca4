<?php
include 'session.php';

$user_id = get_user_id();
if (!$user_id) {
    header("Content-Type: application/json");
    echo json_encode(array("success" => false, "message" => "Користувач не авторизований."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['friend_id'])) {
    header("Content-Type: application/json");
    echo json_encode(array("success" => false, "message" => "Некоректний запит."));
    exit();
}

$friend_id = $_POST['friend_id'];

// Перевіряємо, чи існує користувач, якого намагаємося додати в друзі
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $friend_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Content-Type: application/json");
    echo json_encode(array("success" => false, "message" => "Користувача не знайдено."));
    exit();
}

// Перевіряємо, чи користувач вже є другом
$stmt = $conn->prepare("SELECT * FROM friends WHERE user_id = ? AND friend_id = ?");
$stmt->bind_param("ii", $user_id, $friend_id);
$stmt->execute();
$result = $stmt->get_result();

$is_friend = $result->num_rows > 0;

if ($is_friend) {
    // Видаляємо друга
    $stmt = $conn->prepare("DELETE FROM friends WHERE user_id = ? AND friend_id = ?");
    $stmt->bind_param("ii", $user_id, $friend_id);
    $stmt->execute();
    header("Content-Type: application/json");
    echo json_encode(array("success" => true, "message" => "Користувач видалений з друзів.", "is_friend" => false));
} else {
    // Додаємо друга
    $stmt = $conn->prepare("INSERT INTO friends (user_id, friend_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $friend_id);
    $stmt->execute();
    header("Content-Type: application/json");
    echo json_encode(array("success" => true, "message" => "Користувач доданий в друзі.", "is_friend" => true));
}

exit();
?>



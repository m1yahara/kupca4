<?php
include 'session.php';

$user_id = get_user_id();
if (!$user_id) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['receiver_id'])) {
    $receiver_id = $_GET['receiver_id'];

    $stmt = $conn->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY timestamp");
    $stmt->bind_param("iiii", $user_id, $receiver_id, $receiver_id, $user_id);
    $stmt->execute();
    $messages_result = $stmt->get_result();

    while ($message = $messages_result->fetch_assoc()) {
        echo '<div class="message">';
        echo '<p><strong>' . htmlspecialchars($message['sender_id'] == $user_id ? 'Ви' : 'Друг') . ':</strong> ' . htmlspecialchars($message['message']) . '</p>';
        echo '<small>' . $message['timestamp'] . '</small>';
        echo '</div>';
    }
}
?>

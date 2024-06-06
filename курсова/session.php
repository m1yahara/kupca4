<?php
session_start();
$conn = new mysqli('localhost', 'root', 'root', 'social_network');

function create_session($user_id) {
    global $conn;
    $session_id = session_id();
    $stmt = $conn->prepare("INSERT INTO user_sessions (session_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("si", $session_id, $user_id);
    $stmt->execute();
}

function destroy_session() {
    global $conn;
    $session_id = session_id();
    $stmt = $conn->prepare("DELETE FROM user_sessions WHERE session_id = ?");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time() - 3600);
}

function get_user_id() {
    global $conn;
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $session_id = session_id();
    $stmt = $conn->prepare("SELECT user_id FROM user_sessions WHERE session_id = ?");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $stmt->bind_result($user_id);
    if ($stmt->fetch()) {
        return $user_id;
    }
    return null;
}
?>
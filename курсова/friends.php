<?php
include 'session.php';

$user_id = get_user_id();
if (!$user_id) {
    header("Location: index.php");
    exit();
}

// Функція для отримання списку друзів користувача
function get_friends($conn, $user_id) {
    $stmt = $conn->prepare("SELECT users.id as friend_id, users.username as friend_name, users.profile_picture 
                            FROM friends 
                            JOIN users ON friends.friend_id = users.id
                            WHERE friends.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Функція для видалення друга
function remove_friend($conn, $user_id, $friend_id) {
    $stmt = $conn->prepare("DELETE FROM friends WHERE user_id = ? AND friend_id = ?");
    $stmt->bind_param("ii", $user_id, $friend_id);
    $stmt->execute();
    return $stmt->affected_rows > 0;
}

// Перевірка, чи була відправлена форма для видалення друга
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_friend'])) {
    $friend_id = $_POST['remove_friend'];
    remove_friend($conn, $user_id, $friend_id);
}

// Отримання списку друзів користувача
$friends = get_friends($conn, $user_id);

?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Друзі</title>
    <link rel="stylesheet" href="css/style_friends.css">
</head>
<body>
    <nav>
        <ul>
            <li><a href="home.php">Головна</a></li>
            <li><a href="profile.php">Мій профіль</a></li>
            <li><a href="users.php">Користувачі</a></li>
            <li><a href="messages.php">Повідомлення</a></li>
            <li><a href="logout.php">Вийти</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <h1>Список друзів</h1>
        <ul class="friends-list">
            <?php foreach ($friends as $friend): ?>
                <li class="friend-item">
                    <img src="<?php echo $friend['profile_picture']; ?>" alt="Фото профілю" class="profile-picture">
                    <a href="user_profile.php?user_id=<?php echo $friend['friend_id']; ?>" class="friend-name"><?php echo $friend['friend_name']; ?></a>
                    <form method="POST" class="remove-friend-form">
                        <input type="hidden" name="remove_friend" value="<?php echo $friend['friend_id']; ?>">
                        <button type="submit" class="remove-friend-button">Видалити друга</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="home.php" class="back-button">Назад</a>
    </div>
</body>
</html>





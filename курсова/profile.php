<?php
// Підключаємо файл сесії та файл для роботи з базою даних
include 'session.php';

// Отримуємо ID користувача з сесії
$user_id = get_user_id();
if (!$user_id) {
    // Якщо користувач не авторизований, перенаправляємо його на сторінку входу
    header("Location: index.php");
    exit();
}

// Запит до бази даних для отримання інформації про користувача
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Запит до бази даних для отримання постів користувача
$post_stmt = $conn->prepare("SELECT posts.*, 
    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as like_count,
    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) as user_liked
    FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$post_stmt->bind_param("ii", $user_id, $user_id);
$post_stmt->execute();
$posts_result = $post_stmt->get_result();


if (isset($_POST['delete_account'])) {
    // Видалення всіх записів про друзів користувача з таблиці friends
    $stmt_delete_friends = $conn->prepare("DELETE FROM friends WHERE user_id = ? OR friend_id = ?");
    $stmt_delete_friends->bind_param("ii", $user_id, $user_id);
    $stmt_delete_friends->execute();

    // Видалення коментарів користувача
    $stmt_delete_comments = $conn->prepare("DELETE FROM comments WHERE user_id = ?");
    $stmt_delete_comments->bind_param("i", $user_id);
    $stmt_delete_comments->execute();

    // Видалення лайків користувача
    $stmt_delete_likes = $conn->prepare("DELETE FROM likes WHERE user_id = ?");
    $stmt_delete_likes->bind_param("i", $user_id);
    $stmt_delete_likes->execute();

    // Видалення повідомлень користувача
    $stmt_delete_messages_sent = $conn->prepare("DELETE FROM messages WHERE sender_id = ?");
    $stmt_delete_messages_sent->bind_param("i", $user_id);
    $stmt_delete_messages_sent->execute();

    $stmt_delete_messages_received = $conn->prepare("DELETE FROM messages WHERE receiver_id = ?");
    $stmt_delete_messages_received->bind_param("i", $user_id);
    $stmt_delete_messages_received->execute();

    // Видалення постів користувача
    $stmt_delete_posts = $conn->prepare("DELETE FROM posts WHERE user_id = ?");
    $stmt_delete_posts->bind_param("i", $user_id);
    $stmt_delete_posts->execute();

    // Видалення користувача з таблиці users
    $stmt_delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt_delete_user->bind_param("i", $user_id);
    if ($stmt_delete_user->execute()) {
        // Успішне видалення користувача, знищуємо сесію та перенаправляємо на сторінку входу
        session_destroy();
        header("Location: index.php");
        exit();
    } else {
        echo "Помилка при видаленні аккаунта.";
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профіль</title>
    <link rel="stylesheet" type="text/css" href="css/style_profile.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Обробка лайків
            $('.like-button').click(function(e) {
                e.preventDefault();
                var button = $(this);
                var post_id = button.data('post-id');

                $.ajax({
                    url: 'like_post.php',
                    type: 'POST',
                    data: { post_id: post_id },
                    success: function(response) {
                        if (response.success) {
                            // Оновлюємо текст на кнопці в залежності від результату
                            button.text(response.user_liked ? 'Забрати лайк' : 'Лайкнути');
                            button.closest('.post').find('.like-count').text('Лайки: ' + response.like_count);
                        } else {
                            alert('Помилка при обробці запиту.');
                        }
                    },
                    error: function() {
                        alert('Помилка при відправленні запиту.');
                    }
                });
            });
            // Обробка видалення постів
            $('.delete-post').click(function(e) {
                e.preventDefault();
                if (!confirm('Ви впевнені, що хочете видалити цей пост?')) {
                    return;
                }
                var button = $(this);
                var post_id = button.data('post-id');

                $.ajax({
                    url: 'delete_post.php',
                    type: 'POST',
                    data: { post_id: post_id },
                    success: function(response) {
                        if (response.success) {
                            $('#post-' + post_id).remove();
                        } else {
                            alert('Помилка при видаленні поста.');
                        }
                    },
                    error: function() {
                        alert('Помилка при відправленні запиту.');
                    }
                });
            });
        });
    </script>
</head>
<body>
    <nav>
        <ul>
            <li><a href="home.php">Головна</a></li>
            <li><a href="users.php">Користувачі</a></li>
            <li><a href="friends.php">Друзі</a></li>
            <li><a href="messages.php">Повідомлення</a></li>
            <li><a href="logout.php">Вийти</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="edit-profile-link">
            <a href="edit_profile.php">Редагувати профіль</a>
        </div>
        <div class="user-info">
            <h1>Профіль користувача <?php echo htmlspecialchars($user['username']); ?></h1>
            <p><img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Фото профіля" class="profile-picture"></p>
            <p><strong>Стать:</strong> <?php echo htmlspecialchars($user['gender']); ?></p>
            <p><strong>Вік:</strong> <?php echo htmlspecialchars($user['age']); ?></p>
            <p><strong>Місто:</strong> <?php echo htmlspecialchars($user['city']); ?></p>
            <p><strong>Номер телефону:</strong> <?php echo htmlspecialchars($user['phone_number']); ?></p>
            <p><strong>Біографія:</strong> <?php echo htmlspecialchars($user['bio']); ?></p>
        </div>
        
        <h2>Пости користувача</h2>
        <div id="posts">
            <?php while ($row = $posts_result->fetch_assoc()): ?>
            <div id="post-<?php echo $row['id']; ?>" class="post">
                <div class="post-content">
                    <p><?php echo htmlspecialchars($row['content']); ?></p>
                    <?php if ($row['image_path']): ?>
                    <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="Зображення" class="post-image">
                    <?php endif; ?>
                    <?php if ($row['video_path']): ?>
                    <video controls class="post-video">
                        <source src="<?php echo htmlspecialchars($row['video_path']); ?>" type="video/mp4">
                        Ваш браузер не підтримує відео.
                    </video>
                    <?php endif; ?>
                    <small><?php echo $row['created_at']; ?></small>
                    <p class="like-count">Лайки: <?php echo $row['like_count']; ?></p>
                    <button class="like-button" data-post-id="<?php echo $row['id']; ?>">
                        <?php echo $row['user_liked'] ? 'Забрати лайк' : 'Лайкнути'; ?>
                    </button>
                    <button class="delete-post" data-post-id="<?php echo $row['id']; ?>">Видалити пост</button>
                </div>
                <hr>
                <a href="post.php?post_id=<?php echo $row['id']; ?>" class="comment-link">Коментувати</a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <!-- Форма для видалення аккаунта -->
    <form method="post" onsubmit="return confirm('Ви впевнені, що хочете видалити свій аккаунт?');">
        <button type="submit" name="delete_account">Видалити аккаунт</button>
    </form>
</body>
</html>
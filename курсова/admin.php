<?php
include 'session.php';



// Получение всех пользователей
$users = $conn->query("SELECT * FROM users");

// Получение всех постов
$posts = $conn->query("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id");

// Получение всех комментариев
$comments = $conn->query("SELECT comments.*, users.username AS commenter, posts.content AS post_content, posts.image_path AS post_image, posts.video_path AS post_video FROM comments JOIN users ON comments.user_id = users.id JOIN posts ON comments.post_id = posts.id");

// Функция для удаления пользователя и связанных данных
function delete_user($conn, $user_id) {
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
    $stmt_delete_user->execute();
}

// Функция для удаления поста и связанных комментариев
function delete_post($conn, $post_id) {
    // Видалення коментарів до поста
    $stmt_delete_comments = $conn->prepare("DELETE FROM comments WHERE post_id = ?");
    $stmt_delete_comments->bind_param("i", $post_id);
    $stmt_delete_comments->execute();

    // Видалення самого поста
    $stmt_delete_post = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt_delete_post->bind_param("i", $post_id);
    $stmt_delete_post->execute();
}

// Проверка на удаление пользователя
if (isset($_GET['delete_user_id'])) {
    $delete_user_id = $_GET['delete_user_id'];
    delete_user($conn, $delete_user_id);
    header("Location: admin.php");
    exit();
}

// Проверка на удаление комментария
if (isset($_GET['delete_comment_id'])) {
    $delete_comment_id = $_GET['delete_comment_id'];
    $stmt_delete_comment = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt_delete_comment->bind_param("i", $delete_comment_id);
    $stmt_delete_comment->execute();
    header("Location: admin.php");
    exit();
}

// Проверка на удаление поста
if (isset($_GET['delete_post_id'])) {
    $delete_post_id = $_GET['delete_post_id'];
    delete_post($conn, $delete_post_id);
    header("Location: admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Адмін-панель</title>
    <link rel="stylesheet" href="css/style_admin.css">
    <script>
        function confirmDeletion(type, id) {
            let message = '';
            if (type === 'user') {
                message = "Ви впевнені, що хочете видалити цього користувача і всі його дані?";
            } else if (type === 'comment') {
                message = "Ви впевнені, що хочете видалити цей коментар?";
            } else if (type === 'post') {
                message = "Ви впевнені, що хочете видалити цей пост і всі його коментарі?";
            }

            if (confirm(message)) {
                window.location.href = `admin.php?delete_${type}_id=${id}`;
            }
        }
    </script>
</head>
<body>
    <h1>Адмін-панель</h1>
    <h2>Користувачі</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Ім'я користувача</th>
            <th>Email</th>
            <th>Стать</th>
            <th>Вік</th>
            <th>Місто</th>
            <th>Номер телефону</th>
            <th>Дії</th>
        </tr>
        <?php while ($user = $users->fetch_assoc()): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars($user['gender']); ?></td>
                <td><?php echo htmlspecialchars($user['age']); ?></td>
                <td><?php echo htmlspecialchars($user['city']); ?></td>
                <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                <td>
                    <a href="javascript:void(0);" onclick="confirmDeletion('user', <?php echo $user['id']; ?>);">Видалити</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    
    <h2>Пости</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Користувач</th>
            <th>Зміст</th>
            <th>Зображення</th>
            <th>Відео</th>
            <th>Дата створення</th>
            <th>Дії</th>
        </tr>
        <?php while ($post = $posts->fetch_assoc()): ?>
            <tr>
                <td><?php echo $post['id']; ?></td>
                <td><?php echo htmlspecialchars($post['username']); ?></td>
                <td><?php echo htmlspecialchars($post['content']); ?></td>
                <td><?php if ($post['image_path']): ?><img src="<?php echo $post['image_path']; ?>" alt="Image" width="50"><?php endif; ?></td>
                <td><?php if ($post['video_path']): ?><video width="50" controls><source src="<?php echo $post['video_path']; ?>" type="video/mp4"></video><?php endif; ?></td>
                <td><?php echo $post['created_at']; ?></td>
                <td>
                    <a href="javascript:void(0);" onclick="confirmDeletion('post', <?php echo $post['id']; ?>);">Видалити</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>Коментарі</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Користувач</th>
            <th>Зміст коментаря</th>
            <th>Зміст посту</th>
            <th>Зображення посту</th>
            <th>Відео посту</th>
            <th>Дата створення</th>
            <th>Дії</th>
        </tr>
        <?php while ($comment = $comments->fetch_assoc()): ?>
            <tr>
                <td><?php echo $comment['id']; ?></td>
                <td><?php echo htmlspecialchars($comment['commenter']); ?></td>
                <td><?php echo htmlspecialchars($comment['comment_text']); ?></td>
                <td><?php echo htmlspecialchars($comment['post_content']); ?></td>
                <td><?php if ($comment['post_image']): ?><img src="<?php echo $comment['post_image']; ?>" alt="Image" width="50"><?php endif; ?></td>
                <td><?php if ($comment['post_video']): ?><video width="50" controls><source src="<?php echo $comment['post_video']; ?>" type="video/mp4"></video><?php endif; ?></td>
                <td><?php echo $comment['created_at']; ?></td>
                <td>
                    <a href="javascript:void(0);" onclick="confirmDeletion('comment', <?php echo $comment['id']; ?>);">Видалити</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    
    <p><a href="index.php" class="return-link">Назад</a></p>
</body>
</html>

<?php
include 'session.php';

$user_id = get_user_id();
if (!$user_id) {
    header("Location: index.php");
    exit();
}

// Отримання імені поточного користувача
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$username = $user_data['username'];

// Функція для отримання списку друзів користувача
function get_friends_ids($conn, $user_id) {
    $stmt = $conn->prepare("SELECT friend_id FROM friends WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Отримання списку id друзів поточного користувача
$friends_ids = get_friends_ids($conn, $user_id);
$friend_ids = array_column($friends_ids, 'friend_id');

// Обробка створення поста
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['content'])) {
    $content = $_POST['content'];
    $image_path = null;
    $video_path = null;

    // Обробка завантаження зображення
    if (!empty($_FILES['file']['name']) && strpos($_FILES['file']['type'], 'image') !== false) {
        $image_path = 'uploads/' . basename($_FILES['file']['name']);
        move_uploaded_file($_FILES['file']['tmp_name'], $image_path);
    }

    // Обробка завантаження відео
    if (!empty($_FILES['file']['name']) && strpos($_FILES['file']['type'], 'video') !== false) {
        $video_path = 'uploads/' . basename($_FILES['file']['name']);
        move_uploaded_file($_FILES['file']['tmp_name'], $video_path);
    }

    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image_path, video_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $content, $image_path, $video_path);
    $stmt->execute();

    // Редирект, щоб запобігти повторній відправці форми
    header("Location: home.php");
    exit();
}

// Отримання постів
$stmt = $conn->prepare("SELECT posts.*, users.username, 
    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as like_count,
    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) as user_liked
    FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$posts_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <title>Соціальна мережа</title>
    <link rel="stylesheet" type="text/css" href="css/style_home.css">
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
                            button.text(response.user_liked ? 'Забрати лайк' : 'Лайкнути');
                            button.closest('.post').find('.like-count').text('Лайки: ' + response.like_count);
                        } else {
                            alert('Помилка при обробці запиту.');
                        }
                    },
                    error: function() {
                        alert('Помилка при відправці запиту.');
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
                        alert('Помилка при відправці запиту.');
                    }
                });
            });

            // Додавання/видалення друга
            $('.add-remove-friend').click(function(e) {
                e.preventDefault();
                var button = $(this);
                var friend_id = button.data('friend-id');

                $.ajax({
                    url: 'add_remove_friend.php',
                    type: 'POST',
                    data: { friend_id: friend_id },
                    success: function(response) {
                        if (response.success) {
                            // Оновлюємо текст на кнопці
                            button.text(response.is_friend ? 'Видалити друга' : 'Додати друга');
                        } else {
                            alert('Помилка при обробці запиту.');
                        }
                    },
                    error: function() {
                        alert('Помилка при відправці запиту.');
                    }
                });
            });
        });
    </script>
</head>
<body>
    <nav>
        <ul>
            <li><a href="profile.php">Мій профіль</a></li>
            <li><a href="users.php">Користувачі</a></li>
            <li><a href="friends.php">Друзі</a></li>
            <li><a href="messages.php">Повідомлення</a></li>
            <li><a href="logout.php">Вийти</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1>Ласкаво просимо, <?php echo htmlspecialchars($username); ?>!</h1>
        <form method="post" enctype="multipart/form-data" class="post-form">
            <textarea name="content" placeholder="Напишіть щось..." required></textarea>
            <input type="file" name="file" accept="image/*,video/*">
            <button type="submit">Опублікувати</button>
        </form>

        <h2>Стрічка постів</h2>
        <div id="posts">
            <?php while ($row = $posts_result->fetch_assoc()): ?>
                <div id="post-<?php echo $row['id']; ?>" class="post">
                    <h3>
                        <?php if ($row['user_id'] != $user_id): ?>
                            <a href="user_profile.php?user_id=<?php echo $row['user_id']; ?>">
                                <?php echo htmlspecialchars($row['username']); ?>
                            </a>
                            <button class="add-remove-friend" data-friend-id="<?php echo $row['user_id']; ?>">
                                <?php echo in_array($row['user_id'], $friend_ids) ? 'Видалити друга' : 'Додати друга'; ?>
                            </button>
                        <?php else: ?>
                            <?php echo htmlspecialchars($row['username']); ?>
                        <?php endif; ?>
                    </h3>
                    <p><?php echo htmlspecialchars($row['content']); ?></p>
                    <?php if ($row['image_path']): ?>
                        <img src="<?php echo $row['image_path']; ?>" alt="Зображення" class="post-image">
                    <?php endif; ?>
                    <?php if ($row['video_path']): ?>
                        <video controls class="post-video">
                            <source src="<?php echo $row['video_path']; ?>" type="video/mp4">
                            Ваш браузер не підтримує відео.
                        </video>
                    <?php endif; ?>
                    <small><?php echo $row['created_at']; ?></small>
                    <p class="like-count">Лайки: <?php echo $row['like_count']; ?></p>
                    <button class="like-button" data-post-id="<?php echo $row['id']; ?>">
                        <?php echo $row['user_liked'] ? 'Забрати лайк' : 'Лайкнути'; ?>
                    </button>
                    <?php if ($row['user_id'] == $user_id): ?>
                        <button class="delete-post" data-post-id="<?php echo $row['id']; ?>">Видалити</button>
                    <?php endif; ?>
                    <hr>
                    <a href="post.php?post_id=<?php echo $row['id']; ?>" class="comment-link">Коментувати</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>






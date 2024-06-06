<?php
include 'session.php';

$user_id = get_user_id();
if (!$user_id) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['user_id'])) {
    echo "Користувач не вказаний.";
    exit();
}

$profile_user_id = $_GET['user_id'];

// Отримання даних користувача
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$user_result = $stmt->get_result();
if ($user_result->num_rows === 0) {
    echo "Користувача не знайдено.";
    exit();
}
$user_data = $user_result->fetch_assoc();

// Отримання постів користувача
$stmt = $conn->prepare("SELECT posts.*, users.username, 
    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as like_count,
    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) as user_liked
    FROM posts JOIN users ON posts.user_id = users.id WHERE posts.user_id = ? ORDER BY posts.created_at DESC");
$stmt->bind_param("ii", $user_id, $profile_user_id);
$stmt->execute();
$posts_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Профіль користувача</title>
    <link rel="stylesheet" href="css/style_user_profile.css">
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
         });
    </script>
</head>
<body>
    <nav>
        <ul>
            <li><a href="home.php">Головна</a></li>
            <li><a href="profile.php">Мій профіль</a></li>
            <li><a href="messages.php">Повідомлення</a></li>
            <li><a href="logout.php">Вийти</a></li>
        </ul>
    </nav>

    <div class="container">
        <button class="back-button" onclick="goBack()">Назад</button>

<script>
    function goBack() {
        window.history.back();
    }
</script>

        <h1>Профіль користувача <?php echo htmlspecialchars($user_data['username']); ?></h1>
        <div class="profile-info">
            <img src="<?php echo $user_data['profile_picture']; ?>" alt="Фото профілю" class="profile-picture">
            <p><strong>Стать:</strong> <?php echo htmlspecialchars($user_data['gender']); ?></p>
            <p><strong>Вік:</strong> <?php echo htmlspecialchars($user_data['age']); ?></p>
            <p><strong>Місто:</strong> <?php echo htmlspecialchars($user_data['city']); ?></p>
            <p><strong>Номер телефону:</strong> <?php echo htmlspecialchars($user_data['phone_number']); ?></p>
            <p><strong>Біографія:</strong> <?php echo nl2br(htmlspecialchars($user_data['bio'])); ?></p>
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
                    
                </div>
                <hr>
                <a href="post.php?post_id=<?php echo $row['id']; ?>" class="comment-link">Коментувати</a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>


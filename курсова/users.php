<?php
include 'session.php';

$user_id = get_user_id();
if (!$user_id) {
    header("Location: index.php");
    exit();
}

// Отримання списку всіх користувачів
$search_mode = false;
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = '%' . $_GET['search'] . '%';
    $users_stmt = $conn->prepare("SELECT * FROM users WHERE id != ? AND username LIKE ?");
    $users_stmt->bind_param("is", $user_id, $search);
    $users_stmt->execute();
    $users_result = $users_stmt->get_result();
    $search_mode = true;
} else {
    $users_stmt = $conn->prepare("SELECT * FROM users WHERE id != ?");
    $users_stmt->bind_param("i", $user_id);
    $users_stmt->execute();
    $users_result = $users_stmt->get_result();
    $search_mode = false;
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список користувачів</title>
    <link rel="stylesheet" href="css/style_users.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Обробка додавання/видалення друга
            $('.add-remove-friend-form').submit(function(e) {
                e.preventDefault();
                var form = $(this);
                var friend_id = form.find('input[name="friend_id"]').val();

                $.ajax({
                    url: 'add_remove_friend.php',
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Оновлюємо текст на кнопці
                            var button = form.find('.add-remove-friend');
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

            // Перевірка наявності тексту в полі пошуку перед відправкою форми
            $('form').submit(function(e) {
                var searchInput = $('input[name="search"]');
                if (searchInput.val().trim().length === 0) {
                    e.preventDefault();
                    //alert('Будь ласка, введіть текст для пошуку.');
                }
            });
        });
    </script>
</head>
<body>
    <nav>
        <ul>
            <li><a href="home.php">Головна</a></li>
            <li><a href="profile.php">Мій профіль</a></li>
            <li><a href="friends.php">Друзі</a></li>
            <li><a href="messages.php">Повідомлення</a></li>
            <li><a href="logout.php">Вийти</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1>Список користувачів</h1>
        <form method="GET">
            <input type="text" name="search" placeholder="Пошук по користувачам">
            <button type="submit">Знайти</button>
        </form>
        
        <?php if ($search_mode): ?>
            <?php if ($users_result->num_rows > 0): ?>
                <p>За запитом "<?php echo htmlspecialchars($_GET['search']); ?>" знайдено <?php echo $users_result->num_rows; ?> користувачів:</p>
            <?php else: ?>
                <p>За запитом "<?php echo htmlspecialchars($_GET['search']); ?>" нічого не знайдено.</p>
            <?php endif; ?>
            <p><a href="users.php" class="back-to-home">Повернутися до всіх користувачів</a></p>
        <?php endif; ?>
        
        <ul>
            <?php while ($row = $users_result->fetch_assoc()): ?>
                <li>
                    <img src="<?php echo $row['profile_picture']; ?>" alt="Фото профілю" style="max-width: 50px;">
                    <a href="user_profile.php?user_id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['username']); ?></a>
                    <form method="POST" class="add-remove-friend-form">
                        <input type="hidden" name="friend_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="add-remove-friend">
                            <?php
                            // Перевіряємо, чи є користувач другом
                            $friend_check_stmt = $conn->prepare("SELECT * FROM friends WHERE user_id = ? AND friend_id = ?");
                            $friend_check_stmt->bind_param("ii", $user_id, $row['id']);
                            $friend_check_stmt->execute();
                            $friend_result = $friend_check_stmt->get_result();

                            if ($friend_result->num_rows > 0) {
                                // Користувач вже друг, показуємо кнопку для видалення з друзів
                                echo 'Видалити друга';
                            } else {
                                // Користувач не друг, показуємо кнопку для додавання в друзі
                                echo 'Додати друга';
                            }
                            ?>
                        </button>
                    </form>
                </li>
            <?php endwhile; ?>
        </ul>
        
        <?php if (!$search_mode): ?>
            <p><a href="home.php" class="back-to-home">Повернутися на головну сторінку</a></p>
        <?php endif; ?>
    </div>
</body>
</html>



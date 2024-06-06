<?php
include 'session.php';

$user_id = get_user_id();
if (!$user_id) {
    header("Location: index.php");
    exit();
}

// Отримання списку друзів
$friends_stmt = $conn->prepare("SELECT u.id, u.username, u.profile_picture FROM users u INNER JOIN friends f ON u.id = f.friend_id WHERE f.user_id = ?");
$friends_stmt->bind_param("i", $user_id);
$friends_stmt->execute();
$friends_result = $friends_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чат</title>
    <link rel="stylesheet" href="css/style_messages.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let receiver_id = null;

            function loadMessages() {
                if (receiver_id) {
                    $.ajax({
                        url: 'load_messages.php',
                        type: 'GET',
                        data: { receiver_id: receiver_id },
                        success: function(data) {
                            $('#chat-window').html(data);
                            scrollToBottom();
                        }
                    });
                }
            }

            function scrollToBottom() {
                $('#chat-window').scrollTop($('#chat-window')[0].scrollHeight);
            }

            $('.friend').click(function() {
                receiver_id = $(this).data('id');
                $('#chat-window').html('');
                loadMessages();
            });

            $('#send-message').submit(function(e) {
                e.preventDefault();
                if (receiver_id) {
                    $.ajax({
                        url: 'send_message.php',
                        type: 'POST',
                        data: $(this).serialize() + '&receiver_id=' + receiver_id,
                        success: function() {
                            $('#message').val('');
                            loadMessages();
                        }
                    });
                }
            });

            setInterval(loadMessages, 5000);
        });
    </script>
</head>
<body>
    <nav>
        <ul>
            <li><a href="home.php">Головна</a></li>
            <li><a href="profile.php">Мій профіль</a></li>
            <li><a href="users.php">Користувачі</a></li>
            <li><a href="friends.php">Друзі</a></li>
            <li><a href="logout.php">Вийти</a></li>
        </ul>
    </nav>
    <div class="container">
        <div id="friend-list">
            <h2>Друзі</h2>
            <ul>
                <?php while ($friend = $friends_result->fetch_assoc()): ?>
                    <li class="friend" data-id="<?php echo $friend['id']; ?>">
                        <img src="<?php echo $friend['profile_picture']; ?>" alt="Фото профілю" style="max-width: 50px;">
                        <?php echo htmlspecialchars($friend['username']); ?>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
        <div id="chat-section">
            <div id="chat-window">
                <h2>Повідомлення</h2>
            </div>
            <form id="send-message">
                <textarea name="message" id="message" required></textarea>
                <button type="submit">Надіслати</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php
include 'session.php';

$user_id = get_user_id();
if (!$user_id) {
    header("Location: index.php");
    exit();
}

// Додавання коментаря
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    $post_id = $_POST['post_id'];
    $comment_text = $_POST['comment_text'];

    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $user_id, $comment_text);
    $stmt->execute();

    // Отримання даних нового коментаря
    $new_comment_id = $stmt->insert_id;

    $stmt = $conn->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE comments.id = ?");
    $stmt->bind_param("i", $new_comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $new_comment = $result->fetch_assoc();

    // Вивід даних нового коментаря у форматі JSON
    header('Content-Type: application/json');
    echo json_encode(array('success' => true, 'comment' => $new_comment));
    exit();
}

// Видалення коментаря
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_comment'])) {
    $comment_id = $_POST['comment_id'];

    $stmt = $conn->prepare("SELECT post_id FROM comments WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $comment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $post_id = $row['post_id'];
        $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();

        // Вивід ID видаленого коментаря у форматі JSON
        header('Content-Type: application/json');
        echo json_encode(array('success' => true, 'comment_id' => $comment_id));
        exit();
    }
}

if (isset($_GET['post_id'])) {
    $post_id = $_GET['post_id'];
    $stmt = $conn->prepare("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();

    $stmt = $conn->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $comments_result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пост</title>
    <link rel="stylesheet" href="css/style_post.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="post">
            <h1><?php echo htmlspecialchars($post['content']); ?></h1>
            <?php if ($post['image_path']): ?>
                <img src="<?php echo $post['image_path']; ?>" alt="Зображення">
            <?php endif; ?>
            <?php if ($post['video_path']): ?>
                <video controls>
                    <source src="<?php echo $post['video_path']; ?>" type="video/mp4">
                    Ваш браузер не підтримує відео.
                </video>
            <?php endif; ?>
            <p>Автор: <?php echo htmlspecialchars($post['username']); ?></p>
            <p>Дата публікації: <?php echo $post['created_at']; ?></p>
        </div>

        <div class="comments-section">
            <h2>Коментарі</h2>
            <div id="comments">
                <?php while ($comment = $comments_result->fetch_assoc()): ?>
                    <div class="comment" id="comment_<?php echo $comment['id']; ?>">
                        <p><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                        <p>Автор: <?php echo htmlspecialchars($comment['username']); ?></p>
                        <p>Дата: <?php echo $comment['created_at']; ?></p>
                        <?php if ($comment['user_id'] === $user_id): ?>
                            <form class="delete-form">
                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                <button type="button"type="button" class="delete-comment-btn">Видалити</button>
</form>
<?php endif; ?>
</div>
<?php endwhile; ?>
</div>
</div>


    <div class="add-comment">
        <h3>Додати коментар</h3>
        <form id="comment_form">
            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
            <textarea name="comment_text" id="comment_text" placeholder="Введіть ваш коментар" required></textarea>
            <button type="button" id="add_comment_btn">Відправити</button>
        </form>
    </div>

   <div class="back-to-previous">
    <a href="#" onclick="goBack()">Назад</a>
</div>

<script>
    function goBack() {
        window.history.back();
    }
</script>

</div>

<script>
    $(document).ready(function () {
        // Додавання коментаря
        $('#add_comment_btn').click(function () {
            var post_id = <?php echo $post_id; ?>;
            var comment_text = $('#comment_text').val();
            $.post('post.php', { add_comment: true, post_id: post_id, comment_text: comment_text }, function (response) {
                if (response.success) {
                    var newComment = '<div class="comment" id="comment_' + response.comment.id + '">';
                    newComment += '<p>' + response.comment.comment_text + '</p>';
                    newComment += '<p>Автор: ' + response.comment.username + '</p>';
                    newComment += '<p>Дата: ' + response.comment.created_at + '</p>';
                    newComment += '<form class="delete-form">';
                    newComment += '<input type="hidden" name="comment_id" value="' + response.comment.id + '">';
                    newComment += '<button type="button" class="delete-comment-btn">Видалити</button>';
                    newComment += '</form></div>';
                    $('#comments').append(newComment);
                    $('#comment_text').val('');
                } else {
                    alert('Помилка додавання коментаря.');
                }
            }, 'json');
        });

        // Видалення коментаря
        $(document).on('click', '.delete-comment-btn', function () {
            var comment_id = $(this).closest('.comment').attr('id').replace('comment_', '');
            var commentDiv = $(this).closest('.comment');
            $.post('post.php', { delete_comment: true, comment_id: comment_id }, function (response) {
                if (response.success) {
                    commentDiv.remove();
                } else {
                    alert('Помилка видалення коментаря.');
                }
            }, 'json');
        });
    });
</script>
</body>
</html>
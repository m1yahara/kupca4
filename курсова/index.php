<?php
include 'session.php';

// перевірка на вже активну сесію користувача
if (get_user_id()) {
    header("Location: home.php");
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Перевірка для адмінки
    if ($username === 'admin' && $password === 'admin') {
        
        header("Location: admin.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password);

    if ($stmt->fetch() && password_verify($password, $hashed_password)) {
        session_regenerate_id(true);
        create_session($id);
        header("Location: home.php");
        exit();
    } else {
        $error_message = "Неправильне ім'я користувача або пароль!";
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вхід</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Вхід</h2>
        <form method="post" action="index.php">
            <div class="form-group">
                <input type="text" name="username" placeholder="Ім'я користувача" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Пароль" required>
            </div>
            <button type="submit">Увійти</button>
        </form>
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <div class="account-box">
            <p>Ще немає акаунта? <a href="registration.php">Зареєструйтеся тут</a></p>
        </div>
    </div>
</body>
</html>



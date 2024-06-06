<?php
// Подключаем файл сессии и файл для работы с базой данных
include 'session.php';

// Получаем ID пользователя из сессии
$user_id = get_user_id();
if (!$user_id) {
    // Если пользователь не авторизован, перенаправляем его на страницу входа
    header("Location: index.php");
    exit();
}

// Обработка AJAX-запроса для проверки текущего пароля
if (isset($_POST['ajax']) && $_POST['ajax'] === 'check_current_password') {
    $current_password = $_POST['current_password'];

    // Запрос к базе данных для получения текущего пароля пользователя
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Проверка текущего пароля
    if (password_verify($current_password, $user['password'])) {
        echo 'valid';
    } else {
        echo 'invalid';
    }
    exit();
}

// Запрос к базе данных для получения текущей информации о пользователе
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$error_message = '';
$success_message = '';

// Обработка отправки формы для изменения пароля
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    if ($new_password === $confirm_new_password) {
        if (strlen($new_password) >= 6) {
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_new_password, $user_id);
            if ($stmt->execute()) {
                $success_message = "Пароль успешно изменен.";
            } else {
                $error_message = "Ошибка при изменении пароля.";
            }
        } else {
            $error_message = "Новый пароль должен быть не менее 6 символов.";
        }
    } else {
        $error_message = "Новый пароль и подтверждение пароля не совпадают.";
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Зміна паролю</title>
    <link rel="stylesheet" href="css/style_change_password.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#current_password').on('input', function() {
                var currentPassword = $(this).val();
                $.ajax({
                    url: 'change_password.php',
                    type: 'POST',
                    data: { 
                        ajax: 'check_current_password', 
                        current_password: currentPassword 
                    },
                    success: function(response) {
                        if (response === 'valid') {
                            $('#current-password-error').text('');
                        } else {
                            $('#current-password-error').text('Неправильний поточний пароль.');
                        }
                    }
                });
            });

            $('#new_password, #confirm_new_password').on('input', function() {
                var newPassword = $('#new_password').val();
                var confirmNewPassword = $('#confirm_new_password').val();
                var errorMessage = '';

                if (newPassword.length < 6) {
                    errorMessage = 'Новий пароль має бути не менше 6 символів.';
                } else if (newPassword !== confirmNewPassword) {
                    errorMessage = 'Новий пароль та підтвердження паролю не співпадають.';
                }

                $('#new-password-error').text(errorMessage);
            });
        });

        function validatePassword() {
            var currentPasswordError = $('#current-password-error').text();
            var newPasswordError = $('#new-password-error').text();

            return currentPasswordError === '' && newPasswordError === '';
        }
    </script>
</head>
<body>
    <h1>Зміна паролю</h1>
    <?php if ($error_message): ?>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php elseif ($success_message): ?>
        <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
    <?php endif; ?>
    
    <form method="post" onsubmit="return validatePassword()">
        <label for="current_password">Поточний пароль:</label>
        <input type="password" name="current_password" id="current_password" required><br>
        <p id="current-password-error" class="error-message"></p>
        
        <label for="new_password">Новий пароль:</label>
        <input type="password" name="new_password" id="new_password" required><br>
        
        <label for="confirm_new_password">Підтвердити новий пароль:</label>
        <input type="password" name="confirm_new_password" id="confirm_new_password" required><br>
        <p id="new-password-error" class="error-message"></p>
        
        <button type="submit" name="change_password">Змінити пароль</button>
    </form>
    
   <p class="return-link"><a href="profile.php">Повернутись в профіль</a></p>

</body>
</html>


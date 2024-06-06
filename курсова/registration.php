<?php
$conn = new mysqli('localhost', 'root', 'root', 'social_network');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'check_username') {
        $username = $_POST['username'];
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "Ім'я користувача вже зайняте!";
        } 
        exit();
    } elseif ($_POST['action'] == 'check_email') {
        $email = $_POST['email'];
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "Електронна пошта вже використовується!";
        } 
        exit();
    } elseif ($_POST['action'] == 'register') {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $email = $_POST['email'];

        if ($password != $confirm_password) {
            echo "Паролі не співпадають!";
        } elseif (strlen($password) < 6) {
            echo "Пароль повинен містити щонайменше 6 символів!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, profile_picture) VALUES (?, ?, ?, 'profile_pictures/default_avatar.jpg')");
            $stmt->bind_param("sss", $username, $hashed_password, $email);
            if ($stmt->execute()) {
                echo "Реєстрація пройшла успішно!";
            } else {
                echo "Помилка: " . $stmt->error;
            }
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Реєстрація</title>
    <link rel="stylesheet" type="text/css" href="css/style_registration.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#username').on('input', function() {
                var username = $(this).val();
                if (username.length > 0) {
                    $.ajax({
                        url: 'registration.php',
                        type: 'POST',
                        data: { username: username, action: 'check_username' },
                        success: function(response) {
                            $('#username-message').text(response);
                        }
                    });
                } else {
                    $('#username-message').text('');
                }
            });

            $('#email').on('input', function() {
                var email = $(this).val();
                if (email.length > 0) {
                    $.ajax({
                        url: 'registration.php',
                        type: 'POST',
                        data: { email: email, action: 'check_email' },
                        success: function(response) {
                            $('#email-message').text(response);
                        }
                    });
                } else {
                    $('#email-message').text('');
                }
            });

            $('#password, #confirm_password').on('input', function() {
                var password = $('#password').val();
                var confirmPassword = $('#confirm_password').val();
                if (password.length < 6) {
                    $('#password-message').text('Пароль повинен містити щонайменше 6 символів.');
                } else {
                    $('#password-message').text('');
                }
                if (password !== confirmPassword) {
                    $('#confirm-password-message').text('Паролі не співпадають.');
                } else {
                    $('#confirm-password-message').text('');
                }
            });

            $('form').on('submit', function(e) {
                e.preventDefault();
                var username = $('#username').val();
                var email = $('#email').val();
                var password = $('#password').val();
                var confirmPassword = $('#confirm_password').val();

                if (password.length >= 6 && password === confirmPassword) {
                    $.ajax({
                        url: 'registration.php',
                        type: 'POST',
                        data: {
                            username: username,
                            email: email,
                            password: password,
                            confirm_password: confirmPassword,
                            action: 'register'
                        },
                        success: function(response) {
                            alert(response);
                            if (response.includes('Реєстрація пройшла успішно')) {
                                window.location.href = 'index.php';
                            }
                        }
                    });
                }
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <h1>Реєстрація</h1>
        <form method="post" action="registration.php">
            <input type="text" id="username" name="username" placeholder="Ім'я користувача" required>
            <div id="username-message" class="message"></div>
            <input type="email" id="email" name="email" placeholder="Електронна пошта" required>
            <div id="email-message" class="message"></div>
            <input type="password" id="password" name="password" placeholder="Пароль" required>
            <div id="password-message" class="message"></div>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Підтвердіть пароль" required>
            <div id="confirm-password-message" class="message"></div>
            <button type="submit">Зареєструватися</button>
        </form>
        <p>Вже є акаунт? <a href="index.php">Увійдіть тут</a></p>
    </div>
</body>
</html>

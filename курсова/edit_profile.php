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

// Запит до бази даних для отримання поточної інформації про користувача
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$error_message = '';

// Обробка відправки форми для редагування профілю
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    // Отримуємо дані з форми
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $city = $_POST['city'];
    $phone_number = $_POST['phone_number'];
    $bio = $_POST['bio'];

    // Перевіряємо, чи був завантажений новий файл зображення
    if (!empty($_FILES['profile_picture']['name'])) {
        // Переміщуємо завантажений файл в папку на сервері
        $profile_picture_path = 'profile_pictures/' . basename($_FILES['profile_picture']['name']);
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profile_picture_path)) {
            // Оновлюємо шлях до зображення профілю в базі даних
            $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $profile_picture_path, $user_id);
            if (!$stmt->execute()) {
                // Якщо сталася помилка при оновленні шляху до зображення, виводимо повідомлення про помилку
                $error_message = "Помилка при оновленні шляху до зображення профілю.";
            }
        } else {
            // Якщо сталася помилка при переміщенні файлу, виводимо повідомлення про помилку
            $error_message = "Помилка при завантаженні зображення профілю.";
        }
    }

    // Оновлюємо інші поля профілю в базі даних
    $stmt = $conn->prepare("UPDATE users SET gender = ?, age = ?, city = ?, phone_number = ?, bio = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $gender, $age, $city, $phone_number, $bio, $user_id);
    if (!$stmt->execute()) {
        // Якщо сталася помилка при оновленні інших полів профілю, виводимо повідомлення про помилку
        $error_message = "Помилка при оновленні профілю.";
    }

    // Перенаправляємо користувача на сторінку профілю після успішного оновлення
    if (!$error_message) {
        header("Location: profile.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редагування профілю</title>
    <link rel="stylesheet" href="css/style_edit_profile.css">
</head>
<body>
    <h1>Редагування профілю</h1>
    <?php if (isset($error_message)): ?>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data">
        <label for="gender">Стать:</label>
        <select name="gender" id="gender">
            <option value="male" <?php if ($user['gender'] == 'male') echo 'selected'; ?>>Чоловіча</option>
            <option value="female" <?php if ($user['gender'] == 'female') echo 'selected'; ?>>Жіноча</option>
        </select><br>
        <label for="age">Вік:</label>
        <input type="number" name="age" id="age" value="<?php echo htmlspecialchars($user['age']); ?>"><br>
        <label for="city">Місто:</label>
        <input type="text" name="city" id="city" value="<?php echo htmlspecialchars($user['city']); ?>"><br>
        <label for="phone_number">Номер телефону:</label>
        <input type="text" name="phone_number" id="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>"><br>
        <label for="bio">Біографія:</label><br>
        <textarea name="bio" id="bio"><?php echo htmlspecialchars($user['bio']); ?></textarea><br>
        <label for="profile_picture">Фото профілю:</label><br>
        <input type="file" name="profile_picture" id="profile_picture"><br>
        <button type="submit" name="update_profile">Зберегти зміни</button>
    </form>

    <h2>Пароль</h2>
    <form action="change_password.php" method="get">
        <button type="submit">Змінити пароль</button>
    </form>
    
    <p><a href="profile.php">Скасувати</a></p>
</body>
</html>

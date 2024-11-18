<?php

include_once __DIR__ . '/../plugins/teleboy/setup.php';
session_start();
if (isset($_POST['save_config'])) {
    // Проверка CSRF-токена
    if (isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $apiKey = $_POST['api_key'];
        $chatId = $_POST['chat_id'];

        // Сохранение настроек в базе данных
        $DB->query("REPLACE INTO `glpi_plugin_teleboy_config` (`api_key`, `chat_id`) VALUES ('$apiKey', '$chatId')");
        echo('HUIHUIHUI');
        // Перенаправление пользователя или отображение сообщения об успешном сохранении
        // ...
    } else {
        // Обработка ошибки CSRF-токена
        // ...
        echo('321');
    }
}
?>

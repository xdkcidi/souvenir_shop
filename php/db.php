<?php
// /php/db.php

$host = 'localhost';   // обычно localhost
$db   = 'lavka';       // имя твоей базы
$user = 'root';        // пользователь MySQL
$pass = '';            // пароль (в XAMPP часто пустой, в Денвере/на хостинге — свой)

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // В проде лучше логировать, тут можно просто показать сообщение
    die('Ошибка подключения к базе данных');
}

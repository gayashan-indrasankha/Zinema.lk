<?php
function zinema_dependency_available(): bool {
    try {
        mysqli_report(MYSQLI_REPORT_OFF);
        $connection = mysqli_init();
        $connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
        if (!@$connection->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, 3306)) return false;
        $connection->set_charset('utf8mb4');
        $result = $connection->query('SELECT 1');
        $connection->close();
        if ($result === false) return false;
        $pdo = new PDO('mysql:host=' . DB_HOST . ';port=3306;dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS, [PDO::ATTR_TIMEOUT => 2, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        return $pdo->query('SELECT 1')->fetchColumn() == 1;
    } catch (Throwable $error) {
        return false; // Connection messages, hostnames and credentials stay private.
    }
}

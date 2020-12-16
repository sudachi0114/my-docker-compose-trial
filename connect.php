<?php
    try {
        $dsn = 'mysql:host=db;dbname=test_db;';
        $db = new PDO($dsn, 'sudachi', 'password');  // FIXME:

        $sql = 'SELECT version();';
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        var_dump($result);
    } catch (PDOExeption $e) {
        echo $e->getMessage();
        exit;
    }
?>
<?php
    ini_set('display_errors', 1);
    date_default_timezone_set('Asia/Tokyo');

    try {
        $dsn = 'mysql:host=db;dbname=test_db;';
        $db = new PDO($dsn, 'sudachi', 'password');  // FIXME:

        $insert_sql = 'INSERT INTO counter () VALUE ()';
        $stmt = $db->prepare($insert_sql);
        $stmt->execute();

        $select_sql = 'SELECT * FROM counter ORDER BY id DESC limit 1';
        $stmt = $db->prepare($select_sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // var_dump($result);
        print_r($result);
    } catch (PDOExeption $e) {
        echo $e->getMessage();
        exit;
    }
?>
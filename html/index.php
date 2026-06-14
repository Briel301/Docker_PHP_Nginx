<?php
$Host = getenv('DB_HOST');
$DbName = getenv('DB_NAME');
$User = getenv('DB_USER');
$Password = getenv('DB_PASS');

try {
    $Connection = new mysqli($Host, $User, $Password, $DbName);

    if ($Connection->connect_error) {
        die("Error de conexión: " . $Connection->connect_error);
    }
} catch (Exception $Error) {
    echo "Excepción capturada: " . $Error->getMessage();
}
?>
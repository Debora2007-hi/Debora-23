<?php

$host = 'localhost';
$user = 'root';
$password = '';
$bd = 'facturacion';

$conection = @mysqli_connect($host, $user, $password, $bd);

if (!$conection) {
    die("Error en la conexión: " . mysqli_connect_error());
}

?>
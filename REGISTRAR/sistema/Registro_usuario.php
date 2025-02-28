<?php
session_start();
include '../BD/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $usuario = $_POST['usuario'];
    $clave = $_POST['clave'];
    $tipo_usuario = $_POST['tipo_usuario'];

    
    $sql = "INSERT INTO usuario (nombre, correo, usuario, clave, rol) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conection, $sql);
    
  
    mysqli_stmt_bind_param($stmt, "sssss", $nombre, $correo, $usuario, $clave, $tipo_usuario);

    if (mysqli_stmt_execute($stmt)) {
        echo "Usuario registrado con éxito.";
    } else {
        echo "Error al registrar el usuario: " . mysqli_error($conection);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conection);
}

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            text-align: center;
            padding: 50px;
        }
        .registro-container {
            background-color: #ffffff;
            border: 2px solid #000000;
            padding: 20px;
            width: 75%;
            max-width: 600px;
            margin: auto;
        }
        .registro-container h2 {
            color: #333333;
        }
        .registro-container input[type="text"],
        .registro-container input[type="email"],
        .registro-container input[type="password"],
        .registro-container select {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
        }
        .registro-container input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px;
            width: 95%;
            cursor: pointer;
        }
        .registro-container input[type="submit"]:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="registro-container">
        <h2>Registro Usuario</h2>
        <form method="post" action="">
            <input type="text" name="nombre" placeholder="Nombre completo" required>
            <input type="email" name="correo" placeholder="Correo electrónico" required>
            <input type="text" name="usuario" placeholder="Usuario" required>
            <input type="password" name="clave" placeholder="Clave de acceso" required>
            <select name="tipo_usuario" required>
                <option value="Administrador">Administrador</option>
                <option value="Supervisor">Usuario</option>
                <option value="Vendedor">Usuario</option>
            </select>
            <input type="submit" value="Crear usuario">
        </form>
    </div>
</body>
</html>
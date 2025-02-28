<?php
session_start();
include 'BD/conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $clave = $_POST['clave'];

    $sql = "SELECT idusuario, nombre, rol FROM usuario WHERE correo = ? AND clave = ?";
    $stmt = mysqli_prepare($conection, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $correo, $clave);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($resultado) > 0) {
        $usuario = mysqli_fetch_assoc($resultado);
        $_SESSION['idusuario'] = $usuario['idusuario']; // Guardar ID del usuario
        $_SESSION['nombre'] = $usuario['nombre']; // Guardar nombre del usuario
        $_SESSION['correo'] = $correo;
        $_SESSION['rol'] = $usuario['rol']; // Guardar el rol

        // TODOS los usuarios irán a index.php
        header("Location: sistema/index.php");
        exit();
    } else {
        $error_message = "Credenciales incorrectas.";
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($conection);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <style>
        body {
            font-family: 'Comic Sans MS', cursive, sans-serif;
            background-color: rgb(231, 248, 255); 
            text-align: center;
            margin: 0;
            padding: 50px;
        }
        .login-container {
            background-color: #ffffff;
            border: 2px solid #c0c0c0; 
            border-radius: 15px; 
            padding: 30px;
            width: 75%;
            max-width: 600px;
            margin: auto;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); 
        }
        .login-container h2 {
            color: #555555; 
        }
        .login-container input[type="email"],
        .login-container input[type="password"] {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border: 2px solid #a1c6ea; 
            border-radius: 10px;
            outline: none;
        }
        .login-container input[type="submit"] {
            background-color: #ff6f61; 
            color: white;
            border: none;
            padding: 10px;
            width: 95%;
            border-radius: 10px; 
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .login-container input[type="submit"]:hover {
            background-color: #ff8a7b; 
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .login-image {
            margin: 20px 0;
            width: 80%; 
            max-width: 200px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <img src="img/OIP.jpg" alt="Iniciar Sesión" class="login-image"> 
        <?php if (isset($error_message)) { ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php } ?>
        <form method="post" action="">
            <input type="email" name="correo" placeholder="Correo" required>
            <input type="password" name="clave" placeholder="Clave" required>
            <input type="submit" value="Iniciar Sesión">
        </form>
    </div>
</body>
</html>

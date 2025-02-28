<?php
session_start();
include '../BD/conexion.php';

if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit();
}

$rolUsuario = $_SESSION['rol'];


$accesoPermitido = [
    0 => ['clientes', 'proveedores'],
    1 => ['todos'], 
    2 => ['facturas', 'productos', 'clientes'] 
];
$puedeEditar = ($rolUsuario == 1); 
$puedeCrearUsuario = ($rolUsuario == 1);
$puedeCrearProveedor = ($rolUsuario == 1);
$puedeCrearProducto = ($rolUsuario == 1);
$puedeCrearFactura = ($rolUsuario == 1 || $rolUsuario == 2);

function tieneAcceso($seccion, $rolUsuario, $accesoPermitido) {
    return in_array('todos', $accesoPermitido[$rolUsuario]) || in_array($seccion, $accesoPermitido[$rolUsuario]);
}

$paginaActual = basename($_SERVER['PHP_SELF'], ".php");

if (!tieneAcceso($paginaActual, $rolUsuario, $accesoPermitido)) {
    $mensajeAcceso = "<h2 style='color: red; text-align: center; margin-top: 20px;'>⚠ Acceso Restringido ⚠</h2>
                      <p style='text-align: center;'>No tienes permiso para acceder a esta sección. Consulta con un administrador.</p>";
} else {
    $mensajeAcceso = "";
}
$mensajeAcceso = "";
if (isset($_GET['nuevo_usuario']) && !$puedeCrearUsuario) {
    $mensajeAcceso = "⚠ Acceso Restringido: No puedes registrar un nuevo usuario.";
}
if (isset($_GET['nuevo_proveedor']) && !$puedeCrearProveedor) {
    $mensajeAcceso = "⚠ Acceso Restringido: No puedes registrar un nuevo proveedor.";
}
if (isset($_GET['nuevo_producto']) && !$puedeCrearProducto) {
    $mensajeAcceso = "⚠ Acceso Restringido: No puedes registrar un nuevo producto.";
}
if (isset($_GET['nueva_factura']) && !$puedeCrearFactura) {
    $mensajeAcceso = "⚠ Acceso Restringido: No puedes crear facturas.";
}
$mostrarFormulario = false;
$mostrarListado = false;
$isAdmin = ($rolUsuario == 1);

$usuariosPorPagina = 10;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaActual - 1) * $usuariosPorPagina;

$buscar = isset($_POST['buscar']) ? $_POST['buscar'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['usuario'])) {
        $nombre = $_POST['nombre'];
        $correo = $_POST['correo'];
        $usuario = $_POST['usuario'];
        $clave = $_POST['clave'];
        $tipo_usuario = $_POST['tipo_usuario'];

        $check_sql = "SELECT * FROM usuario WHERE usuario = ?";
        $check_stmt = mysqli_prepare($conection, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $usuario);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            echo "El usuario ya existe. Por favor, elige otro.";
        } else {
            $sql = "INSERT INTO usuario (nombre, correo, usuario, clave, rol) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conection, $sql);
            mysqli_stmt_bind_param($stmt, "sssss", $nombre, $correo, $usuario, $clave, $tipo_usuario);

            if (mysqli_stmt_execute($stmt)) {
                echo "Usuario registrado con éxito.";
            } else {
                echo "Error al registrar el usuario: " . mysqli_error($conection);
            }

            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($check_stmt);
    }

    if (isset($_POST['edit_id']) && $isAdmin) {
        $edit_id = $_POST['edit_id'];
        $nombre = $_POST['nombre'];
        $correo = $_POST['correo'];
        $rol = $_POST['rol'];

        $update_sql = "UPDATE usuario SET nombre = ?, correo = ?, rol = ? WHERE idusuario = ?";
        $update_stmt = mysqli_prepare($conection, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "sssi", $nombre, $correo, $rol, $edit_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
    }
}

if (isset($_GET['delete_id']) && $isAdmin) {
    $delete_id = $_GET['delete_id'];
    $delete_sql = "DELETE FROM usuario WHERE idusuario = ?";
    $delete_stmt = mysqli_prepare($conection, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, "i", $delete_id);
    mysqli_stmt_execute($delete_stmt);
    mysqli_stmt_close($delete_stmt);
    header("Location: ?listado_usuarios=true"); 
    exit();
}

if (isset($_GET['nuevo_usuario'])) {
    $mostrarFormulario = true;
} elseif (isset($_GET['listado_usuarios'])) {
    $mostrarListado = true;
}

$usuarios = [];
$totalUsuarios = 0;

if ($mostrarListado) {
    if (!isset($usuariosPorPagina) || !isset($offset)) {
        echo "Error: valores de paginación no definidos.";
        exit;
    }

    $usuarios = []; 

    if (!empty($buscar)) {
        $buscarLower = strtolower($buscar);
        $rolFilter = null;

        if ($buscarLower === 'usuario') {
            $rolFilter = 0;
        } elseif ($buscarLower === 'administrador') {
            $rolFilter = 1;
        } elseif (strpos($buscarLower, 'vendedor') !== false) {
            $rolFilter = 2;
        }

        if ($rolFilter !== null) {
            $sql = "SELECT idusuario, nombre, correo, rol FROM usuario WHERE rol = ?";
            $stmt = mysqli_prepare($conection, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $rolFilter);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
            }
        } else {
            $paramInicio = $buscar . '%'; 
            $paramLike = '%' . $buscar . '%'; 

            $sql = "SELECT idusuario, nombre, correo, rol 
                    FROM usuario 
                    WHERE idusuario LIKE ? 
                    OR nombre LIKE ? 
                    OR correo LIKE ? 
                    OR usuario LIKE ?";

            $stmt = mysqli_prepare($conection, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssss", $paramLike, $paramInicio, $paramLike, $paramLike);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
            }
        }
    } else {
        $count_sql = "SELECT COUNT(*) as total FROM usuario";
        $count_result = mysqli_query($conection, $count_sql);
        $totalUsuarios = ($count_result) ? mysqli_fetch_assoc($count_result)['total'] ?? 0 : 0;

        $sql = "SELECT idusuario, nombre, correo, rol FROM usuario LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($conection, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $usuariosPorPagina, $offset);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        }
    }

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $usuarios[] = $row;
        }
        mysqli_free_result($result);
    } else {
        echo "Error al obtener usuarios: " . mysqli_error($conection);
        exit;
    }

    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
}


mysqli_close($conection); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <title>Sistema Ventas</title>
    <style>
        .registro-container, .listado-container {
            background-color: #ffffff;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 20px;
            width: 75%;
            max-width: 600px;
            margin: 20px auto;
            display: none; 
        }
        .registro-container {
            display: <?php echo $mostrarFormulario ? 'block' : 'none'; ?>;
        }
        .listado-container {
            display: <?php echo $mostrarListado ? 'block' : 'none'; ?>;
        }
        .registro-container h2, .listado-container h2 {
            color: #333;
            margin-bottom: 15px;
        }
        .registro-container input[type="text"],
        .registro-container input[type="email"],
        .registro-container input[type="password"],
        .registro-container select {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .registro-container input[type="submit"] {
            background-color: rgb(176, 177, 235);
            color: white;
            border: none;
            padding: 10px;
            width: 95%;
            cursor: pointer;
            border-radius: 4px;
        }
        .buscar-container {
    display: flex;
    justify-content: flex-end; 
    margin-bottom: 10px;
}

.search-box {
    display: flex;
    align-items: center;
    background: white;
    border: 2px solid #ccc;
    border-radius: 25px;
    overflow: hidden;
    padding: 5px 10px;
    max-width: 300px;
}

.search-box input {
    border: none;
    outline: none;
    padding: 8px;
    font-size: 16px;
    flex: 1;
    border-radius: 20px;
}

.search-box button {
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
    display: flex;
    align-items: center;
}

.search-box button img {
    width: 20px;
    height: 20px;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.search-box button:hover img {
    opacity: 1;
}
        .registro-container input[type="submit"]:hover {
            background-color: rgb(172, 14, 158);
        }
        .listado-container {
            display: <?php echo $mostrarListado ? 'block' : 'none'; ?>;
            width: 90%; 
            max-width: 800px; 
            padding: 30px; 
            margin: 20px auto; 
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color:rgb(190, 219, 236);
        }
        .pagination {
            margin: 20px 0;
        }
        .pagination a {
            margin: 0 5px;
            padding: 5px 10px;
            text-decoration: none;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .pagination a.active {
            background-color: #007bff;
            color: white;
        }
        .header-listado {
    display: flex;
    align-items: center; 
    justify-content: space-between; 
    margin-bottom: 15px;
    flex-wrap: wrap; 
}

.btn-crear {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 10px 20px;
    font-size: 16px;
    border-radius: 5px;
    cursor: pointer;
    margin-left: 10px;
    transition: background 0.3s;
}

.btn-crear:hover {
    background-color: #0056b3;
}

.buscar-container {
    display: flex;
    align-items: center;
}

.search-box {
    display: flex;
    align-items: center;
    background: white;
    border: 2px solid #ccc;
    border-radius: 25px;
    overflow: hidden;
    padding: 5px 10px;
    max-width: 300px;
}

.search-box input {
    border: none;
    outline: none;
    padding: 8px;
    font-size: 16px;
    flex: 1;
    border-radius: 20px;
}

.search-box button {
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
    display: flex;
    align-items: center;
}

.search-box button img {
    width: 20px;
    height: 20px;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.search-box button:hover img {
    opacity: 1;
}
.btn-restringido {
	display: block;
	width: 100%;
	background-color: rgba(255, 255, 255, 0.2);
	color: white;
	padding: 15px 30px;
	text-align: left;
	font-size: 10pt;
	font-family: 'GothamBook';
	text-transform: uppercase;
	letter-spacing: 2px;
	border: none;
	cursor: not-allowed;
}

.btn-restringido:hover {
	background-color: rgba(255, 255, 255, 0.3);
}
    </style>
    <script>
        function editUser(id) {
            const row = document.getElementById('row-' + id);
            const cells = row.getElementsByTagName('td');

            const nombreInput = `<input type="text" value="${cells[1].innerText}" />`;
            const correoInput = `<input type="email" value="${cells[2].innerText}" />`;
            const rolInput = `<select>
                                <option value="1" ${cells[3].innerText === 'Administrador' ? 'selected' : ''}>Administrador</option>
                                <option value="0" ${cells[3].innerText === 'Usuario' ? 'selected' : ''}>Usuario</option>
                                <option value="2" ${cells[3].innerText === 'Usuario-Vendedor' ? 'selected' : ''}>Usuario-Vendedor</option>
                              </select>`;

            cells[1].innerHTML = nombreInput;
            cells[2].innerHTML = correoInput;
            cells[3].innerHTML = rolInput;

            const editButton = row.querySelector('.edit-button');
            editButton.innerText = 'Guardar';
            editButton.setAttribute('onclick', `saveUser(${id})`);
        }

        function saveUser(id) {
            const row = document.getElementById('row-' + id);
            const cells = row.getElementsByTagName('td');

            const nombre = cells[1].getElementsByTagName('input')[0].value;
            const correo = cells[2].getElementsByTagName('input')[0].value;
            const rol = cells[3].getElementsByTagName('select')[0].value;

            const form = new FormData();
            form.append('edit_id', id);
            form.append('nombre', nombre);
            form.append('correo', correo);
            form.append('rol', rol);

            fetch('', {
                method: 'POST',
                body: form
            }).then(response => {
                if (response.ok) {
                    location.reload();
                }
            });
        }
    </script>
</head>
<body>
    <header>
        <div class="header">
            <h1>Sistema Facturación</h1>
            <div class="optionsBar">
            <p>República Dominicana, <?php echo date('d \d\e F \d\e Y'); ?></p>
                <span>|</span>
                <span class="user">Debora Rodriguez <p>Bienvenido, <strong><?php echo $_SESSION['rol'] == 1 ? "Administrador" : ($_SESSION['rol'] == 2 ? "Usuario-Vendedor" : "Usuario"); ?></strong></p></span>
                <img class="photouser" src="img/user.png" alt="Usuario">
                <a href="#"><img class="close" src="img/salir.png" alt="Salir del sistema" title="Salir"></a>
            </div>
        </div>
        <nav>
    <ul>
        <li><a href="#">Inicio</a></li>
        
        <li class="principal">
            <a href="#">Usuarios</a>
            <ul>
                <?php if ($puedeCrearUsuario) : ?>
                    <li><a href="?nuevo_usuario=true">Nuevo Usuario</a></li>
                <?php else : ?>
                    <li><button class="btn-restringido" onclick="alert('⚠ Acceso Restringido: No puedes registrar usuarios')">Nuevo Usuario</button></li>
                <?php endif; ?>
                <li><a href="?listado_usuarios=true">Lista de Usuarios</a></li>
            </ul>
        </li>

        <li class="principal">
            <a href="#">Clientes</a>
            <ul>
                <li><a href="#">Nuevo Cliente</a></li>
                <li><a href="#">Lista de Clientes</a></li>
            </ul>
        </li>

        <li class="principal">
            <a href="#">Proveedores</a>
            <ul>
                <?php if ($puedeCrearProveedor) : ?>
                    <li><a href="?nuevo_proveedor=true">Nuevo Proveedor</a></li>
                <?php else : ?>
                    <li><button class="btn-restringido" onclick="alert('⚠ Acceso Restringido: No puedes agregar proveedores')">Nuevo Proveedor</button></li>
                <?php endif; ?>
                <li><a href="#">Lista de Proveedores</a></li>
            </ul>
        </li>

        <li class="principal">
            <a href="#">Productos</a>
            <ul>
                <?php if ($puedeCrearProducto) : ?>
                    <li><a href="?nuevo_producto=true">Nuevo Producto</a></li>
                <?php else : ?>
                    <li><button class="btn-restringido" onclick="alert('⚠ Acceso Restringido: No puedes agregar productos')">Nuevo Producto</button></li>
                <?php endif; ?>
                <li><a href="#">Lista de Productos</a></li>
            </ul>
        </li>

        <li class="principal">
            <a href="#">Facturas</a>
            <ul>
                <?php if ($puedeCrearFactura) : ?>
                    <li><a href="?nueva_factura=true">Nueva Factura</a></li>
                <?php else : ?>
                    <li><button class="btn-restringido" onclick="alert('⚠ Acceso Restringido: No puedes crear facturas')">Nueva Factura</button></li>
                <?php endif; ?>
                <li><a href="#">Facturas</a></li>
            </ul>
        </li>
    </ul>
</nav>
    </header>
    <section id="container">
        <h1>Bienvenido al sistema</h1>
        <?php if ($mensajeAcceso) : ?>
        <p class="error"><?php echo $mensajeAcceso; ?></p>
    <?php endif; ?>
        <div class="registro-container">
            <h2>Registro Usuario</h2>
            <form method="post" action="">
                <input type="text" name="nombre" placeholder="Nombre completo" required>
                <input type="email" name="correo" placeholder="Correo electrónico" required>
                <input type="text" name="usuario" placeholder="Usuario" required>
                <input type="password" name="clave" placeholder="Clave de acceso" required>
                <select name="tipo_usuario" required>
                    <option value="1">Administrador</option>
                    <option value="0">Usuario</option>
                    <option value="2">Usuario-Vendedor</option>
                </select>
                <input type="submit" value="Crear usuario">
            </form>
        </div>

        <div class="listado-container">
        <div class="header-listado">
        <h2>Lista de Usuarios</h2>

        <a href="?nuevo_usuario=true">
            <button class="btn-crear">Crear Usuario</button>
        </a>

        <div class="buscar-container">
            <form method="post" action="">
                <div class="search-box">
                    <input type="text" name="buscar" placeholder="Buscar..." value="<?php echo htmlspecialchars($buscar); ?>">
                    <button type="submit">
                        <img src="../img/lupa.jpg" alt="Buscar">
                    </button>
                </div>
            </form>
        </div>
    </div>

            </form>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="5">No se encontró usuario con dicha información.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr id="row-<?php echo $usuario['idusuario']; ?>">
                                <td><?php echo htmlspecialchars($usuario['idusuario']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['correo']); ?></td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars(
                                        $usuario['rol'] == 1 ? 'Administrador' : 
                                        ($usuario['rol'] == 2 ? 'Usuario-Vendedor' : 'Usuario')
                                    ); 
                                    ?>
                                </td>
                                <td>
                                    <?php if ($isAdmin): ?>
                                        <button class="edit-button" onclick="editUser(<?php echo $usuario['idusuario']; ?>)">Editar</button>
                                        <a href="?delete_id=<?php echo $usuario['idusuario']; ?>">
                                            <button>Eliminar</button>
                                        </a>
                                    <?php else: ?>
                                        <button onclick="alert('Solo los administradores pueden editar usuarios.')">Editar</button>
                                        <button onclick="alert('Solo los administradores pueden eliminar usuarios.')">Eliminar</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="pagination">
                <?php
                $totalPaginas = ceil($totalUsuarios / $usuariosPorPagina);
                if ($paginaActual > 1): ?>
                    <a href="?listado_usuarios=true&pagina=<?php echo $paginaActual - 1; ?>">Anterior</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <a href="?listado_usuarios=true&pagina=<?php echo $i; ?>" 
                       class="<?php echo $i == $paginaActual ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                <?php if ($paginaActual < $totalPaginas): ?>
                    <a href="?listado_usuarios=true&pagina=<?php echo $paginaActual + 1; ?>">Siguiente</a>
                <?php endif; ?>
            </div>
        </div>
    </section>
</body>
</html>
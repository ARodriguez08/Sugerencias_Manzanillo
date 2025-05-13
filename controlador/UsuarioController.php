<?php
// Incluir archivos necesarios
include_once 'config/database.php';
include_once 'modelo/Usuario.php';
include_once 'modelo/Rol.php';

class UsuarioControlador {
    private $db;
    private $usuario;
    private $rol;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->usuario = new Usuario($this->db);
        $this->rol = new Rol($this->db);
    }

    // Procesar inicio de sesión
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['email']) && isset($_POST['password'])) {
                $this->usuario->email = $_POST['email'];
                $this->usuario->password = $_POST['password'];

                if ($this->usuario->emailExiste()) {
                    if (password_verify($_POST['password'], $this->usuario->password)) {
                        // Iniciar sesión
                        session_start();
                        $_SESSION['usuario_id'] = $this->usuario->id;
                        $_SESSION['usuario_nombre'] = $this->usuario->nombre;
                        $_SESSION['usuario_apellidos'] = $this->usuario->apellidos;
                        $_SESSION['usuario_rol_id'] = $this->usuario->rol_id;

                        // Redirigir según el rol
                        if ($this->usuario->rol_id == 1) { // Administrador
                            header("Location: index.php?page=admin_dashboard");
                        } else if ($this->usuario->rol_id == 2) { // Funcionario
                            header("Location: index.php?page=funcionario_dashboard");
                        } else { // Ciudadano
                            header("Location: index.php?page=ciudadano_dashboard");
                        }
                        exit;
                    } else {
                        return "Contraseña incorrecta.";
                    }
                } else {
                    return "El correo electrónico no existe.";
                }
            } else {
                return "Por favor, complete todos los campos.";
            }
        }
        return "";
    }

    // Procesar registro de usuario
    public function registrar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (
                isset($_POST['nombre']) && 
                isset($_POST['apellidos']) && 
                isset($_POST['email']) && 
                isset($_POST['password']) && 
                isset($_POST['confirmar_password'])
            ) {
                // Validar que las contraseñas coincidan
                if ($_POST['password'] !== $_POST['confirmar_password']) {
                    return "Las contraseñas no coinciden.";
                }

                // Verificar si el email ya existe
                $this->usuario->email = $_POST['email'];
                if ($this->usuario->emailExiste()) {
                    return "El correo electrónico ya está registrado.";
                }

                // Asignar valores
                $this->usuario->nombre = $_POST['nombre'];
                $this->usuario->apellidos = $_POST['apellidos'];
                $this->usuario->email = $_POST['email'];
                $this->usuario->password = $_POST['password'];
                $this->usuario->telefono = isset($_POST['telefono']) ? $_POST['telefono'] : "";
                $this->usuario->direccion = isset($_POST['direccion']) ? $_POST['direccion'] : "";
                $this->usuario->rol_id = isset($_POST['rol_id']) ? $_POST['rol_id'] : 3; // Por defecto, ciudadano
                $this->usuario->activo = 1;

                // Crear usuario
                if ($this->usuario->crear()) {
                    return "success";
                } else {
                    return "Error al crear el usuario. Inténtelo de nuevo.";
                }
            } else {
                return "Por favor, complete todos los campos obligatorios.";
            }
        }
        return "";
    }

    // Obtener todos los usuarios para el panel de administración
    public function obtenerUsuarios($page = 1) {
        $items_per_page = 10;
        $usuarios = $this->usuario->obtenerTodos($page, $items_per_page);
        $total_usuarios = $this->usuario->contarTotal();
        $total_pages = ceil($total_usuarios / $items_per_page);

        return [
            'usuarios' => $usuarios,
            'total_usuarios' => $total_usuarios,
            'total_pages' => $total_pages,
            'current_page' => $page
        ];
    }

    // Obtener todos los roles para formularios
    public function obtenerRoles() {
        return $this->rol->obtenerTodos();
    }

    // Cerrar sesión
    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        header("Location: index.php");
        exit;
    }


}
?>

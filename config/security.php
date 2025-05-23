<?php
class Security {
    // Token CSRF para formularios
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Verificar token CSRF (antiguo)
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        return true;
    }

    /**
     * Validate a CSRF token
     * @param string $token The token to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // Limpiar datos de entrada
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitizeInput($value);
            }
        } else {
            $data = htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
        return $data;
    }

    // Validar email
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    // Validar contraseña (mínimo 8 caracteres, al menos una letra y un número)
    public static function validatePassword($password) {
        return (strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password));
    }

    // Generar hash seguro de contraseña
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    // Verificar si una contraseña coincide con su hash
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    // Validar archivos subidos
    public static function validateFile($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], $maxSize = 5242880) {
        // Verificar si hay error en la subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // Verificar tipo de archivo
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }

        // Verificar tamaño de archivo (por defecto 5MB)
        if ($file['size'] > $maxSize) {
            return false;
        }

        return true;
    }

    // Generar nombre seguro para archivos
    public static function generateSafeFileName($originalName) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    }

    // Prevenir XSS en salida de datos
    public static function escapeOutput($data) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    // Validar ID numérico
    public static function validateId($id) {
        return is_numeric($id) && $id > 0;
    }

    // Regenerar ID de sesión para prevenir ataques de fijación de sesión
    public static function regenerateSession() {
        session_regenerate_id(true);
    }

    // Validar fecha en formato Y-m-d
    public static function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
?>

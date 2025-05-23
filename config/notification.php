<?php
/**
 * Notification Configuration and Helper Class
 * This file provides notification functionality for the Sugerencias_Manzanillo system
 */

class Notification {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Send a notification to a user
     * 
     * @param int $user_id The ID of the user to notify
     * @param string $message The notification message
     * @param string $type The notification type (info, success, warning, error)
     * @param string $link Optional link to include with the notification
     * @return bool Success or failure
     */
    public function send($user_id, $message, $type = 'info', $link = '') {
        try {
            $query = "INSERT INTO notificaciones (usuario_id, mensaje, tipo, enlace, fecha_creacion, leida) 
                     VALUES (:usuario_id, :mensaje, :tipo, :enlace, NOW(), 0)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':usuario_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':mensaje', $message, PDO::PARAM_STR);
            $stmt->bindParam(':tipo', $type, PDO::PARAM_STR);
            $stmt->bindParam(':enlace', $link, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            // Log error but don't expose details
            error_log("Error sending notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification to multiple users
     * 
     * @param array $user_ids Array of user IDs
     * @param string $message The notification message
     * @param string $type The notification type
     * @param string $link Optional link
     * @return int Number of successful notifications sent
     */
    public function sendMultiple($user_ids, $message, $type = 'info', $link = '') {
        $success_count = 0;
        
        foreach ($user_ids as $user_id) {
            if ($this->send($user_id, $message, $type, $link)) {
                $success_count++;
            }
        }
        
        return $success_count;
    }
    
    /**
     * Get unread notifications for a user
     * 
     * @param int $user_id The user ID
     * @param int $limit Maximum number of notifications to return
     * @return array Array of notification objects
     */
    public function getUnread($user_id, $limit = 10) {
        try {
            $query = "SELECT * FROM notificaciones 
                     WHERE usuario_id = :usuario_id AND leida = 0 
                     ORDER BY fecha_creacion DESC 
                     LIMIT :limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':usuario_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting unread notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark a notification as read
     * 
     * @param int $notification_id The notification ID
     * @return bool Success or failure
     */
    public function markAsRead($notification_id) {
        try {
            $query = "UPDATE notificaciones SET leida = 1 WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $notification_id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications for a user as read
     * 
     * @param int $user_id The user ID
     * @return bool Success or failure
     */
    public function markAllAsRead($user_id) {
        try {
            $query = "UPDATE notificaciones SET leida = 1 WHERE usuario_id = :usuario_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':usuario_id', $user_id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
    }
}
?>
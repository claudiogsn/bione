<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/db.php';

class UserController
{
    public static function getUserRoles($userId)
    {
        global $pdo;

        if (empty($userId)) {
            return ['success' => false, 'message' => 'ID do usuário é obrigatório.'];
        }

        $sql = "
            SELECT r.id, r.name, r.custom_code
            FROM system_user_role ur
            INNER JOIN system_role r ON ur.system_role_id = r.id
            WHERE ur.system_user_id = :user_id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'roles' => $roles];
    }
}

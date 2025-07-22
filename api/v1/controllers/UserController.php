<?php

ini_set('post_max_size', '100M');
ini_set('upload_max_filesize', '100M');
ini_set('max_execution_time', '600');
ini_set('max_input_time', '600');
ini_set('memory_limit', '512M');

require_once __DIR__ . '/../database/db.php';

class UserController
{

    public static function getUserDetails($user)
    {
        global $pdo;

        // Verifica se é ID numérico
        $isId = is_numeric($user);

        if ($isId) {
            $stmt = $pdo->prepare("SELECT id, name, login, function_name, system_unit_id FROM system_users WHERE id = :user AND active = 'Y' LIMIT 1");
            $stmt->bindParam(':user', $user, PDO::PARAM_INT);
        } else {
            $stmt = $pdo->prepare("SELECT id, name, login, function_name, system_unit_id FROM system_users WHERE login = :user AND active = 'Y' LIMIT 1");
            $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        }

        $stmt->execute();
        $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userDetails) {
            return ['success' => false, 'message' => 'Usuário não encontrado'];
        }


        // Busca o último acesso sem logout
        $stmtLog = $pdo->prepare("
        SELECT sessionid
        FROM system_access_log
        WHERE login = :login
        AND logout_time is null
        ORDER BY login_time DESC
        LIMIT 1
    ");
        $stmtLog->bindParam(':login', $userDetails['login'], PDO::PARAM_STR);
        $stmtLog->execute();
        $lastAccess = $stmtLog->fetch(PDO::FETCH_ASSOC);

        $userDetails['token'] = $lastAccess['sessionid'] ?? null;
        $userDetails['is_logged'] = isset($lastAccess['sessionid']);

        return ['success' => true, 'userDetails' => $userDetails];
    }


    public static function getMenuMobile($user_id)
    {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT DISTINCT
                m.id, 
                m.name, 
                m.label, 
                m.description, 
                m.icon, 
                m.route, 
                m.ordem
            FROM menu_mobile m
            INNER JOIN menu_mobile_access a ON a.menu_id = m.id
            WHERE a.system_user_id = :user_id
            ORDER BY m.ordem;

        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$menus) {
            return ['success' => false, 'message' => 'Nenhum menu disponível para esse usuário e unidade.'];
        }

        return ['success' => true, 'menus' => $menus];
    }

    public static function getUsers()
    {
        global $pdo;

        $stmt = $pdo->query("SELECT * FROM system_users WHERE active = 'Y' ORDER BY name ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$users) {
            return ['success' => false, 'message' => 'Nenhum usuário encontrado.'];
        }

        return ['success' => true, 'users' => $users];

    }

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
?>
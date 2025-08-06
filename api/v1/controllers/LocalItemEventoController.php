<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/db.php';


class LocalItemEventoController
{
    public static function createLocalItemEvento($data): array
    {
        global $pdo;

        if (empty($data['local_nome']) || empty($data['evento_id'])) {
            return ['success' => false, 'message' => 'O nome do local e o ID do evento são obrigatórios.'];
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO local_item_evento (local_nome, evento_id, created_at, updated_at)
                VALUES (:local_nome, :evento_id, NOW(), NOW())
            ");

            $stmt->execute([
                ':local_nome' => $data['local_nome'],
                ':evento_id'  => $data['evento_id']
            ]);

            return [
                'success' => true,
                'message' => 'Registro criado com sucesso.',
                'id'      => $pdo->lastInsertId()
            ];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao inserir: ' . $e->getMessage()];
        }
    }

    public static function listLocalItemEventoByEvento($evento_id): array
    {
        global $pdo;

        if (empty($evento_id)) {
            return ['success' => false, 'message' => 'O ID do evento é obrigatório.'];
        }

        try {
            $stmt = $pdo->prepare("
                SELECT * 
                FROM local_item_evento 
                WHERE evento_id = :evento_id
                ORDER BY created_at DESC
            ");

            $stmt->execute([':evento_id' => $evento_id]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $result];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao listar: ' . $e->getMessage()];
        }
    }
}


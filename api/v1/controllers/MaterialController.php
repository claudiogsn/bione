<?php

require_once __DIR__ . '/../database/db.php';

class MaterialController
{
    public static function createMaterial($data)
    {
        global $pdo;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO material (nome, categoria_id, unidade, valor_locacao) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $data['nome'],
                $data['categoria_id'] ?? null,
                $data['unidade'] ?? 'UND',
                $data['valor_locacao'] ?? null
            ]);

            $materialId = $pdo->lastInsertId();

            if (!empty($data['patrimonios']) && is_array($data['patrimonios'])) {
                foreach ($data['patrimonios'] as $patrimonio) {
                    $stmtP = $pdo->prepare("INSERT INTO material_patrimonio 
                        (material_id, fabricante_id, modelo, numero_serie, patrimonio, status, custo_material, custo_locacao, sublocado, fornecedor_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    $stmtP->execute([
                        $materialId,
                        $patrimonio['fabricante_id'] ?? null,
                        $patrimonio['modelo'] ?? null,
                        $patrimonio['numero_serie'] ?? null,
                        $patrimonio['patrimonio'] ?? null,
                        $patrimonio['status'] ?? null,
                        $patrimonio['custo_material'] ?? null,
                        $patrimonio['custo_locacao'] ?? null,
                        $patrimonio['sublocado'] ?? null,
                        $patrimonio['fornecedor_id'] ?? null,
                    ]);
                }
            }

            $pdo->commit();
            return ['success' => true, 'material_id' => $materialId];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Erro ao criar material: ' . $e->getMessage()];
        }
    }

    public static function updateMaterial($id, $data)
    {
        global $pdo;

        $sql = "UPDATE material SET ";
        $values = [];

        foreach ($data as $key => $value) {
            $sql .= "$key = :$key, ";
            $values[":$key"] = $value;
        }

        $sql = rtrim($sql, ', ') . " WHERE id = :id";
        $values[':id'] = $id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        return ($stmt->rowCount() > 0)
            ? ['success' => true, 'message' => 'Material atualizado com sucesso']
            : ['success' => false, 'message' => 'Nenhuma alteração realizada'];
    }

    public static function getMaterialById($id)
    {
        global $pdo;

        $stmt = $pdo->prepare("SELECT m.*, c.nome AS categoria_nome, c.tipo AS categoria_tipo
            FROM material m
            LEFT JOIN categoria c ON c.id = m.categoria_id
            WHERE m.id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $material = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$material) {
            return ['success' => false, 'message' => 'Material não encontrado'];
        }

        $stmtP = $pdo->prepare("SELECT mp.*, f.nome AS fabricante_nome 
            FROM material_patrimonio mp
            LEFT JOIN fabricante f ON f.id = mp.fabricante_id
            WHERE mp.material_id = :id");
        $stmtP->bindParam(':id', $id);
        $stmtP->execute();
        $patrimonios = $stmtP->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'material' => $material, 'patrimonios' => $patrimonios];
    }

    public static function deleteMaterial($id)
    {
        global $pdo;

        try {
            $pdo->beginTransaction();

            $pdo->prepare("DELETE FROM material_patrimonio WHERE material_id = ?")->execute([$id]);
            $stmt = $pdo->prepare("DELETE FROM material WHERE id = ?");
            $stmt->execute([$id]);

            $pdo->commit();

            return ($stmt->rowCount() > 0)
                ? ['success' => true, 'message' => 'Material e patrimônios excluídos com sucesso']
                : ['success' => false, 'message' => 'Material não encontrado'];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Erro ao excluir: ' . $e->getMessage()];
        }
    }

    public static function listMaterials($filters = [])
    {
        global $pdo;

        $where = [];
        $params = [];

        if (!empty($filters['categoria_id'])) {
            $where[] = "m.categoria_id = :categoria_id";
            $params[':categoria_id'] = $filters['categoria_id'];
        }

        $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT 
                m.*,
                c.nome AS categoria_nome,
                (SELECT COUNT(*) FROM material_patrimonio mp WHERE mp.material_id = m.id) AS total_patrimonios
            FROM material m
            LEFT JOIN categoria c ON c.id = m.categoria_id
            $whereClause
            ORDER BY m.nome ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'materials' => $materials];
    }

    public static function addPatrimonio($materialId, $data)
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare("
            INSERT INTO material_patrimonio 
            (material_id, fabricante_id, modelo, numero_serie, patrimonio, status, custo_material, custo_locacao, sublocado, fornecedor_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                material_id = VALUES(material_id),
                fabricante_id = VALUES(fabricante_id),
                modelo = VALUES(modelo),
                numero_serie = VALUES(numero_serie),
                status = VALUES(status),
                custo_material = VALUES(custo_material),
                custo_locacao = VALUES(custo_locacao),
                sublocado = VALUES(sublocado),
                fornecedor_id = VALUES(fornecedor_id),
                updated_at = CURRENT_TIMESTAMP()
        ");

            $stmt->execute([
                $materialId,
                $data['fabricante_id'] ?? null,
                $data['modelo'] ?? null,
                $data['numero_serie'] ?? null,
                $data['patrimonio'] ?? null,
                $data['status'] ?? null,
                $data['custo_material'] ?? null,
                $data['custo_locacao'] ?? null,
                $data['sublocado'] ?? null,
                $data['fornecedor_id'] ?? null
            ]);

            // Buscar ID existente ou novo
            $stmtSelect = $pdo->prepare("SELECT id FROM material_patrimonio WHERE patrimonio = ?");
            $stmtSelect->execute([$data['patrimonio']]);
            $id = $stmtSelect->fetchColumn();

            return [
                'success' => true,
                'message' => 'Patrimônio inserido ou atualizado com sucesso',
                'patrimonio_id' => $id
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao salvar patrimônio: ' . $e->getMessage()
            ];
        }
    }

    public static function listPatrimoniosAgrupadosPorModelo($materialId)
    {
        global $pdo;

        $stmt = $pdo->prepare("
        SELECT 
            mp.modelo,
            f.nome AS fabricante_nome,
            COUNT(*) AS total,
            SUM(mp.custo_material) AS custo_total,
            SUM(mp.custo_locacao) AS locacao_total
        FROM material_patrimonio mp
        LEFT JOIN fabricante f ON f.id = mp.fabricante_id
        WHERE mp.material_id = :material_id
        GROUP BY mp.modelo, f.nome
        ORDER BY mp.modelo ASC");

        $stmt->execute([':material_id' => $materialId]);

        return ['success' => true, 'agrupado' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public static function listPatrimoniosByMaterial($materialId)
    {
        global $pdo;

        $stmt = $pdo->prepare("SELECT 
            mp.*, 
            f.nome AS fabricante_nome 
        FROM material_patrimonio mp
        LEFT JOIN fabricante f ON f.id = mp.fabricante_id
        WHERE mp.material_id = :material_id
        ORDER BY mp.modelo ASC, mp.patrimonio ASC");

        $stmt->execute([':material_id' => $materialId]);

        return ['success' => true, 'patrimonios' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public static function listPatrimonios()
    {
        global $pdo;

        $sql = "
        SELECT 
            mp.id,
            mp.material_id,
            m.nome AS nome_material,
            mp.fabricante_id,
            f.nome AS nome_fabricante,
            mp.modelo,
            mp.numero_serie,
            mp.patrimonio,
            mp.status,
            mp.custo_material,
            mp.custo_locacao,
            mp.sublocado,
            mp.fornecedor_id,
            mp.created_at,
            mp.updated_at
        FROM material_patrimonio mp
        LEFT JOIN material m ON mp.material_id = m.id
        LEFT JOIN fabricante f ON mp.fabricante_id = f.id
        ORDER BY mp.id DESC
    ";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $patrimonios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'patrimonios' => $patrimonios
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao buscar patrimônios: ' . $e->getMessage()
            ];
        }
    }

    public static function getPatrimonioById($id)
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare("SELECT * FROM material_patrimonio WHERE id = ?");
            $stmt->execute([$id]);
            $patrimonio = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($patrimonio) {
                return ['success' => true, 'patrimonio' => $patrimonio];
            } else {
                return ['success' => false, 'message' => 'material_patrimonio não encontrada'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao buscar categoria: ' . $e->getMessage()];
        }
    }

    public static function verificaPatrimonioDisponivel($patrimonio)
    {
        global $pdo;

        $sql = "SELECT COUNT(*) FROM material_patrimonio WHERE patrimonio = :patrimonio";
        $params = [':patrimonio' => $patrimonio];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $count = $stmt->fetchColumn();

        return [
            'success' => true,
            'disponivel' => $count == 0
        ];
    }

    public static function updatePatrimonio($id, $data)
    {
        global $pdo;

        try {
            $sql = "UPDATE material_patrimonio SET ";
            $values = [];

            foreach ($data as $key => $value) {
                $sql .= "$key = :$key, ";
                $values[":$key"] = $value;
            }

            $sql = rtrim($sql, ", ");
            $sql .= " WHERE id = :id";
            $values[':id'] = $id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            return ($stmt->rowCount() > 0)
                ? ['success' => true, 'message' => 'Patrimônio atualizado com sucesso']
                : ['success' => false, 'message' => 'Nenhuma alteração realizada'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar patrimônio: ' . $e->getMessage()];
        }
    }

    public static function deletePatrimonio($id)
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare("DELETE FROM material_patrimonio WHERE id = ?");
            $stmt->execute([$id]);

            return ($stmt->rowCount() > 0)
                ? ['success' => true, 'message' => 'Patrimônio removido com sucesso']
                : ['success' => false, 'message' => 'Patrimônio não encontrado'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao excluir patrimônio: ' . $e->getMessage()];
        }
    }

    public static function listFabricantes()
    {
        global $pdo;

        $stmt = $pdo->query("SELECT * FROM fabricante ORDER BY nome ASC");
        return ['success' => true, 'fabricantes' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public static function createFabricante($data)
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare("INSERT INTO fabricante (nome) VALUES (:nome)");
            $stmt->execute([':nome' => $data['nome']]);

            return ['success' => true, 'message' => 'Fabricante criado com sucesso', 'fabricante_id' => $pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao criar fabricante: ' . $e->getMessage()];
        }
    }

    public static function getFabricanteById($id)
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare("SELECT * FROM fabricante WHERE id = ?");
            $stmt->execute([$id]);
            $fabricante = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($fabricante) {
                return ['success' => true, 'fabricante' => $fabricante];
            } else {
                return ['success' => false, 'message' => 'Fabricante não encontrado'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao buscar fabricante: ' . $e->getMessage()];
        }
    }

    public static function deleteFabricante($id)
    {
        global $pdo;

        try {
            // Verificar se está em uso
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM material_patrimonio WHERE fabricante_id = ?");
            $stmtCheck->execute([$id]);
            $emUso = $stmtCheck->fetchColumn();

            if ($emUso > 0) {
                return ['success' => false, 'message' => 'Fabricante vinculado a patrimônio, não pode ser excluído'];
            }

            $stmt = $pdo->prepare("DELETE FROM fabricante WHERE id = ?");
            $stmt->execute([$id]);

            return ($stmt->rowCount() > 0)
                ? ['success' => true, 'message' => 'Fabricante excluído com sucesso']
                : ['success' => false, 'message' => 'Fabricante não encontrado'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao excluir fabricante: ' . $e->getMessage()];
        }
    }

    public static function updateFabricante($id, $data)
    {
        global $pdo;

        try {
            $sql = "UPDATE fabricante SET ";
            $values = [];

            foreach ($data as $key => $value) {
                $sql .= "$key = :$key, ";
                $values[":$key"] = $value;
            }

            $sql = rtrim($sql, ", ");
            $sql .= " WHERE id = :id";
            $values[':id'] = $id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            return ($stmt->rowCount() > 0)
                ? ['success' => true, 'message' => 'Fabricante atualizado com sucesso']
                : ['success' => false, 'message' => 'Nenhuma alteração realizada'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar fabricante: ' . $e->getMessage()];
        }
    }

    public static function getCategoriaById($id)
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare("SELECT * FROM categoria WHERE id = ?");
            $stmt->execute([$id]);
            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($categoria) {
                return ['success' => true, 'categoria' => $categoria];
            } else {
                return ['success' => false, 'message' => 'Categoria não encontrada'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao buscar categoria: ' . $e->getMessage()];
        }
    }

    public static function updateCategoria($id, $data)
    {
        global $pdo;

        try {
            $sql = "UPDATE categoria SET ";
            $values = [];

            foreach ($data as $key => $value) {
                $sql .= "$key = :$key, ";
                $values[":$key"] = $value;
            }

            $sql = rtrim($sql, ", ");
            $sql .= " WHERE id = :id";
            $values[':id'] = $id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            return ($stmt->rowCount() > 0)
                ? ['success' => true, 'message' => 'Categoria atualizada com sucesso']
                : ['success' => false, 'message' => 'Nenhuma alteração realizada'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar categoria: ' . $e->getMessage()];
        }
    }

    public static function createCategoria($data)
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare("INSERT INTO categoria (nome, tipo) VALUES (:nome, :tipo)");
            $stmt->execute([
                ':nome' => $data['nome'],
                ':tipo' => $data['tipo']
            ]);

            return ['success' => true, 'message' => 'Categoria criada com sucesso', 'categoria_id' => $pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao criar categoria: ' . $e->getMessage()];
        }
    }

    public static function listCategorias($tipo = null)
    {
        global $pdo;

        $sql = "SELECT * FROM categoria";
        $params = [];

        if (!empty($tipo)) {
            $sql .= " WHERE tipo = :tipo";
            $params[':tipo'] = $tipo;
        }

        $sql .= " ORDER BY nome ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return ['success' => true, 'categorias' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public static function createServico($data)
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare("INSERT INTO servico (descricao, valor_servico, custo_servico, terceirizado, status, fornecedor_id) 
                               VALUES (:descricao, :valor_servico, :custo_servico, :terceirizado, :status, :fornecedor_id)");
            $stmt->execute([
                ':descricao'      => $data['descricao'],
                ':valor_servico'  => $data['valor_servico'] ?? null,
                ':custo_servico'  => $data['custo_servico'] ?? null,
                ':terceirizado'   => $data['terceirizado'] ?? null,
                ':status'         => $data['status'] ?? null,
                ':fornecedor_id'  => $data['fornecedor_id'] ?? null
            ]);

            return ['success' => true, 'message' => 'Serviço criado com sucesso', 'servico_id' => $pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao criar serviço: ' . $e->getMessage()];
        }
    }

    public static function updateServico($id, $data)
    {
        global $pdo;

        try {
            $sql = "UPDATE servico SET ";
            $values = [];

            foreach ($data as $key => $value) {
                $sql .= "$key = :$key, ";
                $values[":$key"] = $value;
            }

            $sql = rtrim($sql, ", ");
            $sql .= " WHERE id = :id";
            $values[':id'] = $id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            return ($stmt->rowCount() > 0)
                ? ['success' => true, 'message' => 'Serviço atualizado com sucesso']
                : ['success' => false, 'message' => 'Nenhuma alteração realizada'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar serviço: ' . $e->getMessage()];
        }
    }

    public static function deleteServico($id)
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare("DELETE FROM servico WHERE id = ?");
            $stmt->execute([$id]);

            return ($stmt->rowCount() > 0)
                ? ['success' => true, 'message' => 'Serviço excluído com sucesso']
                : ['success' => false, 'message' => 'Serviço não encontrado'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao excluir serviço: ' . $e->getMessage()];
        }
    }

    public static function getServicoById($id)
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare("SELECT * FROM servico WHERE id = ?");
            $stmt->execute([$id]);
            $servico = $stmt->fetch(PDO::FETCH_ASSOC);

            return $servico
                ? ['success' => true, 'servico' => $servico]
                : ['success' => false, 'message' => 'Serviço não encontrado'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao buscar serviço: ' . $e->getMessage()];
        }
    }

    public static function listServicos($filters = [])
    {
        global $pdo;

        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "status = :status";
            $params[':status'] = $filters['status'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare("SELECT * FROM servico $whereClause ORDER BY descricao ASC");
        $stmt->execute($params);

        return ['success' => true, 'servicos' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

}

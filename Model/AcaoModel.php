<?php
// Model/AcaoModel.php

class AcaoModel
{
    private $db;
    private $table_acoes = 'acoes'; // Nome da tabela no banco de dados

    public function __construct()
    {
        $this->db = Database::getInstance()->connect();
    }

    /**
     * Busca todas as ações disponíveis.
     * @return array Lista de objetos de ação.
     */
    public function buscarTodas()
    {
        $query = "SELECT id, nome, ativo FROM {$this->table_acoes} ORDER BY nome ASC";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            // RETORNA OBJETOS → compatível com $a->id, $a->nome
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erro ao buscar todas as ações: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca uma única ação pelo ID.
     * @param int $id ID da ação.
     * @return object|bool Objeto da ação ou FALSE.
     */
    public function buscarPorId($id)
    {
        $query = "SELECT id, nome, ativo 
                  FROM {$this->table_acoes} 
                  WHERE id = :id 
                  LIMIT 1";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_OBJ); // OBJETO!
        } catch (PDOException $e) {
            error_log("Erro ao buscar ação por ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cria ou atualiza uma ação (UPSERT).
     * @param array $dados ['id', 'nome', 'ativo']
     * @return int|bool ID da ação ou FALSE
     */
    public function salvar($dados)
    {
        $nome  = trim($dados['nome']);
        $ativo = (bool)($dados['ativo'] ?? true);

        try {
            if (!empty($dados['id'])) {
                // UPDATE
                $query = "UPDATE {$this->table_acoes}
                          SET nome = :nome, ativo = :ativo
                          WHERE id = :id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':id', $dados['id'], PDO::PARAM_INT);
            } else {
                // INSERT
                $query = "INSERT INTO {$this->table_acoes} (nome, ativo) 
                          VALUES (:nome, :ativo)";
                $stmt = $this->db->prepare($query);
            }

            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':ativo', $ativo, PDO::PARAM_BOOL);

            if ($stmt->execute()) {
                return $dados['id'] ?? $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $_SESSION['erro'] = 'Erro: Já existe uma ação com esse nome.';
            } else {
                error_log("Erro ao salvar ação: " . $e->getMessage());
                $_SESSION['erro'] = 'Erro interno ao salvar a ação.';
            }
            return false;
        }
    }

    /**
     * Exclui uma ação 
     */
    public function excluir($id)
    {
        $query = "DELETE FROM {$this->table_acoes} WHERE id = :id";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao excluir ação: " . $e->getMessage());
            return false;
        }
    }
}

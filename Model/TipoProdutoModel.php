<?php
// Model/TipoProdutoModel.php

class TipoProdutoModel
{
    private $db;
    private $table_produtos = 'tipos_produto';

    public function __construct()
    {
        // Inicializa a conexão com o banco de dados
        $this->db = Database::getInstance()->connect();
    }

    /**
     * Busca todos os tipos de produto disponíveis (ex: Camarão A, B, etc.).
     * @return array Lista de objetos de tipo de produto.
     */
    public function buscarTodos()
    {
        $query = "SELECT id, nome, usa_lote FROM {$this->table_produtos} ORDER BY nome ASC";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Busca um único tipo de produto pelo ID.
     */
    public function buscarPorId($id)
    {
        $query = "SELECT id, nome, usa_lote FROM {$this->table_produtos} WHERE id = :id LIMIT 1";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            // Em caso de erro na consulta
            error_log("Erro ao buscar Tipo de Produto por ID: " . $e->getMessage());
            return false; // Retorna FALSE em caso de erro
        }
    }

    /**
     * Salva (cria ou edita) um tipo de produto.
     */
    public function salvar($dados)
    {
        // Sanitização e preparo dos dados
        $nome = trim($dados['nome']);
        $usa_lote = (int)($dados['usa_lote'] ?? 0); // 1 ou 0

        if (isset($dados['id']) && $dados['id'] > 0) {
            // UPDATE
            $query = "UPDATE {$this->table_produtos} 
                      SET nome = :nome, usa_lote = :usa_lote 
                      WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $dados['id']);
        } else {
            // INSERT
            $query = "INSERT INTO {$this->table_produtos} (nome, usa_lote) 
                      VALUES (:nome, :usa_lote)";
            $stmt = $this->db->prepare($query);
        }

        try {
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':usa_lote', $usa_lote, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            $_SESSION['erro'] = 'Erro ao salvar Tipo de Produto. Nome duplicado?';
            return false;
        }
    }

    /**
     * Exclui um tipo de produto
     * @param int $id
     * @return bool
     */
    public function excluir($id)
    {
        // 1. Verifica se o tipo está sendo usado em produções
        $query_check = "SELECT COUNT(*) FROM producao WHERE tipo_produto_id = :id";
        $stmt_check = $this->db->prepare($query_check);
        $stmt_check->bindParam(':id', $id);
        $stmt_check->execute();
        $em_uso = $stmt_check->fetchColumn() > 0;

        if ($em_uso) {
            $_SESSION['erro'] = 'Não é possível excluir: este tipo está sendo usado em lançamentos de produção.';
            return false;
        }

        // 2. Exclui
        $query = "DELETE FROM {$this->table_produtos} WHERE id = :id";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $_SESSION['erro'] = 'Erro ao excluir tipo de produto.';
            error_log("Erro excluir tipo: " . $e->getMessage());
            return false;
        }
    }

    public function buscarSemLote()
    {
        $query = "SELECT id, nome FROM tipos_produto WHERE usa_lote = 0 ORDER BY nome ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}

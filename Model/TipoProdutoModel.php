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
        $query = "SELECT id, nome FROM {$this->table_produtos} ORDER BY nome ASC";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            // Em caso de erro na consulta
            // logar_erro($e->getMessage());
            return [];
        }
    }
}

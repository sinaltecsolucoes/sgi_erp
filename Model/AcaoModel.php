<?php
// Model/AcaoModel.php

class AcaoModel
{
    private $db;
    private $table_acoes = 'acoes';

    public function __construct()
    {
        // Inicializa a conexão com o banco de dados
        $this->db = Database::getInstance()->connect();
    }

    /**
     * Busca todas as ações disponíveis (ex: Descabeçar, Descascar, Eviscerar).
     * @return array Lista de objetos de ação.
     */
    public function buscarTodas()
    {
        $query = "SELECT id, nome FROM {$this->table_acoes} ORDER BY nome ASC";

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

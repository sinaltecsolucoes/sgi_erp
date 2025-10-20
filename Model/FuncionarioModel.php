<?php
// Model/FuncionarioModel.php

class FuncionarioModel
{
    private $db;
    private $table_funcionarios = 'funcionarios';
    private $table_presencas = 'presencas';

    public function __construct()
    {
        $this->db = Database::getInstance()->connect();
    }

    /**
     * Busca todos os funcionários ativos (do tipo 'producao').
     * @return array Lista de objetos de funcionário.
     */
    public function buscarTodos()
    {
        $query = "SELECT 
                    id, 
                    nome, 
                    tipo 
                  FROM 
                    {$this->table_funcionarios}
                  WHERE 
                    ativo = TRUE AND tipo = 'producao'
                  ORDER BY 
                    nome ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Busca todos os funcionários e indica se estão presentes na data informada.
     * * @param string $data Data no formato 'YYYY-MM-DD'.
     * @return array Lista de objetos com status de presença.
     */
    public function buscarPresentesHoje($data)
    {
        $query = "SELECT 
                    f.id, 
                    f.nome, 
                    f.tipo,
                    -- Verifica se existe um registro de presença para hoje
                    p.presente AS esta_presente
                  FROM 
                    {$this->table_funcionarios} f
                  LEFT JOIN
                    {$this->table_presencas} p 
                    ON f.id = p.funcionario_id AND p.data = :data
                  WHERE 
                    f.ativo = TRUE AND f.tipo = 'producao'
                  ORDER BY 
                    f.nome ASC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':data', $data);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Futuramente, aqui viriam métodos como:
     * - buscarPorId($id)
     * - inativarFuncionario($id)
     * - ...
     */
}

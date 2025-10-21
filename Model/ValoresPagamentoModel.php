<?php
// Model/ValoresPagamentoModel.php

class ValoresPagamentoModel
{
    private $db;
    private $table = 'valores_pagamento';

    public function __construct()
    {
        $this->db = Database::getInstance()->connect();
    }

    /**
     * Busca o valor por quilo para um par específico de Ação e Tipo de Produto.
     * @param int $produto_id ID do tipo de produto.
     * @param int $acao_id ID da ação.
     * @return float O valor por quilo (DECIMAL), ou 0.00 se não encontrado.
     */
    public function buscarValorPorQuilo($produto_id, $acao_id)
    {
        $query = "SELECT 
                    valor_por_quilo 
                  FROM 
                    {$this->table}
                  WHERE 
                    tipo_produto_id = :produto_id AND acao_id = :acao_id
                  LIMIT 1";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':produto_id', $produto_id);
            $stmt->bindParam(':acao_id', $acao_id);
            $stmt->execute();

            $resultado = $stmt->fetch();

            // Retorna 0.00 se não houver valor cadastrado
            return $resultado ? (float)$resultado->valor_por_quilo : 0.00;
        } catch (PDOException $e) {
            // Em caso de erro na consulta
            error_log("Erro ao buscar valor de pagamento: " . $e->getMessage());
            return 0.00;
        }
    }

    // Futuramente, métodos para CRUD de ValoresPagamento viriam aqui.
}

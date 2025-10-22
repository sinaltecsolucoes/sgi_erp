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

  /**
   * Busca todos os valores cadastrados, fazendo JOIN com Ações e Tipos de Produto.
   * @return array Lista de todos os valores de pagamento.
   */
  public function buscarTodos()
  {
    $query = "SELECT 
                    vp.id, 
                    vp.valor_por_quilo, 
                    a.nome AS acao_nome, 
                    tp.nome AS produto_nome,
                    vp.acao_id,
                    vp.tipo_produto_id
                  FROM 
                    {$this->table} vp
                  JOIN 
                    acoes a ON vp.acao_id = a.id
                  JOIN 
                    tipos_produto tp ON vp.tipo_produto_id = tp.id
                  ORDER BY 
                    a.nome, tp.nome";

    $stmt = $this->db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll();
  }

  /**
   * Cria ou atualiza um registro de valor de pagamento.
   */
  public function salvar($dados)
  {
    // Normaliza para float
    $valor = (float)str_replace(',', '.', $dados['valor_por_quilo']);

    if (isset($dados['id']) && $dados['id'] > 0) {
      // UPDATE
      $query = "UPDATE {$this->table} 
                      SET valor_por_quilo = :valor 
                      WHERE id = :id";
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':id', $dados['id']);
    } else {
      // INSERT (com restrição UNIQUE de tipo_produto_id, acao_id)
      $query = "INSERT INTO {$this->table} (tipo_produto_id, acao_id, valor_por_quilo) 
                      VALUES (:tipo_produto_id, :acao_id, :valor)";
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':tipo_produto_id', $dados['tipo_produto_id']);
      $stmt->bindParam(':acao_id', $dados['acao_id']);
    }

    try {
      $stmt->bindParam(':valor', $valor);
      $stmt->execute();
      return true;
    } catch (PDOException $e) {
      // Se for um erro de chave duplicada
      if ($e->getCode() === '23000') {
        $_SESSION['erro'] = 'Erro: Este par (Ação e Tipo de Produto) já possui um valor cadastrado.';
      } else {
        $_SESSION['erro'] = 'Erro interno ao salvar o valor.';
      }
      return false;
    }
  }
}

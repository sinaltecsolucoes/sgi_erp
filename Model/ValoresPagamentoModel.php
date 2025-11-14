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
   * Busca todos os valores com JOIN
   * @return array
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
    return $stmt->fetchAll(PDO::FETCH_OBJ);
  }

  /**
   * Busca por ID para edição
   * @param int $id
   * @return object|null
   */
  public function buscarPorId($id)
  {
    $query = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
  }

  /**
   * Busca valor por quilo
   * @param int $produto_id
   * @param int $acao_id
   * @return float
   */
  public function buscarValorPorQuilo($produto_id, $acao_id)
  {
    $query = "SELECT valor_por_quilo FROM {$this->table} WHERE tipo_produto_id = :produto_id AND acao_id = :acao_id LIMIT 1";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':produto_id', $produto_id);
    $stmt->bindParam(':acao_id', $acao_id);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_OBJ);
    return $resultado ? (float)$resultado->valor_por_quilo : 0.00;
  }

  /**
   * Salva (UPSERT)
   * @param array $dados
   * @return bool
   */
  public function salvar($dados)
  {
    $valor = (float)str_replace(',', '.', $dados['valor_por_quilo']);

    try {
      if (!empty($dados['id'])) {
        // UPDATE
        $query = "UPDATE {$this->table} SET valor_por_quilo = :valor WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $dados['id']);
      } else {
        // INSERT
        $query = "INSERT INTO {$this->table} (tipo_produto_id, acao_id, valor_por_quilo) VALUES (:tipo_produto_id, :acao_id, :valor)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':tipo_produto_id', $dados['tipo_produto_id']);
        $stmt->bindParam(':acao_id', $dados['acao_id']);
      }

      $stmt->bindParam(':valor', $valor);
      return $stmt->execute();
    } catch (PDOException $e) {
      if ($e->getCode() === '23000') {
        $_SESSION['erro'] = 'Já existe um valor para esta combinação de Ação e Produto.';
      } else {
        $_SESSION['erro'] = 'Erro interno ao salvar.';
      }
      return false;
    }
  }

  /**
   * Exclui um valor com segurança
   * @param int $id
   * @return bool
   */
  public function excluir($id)
  {
    try {
      // 1. Pegue tipo_produto_id e acao_id do valor
      $query_get = "SELECT tipo_produto_id, acao_id FROM {$this->table} WHERE id = :id LIMIT 1";
      $stmt_get = $this->db->prepare($query_get);
      $stmt_get->bindParam(':id', $id);
      $stmt_get->execute();
      $valor = $stmt_get->fetch(PDO::FETCH_OBJ);

      if (!$valor) {
        $_SESSION['erro'] = 'Valor não encontrado.';
        return false;
      }

      // 2. Verifica se está em uso na producao
      $query_check = "SELECT COUNT(*) FROM producao 
                        WHERE tipo_produto_id = :tipo_produto_id 
                          AND acao_id = :acao_id";
      $stmt_check = $this->db->prepare($query_check);
      $stmt_check->bindParam(':tipo_produto_id', $valor->tipo_produto_id);
      $stmt_check->bindParam(':acao_id', $valor->acao_id);
      $stmt_check->execute();
      $em_uso = $stmt_check->fetchColumn() > 0;

      if ($em_uso) {
        $_SESSION['erro'] = 'Não é possível excluir: este valor está sendo usado em lançamentos de produção.';
        return false;
      }

      // 3. Exclui
      $query = "DELETE FROM {$this->table} WHERE id = :id";
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':id', $id);
      $stmt->execute();
      return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
      $_SESSION['erro'] = 'Erro no banco de dados: ' . $e->getMessage();
      error_log("Erro excluir valor: " . $e->getMessage());
      return false;
    }
  }
}

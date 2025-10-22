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
   * Busca um único funcionário pelo ID.
   * @param int $id ID do funcionário.
   * @return object|bool Objeto do funcionário ou FALSE.
   */
  public function buscarPorId($id)
  {
    $query = "SELECT 
                    f.*, 
                    u.login, 
                    u.id as usuario_id 
                  FROM 
                    {$this->table_funcionarios} f
                  LEFT JOIN
                    usuarios u ON f.id = u.funcionario_id
                  WHERE 
                    f.id = :id 
                  LIMIT 1";

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    return $stmt->fetch();
  }

  /**
   * Cria ou atualiza um registro de funcionário.
   * @param array $dados Array associativo com nome, tipo, e-mail, etc.
   * @return int|bool Retorna o ID do funcionário ou FALSE em caso de erro.
   */
  public function salvar($dados)
  {
    if (isset($dados['id']) && $dados['id'] > 0) {
      // Lógica de UPDATE
      $query = "UPDATE {$this->table_funcionarios}
                      SET nome = :nome, cpf = :cpf, tipo = :tipo, ativo = :ativo
                      WHERE id = :id";
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':id', $dados['id']);
    } else {
      // Lógica de INSERT
      $query = "INSERT INTO {$this->table_funcionarios} (nome, cpf, tipo, ativo) 
                      VALUES (:nome, :cpf, :tipo, :ativo)";
      $stmt = $this->db->prepare($query);
    }

    try {
      $stmt->bindParam(':nome', $dados['nome']);
      $stmt->bindParam(':cpf', $dados['cpf']);
      $stmt->bindParam(':tipo', $dados['tipo']);
      $stmt->bindParam(':ativo', $dados['ativo'], PDO::PARAM_BOOL);

      if ($stmt->execute()) {
        return $dados['id'] ?? $this->db->lastInsertId();
      }
      return false;
    } catch (PDOException $e) {
      // ERRO: Captura erro de CPF duplicado (restrição UNIQUE)
      if ($e->getCode() === '23000') {
        $_SESSION['erro'] = 'Erro: O CPF informado já está cadastrado no sistema.';
      } else {
        $_SESSION['erro'] = 'Erro interno ao salvar o funcionário.';
      }
      return false;
    }
  }

  /**
   * Cria ou atualiza o login do usuário associado a um funcionário.
   * @param int $funcionario_id ID do funcionário.
   * @param string $login O login do sistema.
   * @param string $senha A senha (texto plano).
   * @return bool TRUE se sucesso, FALSE se falha.
   */
  public function criarOuAtualizarUsuario($funcionario_id, $login, $senha)
  {
    // Verifica se o login já existe
    $check_query = "SELECT id FROM usuarios WHERE funcionario_id = :funcionario_id";
    $check_stmt = $this->db->prepare($check_query);
    $check_stmt->bindParam(':funcionario_id', $funcionario_id);
    $check_stmt->execute();
    $usuario_existe = $check_stmt->fetch();

    $senha_hash_query = $senha ? ", senha = PASSWORD(:senha)" : "";

    if ($usuario_existe) {
      // UPDATE
      $query = "UPDATE usuarios SET login = :login {$senha_hash_query} WHERE funcionario_id = :funcionario_id";
    } else {
      // INSERT
      $query = "INSERT INTO usuarios (funcionario_id, login, senha) VALUES (:funcionario_id, :login, PASSWORD(:senha))";
    }

    try {
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':funcionario_id', $funcionario_id);
      $stmt->bindParam(':login', $login);
      if ($senha) {
        $stmt->bindParam(':senha', $senha);
      }

      return $stmt->execute();
    } catch (PDOException $e) {
      // Erro: Login duplicado (UNIQUE na tabela usuarios)
      $_SESSION['erro'] = "Erro de login: o nome de login **{$login}** já está em uso.";
      return false;
    }
  }

  /**
   * Conta o total de funcionários marcados como presentes na data de hoje.
   * @return int O número total de presentes.
   */
  public function contarPresentesHoje()
  {
    $hoje = date('Y-m-d');
    $query = "SELECT 
                    COUNT(id) AS total_presentes
                  FROM 
                    {$this->table_presencas}
                  WHERE 
                    data = :hoje AND presente = TRUE";

    try {
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':hoje', $hoje);
      $stmt->execute();

      return (int)$stmt->fetch()->total_presentes;
    } catch (PDOException $e) {
      error_log("Erro ao contar presentes: " . $e->getMessage());
      return 0;
    }
  }
}

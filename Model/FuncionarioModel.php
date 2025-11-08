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
                    f.id, 
                    f.nome, 
                    f.cpf, 
                    f.tipo,
                    f.ativo,
                    u.login    
                  FROM 
                    {$this->table_funcionarios} f
                  LEFT JOIN 
                    usuarios u ON f.id = u.funcionario_id
                  ORDER BY 
                    f.nome ASC";

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
      if ($e->getCode() === '23000') {
        return 'CPF_DUPLICADO';
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
    // 1. Verifica se o login já existe para este funcionário
    $check_query = "SELECT id, login FROM usuarios WHERE funcionario_id = :funcionario_id";
    $check_stmt = $this->db->prepare($check_query);
    $check_stmt->bindParam(':funcionario_id', $funcionario_id);
    $check_stmt->execute();
    $usuario_existente = $check_stmt->fetch(); // Objeto ou FALSE

    $query = "";

    // Hashear a senha se ela foi fornecida (sempre necessário para salvar)
    $senha_hash = null;
    if (!empty($senha)) {
      $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    }

    // Se o LOGIN não foi fornecido, usamos o login existente para evitar erro de UNIQUE no UPDATE
    // No INSERT, se o login for vazio, a chamada nem acontece, mas este é um bom fallback
    $login_para_salvar = !empty($login) ? $login : ($usuario_existente->login ?? null);


    // =================================================================
    // LÓGICA DE FLEXIBILIDADE DE CRIAÇÃO/ATUALIZAÇÃO
    // =================================================================

    if ($usuario_existente) {
      // UPDATE: Já existe um registro de usuário

      if ($senha_hash) {
        // Cenário B: Atualiza o Login E a Senha
        $query = "UPDATE usuarios SET login = :login, senha = :senha_hash WHERE funcionario_id = :funcionario_id";
      } else {
        // Cenário C: Atualiza APENAS o Login (a senha é mantida)
        $query = "UPDATE usuarios SET login = :login WHERE funcionario_id = :funcionario_id";
      }
    } else {
      // INSERT: Não existe registro de usuário

      // O Controller já garantiu que pelo menos LOGIN ou SENHA existe aqui.

      if (!empty($login_para_salvar)) {
        // Cenário A: Cria o registro. Se a senha veio vazia, a coluna 'senha' pode ser NULL.

        // A senha deve ser hasheada e salva. Se a senha foi vazia, a coluna 'senha'
        // no banco deve aceitar NULL.
        $query = "INSERT INTO usuarios (funcionario_id, login, senha) 
                  VALUES (:funcionario_id, :login, :senha_hash)";
      } else {
        // Não deve cair aqui se a chamada veio do Controller com validação, mas é um bom ponto de segurança.
        $_SESSION['erro'] = "Erro de lógica: Tentativa de criar usuário sem Login.";
        return false;
      }
    }

    try {
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':funcionario_id', $funcionario_id);
      $stmt->bindParam(':login', $login_para_salvar); // Usamos a variável limpa

      // Bind da senha hasheada (apenas se ela existir para INSERT/UPDATE)
      if ($senha_hash) {
        $stmt->bindParam(':senha_hash', $senha_hash);
      } else if (strpos($query, ':senha_hash') !== false) {
        // Se estamos fazendo um INSERT, mas a senha veio vazia, bindamos NULL (se o banco permitir)
        // Isso atende à regra de 'só salva se for informada' no INSERT.
        $stmt->bindValue(':senha_hash', null, PDO::PARAM_NULL);
      }

      return $stmt->execute();
    } catch (PDOException $e) {
      // ... (Restante do tratamento de erro)
      if ($e->getCode() === '23000') {
        $_SESSION['erro'] = "Erro de login: o nome de login **{$login_para_salvar}** já está em uso.";
      } else {
        error_log("Erro ao salvar login/senha: " . $e->getMessage());
        $_SESSION['erro'] = "Erro interno ao salvar o login do usuário.";
      }
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

  /**
   * Busca um funcionário ativo pelo CPF.
   * @param string $cpf CPF (somente números).
   * @return object|bool Objeto do funcionário com ID, ou FALSE.
   */
  public function buscarPorCpf($cpf)
  {
    $query = "SELECT id, nome FROM {$this->table_funcionarios} WHERE cpf = :cpf LIMIT 1";

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':cpf', $cpf);
    $stmt->execute();

    return $stmt->fetch();
  }
}

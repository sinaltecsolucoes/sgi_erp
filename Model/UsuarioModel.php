<?php
// Model/UsuarioModel.php

class UsuarioModel
{
  private $db;
  private $table_usuarios = 'usuarios';
  private $table_funcionarios = 'funcionarios';

  public function __construct()
  {
    // Obtém a instância única da conexão de banco de dados
    $this->db = Database::getInstance()->connect();
  }

  /**
   * Tenta autenticar o usuário no sistema com dupla checagem para migração de hash.
   * @param string $login O login (username) do usuário.
   * @param string $senha A senha em texto plano.
   * @return object|bool Retorna o objeto do usuário se logado, ou FALSE se falhar.
   */
  public function logar($login, $senha)
  {
    $query = "SELECT 
                u.id as usuario_id,
                u.senha as senha_hash, -- Seleciona o hash da senha BCrypt
                f.id as funcionario_id, 
                f.nome as funcionario_nome,
                f.tipo as funcionario_tipo 
              FROM 
                {$this->table_usuarios} u
              JOIN
                {$this->table_funcionarios} f ON u.funcionario_id = f.id
              WHERE 
                u.login = :login
              LIMIT 1";

    try {
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':login', $login);
      $stmt->execute();
      $usuario = $stmt->fetch();

      if (!$usuario) {
        return false; // Usuário não encontrado
      }

      $senha_valida = false;

      // 1. TENTA AUTENTICAÇÃO ATUAL (BCRYPT)
      if (password_verify($senha, $usuario->senha_hash)) {
        $senha_valida = true;
      }

      // 2. TENTA AUTENTICAÇÃO ANTIGA (MySQL PASSWORD() ou Texto Puro)
      // APENAS SE A PRIMEIRA FALHAR
      if (!$senha_valida) {
        // Usa a lógica antiga (MySQL PASSWORD() era a anterior, baseada no código comentado)
        $antiga_query = "SELECT id FROM usuarios WHERE login = :login AND senha = PASSWORD(:senha_antiga)";
        $antiga_stmt = $this->db->prepare($antiga_query);
        $antiga_stmt->bindParam(':login', $login);
        $antiga_stmt->bindParam(':senha_antiga', $senha);
        $antiga_stmt->execute();

        if ($antiga_stmt->rowCount() > 0) {
          // Senha ANTIGA válida!
          $senha_valida = true;

          // ** MIGRAÇÃO AUTOMÁTICA DA SENHA **
          $nova_senha_hash = password_hash($senha, PASSWORD_DEFAULT);
          $this->db->prepare("UPDATE usuarios SET senha = :hash WHERE id = :id")
            ->execute([':hash' => $nova_senha_hash, ':id' => $usuario->usuario_id]);

          error_log("Migração de hash efetuada para o usuário: " . $login);
        }
      }

      if ($senha_valida) {
        // Autenticação bem-sucedida
        unset($usuario->senha_hash); // Remove o hash por segurança
        return $usuario;
      }

      return false; // Senha inválida
    } catch (PDOException $e) {
      error_log("Erro ao tentar logar: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Atualiza a senha de um usuário associado a um funcionário, aplicando o hash BCrypt.
   * @param int $funcionario_id ID do funcionário.
   * @param string $nova_senha A nova senha (texto plano).
   * @return bool TRUE se sucesso, FALSE se falha.
   */
  public function atualizarSenha($funcionario_id, $nova_senha)
  {
    // 1. Gerar o hash da nova senha
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

    // 2. Query de atualização
    $query = "UPDATE {$this->table_usuarios} 
                  SET senha = :senha_hash 
                  WHERE funcionario_id = :funcionario_id";

    try {
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':senha_hash', $senha_hash);
      $stmt->bindParam(':funcionario_id', $funcionario_id);

      return $stmt->execute();
    } catch (PDOException $e) {
      error_log("Erro ao atualizar a senha do usuário: " . $e->getMessage());
      // Em caso de erro, você pode querer registrar a exceção.
      return false;
    }
  }
}

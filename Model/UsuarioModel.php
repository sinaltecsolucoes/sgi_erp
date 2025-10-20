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
     * Tenta autenticar o usuário no sistema.
     * @param string $login O login (username) do usuário.
     * @param string $senha A senha em texto plano.
     * @return object|bool Retorna o objeto do usuário (com dados do funcionário) se logado, ou FALSE se falhar.
     */
    public function logar($login, $senha)
    {
        // Consulta SQL: Busca o usuário pelo login e compara a senha criptografada.
        // O MySQL possui a função PASSWORD() para hashear senhas, usada no nosso seeder.
        $query = "SELECT 
                    u.id as usuario_id, 
                    u.login, 
                    f.id as funcionario_id, 
                    f.nome as funcionario_nome,
                    f.tipo as funcionario_tipo 
                  FROM 
                    {$this->table_usuarios} u
                  JOIN
                    {$this->table_funcionarios} f ON u.funcionario_id = f.id
                  WHERE 
                    u.login = :login AND u.senha = PASSWORD(:senha)
                  LIMIT 0,1";

        try {
            // Prepara a consulta
            $stmt = $this->db->prepare($query);

            // Bind dos valores
            $stmt->bindParam(':login', $login);
            $stmt->bindParam(':senha', $senha);

            // Executa a consulta
            $stmt->execute();

            // Verifica se encontrou um registro
            if ($stmt->rowCount() > 0) {
                // Retorna o objeto do usuário encontrado
                return $stmt->fetch();
            } else {
                return false; // Login ou senha inválidos
            }
        } catch (PDOException $e) {
            // Em caso de erro na consulta, pode ser útil logar o erro
            // echo "Erro ao tentar logar: " . $e->getMessage();
            return false;
        }
    }

    // Futuramente, outros métodos como 'criarUsuario', 'atualizarSenha', etc., viriam aqui.
}

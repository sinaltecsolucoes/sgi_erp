<?php
// Model/AcaoModel.php

class AcaoModel
{
    private $db;
    private $table_acoes = 'acoes'; // Nome da tabela no banco de dados

    public function __construct()
    {
        // Inicializa a conexão com o banco de dados
        $this->db = Database::getInstance()->connect();
    }

    /**
     * Busca todas as ações disponíveis (ex: Descabeçar, Descascar, Eviscerar).
     * Inclui o status 'ativo' para listagem.
     * @return array Lista de objetos de ação.
     */
    public function buscarTodas()
    {
        $query = "SELECT id, nome, ativo FROM {$this->table_acoes} ORDER BY nome ASC";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erro ao buscar todas as ações: " . $e->getMessage());
            return [];
        }
    }
    /**
     * Busca uma única ação pelo ID para a tela de edição.
     * @param int $id ID da ação.
     * @return object|bool Objeto da ação ou FALSE se não encontrado.
     */
    public function buscarPorId($id)
    {
        $query = "SELECT id, nome, ativo 
                  FROM {$this->table_acoes} 
                  WHERE id = :id 
                  LIMIT 1";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erro ao buscar ação por ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cria ou atualiza um registro de ação (o famoso 'UPSERT').
     * @param array $dados Array associativo com 'id', 'nome' e 'ativo'.
     * @return int|bool Retorna o ID da ação salva ou FALSE em caso de erro.
     */
    public function salvar($dados)
    {
        // Tratamento dos dados para garantir tipos corretos no banco
        $nome  = trim($dados['nome']);
        // O MySQL trata 1 como TRUE e 0 como FALSE (usamos bool no PHP para clareza)
        $ativo = (bool)$dados['ativo'];

        if (isset($dados['id']) && $dados['id'] > 0) {
            // É uma EDIÇÃO (UPDATE)
            $query = "UPDATE {$this->table_acoes}
                      SET nome = :nome, ativo = :ativo
                      WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $dados['id'], PDO::PARAM_INT);
        } else {
            // É um NOVO CADASTRO (INSERT)
            $query = "INSERT INTO {$this->table_acoes} (nome, ativo) 
                      VALUES (:nome, :ativo)";
            $stmt = $this->db->prepare($query);
        }

        try {
            // Binds comuns para INSERT e UPDATE
            $stmt->bindParam(':nome', $nome);
            // PDO::PARAM_BOOL garante que 0 ou 1 seja enviado corretamente
            $stmt->bindParam(':ativo', $ativo, PDO::PARAM_BOOL);

            if ($stmt->execute()) {
                // Retorna o ID do registro (existente ou recém-criado)
                return $dados['id'] ?? $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            // Código de erro '23000' é comum para violação de constraint (ex: nome duplicado)
            if ($e->getCode() === '23000') {
                $_SESSION['erro'] = 'Erro: O nome da ação já existe. Por favor, escolha um nome diferente.';
            } else {
                error_log("Erro ao salvar a ação: " . $e->getMessage());
                $_SESSION['erro'] = 'Erro interno ao salvar o registro da ação.';
            }
            return false;
        }
    }
}

<?php
// Model/EquipeModel.php

class EquipeModel
{
  private $db;
  private $table_equipes = 'equipes';
  private $table_assoc = 'equipe_funcionarios';
  private $table_funcionarios = 'funcionarios';

  public function __construct()
  {
    $this->db = Database::getInstance()->connect();
  }

  /**
   * Cria uma nova equipe sob a liderança do apontador.
   * @param int $apontador_id ID do funcionário Apontador.
   * @param string $nome Nome da equipe.
   * @return int|bool Retorna o ID da nova equipe ou FALSE em caso de falha.
   */
  /* public function criarEquipe($apontador_id, $nome)
    {
        $query = "INSERT INTO {$this->table_equipes} (apontador_id, nome) VALUES (:apontador_id, :nome)";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':apontador_id', $apontador_id);
            $stmt->bindParam(':nome', $nome);

            if ($stmt->execute()) {
                // Retorna o ID do registro que acabou de ser criado
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            // Em caso de erro (ex: nome de equipe duplicado, se tivéssemos UNIQUE)
            return false;
        }
    } */

  /**
   * Cria uma nova equipe sob a liderança do apontador.
   * @param int $apontador_id ID do funcionário Apontador.
   * @param string $nome Nome da equipe.
   * @return int|bool Retorna o ID da nova equipe ou FALSE em caso de falha.
   */
  public function criarEquipe($apontador_id, $nome)
  {
    // NOVO: Usa a data atual para marcar a atividade da equipe
    $data_hoje = date('Y-m-d');

    $query = "INSERT INTO {$this->table_equipes} (apontador_id, nome, data_atividade) 
              VALUES (:apontador_id, :nome, :data_atividade)"; // Coluna adicionada

    try {
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':apontador_id', $apontador_id);
      $stmt->bindParam(':nome', $nome);
      $stmt->bindParam(':data_atividade', $data_hoje); // Bind da data de hoje

      if ($stmt->execute()) {
        return $this->db->lastInsertId();
      }
      return false;
    } catch (PDOException $e) {
      return false;
    }
  }

  /**
   * Associa um funcionário a uma equipe.
   */
  public function associarFuncionario($equipe_id, $funcionario_id)
  {
    $query = "INSERT IGNORE INTO {$this->table_assoc} (equipe_id, funcionario_id) VALUES (:equipe_id, :funcionario_id)";

    try {
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':equipe_id', $equipe_id);
      $stmt->bindParam(':funcionario_id', $funcionario_id);
      return $stmt->execute();
    } catch (PDOException $e) {
      return false;
    }
  }

  /**
   * Busca a equipe ativa do apontador, criada HOJE.
   * @param int $apontador_id ID do funcionário Apontador.
   * @return object|bool A equipe ou FALSE.
   */
  public function buscarEquipeDoApontador($apontador_id)
  {
    $hoje = date('Y-m-d');

    // Filtra pela nova coluna data_atividade
    $query = "SELECT 
                id, 
                nome 
              FROM 
                {$this->table_equipes} 
              WHERE 
                apontador_id = :apontador_id AND data_atividade = :hoje 
              LIMIT 1";

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':apontador_id', $apontador_id);
    $stmt->bindParam(':hoje', $hoje); // Filtro pela data de atividade
    $stmt->execute();

    return $stmt->fetch();
  }

  /**
   * Busca todos os funcionários associados a uma equipe.
   * @param int $equipe_id ID da equipe.
   * @return array Lista de funcionários.
   */
  public function buscarFuncionariosDaEquipe($equipe_id)
  {
    $query = "SELECT 
                    f.id, 
                    f.nome 
                  FROM 
                    {$this->table_funcionarios} f
                  JOIN
                    {$this->table_assoc} ea ON f.id = ea.funcionario_id
                  WHERE 
                    ea.equipe_id = :equipe_id
                  ORDER BY f.nome ASC";

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':equipe_id', $equipe_id);
    $stmt->execute();

    return $stmt->fetchAll();
  }

  /**
   * Remove todos os funcionários de uma equipe específica.
   */
  public function removerTodosFuncionarios($equipe_id)
  {
    $query = "DELETE FROM {$this->table_assoc} WHERE equipe_id = :equipe_id";

    try {
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':equipe_id', $equipe_id);
      return $stmt->execute();
    } catch (PDOException $e) {
      return false;
    }
  }

  /**
   * Atualiza apenas o nome de uma equipe existente.
   */
  public function atualizarNome($equipe_id, $novo_nome)
  {
    $query = "UPDATE {$this->table_equipes} SET nome = :nome WHERE id = :equipe_id";

    try {
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':nome', $novo_nome);
      $stmt->bindParam(':equipe_id', $equipe_id);
      return $stmt->execute();
    } catch (PDOException $e) {
      return false;
    }
  }

  /**
   * Busca os IDs dos funcionários que já estão em alguma equipe HOJE.
   * (Retorna TODOS os alocados, incluindo os do apontador que está chamando)
   * @return array IDs dos funcionários alocados.
   */
  public function buscarFuncionariosAlocadosHoje()
  {
    $hoje = date('Y-m-d');

    $query = "SELECT 
                ef.funcionario_id 
              FROM 
                {$this->table_assoc} ef
              JOIN
                {$this->table_equipes} e ON ef.equipe_id = e.id
              WHERE
                e.data_atividade = :hoje -- Filtra pela data de atividade
              GROUP BY
                ef.funcionario_id";

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':hoje', $hoje);
    $stmt->execute();

    // Retorna um array simples de IDs (inteiros)
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN, 0));
  }

  /**
   * Salva ou atualiza uma equipe para o apontador.
   * @param int $apontador_id ID do apontador.
   * @param string $nome_equipe Nome da equipe.
   * @param array $membros_ids Array de IDs dos membros selecionados.
   * @return bool True se salvo com sucesso, false em caso de erro.
   */
  public function salvarEquipe($apontador_id, $nome_equipe, $membros_ids)
  {
    // 1. Busca equipe existente do apontador
    $equipe = $this->buscarEquipeDoApontador($apontador_id);
    $equipe_id = null;

    if (!$equipe) {
      // 2. Cria nova equipe se não existir
      $equipe_id = $this->criarEquipe($apontador_id, $nome_equipe);
      if (!$equipe_id) {
        return false;
      }
    } else {
      // 2b. Usa ID existente e atualiza nome
      $equipe_id = $equipe->id;
      $this->atualizarNome($equipe_id, $nome_equipe);
    }

    // 3. Remove todos os membros antigos
    $this->removerTodosFuncionarios($equipe_id);

    // 4. Adiciona os novos membros
    foreach ($membros_ids as $funcionario_id) {
      $id = (int)$funcionario_id; // Garante que é inteiro
      if (!$this->associarFuncionario($equipe_id, $id)) {
        return false; // Retorna false se falhar em algum
      }
    }

    return true;
  }

  /**
   * Busca TODAS as equipes ativas do apontador hoje.
   * @param int $apontador_id ID do funcionário Apontador.
   * @return array Lista de equipes.
   */
  public function buscarTodasEquipesDoApontador($apontador_id)
  {
    $hoje = date('Y-m-d');

    $query = "SELECT 
                id, 
                nome 
              FROM 
                {$this->table_equipes} 
              WHERE 
                apontador_id = :apontador_id AND data_atividade = :hoje 
              ORDER BY 
                nome ASC";

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':apontador_id', $apontador_id);
    $stmt->bindParam(':hoje', $hoje);
    $stmt->execute();

    return $stmt->fetchAll();
  }

  /**
   * Remove uma equipe e todas as suas associações de funcionários.
   * @param int $equipe_id ID da equipe a ser excluída.
   * @return bool TRUE se a exclusão for bem-sucedida (ou se não houver registros), FALSE em caso de erro.
   */
  public function excluirEquipe($equipe_id)
  {
    // Usa transação para garantir que as duas exclusões aconteçam ou falhem juntas
    $this->db->beginTransaction();

    try {
      // 1. Remove todas as associações de funcionários (tabela equipe_funcionarios)
      $query_assoc = "DELETE FROM {$this->table_assoc} WHERE equipe_id = :equipe_id";
      $stmt_assoc = $this->db->prepare($query_assoc);
      $stmt_assoc->bindParam(':equipe_id', $equipe_id);
      $stmt_assoc->execute();

      // 2. Remove a equipe principal (tabela equipes)
      $query_equipe = "DELETE FROM {$this->table_equipes} WHERE id = :equipe_id";
      $stmt_equipe = $this->db->prepare($query_equipe);
      $stmt_equipe->bindParam(':equipe_id', $equipe_id);
      $stmt_equipe->execute();

      $this->db->commit();
      return true;
    } catch (PDOException $e) {
      $this->db->rollBack();
      error_log("Erro ao excluir equipe: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Busca uma única equipe pelo ID.
   * @param int $equipe_id ID da equipe.
   * @return object|null A equipe ou NULL.
   */
  public function buscarEquipePorId($equipe_id)
  {
    $query = "SELECT id, nome, apontador_id, data_atividade FROM {$this->table_equipes} WHERE id = :equipe_id LIMIT 1";

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':equipe_id', $equipe_id);
    $stmt->execute();

    return $stmt->fetch();
  }

  /**
   * Remove um único funcionário de uma equipe específica.
   */
  public function removerFuncionarioDeEquipe($equipe_id, $funcionario_id)
  {
    $query = "DELETE FROM {$this->table_assoc} WHERE equipe_id = :equipe_id AND funcionario_id = :funcionario_id";

    try {
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':equipe_id', $equipe_id);
      $stmt->bindParam(':funcionario_id', $funcionario_id);
      return $stmt->execute();
    } catch (PDOException $e) {
      // Logar erro
      return false;
    }
  }

  /**
   * Busca TODAS as equipes ativas hoje, com nome do apontador.
   * @return array
   */
  public function buscarTodasEquipesAtivasHoje()
  {
    $hoje = date('Y-m-d');

    $query = "SELECT 
                e.id, 
                e.nome, 
                f.nome AS apontador_nome
              FROM 
                {$this->table_equipes} e
              JOIN 
                {$this->table_funcionarios} f ON e.apontador_id = f.id
              WHERE 
                e.data_atividade = :hoje
              ORDER BY 
                e.nome ASC";

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':hoje', $hoje);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_OBJ);
  }

  /**
   * Verifica se um funcionário está associado a uma equipe.
   */
  public function estaFuncionarioNaEquipe($equipe_id, $funcionario_id)
  {
    $query = "SELECT 1 FROM {$this->table_assoc} WHERE equipe_id = :equipe_id AND funcionario_id = :funcionario_id LIMIT 1";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':equipe_id', $equipe_id);
    $stmt->bindParam(':funcionario_id', $funcionario_id);
    $stmt->execute();
    return $stmt->fetchColumn() !== false;
  }
  
}

<?php
// Model/ProducaoModel.php

class ProducaoModel
{
    private $db;
    private $table_producao = 'producao';

    public function __construct()
    {
        // Inicializa a conexão com o banco de dados
        $this->db = Database::getInstance()->connect();
    }

    /**
     * Registra um lançamento de produção na base de dados.
     * * @param int $funcionario_id ID do funcionário que realizou a ação.
     * @param int $acao_id ID da ação realizada (ex: descabeçar).
     * @param int $tipo_produto_id ID do tipo de produto (ex: camarão Pescado).
     * @param float $quantidade_kg Quantidade produzida em quilos.
     * @param int|null $equipe_id ID da equipe (NULL se for produção individual).
     * @return bool TRUE se a inserção for bem-sucedida, FALSE caso contrário.
     */
    public function registrarLancamento($funcionario_id, $acao_id, $tipo_produto_id, $quantidade_kg, $equipe_id = null)
    {
        $query = "INSERT INTO {$this->table_producao} 
                  (funcionario_id, acao_id, tipo_produto_id, quantidade_kg, data_hora, equipe_id) 
                  VALUES 
                  (:funcionario_id, :acao_id, :tipo_produto_id, :quantidade_kg, NOW(), :equipe_id)";

        try {
            $stmt = $this->db->prepare($query);

            // Bind dos parâmetros
            $stmt->bindParam(':funcionario_id', $funcionario_id);
            $stmt->bindParam(':acao_id', $acao_id);
            $stmt->bindParam(':tipo_produto_id', $tipo_produto_id);
            $stmt->bindParam(':quantidade_kg', $quantidade_kg);

            // Tratamento especial para $equipe_id (pode ser NULL)
            if (is_null($equipe_id)) {
                $stmt->bindValue(':equipe_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':equipe_id', $equipe_id);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            // Em caso de erro na consulta, logar ou tratar.
            // echo "Erro ao registrar produção: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Soma a quantidade total de KG produzidos na data de hoje.
     * @return float O total de quilos produzidos.
     */
    public function somarProducaoHoje()
    {
        // Usa a data atual no início do dia
        $hoje_inicio = date('Y-m-d 00:00:00');
        // E o final do dia
        $hoje_fim = date('Y-m-d 23:59:59');

        $query = "SELECT 
                    SUM(quantidade_kg) AS total_kg_hoje
                  FROM 
                    {$this->table_producao}
                  WHERE 
                    data_hora BETWEEN :hoje_inicio AND :hoje_fim";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':hoje_inicio', $hoje_inicio);
            $stmt->bindParam(':hoje_fim', $hoje_fim);
            $stmt->execute();

            // Retorna 0.00 se o resultado for nulo
            $resultado = $stmt->fetch()->total_kg_hoje;
            return (float)($resultado ?? 0.00);
        } catch (PDOException $e) {
            error_log("Erro ao somar produção: " . $e->getMessage());
            return 0.00;
        }
    }
}

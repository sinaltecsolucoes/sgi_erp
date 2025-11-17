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
     * @param int $funcionario_id ID do funcionário.
     * @param int $acao_id ID da ação realizada.
     * @param int $tipo_produto_id ID do tipo de produto.
     * @param string $lote_produto NOVO: Lote do produto.
     * @param float $quantidade_kg Quantidade produzida em quilos.
     * @param int|null $equipe_id ID da equipe.
     * @return bool TRUE se a inserção for bem-sucedida, FALSE caso contrário.
     */
    public function registrarLancamento($funcionario_id, $acao_id, $tipo_produto_id, $lote_produto, $quantidade_kg, $equipe_id = null, $hora_inicio = null, $hora_fim = null)
    {
        $query = "INSERT INTO {$this->table_producao} 
                  (funcionario_id, acao_id, tipo_produto_id, lote_produto, quantidade_kg, data_hora, equipe_id, hora_inicio, hora_fim) 
                  VALUES 
                  (:funcionario_id, :acao_id, :tipo_produto_id, :lote_produto, :quantidade_kg, NOW(), :equipe_id, :hora_inicio, :hora_fim)";

        try {
            $stmt = $this->db->prepare($query);

            // Bind dos parâmetros
            $stmt->bindParam(':funcionario_id', $funcionario_id);
            $stmt->bindParam(':acao_id', $acao_id);
            $stmt->bindParam(':tipo_produto_id', $tipo_produto_id);
            $stmt->bindParam(':lote_produto', $lote_produto);
            $stmt->bindParam(':quantidade_kg', $quantidade_kg);
            $stmt->bindParam(':hora_inicio', $hora_inicio);
            $stmt->bindParam(':hora_fim', $hora_fim);

            // Tratamento especial para $equipe_id (pode ser NULL)
            if (is_null($equipe_id)) {
                $stmt->bindValue(':equipe_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':equipe_id', $equipe_id);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
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

    public function buscarLancamentosPorData($data)
    {
        $sql = "SELECT 
                p.id,
                p.funcionario_id,
                p.acao_id,
                p.tipo_produto_id,
                p.lote_produto,
                p.quantidade_kg,
                p.equipe_id,
                p.hora_inicio,
                p.hora_fim
            FROM {$this->table_producao} p
            WHERE DATE(p.data_hora) = :data
            ORDER BY p.funcionario_id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':data', $data);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function excluirLancamento($id)
    {
        $sql = "DELETE FROM producao WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
    }
    
    public function buscarLancamentoUnico($data, $funcionario_id, $acao_id, $tipo_produto_id)
    {
        $sql = "SELECT id, quantidade_kg 
            FROM producao 
            WHERE DATE(data_hora) = ? 
              AND funcionario_id = ? 
              AND acao_id = ? 
              AND tipo_produto_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$data, $funcionario_id, $acao_id, $tipo_produto_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function atualizarLancamento($id, $acao_id, $tipo_produto_id, $quantidade_kg, $lote_produto, $hora_inicio, $hora_fim)
    {
        $sql = "UPDATE producao SET 
                acao_id = :acao,          
                tipo_produto_id = :tp,    
                quantidade_kg = :qtd,
                lote_produto = :lote,
                hora_inicio = :inicio,
                hora_fim = :fim
            WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':acao' => $acao_id,
                ':tp' => $tipo_produto_id,
                ':qtd' => $quantidade_kg,
                ':lote' => $lote_produto,
                ':inicio' => $hora_inicio,
                ':fim' => $hora_fim,
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar produção: " . $e->getMessage());
            return false;
        }
    }

    public function buscarTodosLancamentosDoDia($data)
    {
        $sql = "SELECT 
                p.id,
                p.funcionario_id,
                f.nome as funcionario_nome,
                p.acao_id,
                a.nome as acao_nome,
                p.tipo_produto_id,
                tp.nome as produto_nome,
                p.quantidade_kg,
                p.lote_produto,
                p.hora_inicio,
                p.hora_fim
            FROM producao p
            JOIN funcionarios f ON p.funcionario_id = f.id
            JOIN acoes a ON p.acao_id = a.id
            JOIN tipos_produto tp ON p.tipo_produto_id = tp.id
            WHERE DATE(p.data_hora) = ?
            ORDER BY f.nome, p.hora_inicio";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$data]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

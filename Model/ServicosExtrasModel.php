<?php
// Model/ServicosExtrasModel.php

class ServicosExtrasModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->connect();
    }

    public function registrar($funcionario_id, $descricao, $valor, $acao_id = null)
    {
        $sql = "INSERT INTO servicos_extras 
            (funcionario_id, descricao, valor, data, acao_id) 
            VALUES (:func, :desc, :valor, CURDATE(), :acao)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':func', $funcionario_id, PDO::PARAM_INT);
            $stmt->bindValue(':desc', $descricao, PDO::PARAM_STR);
            $stmt->bindValue(':valor', $valor, PDO::PARAM_STR);
            $stmt->bindValue(':acao', $acao_id, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (Exception $e) {
            error_log("Erro ao salvar serviço extra: " . $e->getMessage());
            return false;
        }
    }

    public function buscarPorPeriodo($inicio, $fim)
    {
        $fim .= ' 23:59:59';

        $sql = "SELECT 
                se.funcionario_id,
                f.nome AS funcionario_nome,
                DATE(se.data) AS data_servico,
                se.descricao,
                se.valor
                FROM servicos_extras se
            JOIN funcionarios f ON se.funcionario_id = f.id
            WHERE se.data BETWEEN ? AND ?
            ORDER BY se.data DESC, f.nome";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$inicio, $fim]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            error_log("Erro ao buscar serviços extras: " . $e->getMessage());
            return [];
        }
    }
}

<?php
// Model/RelatorioModel.php

class RelatorioModel
{
    private $db;
    private $valPagamentoModel; // Para buscar o valor/quilo

    private $table_producao = 'producao';
    private $table_funcionarios = 'funcionarios';
    private $table_acoes = 'acoes';
    private $table_tipos_produto = 'tipos_produto';

    public function __construct()
    {
        $this->db = Database::getInstance()->connect();
        // Inicializa o model que buscará os valores por quilo
        $this->valPagamentoModel = new ValoresPagamentoModel();
    }

    /**
     * Calcula a produção total e o valor a pagar por funcionário dentro de um período.
     * @param string $data_inicio Data de início do período (YYYY-MM-DD).
     * @param string $data_fim Data de fim do período (YYYY-MM-DD).
     * @return array Lista de resultados com total_kg, valor_a_pagar, etc.
     */
    public function calcularPagamentoPorFuncionario($data_inicio, $data_fim)
    {
        // SQL: Agrupa a produção por Funcionário, Ação e Produto (que define o valor)
        $query = "SELECT 
                    f.id AS funcionario_id,
                    f.nome AS funcionario_nome,
                    a.id AS acao_id,
                    a.nome AS acao_nome,
                    tp.id AS produto_id,
                    tp.nome AS produto_nome,
                    SUM(p.quantidade_kg) AS total_kg_produzido
                  FROM 
                    {$this->table_producao} p
                  JOIN 
                    {$this->table_funcionarios} f ON p.funcionario_id = f.id
                  JOIN 
                    {$this->table_acoes} a ON p.acao_id = a.id
                  JOIN 
                    {$this->table_tipos_produto} tp ON p.tipo_produto_id = tp.id
                  WHERE 
                    p.data_hora >= :data_inicio AND p.data_hora <= :data_fim_inclusive
                  GROUP BY 
                    f.id, a.id, tp.id
                  ORDER BY 
                    f.nome, a.nome";

        try {
            $stmt = $this->db->prepare($query);
            // Adicionamos 23:59:59 ao final para incluir o dia final no cálculo
            $data_fim_inclusive = $data_fim . ' 23:59:59';

            $stmt->bindParam(':data_inicio', $data_inicio);
            $stmt->bindParam(':data_fim_inclusive', $data_fim_inclusive);
            $stmt->execute();

            $resultados_agregados = $stmt->fetchAll();
            $relatorio_final = [];

            // Lógica de Cálculo (Feito no PHP para usar o ValoresPagamentoModel)
            foreach ($resultados_agregados as $item) {
                $valor_por_quilo = $this->valPagamentoModel->buscarValorPorQuilo(
                    $item->produto_id,
                    $item->acao_id
                );

                $valor_pagar = $item->total_kg_produzido * $valor_por_quilo;

                // Agrupando por funcionário para o relatório final
                $funcionario_id = $item->funcionario_id;

                if (!isset($relatorio_final[$funcionario_id])) {
                    $relatorio_final[$funcionario_id] = [
                        'nome' => $item->funcionario_nome,
                        'total_a_pagar' => 0.00,
                        'detalhes' => []
                    ];
                }

                $relatorio_final[$funcionario_id]['total_a_pagar'] += $valor_pagar;
                $relatorio_final[$funcionario_id]['detalhes'][] = [
                    'acao_nome' => $item->acao_nome,
                    'produto_nome' => $item->produto_nome,
                    'total_kg' => (float)$item->total_kg_produzido,
                    'valor_unitario' => $valor_por_quilo,
                    'valor_subtotal' => $valor_pagar
                ];
            }

            // Re-indexa o array para ser mais fácil de usar na View
            return array_values($relatorio_final);
        } catch (PDOException $e) {
            error_log("Erro no cálculo do relatório de pagamento: " . $e->getMessage());
            return [];
        }
    }
}

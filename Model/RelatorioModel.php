<?php
// Model/RelatorioModel.php

class RelatorioModel
{
  private $db;
  private $valPagamentoModel;

  private $table_producao = 'producao';
  private $table_funcionarios = 'funcionarios';
  private $table_acoes = 'acoes';
  private $table_tipos_produto = 'tipos_produto';
  private $table_presencas = 'presencas'; // Usada em outros métodos, mantida aqui.

  public function __construct()
  {
    $this->db = Database::getInstance()->connect();
    // Inicializa o model que buscará os valores por quilo (para o cálculo)
    $this->valPagamentoModel = new ValoresPagamentoModel();
  }

  /**
   * Função auxiliar para calcular a diferença de tempo em horas decimais.
   * @param string $hora_inicio Hora no formato 'HH:MM:SS'
   * @param string $hora_fim Hora no formato 'HH:MM:SS'
   * @return float O total de horas trabalhadas (decimal).
   */
  private function calcularHorasDecimais($hora_inicio, $hora_fim)
  {
    if (empty($hora_inicio) || empty($hora_fim)) {
      return 0.00;
    }
    // Converte TIME para segundos
    $inicio = strtotime("1970-01-01 $hora_inicio UTC");
    $fim = strtotime("1970-01-01 $hora_fim UTC");

    // Se a hora final for anterior à inicial (erro de lançamento), retorna 0
    if ($fim < $inicio) return 0.00;

    // Calcula a diferença em horas decimais (segundos / 3600) e arredonda
    return round(($fim - $inicio) / 3600.0, 2);
  }

  /**
   * Gera todos os dados agregados necessários para os relatórios (Pagamento, Quantidade, Produtividade).
   */
  public function gerarRelatorioCompleto($data_inicio, $data_fim)
  {

    // 1. SQL: Seleciona todos os lançamentos (produção e serviços) no período.
    $query = "SELECT 
                    p.id AS lancamento_id,
                    f.id AS funcionario_id,
                    f.nome AS funcionario_nome,
                    a.nome AS acao_nome,
                    tp.nome AS produto_nome,
                    tp.usa_lote, -- Flag de serviço/apoio
                    p.lote_produto,
                    p.quantidade_kg,
                    p.acao_id,
                    p.tipo_produto_id,
                    p.hora_inicio,
                    p.hora_fim
                  FROM 
                    {$this->table_producao} p
                  JOIN 
                    {$this->table_funcionarios} f ON p.funcionario_id = f.id
                  JOIN 
                    {$this->table_acoes} a ON p.acao_id = a.id
                  JOIN 
                    {$this->table_tipos_produto} tp ON p.tipo_produto_id = tp.id
                  WHERE 
                    p.data_hora BETWEEN :data_inicio AND :data_fim_inclusive
                  ORDER BY 
                    f.nome, p.data_hora";

    $data_fim_inclusive = $data_fim . ' 23:59:59';

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':data_inicio', $data_inicio);
    $stmt->bindParam(':data_fim_inclusive', $data_fim_inclusive);
    $stmt->execute();
    $lancamentos = $stmt->fetchAll();

    // 2. Processamento e Agregação no PHP (Inicialização Segura)
    $relatorio_agregado = [
      'total_geral_pagar' => 0.00,
      'producao' => [], // R01/R02: Itens rastreáveis (usa_lote=1)
      'servicos_apoio' => [], // R03: Itens de serviço/diária (usa_lote=0)
      'analise_produtividade' => [] // R04: Para calcular Kg/Hora
    ];

    foreach ($lancamentos as $item) {
      $h_trabalhadas = $this->calcularHorasDecimais($item->hora_inicio, $item->hora_fim);
      $valor_por_quilo = 0.00;
      $valor_item = 0.00;

      // Determina valor e tipo de agregação
      if (isset($item->produto_id) && isset($item->acao_id)) {
        if ((int)$item->usa_lote === 1) {
          // É um PRODUTO RASTREÁVEL
          $valor_por_quilo = $this->valPagamentoModel->buscarValorPorQuilo($item->produto_id, $item->acao_id);
          $valor_item = $item->quantidade_kg * $valor_por_quilo;
        } else {
          // É um SERVIÇO DE APOIO (Valor por quilo é o valor fixo por unidade/diária)
          $valor_por_quilo = $this->valPagamentoModel->buscarValorPorQuilo($item->produto_id, $item->acao_id);
          $valor_item = $item->quantidade_kg * $valor_por_quilo;
        }
      }

      // Estrutura do Item de Relatório
      $detalhe = [
        'acao_nome' => $item->acao_nome,
        'produto_nome' => $item->produto_nome,
        'lote' => $item->lote_produto,
        'total_kg' => (float)$item->quantidade_kg,
        'valor_unitario' => $valor_por_quilo,
        'valor_subtotal' => $valor_item,
        'horas_trabalhadas' => $h_trabalhadas
      ];

      $func_id = $item->funcionario_id;
      $func_nome = $item->funcionario_nome;

      // 2c. Inicialização Segura (Para evitar Undefined Property Warnings)
      if (!isset($relatorio_agregado['producao'][$func_id])) {
        $relatorio_agregado['producao'][$func_id] = ['nome' => $func_nome, 'total_a_pagar' => 0.00, 'detalhes' => []];
      }
      if (!isset($relatorio_agregado['servicos_apoio'][$func_id])) {
        $relatorio_agregado['servicos_apoio'][$func_id] = ['nome' => $func_nome, 'total_a_pagar' => 0.00, 'detalhes' => []];
      }
      if (!isset($relatorio_agregado['analise_produtividade'][$func_id])) {
        $relatorio_agregado['analise_produtividade'][$func_id] = ['nome' => $func_nome, 'total_kg' => 0.00, 'total_horas' => 0.00];
      }

      // 2d. Agregação por Tipo (Produção vs. Serviço)
      if ((int)$item->usa_lote === 1) {
        // R01/R02: PRODUÇÃO RASTREÁVEL (Quantidades e Pagamento)
        $relatorio_agregado['producao'][$func_id]['total_a_pagar'] += $valor_item;
        $relatorio_agregado['producao'][$func_id]['detalhes'][] = $detalhe;
      } else {
        // R03: SERVIÇOS DE APOIO
        $relatorio_agregado['servicos_apoio'][$func_id]['total_a_pagar'] += $valor_item;
        $relatorio_agregado['servicos_apoio'][$func_id]['detalhes'][] = $detalhe;
      }

      // 2e. Agregação de PRODUTIVIDADE (Kg e Horas)
      $relatorio_agregado['analise_produtividade'][$func_id]['total_kg'] += $item->quantidade_kg;
      $relatorio_agregado['analise_produtividade'][$func_id]['total_horas'] += $h_trabalhadas;

      $relatorio_agregado['total_geral_pagar'] += $valor_item;
    }

    // 3. CÁLCULO FINAL DE PRODUTIVIDADE/HORA
    foreach ($relatorio_agregado['analise_produtividade'] as $id => &$data) {
      $data['produtividade_hora'] = 0.00;
      if ($data['total_horas'] > 0) {
        $data['produtividade_hora'] = round($data['total_kg'] / $data['total_horas'], 2);
      }
    }

    return $relatorio_agregado;
  }
}

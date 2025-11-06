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
  /* public function gerarRelatorioCompleto($data_inicio, $data_fim)
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
  } */

  public function gerarRelatorioCompleto($data_inicio, $data_fim)
  {
    $data_fim_inclusive = $data_fim . ' 23:59:59';

    $query = "SELECT 
        p.data_hora,
        DATE(p.data_hora) as data,
        f.id as funcionario_id,
        f.nome as funcionario_nome,
        a.nome as acao_nome,
        tp.nome as produto_nome,
        tp.usa_lote,
        p.lote_produto,
        p.quantidade_kg,
        p.hora_inicio,
        p.hora_fim,
        vp.valor_por_kg
    FROM producao p
    JOIN funcionarios f ON p.funcionario_id = f.id
    JOIN acoes a ON p.acao_id = a.id
    JOIN tipos_produto tp ON p.tipo_produto_id = tp.id
    LEFT JOIN valores_pagamento vp ON vp.tipo_produto_id = p.tipo_produto_id AND vp.acao_id = p.acao_id
    WHERE p.data_hora BETWEEN ? AND ?
    ORDER BY p.data_hora, f.nome";

    $stmt = $this->db->prepare($query);
    $stmt->execute([$data_inicio, $data_fim_inclusive]);
    $lancamentos = $stmt->fetchAll();

    // Estrutura final
    $relatorio = [
      'por_dia' => [],           // para pagamentos e quantidades dia a dia
      'servicos' => [],          // só usa_lote = 0
      'produtividade' => [],     // kg e horas por funcionário
      'total_geral' => 0.00
    ];

    // Inicializa arrays
    $funcionarios = [];

    foreach ($lancamentos as $l) {
      $data = $l->data;
      $fid = $l->funcionario_id;
      $nome = $l->funcionario_nome;
      $valor = $l->quantidade_kg * $l->valor_por_kg;
      $horas = $this->calcularHorasDecimais($l->hora_inicio, $l->hora_fim);

      // Inicializa funcionário
      if (!isset($funcionarios[$fid])) {
        $funcionarios[$fid] = [
          'nome' => $nome,
          'total_valor' => 0,
          'total_kg' => 0,
          'total_horas' => 0,
          'dias' => [],
          'detalhes' => []
        ];
      }

      // Acumula totais
      $funcionarios[$fid]['total_valor'] += $valor;
      $funcionarios[$fid]['total_kg'] += $l->quantidade_kg;
      $funcionarios[$fid]['total_horas'] += $horas;
      $relatorio['total_geral'] += $valor;

      // Por dia
      if (!isset($funcionarios[$fid]['dias'][$data])) {
        $funcionarios[$fid]['dias'][$data] = ['valor' => 0, 'kg' => 0, 'detalhes' => []];
      }
      $funcionarios[$fid]['dias'][$data]['valor'] += $valor;
      $funcionarios[$fid]['dias'][$data]['kg'] += $l->quantidade_kg;
      $funcionarios[$fid]['dias'][$data]['detalhes'][] = [
        'acao' => $l->acao_nome,
        'produto' => $l->produto_nome,
        'lote' => $l->lote_produto,
        'kg' => $l->quantidade_kg,
        'valor' => $valor
      ];

      // Serviços separados
      if ((int)$l->usa_lote === 0) {
        $relatorio['servicos'][$fid]['nome'] = $nome;
        $relatorio['servicos'][$fid]['total'] = ($relatorio['servicos'][$fid]['total'] ?? 0) + $valor;
        $relatorio['servicos'][$fid]['detalhes'][$data][] = $l;
      }
    }

    // Produtividade
    foreach ($funcionarios as $fid => $f) {
      $relatorio['produtividade'][$fid] = [
        'nome' => $f['nome'],
        'total_kg' => $f['total_kg'],
        'total_horas' => $f['total_horas'],
        'kg_hora' => $f['total_horas'] > 0 ? round($f['total_kg'] / $f['total_horas'], 2) : 0
      ];
    }

    // Monta matriz dia a dia (pagamentos e quantidades)
    $datas = $this->gerarRangeDatas($data_inicio, $data_fim);
    foreach ($funcionarios as $fid => $f) {
      $linha = ['funcionario' => $f['nome'], 'total' => $f['total_valor']];
      foreach ($datas as $d) {
        $linha[$d] = $f['dias'][$d]['valor'] ?? 0;
        $linha['detalhes_' . $d] = $f['dias'][$d]['detalhes'] ?? [];
      }
      $relatorio['por_dia']['pagamentos'][] = $linha;

      // Quantidades
      $linha_kg = ['funcionario' => $f['nome'], 'total' => $f['total_kg']];
      foreach ($datas as $d) {
        $linha_kg[$d] = $f['dias'][$d]['kg'] ?? 0;
      }
      $relatorio['por_dia']['quantidades'][] = $linha_kg;
    }

    $relatorio['datas'] = $datas;
    return $relatorio;
  }

  private function gerarRangeDatas($inicio, $fim)
  {
    $datas = [];
    $atual = new DateTime($inicio);
    $fim = new DateTime($fim);
    while ($atual <= $fim) {
      $datas[] = $atual->format('Y-m-d');
      $atual->modify('+1 day');
    }
    return $datas;
  }

  public function getQuantidadesDiaADia($data_inicio, $data_fim)
  {
    $data_fim_sql = $data_fim . ' 23:59:59';

    $sql = "SELECT 
                DATE(p.data_hora) as data,
                f.id as funcionario_id,
                f.nome,
                tp.nome as tipo_produto,
                p.id as lancamento_id,
                SUM(p.quantidade_kg) as total_kg
            FROM producao p
            JOIN funcionarios f ON p.funcionario_id = f.id
            JOIN tipos_produto tp ON p.tipo_produto_id = tp.id
            WHERE p.data_hora BETWEEN ? AND ?
            GROUP BY DATE(p.data_hora), f.id, tp.id
            ORDER BY f.nome, data, tp.nome";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$data_inicio, $data_fim_sql]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estrutura final
    $matriz = [];        // funcionário → data → total
    $detalhes = [];      // funcionário → data → [produto => kg]
    $ids = [];           // funcionário → data → [produto => id_lancamento]
    $datas = [];

    foreach ($resultados as $r) {
      $data = $r['data'];
      $nome = $r['nome'];
      $produto = $r['tipo_produto'];
      $kg = (float)$r['total_kg'];
      $id_lancamento = (int)$r['lancamento_id'];

      if (!in_array($data, $datas)) $datas[] = $data;

      // Total por funcionário/dia
      if (!isset($matriz[$nome][$data])) {
        $matriz[$nome][$data] = 0;
      }
      $matriz[$nome][$data] += $kg;
      $matriz[$nome]['total'] = ($matriz[$nome]['total'] ?? 0) + $kg;

      // Detalhe por produto
      $detalhes[$nome][$data][$produto] = $kg;

      // Guarda o ID do lançamento (importante pra edição!)
      $ids[$nome][$data][$produto] = $id_lancamento;
    }

    sort($datas);

    // Total por dia
    $total_por_dia = array_fill_keys($datas, 0);
    foreach ($matriz as $nome => $dias) {
      foreach ($dias as $data => $kg) {
        if ($data !== 'total') {
          $total_por_dia[$data] += $kg;
        }
      }
    }

    return [
      'matriz' => $matriz,
      'detalhes' => $detalhes,
      'ids' => $ids,                    // NOVO: IDs pra edição
      'datas' => $datas,
      'total_por_dia' => $total_por_dia,
      'total_geral' => array_sum($total_por_dia)
    ];
  }

  public function atualizarLancamentos($updates)
  {
    $sucesso = 0;
    $erros = [];
    $novos_ids = [];

    $acao_id_default = 1;

    foreach ($updates as $u) {
      $id = $u['id'] ?? 0;
      // MUDANÇA AQUI: Usa 'quantidade_kg' que veio do JS, em vez de 'valor'
      $valor = floatval($u['quantidade_kg'] ?? 0);

      $data = $u['data'] ?? date('Y-m-d');
      $func_id = $u['funcionario_id'] ?? null;
      $tipo_id = $u['tipo_produto_id'] ?? null;

      // Se o valor for zero e o ID for zero, não insere!
      if ($id == 0 && $valor <= 0) {
        continue; // Ignora lançamentos novos com quantidade zero
      }

      // Se for UPDATE (id > 0) e o valor for zero, EXCLUIR!
      if ($id > 0 && $valor <= 0) {
        // Lógica de exclusão
        $sql_delete = "DELETE FROM producao WHERE id = ?";
        $stmt_delete = $this->db->prepare($sql_delete);
        $stmt_delete->execute([$id]);
        $sucesso++;
        continue; // Pula para o próximo update
      }


      if ($func_id === null || $tipo_id === null) {
        $erros[] = "Dados incompletos para ID $id";
        continue;
      }

      try {
        if ($id == 0) {
          // INSERT novo
          $sql = "INSERT INTO producao 
                        (funcionario_id, tipo_produto_id, data_hora, quantidade_kg, acao_id)
                        VALUES (?, ?, CONCAT(?,' 00:00:00'), ?, ?)";

          $stmt = $this->db->prepare($sql);
          $stmt->execute([$func_id, $tipo_id, $data, $valor, $acao_id_default]);

          // *** MUDANÇA AQUI: Captura o novo ID ***
          $novo_id = $this->db->lastInsertId();
          if ($novo_id) {
            // Guarda o novo ID junto com os identificadores do lançamento original (data, func_id, tipo_id)
            $novos_ids[] = [
              'temp_id' => $u['id'], // Será sempre 0, mas ajuda a mapear no JS
              'data' => $data,
              'func_id' => $func_id,
              'tipo_id' => $tipo_id,
              'new_id' => $novo_id
            ];
          }

          $sucesso++;
        } else {
          // UPDATE (mantido)
          $sql = "UPDATE producao SET quantidade_kg = ? WHERE id = ?";
          $stmt = $this->db->prepare($sql);
          $stmt->execute([$valor, $id]);
          $sucesso++;
        }
      } catch (Exception $e) {
        // MUDANÇA AQUI: Captura o erro do PDO
        $pdoError = $stmt ? $stmt->errorInfo() : ['00000', 'Geral', $e->getMessage()];
        $erros[] = "ID $id: SQL Error " . $pdoError[1] . " - " . $pdoError[2];
      }
    }

    return [
      'success' => $sucesso > 0,
      'msg' => $sucesso > 0 ? "$sucesso lançamento(s) processado(s)!" : "Nenhum valor relevante para salvar/excluir foi detectado.",
      'erros' => $erros,
      'novos_ids' => $novos_ids // NOVO: Retorna a lista de IDs inseridos
    ];
  }

  // Auxiliares pra buscar IDs (adicione isso no Model)
  public function getFuncionarioIdByNome($nome)
  {
    $stmt = $this->db->prepare("SELECT id FROM funcionarios WHERE nome = ? LIMIT 1");
    $stmt->execute([$nome]);
    return $stmt->fetchColumn() ?: null;
  }

  public function getTipoProdutoIdByNome($nome)
  {
    $stmt = $this->db->prepare("SELECT id FROM tipos_produto WHERE nome = ? LIMIT 1");
    $stmt->execute([$nome]);
    return $stmt->fetchColumn() ?: null;
  }

  public function excluirLancamentos($ids)
  {
    try {
      $placeholders = str_repeat('?,', count($ids) - 1) . '?';
      $sql = "DELETE FROM producao WHERE id IN ($placeholders)";
      $stmt = $this->db->prepare($sql);
      $stmt->execute($ids);

      $deletados = $stmt->rowCount();

      return [
        'success' => true,
        'msg' => "$deletados lançamento(s) excluído(s)!"
      ];
    } catch (Exception $e) {
      return [
        'success' => false,
        'msg' => 'Erro ao excluir: ' . $e->getMessage()
      ];
    }
  }

  // Permite que o Controller acesse o PDO de forma segura
  public function getDb()
  {
    return $this->db;
  }

  public function getAllTipoProdutoIds()
  {
    $stmt = $this->db->query("SELECT id, nome FROM tipos_produto");
    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $result[trim($row['nome'])] = (int)$row['id'];
    }
    return $result;
  }
}

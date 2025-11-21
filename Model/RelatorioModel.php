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
        vp.valor_por_quilo
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
      //$valor = $l->quantidade_kg * $l->valor_por_kg;

      // Usa '?? 0' para garantir que se for NULL, o valor seja zero.
      $valor_por_kg = $l->valor_por_kg ?? 0;
      $valor = $l->quantidade_kg * $valor_por_kg; // Agora multiplica por um valor seguro (0 ou o preço)

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
    $matriz = [];              // funcionário → data → total
    $detalhes = [];            // funcionário → data → [produto => kg]
    $ids = [];                 // funcionário → data → [produto => id_lancamento]
    $datas = [];
    $total_por_dia = [];
    $totais_funcionarios = []; // totais por funcionário

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

      // Mantém os totais em um array separado
      $totais_funcionarios[$nome] = ($totais_funcionarios[$nome] ?? 0) + $kg;

      // Detalhe por produto
      $detalhes[$nome][$data][$produto] = $kg;

      // Guarda o ID do lançamento (importante pra edição!)
      $ids[$nome][$data][$produto] = $id_lancamento;
    }

    // Se não houver nenhuma data com lançamentos, retorna vazio
    if (empty($datas)) {
      return [
        'matriz' => [],
        'detalhes' => [],
        'ids' => [],
        'datas' => [],
        'total_por_dia' => [],
        'total_geral' => 0,
        'totais_funcionarios' => []
      ];
    }

    // Ordena apenas as datas que realmente tiveram lançamentos
    sort($datas);

    // Total por dia (somente datas existentes)
    $total_por_dia = array_fill_keys($datas, 0);
    foreach ($matriz as $nome => $dias) {
      foreach ($dias as $data => $kg) {
        if (isset($total_por_dia[$data])) {
          $total_por_dia[$data] += $kg;
        }
      }
    }

    // ============================================================
    // === SOMAR SERVIÇOS EXTRAS / DIÁRIAS NO RELATÓRIO DE QUANTIDADES ===
    // ============================================================

    $servicosExtras = $this->getServicosExtrasValor($data_inicio, $data_fim);

    foreach ($servicosExtras as $nome => $valores) {
      if (!isset($matriz[$nome])) {
        $matriz[$nome] = array_fill_keys($datas, 0.0);
      }

      foreach ($valores as $data => $valor) {
        if ($data === 'total') {
          $totais_funcionarios[$nome] = ($totais_funcionarios[$nome] ?? 0) + $valor;
          continue;
        }

        if (isset($matriz[$nome][$data])) {
          $matriz[$nome][$data] += $valor;
          $total_por_dia[$data] += $valor;
          $totais_funcionarios[$nome] = ($totais_funcionarios[$nome] ?? 0) + $valor;
        }
      }
    }

    unset($matriz['total'], $matriz['totais'], $matriz['detalhes']);
    
    return [
      'matriz' => $matriz,
      'detalhes' => $detalhes,
      'ids' => $ids,
      'datas' => $datas,
      'total_por_dia' => $total_por_dia,
      'total_geral' => array_sum($total_por_dia),
      'totais_funcionarios' => $totais_funcionarios
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
      $data = $u['data'] ?? date('Y-m-d');
      $func_id = $u['funcionario_id'] ?? null;
      $tipo_id = $u['tipo_produto_id'] ?? null;

      // === NORMALIZAÇÃO DE VALORES (aceita 5.000, 5,000, 5000, etc.) ===
      $valorStr = trim($u['quantidade_kg'] ?? $u['valor'] ?? '0'); // Aceita os dois nomes por segurança

      if ($valorStr === '' || $valorStr === '-' || $valorStr === '0' || $valorStr === '0,000' || $valorStr === '0.000') {
        $valor = 0.0;
      } else {
        // Remove tudo que não for número, ponto ou vírgula
        $clean = preg_replace('/[^\d,\.]/', '', $valorStr);
        // Remove pontos de milhar (mantém apenas o último como decimal se houver)
        $clean = preg_replace('/\.(?=.*\.)/', '', $clean);
        // Troca vírgula por ponto (para floatval)
        $clean = str_replace(',', '.', $clean);
        $valor = (float) $clean;
      }

      // === REGRAS DE NEGÓCIO ===
      // Se novo (id=0) e valor <=0 → ignora
      if ($id == 0 && $valor <= 0) {
        continue;
      }

      // Se update (id>0) e valor <=0 → EXCLUI
      if ($id > 0 && $valor <= 0) {
        $sql_delete = "DELETE FROM producao WHERE id = ?";
        $stmt_delete = $this->db->prepare($sql_delete);
        $stmt_delete->execute([$id]);
        $sucesso++;
        continue;
      }

      // Validação FKs
      if ($func_id === null || $tipo_id === null) {
        $erros[] = "Dados incompletos para ID $id";
        continue;
      }

      try {
        if ($id == 0) {
          // INSERT
          $sql = "INSERT INTO producao 
                        (funcionario_id, tipo_produto_id, data_hora, quantidade_kg, acao_id)
                        VALUES (?, ?, CONCAT(?,' 00:00:00'), ?, ?)";
          $stmt = $this->db->prepare($sql);
          $stmt->execute([$func_id, $tipo_id, $data, $valor, $acao_id_default]);

          $novo_id = $this->db->lastInsertId();
          if ($novo_id) {
            $novos_ids[] = [
              'temp_id' => $id,  // 0 para novos
              'data' => $data,
              'func_id' => $func_id,
              'tipo_id' => $tipo_id,
              'new_id' => $novo_id
            ];
          }
          $sucesso++;
        } else {
          // UPDATE
          $sql = "UPDATE producao SET quantidade_kg = ? WHERE id = ?";
          $stmt = $this->db->prepare($sql);
          $stmt->execute([$valor, $id]);
          $sucesso++;
        }
      } catch (Exception $e) {
        $erros[] = "Erro ao processar ID $id: " . $e->getMessage();
      }
    }

    return [
      'success' => $sucesso > 0,
      'msg' => $sucesso > 0 ? "$sucesso lançamento(s) processado(s)!" : "Nenhum valor relevante para salvar/excluir foi detectado.",
      'erros' => $erros,
      'novos_ids' => $novos_ids
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

  public function getValoresFinanceirosDiaADia($data_inicio, $data_fim)
  {
    $data_fim_sql = $data_fim . ' 23:59:59';

    // Gera todas as datas do período
    $datas = [];
    $inicio = new DateTime($data_inicio);
    $fim = new DateTime($data_fim);
    while ($inicio <= $fim) {
      $datas[] = $inicio->format('Y-m-d');
      $inicio->modify('+1 day');
    }

    // PRODUÇÃO
    $sql = "SELECT 
                DATE(p.data_hora) as data,
                f.id as funcionario_id,
                f.nome,
                tp.nome as tipo_produto,
                tp.id as tipo_produto_id,
                a.id as acao_id,
                p.id as lancamento_id,
                SUM(p.quantidade_kg) as total_kg
            FROM producao p
            JOIN funcionarios f ON p.funcionario_id = f.id
            JOIN tipos_produto tp ON p.tipo_produto_id = tp.id
            JOIN acoes a ON p.acao_id = a.id
            WHERE p.data_hora BETWEEN ? AND ?
            GROUP BY DATE(p.data_hora), f.id, tp.id, a.id
            ORDER BY f.nome, data";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$data_inicio, $data_fim_sql]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $valPagModel = new ValoresPagamentoModel();

    $matriz = [];
    $detalhes = [];
    $ids = [];
    $funcionario_ids = [];
    $tipo_produto_ids = [];

    foreach ($resultados as $r) {
      $data = $r['data'];
      $nome = $r['nome'];
      $produto = $r['tipo_produto'];
      $kg = (float)$r['total_kg'];
      $id_lancamento = (int)$r['lancamento_id'];
      $tipo_id = (int)$r['tipo_produto_id'];
      $acao_id = (int)$r['acao_id'];

      $valor_por_kg = $valPagModel->buscarValorPorQuilo($tipo_id, $acao_id);
      $valor_total = $kg * $valor_por_kg;

      if (!isset($matriz[$nome])) {
        $matriz[$nome] = array_fill_keys($datas, 0.0);
        $matriz[$nome]['total'] = 0.0;
      }

      $matriz[$nome][$data] += $valor_total;
      $matriz[$nome]['total'] += $valor_total;

      $detalhes[$nome][$data][$produto] = $valor_total;
      $ids[$nome][$data][$produto] = $id_lancamento;

      $funcionario_ids[$nome] = $r['funcionario_id'];
      $tipo_produto_ids[$produto] = $tipo_id;
    }

    sort($datas);

    // Totais por dia da produção
    $total_por_dia = array_fill_keys($datas, 0.0);
    foreach ($matriz as $nome => $dias) {
      foreach ($dias as $data => $valor) {
        if ($data !== 'total') {
          $total_por_dia[$data] += $valor;
        }
      }
    }

    // SERVIÇOS EXTRAS
    $extras = $this->getServicosExtrasValor($data_inicio, $data_fim);
    $servicosExtras = $extras['totais'];
    $detalhesExtras = $extras['detalhes'];

    foreach ($servicosExtras as $nome => $valores) {
      if (!isset($matriz[$nome])) {
        $matriz[$nome] = array_fill_keys($datas, 0.0);
        $matriz[$nome]['total'] = 0.0;

        if (!isset($funcionario_ids[$nome])) {
          $stmt = $this->db->prepare("SELECT id FROM funcionarios WHERE nome = ? LIMIT 1");
          $stmt->execute([$nome]);
          $id = $stmt->fetchColumn();
          if ($id) $funcionario_ids[$nome] = (int)$id;
        }
      }

      foreach ($valores as $data => $valor) {
        if ($data === 'total') {
          $matriz[$nome]['total'] += $valor;
          continue;
        }

        $matriz[$nome][$data] += $valor;
        $total_por_dia[$data] += $valor;

        // adiciona nos detalhes usando o nome da ação
        if (!isset($detalhes[$nome][$data])) {
          $detalhes[$nome][$data] = [];
        }
        foreach ($detalhesExtras[$nome][$data] ?? [] as $acaoNome => $valorExtra) {
          $detalhes[$nome][$data][$acaoNome] = $valorExtra;
          $ids[$nome][$data][$acaoNome] = 0; // sem ID de produção

        }
      }
    }

    $total_geral = array_sum($total_por_dia);

    return [
      'matriz' => $matriz,
      'detalhes' => $detalhes,
      'ids' => $ids,
      'datas' => $datas,
      'total_por_dia' => $total_por_dia,
      'total_geral' => $total_geral,
      'funcionario_ids' => $funcionario_ids,
      'tipo_produto_ids' => $tipo_produto_ids
    ];
  }

  public function getServicosExtrasValor($data_inicio, $data_fim)
  {
    $model = new ServicosExtrasModel();
    $servicos = $model->buscarPorPeriodo($data_inicio, $data_fim);

    $totais = [];
    $detalhesExtras = [];

    foreach ($servicos as $s) {
      $nome = $s->funcionario_nome;
      $data = $s->data_servico;
      $acaoNome = $s->descricao; // nome da ação cadastrada

      if (!isset($totais[$nome])) {
        $totais[$nome] = array_fill_keys($this->getDatasArray($data_inicio, $data_fim), 0.0);
        $totais[$nome]['total'] = 0.0;
      }

      $totais[$nome][$data] += (float)$s->valor;
      $totais[$nome]['total'] += (float)$s->valor;

      // guarda também os detalhes
      if (!isset($detalhesExtras[$nome][$data])) {
        $detalhesExtras[$nome][$data] = [];
      }
      $detalhesExtras[$nome][$data][$acaoNome] = (float)$s->valor;
    }

    return ['totais' => $totais, 'detalhes' => $detalhesExtras];
  }


  private function getDatasArray($inicio, $fim)
  {
    $datas = [];
    $atual = new DateTime($inicio);
    $final = new DateTime($fim);
    while ($atual <= $final) {
      $datas[] = $atual->format('Y-m-d');
      $atual->modify('+1 day');
    }
    return $datas;
  }

  public function getServicosExtrasPorPeriodo($data_inicio, $data_fim)
  {
    $model = new ServicosExtrasModel();
    $servicos = $model->buscarPorPeriodo($data_inicio, $data_fim);

    $resultado = [];
    foreach ($servicos as $s) {
      $nome = $s->funcionario_nome;
      $data = $s->data_servico;

      if (!isset($resultado[$nome])) {
        $resultado[$nome] = ['total' => 0];
      }
      $resultado[$nome][$data] = ($resultado[$nome][$data] ?? 0) + (float)$s->valor;
      $resultado[$nome]['total'] += (float)$s->valor;
    }
    return $resultado;
  }
}

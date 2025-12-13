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
  private $table_presencas = 'presencas';

  public function __construct()
  {
    $this->db = Database::getInstance()->connect();
    // Inicializa o model que buscará os valores por quilo (para o cálculo)
    $this->valPagamentoModel = new ValoresPagamentoModel();
  }

  /**
   * Auxiliar datas
   */
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

  /**
   * Gera o relatório consolidado (Produtividade + Extras)
   */
  public function gerarRelatorioCompleto($data_inicio, $data_fim, $funcionarioId = null, $tipo = 'qtd')
  {
    // 1. Busca os dados
    if ($tipo === 'valor') {
      $producao = $this->getProducaoValores($data_inicio, $data_fim, $funcionarioId);
    } else {
      $producao = $this->getProducaoPorPeriodo($data_inicio, $data_fim, $funcionarioId);
    }

    $extras = $this->getServicosExtrasPorPeriodo($data_inicio, $data_fim, $funcionarioId);

    $relatorio = [];
    $datasPeriodo = $this->getDatasArray($data_inicio, $data_fim);

    // 2. Processa PRODUÇÃO
    foreach ($producao as $funcNome => $dados) {
      $relatorio[$funcNome] = $dados;
    }

    // 3. Processa EXTRAS
    foreach ($extras as $funcNome => $dados) {
      if (!isset($relatorio[$funcNome])) {
        $relatorio[$funcNome] = [
          'dias' => array_fill_keys($datasPeriodo, 0),
          'total' => 0,
          'detalhes' => []
        ];
      }

      // Se for relatório de VALOR, soma o dinheiro dos extras
      if ($tipo === 'valor') {
        foreach ($dados['dias'] as $dia => $valor) {
          if (isset($relatorio[$funcNome]['dias'][$dia])) {
            $relatorio[$funcNome]['dias'][$dia] += $valor;
          }
        }
        $relatorio[$funcNome]['total'] += $dados['total'];
      }
    }

    return $relatorio;
  }

  public function getProducaoPorPeriodo($data_inicio, $data_fim, $funcionarioId = null)
  {
    $query = "SELECT 
                    DATE(p.data_hora) AS data, 
                    f.nome AS funcionario, 
                    tp.nome AS produto,
                    SUM(p.quantidade_kg) as total_kg
                  FROM {$this->table_producao} p
                  JOIN {$this->table_funcionarios} f ON p.funcionario_id = f.id
                  JOIN {$this->table_tipos_produto} tp ON p.tipo_produto_id = tp.id
                  WHERE DATE(p.data_hora) BETWEEN :inicio AND :fim";

    if ($funcionarioId) {
      $query .= " AND p.funcionario_id = :fid ";
    }

    $query .= " GROUP BY DATE(p.data_hora), f.nome, tp.nome
                    ORDER BY f.nome, DATE(p.data_hora)";

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':inicio', $data_inicio);
    $stmt->bindParam(':fim', $data_fim);

    if ($funcionarioId) {
      $stmt->bindParam(':fid', $funcionarioId);
    }

    $stmt->execute();

    // Força FETCH_OBJ para garantir que $row->data funcione
    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);

    $dados = [];
    $datasPeriodo = $this->getDatasArray($data_inicio, $data_fim);

    foreach ($resultados as $row) {
      $func = $row->funcionario;
      $dia  = $row->data;
      $prod = $row->produto;
      $qtd  = (float)$row->total_kg;

      if (!isset($dados[$func])) {
        $dados[$func] = [
          'dias' => array_fill_keys($datasPeriodo, 0),
          'total' => 0,
          'detalhes' => []
        ];
      }

      if (isset($dados[$func]['dias'][$dia])) {
        $dados[$func]['dias'][$dia] += $qtd;
      }
      $dados[$func]['total'] += $qtd;

      if (!isset($dados[$func]['detalhes'][$prod])) {
        $dados[$func]['detalhes'][$prod] = [
          'dias' => array_fill_keys($datasPeriodo, 0),
          'total' => 0
        ];
      }
      if (isset($dados[$func]['detalhes'][$prod]['dias'][$dia])) {
        $dados[$func]['detalhes'][$prod]['dias'][$dia] += $qtd;
      }
      $dados[$func]['detalhes'][$prod]['total'] += $qtd;
    }

    return $dados;
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

  public function getProducaoValores($data_inicio, $data_fim, $funcionarioId = null)
  {
    $query = "SELECT 
                    DATE(p.data_hora) as data, 
                    f.nome AS funcionario, 
                    tp.nome AS produto,
                    SUM(p.quantidade_kg * COALESCE(vp.valor_por_quilo, 0)) as total_valor
                  FROM {$this->table_producao} p
                  JOIN {$this->table_funcionarios} f ON p.funcionario_id = f.id
                  JOIN {$this->table_tipos_produto} tp ON p.tipo_produto_id = tp.id
                  LEFT JOIN valores_pagamento vp ON p.tipo_produto_id = vp.tipo_produto_id AND p.acao_id = vp.acao_id
                  WHERE DATE(p.data_hora) BETWEEN :inicio AND :fim";

    if ($funcionarioId) {
      $query .= " AND p.funcionario_id = :fid ";
    }

    $query .= " GROUP BY DATE(p.data_hora), f.nome, tp.nome
                    ORDER BY f.nome, DATE(p.data_hora)";

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':inicio', $data_inicio);
    $stmt->bindParam(':fim', $data_fim);

    if ($funcionarioId) {
      $stmt->bindParam(':fid', $funcionarioId);
    }

    $stmt->execute();

    // CORREÇÃO 3: Força FETCH_OBJ
    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);

    $dados = [];
    $datasPeriodo = $this->getDatasArray($data_inicio, $data_fim);

    foreach ($resultados as $row) {
      $func = $row->funcionario;
      $dia  = $row->data;
      $prod = $row->produto;
      $val  = (float)$row->total_valor;

      if (!isset($dados[$func])) {
        $dados[$func] = [
          'dias' => array_fill_keys($datasPeriodo, 0),
          'total' => 0,
          'detalhes' => []
        ];
      }

      if (isset($dados[$func]['dias'][$dia])) {
        $dados[$func]['dias'][$dia] += $val;
      }
      $dados[$func]['total'] += $val;

      if (!isset($dados[$func]['detalhes'][$prod])) {
        $dados[$func]['detalhes'][$prod] = [
          'dias' => array_fill_keys($datasPeriodo, 0),
          'total' => 0
        ];
      }
      if (isset($dados[$func]['detalhes'][$prod]['dias'][$dia])) {
        $dados[$func]['detalhes'][$prod]['dias'][$dia] += $val;
      }
      $dados[$func]['detalhes'][$prod]['total'] += $val;
    }

    return $dados;
  }

  public function getServicosExtrasPorPeriodo($data_inicio, $data_fim, $funcionarioId = null)
  {
    $query = "SELECT 
                    s.data, 
                    f.nome AS funcionario, 
                    a.nome AS acao, 
                    s.valor
                  FROM servicos_extras s
                  JOIN funcionarios f ON s.funcionario_id = f.id
                  LEFT JOIN acoes a ON s.id = a.id
                  WHERE s.data BETWEEN :inicio AND :fim";

    if ($funcionarioId) {
      $query .= " AND s.funcionario_id = :fid ";
    }

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':inicio', $data_inicio);
    $stmt->bindParam(':fim', $data_fim);

    if ($funcionarioId) {
      $stmt->bindParam(':fid', $funcionarioId);
    }

    $stmt->execute();

    // CORREÇÃO 3: Força FETCH_OBJ
    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);

    $dados = [];
    $datasPeriodo = $this->getDatasArray($data_inicio, $data_fim);

    foreach ($resultados as $row) {
      $func = $row->funcionario;
      $dia  = isset($row->data) ? date('Y-m-d', strtotime($row->data)) : null; // Garante formato Y-m-d
      $valor = (float)$row->valor;

      if ($dia && in_array($dia, $datasPeriodo)) {
        if (!isset($dados[$func])) {
          $dados[$func] = [
            'dias' => array_fill_keys($datasPeriodo, 0),
            'total' => 0
          ];
        }
        $dados[$func]['dias'][$dia] += $valor;
        $dados[$func]['total'] += $valor;
      }
    }

    return $dados;
  }

  // Model/RelatorioModel.php

  public function getQuantidadesDiaADia($data_inicio, $data_fim, $funcionarioId = null)
  {
    $data_fim_sql = $data_fim . ' 23:59:59';
    $datasPeriodo = $this->getDatasArray($data_inicio, $data_fim); // Garante todas as datas

    // SQL (Mantido igual, trazendo IDs)
    $sql = "SELECT 
                DATE(p.data_hora) as data,
                f.id as funcionario_id,
                f.nome,
                tp.nome as tipo_produto,
                tp.id as tipo_produto_id, 
                p.id as lancamento_id,
                SUM(p.quantidade_kg) as total_kg
            FROM producao p
            JOIN funcionarios f ON p.funcionario_id = f.id
            JOIN tipos_produto tp ON p.tipo_produto_id = tp.id
            WHERE p.data_hora BETWEEN :inicio AND :fim";

    if ($funcionarioId) {
      $sql .= " AND p.funcionario_id = :fid ";
    }

    $sql .= " GROUP BY DATE(p.data_hora), f.id, tp.id
              ORDER BY f.nome, data, tp.nome";

    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':inicio', $data_inicio);
    $stmt->bindParam(':fim', $data_fim_sql);

    if ($funcionarioId) {
      $stmt->bindParam(':fid', $funcionarioId);
    }

    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- ESTRUTURA UNIFICADA (Para corrigir o erro da View) ---
    // Estrutura: $matriz['NomeFunc']['detalhes']['NomeProd']['dias']['Data'] = valor
    $matriz = [];

    // Arrays de Mapeamento de IDs
    $funcionario_ids = [];
    $tipo_produto_ids = [];

    // Totais globais
    $total_por_dia = array_fill_keys($datasPeriodo, 0);
    $total_geral = 0;

    foreach ($resultados as $r) {
      $data = $r['data'];
      $nome = $r['nome'];
      $produto = $r['tipo_produto'];
      $kg = (float)$r['total_kg'];

      $f_id = (int)$r['funcionario_id'];
      $p_id = (int)$r['tipo_produto_id'];

      // Salva IDs para a View usar nos data-attributes
      $funcionario_ids[$nome] = $f_id;
      $tipo_produto_ids[$produto] = $p_id;

      // 1. Inicializa Funcionário se não existir
      if (!isset($matriz[$nome])) {
        $matriz[$nome] = [
          'dias' => array_fill_keys($datasPeriodo, 0),
          'total' => 0,
          'detalhes' => [] // <--- AQUI ESTAVA FALTANDO!
        ];
      }

      // 2. Preenche Totais do Funcionário
      if (in_array($data, $datasPeriodo)) {
        $matriz[$nome]['dias'][$data] += $kg;
        $matriz[$nome]['total'] += $kg;

        $total_por_dia[$data] += $kg;
        $total_geral += $kg;
      }

      // 3. Inicializa Produto (Detalhes) se não existir
      if (!isset($matriz[$nome]['detalhes'][$produto])) {
        $matriz[$nome]['detalhes'][$produto] = [
          'dias' => array_fill_keys($datasPeriodo, 0)
        ];
      }

      // 4. Preenche Dias do Produto
      if (in_array($data, $datasPeriodo)) {
        $matriz[$nome]['detalhes'][$produto]['dias'][$data] += $kg;
      }
    }

    // --- SERVIÇOS EXTRAS (Mantendo compatibilidade) ---
    $servicosExtras = $this->getServicosExtrasValor($data_inicio, $data_fim);

    foreach ($servicosExtras['totais'] as $nome => $valores) {
      // Filtra se necessário
      if ($funcionarioId && (!isset($funcionario_ids[$nome]) || $funcionario_ids[$nome] != $funcionarioId)) {
        // Tenta buscar ID se não tivermos
        if (!isset($funcionario_ids[$nome])) {
          $id_extra = $this->getFuncionarioIdByNome($nome);
          if ($id_extra != $funcionarioId) continue;
          $funcionario_ids[$nome] = $id_extra;
        } else {
          continue;
        }
      }

      if (!isset($matriz[$nome])) {
        $matriz[$nome] = [
          'dias' => array_fill_keys($datasPeriodo, 0),
          'total' => 0,
          'detalhes' => []
        ];
        // Busca ID para extras isolados
        if (!isset($funcionario_ids[$nome])) {
          $id_extra = $this->getFuncionarioIdByNome($nome);
          if ($id_extra) $funcionario_ids[$nome] = $id_extra;
        }
      }

      foreach ($valores as $data => $valor) {
        if ($data === 'total') {
          // O total já é somado no loop abaixo, ignorar chave 'total'
          continue;
        }

        if (in_array($data, $datasPeriodo)) {
          $matriz[$nome]['dias'][$data] += $valor;
          $matriz[$nome]['total'] += $valor;

          $total_por_dia[$data] += $valor;
          $total_geral += $valor;
        }
      }
    }

    // Ordena as datas para o cabeçalho
    sort($datasPeriodo);

    return [
      'matriz' => $matriz,            // Agora contém 'detalhes' dentro!
      'datas' => $datasPeriodo,
      'total_por_dia' => $total_por_dia,
      'total_geral' => $total_geral,
      'funcionario_ids' => $funcionario_ids,
      'tipo_produto_ids' => $tipo_produto_ids
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
}

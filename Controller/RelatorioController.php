<?php
// app/Controller/RelatorioController.php
use Dompdf\Dompdf;
use Dompdf\Options;

class RelatorioController extends AppController
{
    private $relatorioModel;

    public function __construct()
    {
        parent::__construct();
        $this->relatorioModel = new RelatorioModel();
    }

    private function coletarFiltro()
    {
        $hoje = new DateTime();
        $data_inicio = $_GET['data_inicio'] ?? $hoje->format('Y-m-01');
        $data_fim = $_GET['data_fim'] ?? $hoje->format('Y-m-t');
        $visualizacao = $_GET['visualizacao'] ?? 'sintetico';

        if (strtotime($data_inicio) > strtotime($data_fim)) {
            $erro = "Data inicial não pode ser maior que data final.";
            return compact('data_inicio', 'data_fim', 'visualizacao', 'erro');
        }

        $dados = $this->relatorioModel->gerarRelatorioCompleto($data_inicio, $data_fim);

        return compact('data_inicio', 'data_fim', 'visualizacao', 'dados', 'erro');
    }

    // RELATÓRIO DE QUANTIDADES
    public function quantidades()
    {
        $hoje = new DateTime();
        $data_inicio = $_GET['ini'] ?? $hoje->format('Y-m-01');
        $data_fim = $_GET['fim'] ?? $hoje->format('Y-m-t');

        if (strtotime($data_inicio) > strtotime($data_fim)) {
            $_SESSION['erro'] = "Data inicial não pode ser maior que data final.";
            header('Location: /sgi_erp/relatorios/quantidades');
            exit;
        }

        $relatorio = $this->relatorioModel->getQuantidadesDiaADia($data_inicio, $data_fim);

        $title = "RELATÓRIO DE PRODUÇÃO - PERÍODO: " .
            date('d/m/Y', strtotime($data_inicio)) . " - " .
            date('d/m/Y', strtotime($data_fim));

        $content_view = ROOT_PATH . 'View/relatorio_quantidades.php';

        $dados = [
            'matriz' => $relatorio['matriz'],
            'detalhes' => $relatorio['detalhes'],
            'ids' => $relatorio['ids'],           // ESSA LINHA!
            'datas' => $relatorio['datas'],
            'total_por_dia' => $relatorio['total_por_dia'],
            'total_geral' => $relatorio['total_geral'],
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'pode_editar' => true
        ];

        // === BUSCA IDS PARA EDIÇÃO INLINE ===
        $funcionario_ids = [];
        $tipo_produto_ids = $this->relatorioModel->getAllTipoProdutoIds();

        foreach ($dados['matriz'] as $nome => $linha) {
            $id = $this->relatorioModel->getFuncionarioIdByNome($nome);
            $funcionario_ids[$nome] = $id ?: 0;
        }

        $dados['funcionario_ids'] = $funcionario_ids;
        $dados['tipo_produto_ids'] = $tipo_produto_ids;

        require_once ROOT_PATH . 'View/template/main.php';
    }

    public function atualizarProducao()
    {
        header('Content-Type: application/json');
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data || !isset($data['updates']) || !is_array($data['updates'])) {
            // Adicionado is_array para garantir que 'updates' é um array
            echo json_encode(['success' => false, 'message' => 'Dados inválidos. Nenhum array "updates" recebido.']);
            exit;
        }

        $updates = $data['updates'];

        // REFINAMENTO: Removido o código de busca por nome. 
        // Agora, validamos se os IDs necessários (enviados pelo JS) estão presentes.
        foreach ($updates as &$up) {
            // 1. Renomeia 'quantidade_kg' para 'valor' para o Model
            $up['valor'] = $up['quantidade_kg'] ?? 0;

            // 2. Garante que os IDs vieram do JS (necessário para INSERT no Model)
            $func_id = $up['funcionario_id'] ?? null;
            $tipo_id = $up['tipo_produto_id'] ?? null;

            // 3. Validação: se o lançamento é novo (id=0), os IDs de FKs são obrigatórios
            if (($up['id'] ?? 0) == 0 && ($func_id === null || $tipo_id === null)) {
                // Se for um novo lançamento e o JS falhou em enviar os IDs, retorna erro.
                echo json_encode([
                    'success' => false,
                    'message' => 'Lançamento novo não possui Funcionário/Produto ID.'
                ]);
                exit;
            }
            // Se o lançamento é UPDATE (id > 0), o Model só precisa do id e valor. 
            // O Model já está preparado para lidar com IDs nulos/zero, mas é bom ter o func_id/tipo_id.

            // *Opcional: Se for um UPDATE (id>0), não precisamos dos FKs.
            // Para simplificar, vamos manter a validação mínima (só para novos).
        }
        // *Note: A variável $up é por referência, então o array $updates agora tem a chave 'valor'.

        $result = $this->relatorioModel->atualizarLancamentos($updates);

        echo json_encode([
            'success' => $result['success'],
            'message' => $result['msg'],
            'errors' => $result['erros'],
            'novos_ids' => $result['novos_ids'] // *** MUDANÇA AQUI: Repassa a lista de novos IDs ***
        ]);
        exit;
    }

    public function excluirProducao()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];

        if (empty($ids)) {
            echo json_encode(['success' => false, 'msg' => 'IDs não informados']);
            exit;
        }

        $resultado = $this->relatorioModel->excluirLancamentos($ids);

        echo json_encode($resultado);
        exit;
    }

    // === RELATÓRIO DE VALORES A PAGAR ===
    public function pagamentos()
    {
        $hoje = new DateTime();
        $data_inicio = $_GET['ini'] ?? $hoje->format('Y-m-01');
        $data_fim = $_GET['fim'] ?? $hoje->format('Y-m-t');

        if (strtotime($data_inicio) > strtotime($data_fim)) {
            $_SESSION['erro'] = "Data inicial não pode ser maior que data final.";
            header('Location: /sgi_erp/relatorios/pagamentos');
            exit;
        }

        // Reusa a lógica de quantidades + multiplica pelo valor por quilo
        $relatorio = $this->relatorioModel->getValoresFinanceirosDiaADia($data_inicio, $data_fim);

        $title = "RELATÓRIO FINANCEIRO - VALORES A PAGAR: " .
            date('d/m/Y', strtotime($data_inicio)) . " - " .
            date('d/m/Y', strtotime($data_fim));

        $content_view = ROOT_PATH . 'View/relatorio_pagamentos.php';

        $dados = [
            'matriz' => $relatorio['matriz'],
            'detalhes' => $relatorio['detalhes'],
            'ids' => $relatorio['ids'],
            'datas' => $relatorio['datas'],
            'total_por_dia' => $relatorio['total_por_dia'],
            'total_geral' => $relatorio['total_geral'],
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'pode_editar' => true,
            'funcionario_ids' => $relatorio['funcionario_ids'],
            'tipo_produto_ids' => $relatorio['tipo_produto_ids']
        ];

        require_once ROOT_PATH . 'View/template/main.php';
    }
}

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
        require_once ROOT_PATH . 'public' . DS . 'libs' . DS . 'dompdf' . DS . 'vendor' . DS . 'autoload.php';
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
            'ids' => $relatorio['ids'],
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
        }

        $result = $this->relatorioModel->atualizarLancamentos($updates);

        echo json_encode([
            'success' => $result['success'],
            'message' => $result['msg'],
            'errors' => $result['erros'],
            'novos_ids' => $result['novos_ids'] // *** Repassa a lista de novos IDs ***
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

    // === RELATÓRIO DE PRODUTIVIDADE ===
    public function produtividade()
    {
        $hoje = new DateTime();
        $data_inicio = $_GET['ini'] ?? $hoje->format('Y-m-01');
        $data_fim = $_GET['fim'] ?? $hoje->format('Y-m-t');

        if (strtotime($data_inicio) > strtotime($data_fim)) {
            $_SESSION['erro'] = "Data inicial não pode ser maior que data final.";
            header('Location: /sgi_erp/relatorios/produtividade');
            exit;
        }

        // 1. Usa a função que busca o relatório completo (que já calcula a produtividade)
        $relatorio = $this->relatorioModel->gerarRelatorioCompleto($data_inicio, $data_fim);

        $title = "ANÁLISE DE PRODUTIVIDADE/HORA - PERÍODO: " .
            date('d/m/Y', strtotime($data_inicio)) . " - " .
            date('d/m/Y', strtotime($data_fim));

        $content_view = ROOT_PATH . 'View/relatorio_produtividade.php';

        $dados = [
            // Passa apenas o bloco de produtividade necessário, além das datas de filtro
            'produtividade' => $relatorio['produtividade'],
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
        ];

        require_once ROOT_PATH . 'View/template/main.php';
    }

    /**
     * Gera e envia o relatório em formato PDF ou Excel.
     * Rota: /relatorios/imprimir
     */
    public function imprimir()
    {
        // 1. Coleta e valida filtros básicos
        $data_inicio = $_GET['ini'] ?? date('Y-m-01');
        $data_fim = $_GET['fim'] ?? date('Y-m-t');
        $tipo_relatorio = $_GET['tipo'] ?? 'pagamentos'; // 'pagamentos', 'quantidades' ou 'produtividade'
        $formato = $_GET['formato'] ?? 'pdf'; // 'pdf' ou 'excel'

        if (strtotime($data_inicio) > strtotime($data_fim)) {
            $_SESSION['erro'] = "Data inicial não pode ser maior que data final.";
            header('Location: /sgi_erp/relatorios/' . $tipo_relatorio);
            exit;
        }

        // 2. Busca os dados com base no tipo de relatório
        $relatorio = [];
        $view_path = '';
        $relatorio_title = '';

        if ($tipo_relatorio === 'pagamentos') {
            $relatorio = $this->relatorioModel->getValoresFinanceirosDiaADia($data_inicio, $data_fim);
            $relatorio_title = "RELATÓRIO FINANCEIRO - VALORES A PAGAR";
            $view_path = ROOT_PATH . 'View/relatorio_pagamento_pdf.php';
        } elseif ($tipo_relatorio === 'quantidades') {
            $relatorio = $this->relatorioModel->getQuantidadesDiaADia($data_inicio, $data_fim);
            $relatorio_title = "RELATÓRIO DE PRODUÇÃO - QUANTIDADES (KG)";
            $view_path = ROOT_PATH . 'View/relatorio_quantidades_pdf.php';
        } elseif ($tipo_relatorio === 'produtividade') {
            // Produtividade usa o método completo para obter a produtividade (kg/h)
            $relatorio = $this->relatorioModel->gerarRelatorioCompleto($data_inicio, $data_fim);
            // Renomeia o array principal para 'produtividade' para a View
            $relatorio['produtividade_data'] = $relatorio['produtividade'];
            $relatorio_title = "ANÁLISE DE PRODUTIVIDADE/HORA";
            $view_path = ROOT_PATH . 'View/relatorio_produtividade.php'; // Reusa a view original
        } else {
            $_SESSION['erro'] = "Tipo de relatório inválido.";
            header('Location: /sgi_erp/dashboard');
            exit;
        }

        // 3. Monta o array $dados
        $dados = array_merge($relatorio, [
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim
        ]);
        $title = $relatorio_title . " - PERÍODO: " . date('d/m/Y', strtotime($data_inicio)) . " - " . date('d/m/Y', strtotime($data_fim));

        // 4. Processamento da Exportação
        if ($formato === 'pdf') {
            $this->gerarPDF($view_path, $title, $dados, $tipo_relatorio);
        } elseif ($formato === 'excel') {
            $this->gerarExcel($relatorio, $tipo_relatorio, $data_inicio, $data_fim);
        } else {
            $_SESSION['erro'] = "Formato de exportação inválido.";
            header('Location: /sgi_erp/relatorios/' . $tipo_relatorio);
            exit;
        }
    }

    /**
     * Função que renderiza e envia o PDF (usando Dompdf).
     */
    private function gerarPDF($view_path, $title, $dados, $tipo_relatorio)
    {
        // Certifica-se que as variáveis do array $dados estão disponíveis na View
        extract($dados);

        // 1. Captura o HTML da View
        ob_start();
        if ($tipo_relatorio === 'produtividade') {
            // Produtividade reusa a view original e usa o array 'produtividade_data'
            $dados['produtividade'] = $dados['produtividade_data'];
            require ROOT_PATH . 'View/relatorio_produtividade.php';
        } else {
            // Pagamentos/Quantidades usam o layout de impressão simplificado
            require $view_path;
        }
        $html = ob_get_clean();

        // 2. Configura e gera o Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial'); // Fonte simples para garantir compatibilidade

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape'); // Layout paisagem ideal
        $dompdf->render();

        // 3. Envia o PDF para o navegador
        $filename = "Relatorio_{$tipo_relatorio}_" . date('Ymd_His') . ".pdf";
        $dompdf->stream($filename, ["Attachment" => true]);
        exit;
    }

    /**
     * Função que gera e envia a exportação para Excel (via HTML).
     */
    private function gerarExcel($relatorio, $tipo_relatorio, $data_inicio, $data_fim)
    {
        // 1. Define o caminho da View de impressão com base no tipo
        if ($tipo_relatorio === 'pagamentos') {
            $view_path = ROOT_PATH . 'View/relatorio_pagamento_pdf.php';
            $relatorio_title = "RELATÓRIO FINANCEIRO - VALORES A PAGAR";
        } elseif ($tipo_relatorio === 'quantidades') {
            $view_path = ROOT_PATH . 'View/relatorio_quantidades_pdf.php';
            $relatorio_title = "RELATÓRIO DE PRODUÇÃO - QUANTIDADES (KG)";
        } elseif ($tipo_relatorio === 'produtividade') {
            $view_path = ROOT_PATH . 'View/relatorio_produtividade.php';
            $relatorio['produtividade'] = $relatorio['produtividade_data'];
            $relatorio_title = "ANÁLISE DE PRODUTIVIDADE/HORA";
        } else {
            return;
        }

        // 2. Prepara variáveis para a View
        $dados = array_merge($relatorio, [
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim
        ]);
        $title = $relatorio_title . " - PERÍODO: " . date('d/m/Y', strtotime($data_inicio)) . " - " . date('d/m/Y', strtotime($data_fim));

        extract($dados);

        // 3. Captura o HTML da View (o mesmo usado para PDF)
        ob_start();
        require $view_path;
        $html = ob_get_clean();

        // 4. Configura os Headers para o Excel entender que o conteúdo é uma tabela
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        $filename = "Relatorio_{$tipo_relatorio}_" . date('Ymd_His') . ".xls";
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: max-age=0');

        // 5. Remove tags específicas do HTML que podem quebrar o Excel (como a barra lateral)
        // E envia apenas o HTML puro da tabela.

        // Se for produtividade, o HTML capturado já contém a tabela simples.
        if ($tipo_relatorio === 'produtividade') {
            $tabela_html = $html;
        } else {
            // Para pagamentos/quantidades, assumimos que a view de PDF tem a tabela limpa
            // Se precisar isolar APENAS a tabela, ajuste este trecho:
            $tabela_html = $html; // No momento, enviamos o HTML completo da view.
        }

        echo $tabela_html;
        exit;
    }
}

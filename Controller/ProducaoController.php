<?php
// Controller/ProducaoController.php

class ProducaoController extends AppController
{
    private $equipeModel;
    private $acaoModel;
    private $tipoProdutoModel;
    private $producaoModel;
    private $funcionarioModel;

    public function __construct()
    {
        // Garante a autenticação via AppController
        parent::__construct();

        $this->equipeModel = new EquipeModel();
        $this->acaoModel = new AcaoModel();
        $this->tipoProdutoModel = new TipoProdutoModel();
        $this->producaoModel = new ProducaoModel();
        $this->funcionarioModel = new FuncionarioModel();
    }

    /**
     * Exibe a interface de lançamento de produção.
     * Rota: /producao
     */
    public function index()
    {
        $apontador_id = $_SESSION['funcionario_id'];

        // 1. Buscar a equipe do apontador para saber quem lançar
        $equipe = $this->equipeModel->buscarEquipeDoApontador($apontador_id);

        if (!$equipe) {
            $_SESSION['erro'] = 'Você precisa montar uma equipe primeiro para lançar a produção.';
            header('Location: /sgi_erp/equipes');
            exit();
        }

        // 2. Buscar os membros da equipe
        $membros = $this->equipeModel->buscarFuncionariosDaEquipe($equipe->id);

        // 3. Buscar as opções do formulário
        $acoes = $this->acaoModel->buscarTodas();
        $tipos_produto = $this->tipoProdutoModel->buscarTodos();

        // 4. Preparar dados para a View
        $dados = [
            'equipe' => $equipe,
            'membros' => $membros,
            'acoes' => $acoes,
            'tipos_produto' => $tipos_produto
        ];

        // Variáveis para o Template
        $title = "Lançamento de Produção";
        $content_view = 'View/producao.php';

        // Inclui o template principal
        require_once ROOT_PATH . 'View/template/main.php';
    }

    /**
     * Processa o formulário de lançamento de produção.
     * Rota: /producao/salvar
     */
    public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/producao');
            exit();
        }

        //Tenta ler o JSON (requisição do producao-editar-dia.js)
        $json_data = file_get_contents('php://input');
        $dados_post = json_decode($json_data, true);

        $is_ajax = !empty($json_data);
        $id = $dados_post['id'] ?? null; // ID só existe na EDIÇÃO

        if ($id && $is_ajax) {
            // Se for requisição AJAX com ID (EDIÇÃO/UPDATE)

            // 1. Coletar dados do $dados_post (JSON)
            $acao_id = $dados_post['acao_id'] ?? null;
            $tipo_produto_id = $dados_post['tipo_produto_id'] ?? null;
            $quantidade_kg = $dados_post['quantidade_kg'] ?? 0.0;
            $hora_inicio = $dados_post['hora_inicio'] ?? null;
            $hora_fim = $dados_post['hora_fim'] ?? null;
            $lote_produto = ''; // O JS não envia, então deixamos vazio/default

            $response = ['success' => false, 'msg' => ''];

            // 2. Chama o método de atualização no Model
            if ($this->producaoModel->atualizarLancamento(
                $id,
                $acao_id,
                $tipo_produto_id,
                $quantidade_kg,
                $lote_produto,
                $hora_inicio,
                $hora_fim
            )) {
                $response['success'] = true;
            } else {
                $response['msg'] = 'Falha ao salvar a edição no banco de dados.';
            }

            // 3. Retorna o JSON e TERMINA A EXECUÇÃO
            header('Content-Type: application/json'); // Garante que o navegador saiba que é JSON
            echo json_encode($response);
            exit();
        }

        // 1. Coleta e Sanitiza os dados do POST
        $funcionario_id = filter_input(INPUT_POST, 'funcionario_id', FILTER_VALIDATE_INT);
        $acao_id = filter_input(INPUT_POST, 'acao_id', FILTER_VALIDATE_INT);
        $tipo_produto_id = filter_input(INPUT_POST, 'tipo_produto_id', FILTER_VALIDATE_INT);
        $lote_produto = trim(filter_input(INPUT_POST, 'lote_produto', FILTER_SANITIZE_STRING));
        $hora_inicio = trim(filter_input(INPUT_POST, 'hora_inicio', FILTER_SANITIZE_STRING));
        $hora_fim = trim(filter_input(INPUT_POST, 'hora_fim', FILTER_SANITIZE_STRING));
        $quantidade_kg = filter_input(INPUT_POST, 'quantidade_kg', FILTER_VALIDATE_FLOAT);

        $apontador_id = $_SESSION['funcionario_id'];

        // 2. Busca a equipe para obter o ID da equipe atual
        $equipe = $this->equipeModel->buscarEquipeDoApontador($apontador_id);
        $equipe_id = $equipe ? $equipe->id : null;


        // 3. Validação
        if (!$funcionario_id || !$acao_id || !$tipo_produto_id || $quantidade_kg === false || $quantidade_kg <= 0 || !$equipe_id || empty($hora_inicio) || empty($hora_fim)) {
            $_SESSION['erro'] = 'Todos os campos são obrigatórios, incluindo Horário e Quantidade.';
            header('Location: /sgi_erp/producao');
            exit();
        }

        // 4. Validação do Lote: Checar se é obrigatório (Se usa_lote = TRUE, o lote não pode ser vazio)
        $tipoProdutoModel = new TipoProdutoModel();
        $tipoProduto = $tipoProdutoModel->buscarPorId($tipo_produto_id);

        if (($tipoProduto->usa_lote ?? 1) && empty($lote_produto)) { // Se usa_lote for TRUE e o lote estiver vazio
            $_SESSION['erro'] = 'O Lote do Produto é obrigatório para este tipo de produto/serviço.';
            header('Location: /sgi_erp/producao');
            exit();
        }

        // 5. Salva o Lançamento usando o Model
        if ($this->producaoModel->registrarLancamento(
            $funcionario_id,
            $acao_id,
            $tipo_produto_id,
            $lote_produto,
            $quantidade_kg,
            $equipe_id,
            $hora_inicio,
            $hora_fim
        )) {

            $_SESSION['sucesso'] = "Produção de **{$quantidade_kg} kg** registrada com sucesso!";
        } else {
            $_SESSION['erro'] = 'Erro interno ao salvar o registro de produção. Tente novamente.';
        }

        header('Location: /sgi_erp/producao');
        exit();
    }

    /**
     * Exibe a interface de lançamento de produção em massa.
     * Rota: /producao/massa
     */
    public function massa()
    {
        $apontador_id = $_SESSION['funcionario_id'];

        // 1. Buscar a equipe do apontador (necessária para saber quem listar)
        $equipe = $this->equipeModel->buscarEquipeDoApontador($apontador_id);

        if (!$equipe) {
            $_SESSION['erro'] = 'Você precisa montar uma equipe primeiro para lançar a produção.';
            header('Location: /sgi_erp/equipes');
            exit();
        }

        // 2. Buscar os membros da equipe (Os funcionários que terão campos de quantidade)
        $membros = $this->equipeModel->buscarFuncionariosDaEquipe($equipe->id);

        // 3. Buscar as opções (Ações e Produtos) para os dropdowns
        $acoes = $this->acaoModel->buscarTodas();
        $tipos_produto = $this->tipoProdutoModel->buscarTodos();

        // 4. Preparar dados para a View
        $dados = [
            'equipe' => $equipe,
            'membros' => $membros,
            'acoes' => $acoes,
            'tipos_produto' => $tipos_produto
        ];

        // Variáveis para o Template
        $title = "Lançamento em Massa por Equipe";
        $content_view = ROOT_PATH . 'View' . DS . 'producao_massa.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Processa o formulário de lançamento em massa
     * Rota: /producao/massa/salvar
     */
    public function salvarMassa()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/producao/massa');
            exit();
        }

        // 1. Coleta e Valida os dados comuns
        $acao_id = filter_input(INPUT_POST, 'acao_id', FILTER_VALIDATE_INT);
        $tipo_produto_id = filter_input(INPUT_POST, 'tipo_produto_id', FILTER_VALIDATE_INT);
        $lote_produto = trim(filter_input(INPUT_POST, 'lote_produto', FILTER_SANITIZE_STRING));
        $hora_inicio = trim(filter_input(INPUT_POST, 'hora_inicio', FILTER_SANITIZE_STRING));
        $hora_fim = trim(filter_input(INPUT_POST, 'hora_fim', FILTER_SANITIZE_STRING));
        $apontador_id = $_SESSION['funcionario_id'];
        $quantidades = $_POST['quantidades'] ?? []; // Array associativo: [funcionario_id => quantidade_kg]

        $equipeModel = new EquipeModel(); // Garante que o Model esteja acessível
        $tipoProdutoModel = new TipoProdutoModel();

        $equipe = $equipeModel->buscarEquipeDoApontador($apontador_id);
        $equipe_id = $equipe ? $equipe->id : null;

        // 2. Validação Básica e de Tempo
        if (!$acao_id || !$tipo_produto_id || !$equipe_id || empty($hora_inicio) || empty($hora_fim)) {
            $_SESSION['erro'] = 'Erro: Ação, Produto, Horário de Início/Fim e Equipe são obrigatórios.';
            header('Location: /sgi_erp/producao/massa');
            exit();
        }

        // 3. Validação do Lote: Checar se é obrigatório (usa_lote = TRUE)
        $tipoProduto = $tipoProdutoModel->buscarPorId($tipo_produto_id);

        if (($tipoProduto->usa_lote ?? 1) && empty($lote_produto)) {
            $_SESSION['erro'] = 'O Lote do Produto é obrigatório para este tipo de produto/serviço.';
            header('Location: /sgi_erp/producao/massa');
            exit();
        }

        $registros_salvos = 0;
        $erros = 0;

        // 4. Itera sobre as quantidades lançadas
        foreach ($quantidades as $funcionario_id => $quantidade_str) {
            $quantidade_kg = filter_var($quantidade_str, FILTER_VALIDATE_FLOAT);

            if ($quantidade_kg > 0) {
                // 5. Salva o lançamento (reutilizando o método do ProducaoModel)
                if ($this->producaoModel->registrarLancamento(
                    $funcionario_id,
                    $acao_id,
                    $tipo_produto_id,
                    $lote_produto,
                    $quantidade_kg,
                    $equipe_id,
                    $hora_inicio,
                    $hora_fim
                )) {

                    $registros_salvos++;
                } else {
                    $erros++;
                }
            }
        }

        // 6. Feedback
        if ($registros_salvos > 0) {
            $_SESSION['sucesso'] = "Lançamento em massa concluído! Total de $registros_salvos registros salvos.";
        } elseif ($erros > 0) {
            $_SESSION['erro'] = "Nenhum registro salvo, mas ocorreram $erros erros no processamento.";
        } else {
            $_SESSION['erro'] = 'Nenhuma quantidade válida foi inserida.';
        }

        header('Location: /sgi_erp/producao/massa');
        exit();
    }

    /* public function editarMassa()
    {
        $data_selecionada = $_GET['data'] ?? date('Y-m-d');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_selecionada)) {
            $_SESSION['erro'] = 'Data inválida.';
            header('Location: /sgi_erp/producao/massa');
            exit;
        }

        // Busca lançamentos existentes na data
        $lancamentos_existentes = $this->producaoModel->buscarLancamentosPorData($data_selecionada);

        // Opções dos selects
        $acoes = $this->acaoModel->buscarTodas();
        $tipos_produto = $this->tipoProdutoModel->buscarTodos();

        // Equipe atual
        $apontador_id = $_SESSION['funcionario_id'];
        $equipe = $this->equipeModel->buscarEquipeDoApontador($apontador_id);
        if (!$equipe) {
            $_SESSION['erro'] = 'Você não tem equipe montada.';
            header('Location: /sgi_erp/equipes');
            exit;
        }
        $membros = $this->equipeModel->buscarFuncionariosDaEquipe($equipe->id);

        // Preenche valores globais (se já tiver lançamento)
        $acao_global = '';
        $produto_global = '';
        $lote_global = '';
        $hora_inicio = '';
        $hora_fim = '';
        $preenchidos = [];

        if (!empty($lancamentos_existentes)) {
            $primeiro = $lancamentos_existentes[0];
            $acao_global = $primeiro['acao_id'];
            $produto_global = $primeiro['tipo_produto_id'];
            $lote_global = $primeiro['lote_produto'] ?? '';
            $hora_inicio = $primeiro['hora_inicio'] ?? '';
            $hora_fim = $primeiro['hora_fim'] ?? '';

            foreach ($lancamentos_existentes as $l) {
                $preenchidos[$l['funcionario_id']] = $l['quantidade_kg'];
            }
        }

        $title = "EDIÇÃO EM MASSA - " . date('d/m/Y', strtotime($data_selecionada));

        $dados = compact(
            'data_selecionada',
            'equipe',
            'membros',
            'acoes',
            'tipos_produto',
            'preenchidos',
            'acao_global',
            'produto_global',
            'lote_global',
            'hora_inicio',
            'hora_fim'
        );

        $content_view = ROOT_PATH . 'View/producao_editar_massa.php';
        require_once ROOT_PATH . 'View/template/main.php';
    } */

    /* public function salvarMassaEdit()
    {
        $data = $_POST['data'] ?? '';
        $acao_id = $_POST['acao_id'] ?? 0;
        $tipo_produto_id = $_POST['tipo_produto_id'] ?? 0;
        $lote_produto = $_POST['lote_produto'] ?? '';
        $hora_inicio = !empty($_POST['hora_inicio']) ? $_POST['hora_inicio'] : null;
        $hora_fim = !empty($_POST['hora_fim']) ? $_POST['hora_fim'] : null;
        $quantidades = $_POST['quantidades'] ?? [];

        if (empty($data) || empty($acao_id) || empty($tipo_produto_id)) {
            $_SESSION['erro'] = 'Preencha todos os campos obrigatórios.';
            header('Location: /sgi_erp/producao/editar-massa?data=' . $data);
            exit;
        }

        // Busca equipe
        $apontador_id = $_SESSION['funcionario_id'];
        $equipe = $this->equipeModel->buscarEquipeDoApontador($apontador_id);
        $equipe_id = $equipe->id ?? null;

        $salvos = 0;
        $atualizados = 0;
        $removidos = 0;

        foreach ($quantidades as $funcionario_id => $qtd_str) {
            $qtd = (float)str_replace(['.', ','], ['', '.'], $qtd_str);

            // Busca se já existe lançamento deste funcionário na data + ação + produto
            $existente = $this->producaoModel->buscarLancamentoUnico($data, $funcionario_id, $acao_id, $tipo_produto_id);

            if ($qtd > 0) {
                if ($existente) {
                    // UPDATE
                    $this->producaoModel->atualizarLancamento(
                        $existente['id'],
                        $qtd,
                        $lote_produto,
                        $hora_inicio,
                        $hora_fim
                    );
                    $atualizados++;
                } else {
                    // INSERT
                    $this->producaoModel->registrarLancamento(
                        $funcionario_id,
                        $acao_id,
                        $tipo_produto_id,
                        $lote_produto,
                        $qtd,
                        $equipe_id,
                        $hora_inicio,
                        $hora_fim
                    );
                    $salvos++;
                }
            } else {
                // REMOVE se existia e agora é zero
                if ($existente) {
                    $this->producaoModel->excluirLancamento($existente['id']);
                    $removidos++;
                }
            }
        }

        $_SESSION['sucesso'] = "Edição concluída! Salvos: $salvos | Atualizados: $atualizados | Removidos: $removidos";
        header('Location: /sgi_erp/producao/editar-massa?data=' . $data);
        exit;
    } */

    public function editarDia()
    {
        // 1. Pega a data da URL ou usa a data atual (Y-m-d)
        $data = $_GET['data'] ?? date('Y-m-d');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            $data = date('Y-m-d');
        }

        // 2. Validação/Sanitização da data 
        // Se a data não for um formato YYYY-MM-DD válido, volta para a data atual.
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            // Em vez de só redefinir a variável local $data, 
            // precisamos ter certeza que a view recebe o valor correto.
            $data = date('Y-m-d');
            // Se quiser informar o erro de data para o usuário
            if (isset($_GET['data'])) { // Só se a data foi enviada e estava errada
                $_SESSION['erro'] = 'Data informada é inválida. Exibindo dados da data atual.';
            }
        }

        // A variável $data é usada para buscar os lançamentos e é passada para a View 
        $data_selecionada = $data; // Renomeando para clareza com a View

        $lancamentos = $this->producaoModel->buscarTodosLancamentosDoDia($data_selecionada);

        // Cálculo do total por funcionário
        $totais = [];
        foreach ($lancamentos as $l) {
            $nome = $l['funcionario_nome'];
            if (!isset($totais[$nome])) $totais[$nome] = 0;
            $totais[$nome] += $l['quantidade_kg'];
        }

        $title = "EDITAR PRODUÇÃO - " . date('d/m/Y', strtotime($data));

        $dados = [
            'data_selecionada' => $data, // <- SEMPRE DEFINIDA
            'lancamentos' => $lancamentos,
            'acoes' => $this->acaoModel->buscarTodas(),
            'tipos_produto' => $this->tipoProdutoModel->buscarTodos(),
            'totais' => $totais // <- passa os totais pra view
        ];

        extract($dados);

        $content_view = ROOT_PATH . 'View/producao_editar_dia.php';
        require_once ROOT_PATH . 'View/template/main.php';
    }
}

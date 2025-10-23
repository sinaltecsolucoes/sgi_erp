<?php
// Controller/ProducaoController.php

class ProducaoController extends AppController
{
    private $equipeModel;
    private $acaoModel;
    private $tipoProdutoModel;
    private $producaoModel;

    public function __construct()
    {
        // Garante a autenticação via AppController
        parent::__construct();

        $this->equipeModel = new EquipeModel();
        $this->acaoModel = new AcaoModel();
        $this->tipoProdutoModel = new TipoProdutoModel();
        $this->producaoModel = new ProducaoModel();

        // Regra de Negócio: Apenas apontadores
        /* if ($_SESSION['funcionario_tipo'] !== 'apontador') {
            $_SESSION['erro'] = 'Acesso negado. Apenas Apontadores podem lançar a produção.';
            header('Location: /sgi_erp/dashboard');
            exit();
        } */
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
}

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
        if ($_SESSION['funcionario_tipo'] !== 'apontador') {
            $_SESSION['erro'] = 'Acesso negado. Apenas Apontadores podem lançar a produção.';
            header('Location: /sgi_erp/dashboard');
            exit();
        }
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
        $quantidade_kg = filter_input(INPUT_POST, 'quantidade_kg', FILTER_VALIDATE_FLOAT);

        $apontador_id = $_SESSION['funcionario_id'];

        // 2. Busca a equipe para obter o ID da equipe atual
        $equipe = $this->equipeModel->buscarEquipeDoApontador($apontador_id);
        $equipe_id = $equipe ? $equipe->id : null;


        // 3. Validação Básica
        if (!$funcionario_id || !$acao_id || !$tipo_produto_id || $quantidade_kg === false || $quantidade_kg <= 0 || !$equipe_id) {
            $_SESSION['erro'] = 'Todos os campos são obrigatórios ou a quantidade é inválida. Certifique-se de que a equipe foi montada.';
            header('Location: /sgi_erp/producao');
            exit();
        }

        // 4. Salva o Lançamento usando o Model
        if ($this->producaoModel->registrarLancamento($funcionario_id, $acao_id, $tipo_produto_id, $quantidade_kg, $equipe_id)) {
            $_SESSION['sucesso'] = "Produção de **{$quantidade_kg} kg** registrada com sucesso!";
        } else {
            $_SESSION['erro'] = 'Erro interno ao salvar o registro de produção. Tente novamente.';
        }

        header('Location: /sgi_erp/producao');
        exit();
    }
}

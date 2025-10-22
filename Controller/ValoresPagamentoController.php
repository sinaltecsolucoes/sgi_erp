<?php
// Controller/ValoresPagamentoController.php

class ValoresPagamentoController extends AppController
{
    private $valPagModel;
    private $acaoModel;
    private $tipoProdutoModel;

    public function __construct()
    {
        parent::__construct();
        $this->valPagModel = new ValoresPagamentoModel();
        $this->acaoModel = new AcaoModel();
        $this->tipoProdutoModel = new TipoProdutoModel();

        // A ACL no index.php deve proteger esta rota para 'admin'.
    }

    /**
     * Exibe a lista de valores de pagamento cadastrados.
     * Rota: /admin/valores-pagamento
     */
    public function index()
    {
        $valores = $this->valPagModel->buscarTodos();

        $dados = ['valores' => $valores];
        $title = "Cadastro de Valores de Pagamento";
        $content_view = ROOT_PATH . 'View' . DS . 'valores_pagamento_lista.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Exibe o formulário para criar um novo valor.
     * Rota: /admin/valores-pagamento/cadastro
     */
    public function cadastro()
    {
        // Carrega as opções para os dropdowns
        $acoes = $this->acaoModel->buscarTodas();
        $tipos_produto = $this->tipoProdutoModel->buscarTodos();

        $dados = [
            'acoes' => $acoes,
            'tipos_produto' => $tipos_produto
        ];

        $title = "Novo Valor de Pagamento";
        $content_view = ROOT_PATH . 'View' . DS . 'valores_pagamento_cadastro.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Processa o salvamento do formulário.
     * Rota: /admin/valores-pagamento/salvar
     */
    public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/admin/valores-pagamento');
            exit();
        }

        $dados = [
            'id' => filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT),
            'tipo_produto_id' => filter_input(INPUT_POST, 'tipo_produto_id', FILTER_VALIDATE_INT),
            'acao_id' => filter_input(INPUT_POST, 'acao_id', FILTER_VALIDATE_INT),
            // Valor por quilo é capturado como string e limpo no Model
            'valor_por_quilo' => filter_input(INPUT_POST, 'valor_por_quilo', FILTER_SANITIZE_STRING)
        ];

        if (!$dados['tipo_produto_id'] || !$dados['acao_id'] || empty($dados['valor_por_quilo'])) {
            $_SESSION['erro'] = 'Todos os campos são obrigatórios.';
            header('Location: /sgi_erp/admin/valores-pagamento/cadastro');
            exit();
        }

        if ($this->valPagModel->salvar($dados)) {
            $_SESSION['sucesso'] = 'Valor de pagamento salvo com sucesso!';
        } else {
            // A mensagem de erro de duplicidade já é setada no Model
            $_SESSION['erro'] = $_SESSION['erro'] ?? 'Não foi possível salvar o valor.';
        }

        header('Location: /sgi_erp/admin/valores-pagamento');
        exit();
    }
}

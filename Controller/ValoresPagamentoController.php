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
    }

    /**
     * Lista todos os valores
     * Rota: /admin/valores-pagamento
     */
    public function index()
    {
        $valores = $this->valPagModel->buscarTodos();
        // $tipo_usuario = $_SESSION['funcionario_tipo'] ?? 'convidado';

        $dados = [
            'valores' => $valores,
            //'pode_editar' => Acl::check('ValoresPagamentoController@cadastro', $tipo_usuario)
            'pode_editar' => true
        ];

        $title = "Gestão de Valores de Pagamento";
        $content_view = ROOT_PATH . 'View' . DS . 'valores_pagamento_lista.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Formulário de cadastro/edição
     * Rota: /admin/valores-pagamento/cadastro?id=X
     */
    /* public function cadastro()
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $valor_existente = null;
        $tipo_usuario = $_SESSION['funcionario_tipo'] ?? 'convidado';

        if ($id) {
            $valor_existente = $this->valPagModel->buscarPorId($id);
            if (!$valor_existente) {
                $_SESSION['erro'] = 'Valor não encontrado.';
                header('Location: /sgi_erp/admin/valores-pagamento');
                exit();
            }
        }

        $acoes = $this->acaoModel->buscarTodas();
        $tipos_produto = $this->tipoProdutoModel->buscarTodos();

        $dados = [
            'acoes' => $acoes,
            'tipos_produto' => $tipos_produto,
            'valor_existente' => $valor_existente,
            'pode_editar' => Acl::check('ValoresPagamentoController@cadastro', $tipo_usuario)
        ];

        $title = $id ? "Editar Valor de Pagamento" : "Novo Valor de Pagamento";
        $content_view = ROOT_PATH . 'View' . DS . 'valores_pagamento_cadastro.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    } */

    public function cadastro()
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $valor_existente = null;
        //$tipo_usuario = $_SESSION['funcionario_tipo'] ?? 'convidado';

        if ($id) {
            $valor_existente = $this->valPagModel->buscarPorId($id);
            if (!$valor_existente) {
                $_SESSION['erro'] = 'Valor não encontrado.';
                header('Location: /sgi_erp/admin/valores-pagamento');
                exit();
            }
        }

        $acoes = $this->acaoModel->buscarTodas();
        $tipos_produto = $this->tipoProdutoModel->buscarTodos();

        $dados = [
            'acoes' => $acoes,
            'tipos_produto' => $tipos_produto,
            'valor_existente' => $valor_existente,
            //'pode_editar' => Acl::check('ValoresPagamentoController@cadastro', $tipo_usuario)
            'pode_editar' => true
        ];

        $title = $id ? "Editar Valor de Pagamento" : "Novo Valor de Pagamento";
        $content_view = ROOT_PATH . 'View' . DS . 'valores_pagamento_cadastro.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Salva (criar ou editar)
     * Rota: /admin/valores-pagamento/salvar (POST)
     */
    public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/admin/valores-pagamento');
            exit();
        }

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $tipo_produto_id = filter_input(INPUT_POST, 'tipo_produto_id', FILTER_VALIDATE_INT);
        $acao_id = filter_input(INPUT_POST, 'acao_id', FILTER_VALIDATE_INT);
        $valor_por_quilo = trim($_POST['valor_por_quilo'] ?? '');

        if (!$tipo_produto_id || !$acao_id || empty($valor_por_quilo)) {
            $_SESSION['erro'] = 'Todos os campos são obrigatórios.';
            $redirect = '/sgi_erp/admin/valores-pagamento/cadastro';
            if ($id) $redirect .= "?id=$id";
            header("Location: $redirect");
            exit();
        }

        // Valida valor positivo
        $valor_por_quilo = str_replace(',', '.', $valor_por_quilo);
        if (!is_numeric($valor_por_quilo) || $valor_por_quilo <= 0) {
            $_SESSION['erro'] = 'O valor deve ser um número positivo.';
            header("Location: /sgi_erp/admin/valores-pagamento/cadastro?id=$id");
            exit();
        }

        $dados = [
            'id' => $id ?: null,
            'tipo_produto_id' => $tipo_produto_id,
            'acao_id' => $acao_id,
            'valor_por_quilo' => $valor_por_quilo
        ];

        if ($this->valPagModel->salvar($dados)) {
            $_SESSION['sucesso'] = 'Valor salvo com sucesso!';
        } else {
            $_SESSION['erro'] = $_SESSION['erro'] ?? 'Erro ao salvar o valor.';
        }

        header('Location: /sgi_erp/admin/valores-pagamento');
        exit();
    }

    /**
     * Exclui um valor
     * Rota: /admin/valores-pagamento/excluir?id=X
     */
    public function excluir()
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id || !$this->valPagModel->excluir($id)) {
            $_SESSION['erro'] = 'Erro ao excluir o valor.';
        } else {
            $_SESSION['sucesso'] = 'Valor excluído com sucesso.';
        }
        header('Location: /sgi_erp/admin/valores-pagamento');
        exit();
    }
}

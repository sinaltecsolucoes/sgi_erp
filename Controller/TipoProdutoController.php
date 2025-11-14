<?php
// Controller/TipoProdutoController.php

class TipoProdutoController extends AppController
{
    private $tipoProdutoModel;

    public function __construct()
    {
        parent::__construct();
        $this->tipoProdutoModel = new TipoProdutoModel();

        // A ACL no index.php deve proteger esta rota para 'admin'.
    }

    /**
     * Exibe a lista de tipos de produto cadastrados.
     * Rota: /admin/tipos-produto
     */
    public function index()
    {
        $tipos = $this->tipoProdutoModel->buscarTodos();

        $dados = ['tipos' => $tipos];
        $title = "Cadastro de Tipos de Produto";
        $content_view = ROOT_PATH . 'View' . DS . 'tipo_produto_lista.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Exibe o formulário para criar/editar um tipo de produto.
     * Rota: /admin/tipos-produto/cadastro?id={id}
     */
    public function cadastro()
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $tipo = null;

        if ($id) {
            $tipo = $this->tipoProdutoModel->buscarPorId($id);
            if (!$tipo) {
                $_SESSION['erro'] = 'Tipo de Produto não encontrado.';
                header('Location: /sgi_erp/admin/tipos-produto');
                exit();
            }
        }

        $dados = ['tipo' => $tipo];
        $title = ($id ? "Editar" : "Novo") . " Tipo de Produto";
        $content_view = ROOT_PATH . 'View' . DS . 'tipo_produto_cadastro.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Processa o salvamento do formulário (Criação/Edição).
     * Rota: /admin/tipos-produto/salvar
     */
    public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/admin/tipos-produto');
            exit();
        }

        $dados = [
            'id' => filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT),
            'nome' => filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING),
            'usa_lote' => isset($_POST['usa_lote']) ? 1 : 0 // Captura 1 se marcado, 0 se não
        ];

        if (empty($dados['nome'])) {
            $_SESSION['erro'] = 'O Nome do Tipo de Produto é obrigatório.';
            header('Location: /sgi_erp/admin/tipos-produto/cadastro');
            exit();
        }

        if ($this->tipoProdutoModel->salvar($dados)) {
            $_SESSION['sucesso'] = 'Tipo de Produto salvo com sucesso!';
        } else {
            $_SESSION['erro'] = $_SESSION['erro'] ?? 'Não foi possível salvar o Tipo de Produto.';
        }

        header('Location: /sgi_erp/admin/tipos-produto');
        exit();
    }

    /**
     * Exclui um tipo de produto (via AJAX)
     * Rota: /admin/tipos-produto/excluir (POST)
     */
    public function excluir()
    {
        // Protege contra acesso direto
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit();
        }

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit();
        }

        if ($this->tipoProdutoModel->excluir($id)) {
            echo json_encode(['success' => true, 'message' => 'Tipo de produto excluído com sucesso.']);
        } else {
            echo json_encode(['success' => false, 'message' => $_SESSION['erro'] ?? 'Erro ao excluir.']);
            unset($_SESSION['erro']);
        }
        exit();
    }
}

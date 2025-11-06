<?php
// Controller/AcaoController.php

class AcaoController extends AppController
{
    private $acaoModel;

    public function __construct()
    {
        parent::__construct();
        $this->acaoModel = new AcaoModel();
    }

    /**
     * Lista todas as ações
     * Rota: /admin/acoes
     */
    public function index()
    {
        $acoes = $this->acaoModel->buscarTodas();

        $dados = [
            'acoes' => $acoes,
            'pode_editar' => true  
        ];

        $title = "Gestão de Ações de Produção";
        $content_view = ROOT_PATH . 'View' . DS . 'acao_lista.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Formulário de cadastro/edição
     * Rota: /admin/acoes/cadastro?id=X
     */
    public function cadastro()
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $acao = null;

        if ($id) {
            $acao = $this->acaoModel->buscarPorId($id);
            if (!$acao) {
                $_SESSION['erro'] = 'Ação não encontrada.';
                header('Location: /sgi_erp/admin/acoes');
                exit();
            }
        }

        $dados = [
            'acao' => $acao,
            'pode_editar' => true  
        ];

        $title = $id ? "Editar Ação" : "Nova Ação";
        $content_view = ROOT_PATH . 'View' . DS . 'acao_cadastro.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }
    /**
     * Salva (criar ou editar)
     * Rota: /admin/acoes/salvar (POST)
     */
    public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/admin/acoes');
            exit();
        }

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nome = trim($_POST['nome'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if (empty($nome)) {
            $_SESSION['erro'] = 'O nome da ação é obrigatório.';
            $redirect = '/sgi_erp/admin/acoes/cadastro';
            if ($id) $redirect .= "?id=$id";
            header("Location: $redirect");
            exit();
        }

        $dados_acao = [
            'id' => $id ?: null,
            'nome' => $nome,
            'ativo' => $ativo
        ];

        if ($this->acaoModel->salvar($dados_acao)) {
            $_SESSION['sucesso'] = "Ação **{$nome}** salva com sucesso!";
        } else {
            $_SESSION['erro'] = $_SESSION['erro'] ?? 'Erro ao salvar a ação.';
        }

        header('Location: /sgi_erp/admin/acoes');
        exit();
    }

    /**
     * Exclusão (opcional - adicione botão na view se quiser)
     */
    public function excluir()
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id || !$this->acaoModel->excluir($id)) {
            $_SESSION['erro'] = 'Erro ao excluir a ação.';
        } else {
            $_SESSION['sucesso'] = 'Ação excluída com sucesso.';
        }
        header('Location: /sgi_erp/admin/acoes');
        exit();
    }
}

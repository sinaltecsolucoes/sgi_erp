<?php
// Controller/AcaoController.php

class AcaoController extends AppController
{
    private $acaoModel;

    public function __construct()
    {
        parent::__construct();
        // Assume que a classe AcaoModel está disponível via Autoload
        $this->acaoModel = new AcaoModel();
    }

    /**
     * Exibe a lista de todas as ações (CRUD - Leitura/Read).
     * Rota: /admin/acoes
     * Permitido: Admin
     */
    public function index()
    {
        // NOVO: Chamamos o Model para buscar os dados
        $acoes = $this->acaoModel->buscarTodas();

        $dados = [
            'acoes' => $acoes
        ];

        $title = "Gestão de Ações de Produção";

        $content_view = ROOT_PATH . 'View' . DS . 'acao_lista.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Exibe o formulário de cadastro ou edição de ação.
     * Rota: /admin/acoes/cadastro?id={id}
     * Permitido: Admin
     */
    public function cadastro()
    {
        // O AcaoModel precisa de um método 'buscarPorId' para este passo.
        // Vamos assumir que você irá adicioná-lo ao AcaoModel.php.

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $acao = null;

        if ($id) {
            // Assumimos que o método buscarPorId foi implementado no AcaoModel
            // $acao = $this->acaoModel->buscarPorId($id); 

            // Simulação para o MVP (se o buscarPorId ainda não existe)
            // Se buscarPorId for implementado, retire a simulação abaixo.
            if ($id == 1) $acao = (object)['id' => 1, 'nome' => 'Descascar', 'ativo' => true];

            if (!$acao && $id != 1) { // Lógica real de não encontrado
                $_SESSION['erro'] = 'Ação não encontrada.';
                header('Location: /sgi_erp/admin/acoes');
                exit();
            }
        }

        $dados = [
            'acao' => $acao
        ];

        $title = ($id ? "Editar" : "Cadastrar") . " Ação";
        $content_view = ROOT_PATH . 'View' . DS . 'acao_cadastro.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Processa a criação ou edição de uma ação (CRUD - Create/Update).
     * Rota: /admin/acoes/salvar (via POST)
     * Permitido: Admin
     */
    public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/admin/acoes');
            exit();
        }

        // 1. Coleta e Sanitiza os dados
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
        $ativo = filter_input(INPUT_POST, 'ativo', FILTER_SANITIZE_NUMBER_INT) === '1';

        if (empty($nome)) {
            $_SESSION['erro'] = 'O nome da Ação é obrigatório.';
            header('Location: /sgi_erp/admin/acoes/cadastro' . ($id ? '?id=' . $id : ''));
            exit();
        }

        $dados_acao = [
            'id' => $id,
            'nome' => $nome,
            'ativo' => $ativo
        ];

        // 2. Salva a Ação (Assumindo que você adicionará o método 'salvar' ao AcaoModel)
        // if ($this->acaoModel->salvar($dados_acao)) { 

        // Simulação de Salvamento
        if (true) { // Substituir 'true' pela chamada real ao Model
            $_SESSION['sucesso'] = "Ação **{$nome}** salva com sucesso!";
        } else {
            $_SESSION['erro'] = $_SESSION['erro'] ?? 'Erro interno ao salvar o registro da ação.';
        }

        header('Location: /sgi_erp/admin/acoes');
        exit();
    }
}
    
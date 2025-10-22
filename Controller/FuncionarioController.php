<?php
// Controller/FuncionarioController.php

class FuncionarioController extends AppController
{
    private $funcionarioModel;

    public function __construct()
    {
        parent::__construct();
        $this->funcionarioModel = new FuncionarioModel();
    }

    /**
     * Exibe a lista de todos os funcionários (CRUD - Leitura/Read).
     * Rota: /admin/funcionarios
     * Permitido: Admin (checa no ACL)
     */
    public function index()
    {
        $funcionarios = $this->funcionarioModel->buscarTodos();

        $dados = [
            'funcionarios' => $funcionarios
        ];

        $title = "Gestão de Funcionários";

        $content_view = ROOT_PATH . 'View' . DS . 'funcionario_lista.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Exibe o formulário de cadastro ou edição de funcionário.
     * Rota: /admin/funcionarios/cadastro?id={id}
     * Permitido: Admin (checa no ACL)
     */
    public function cadastro()
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $funcionario = null;

        if ($id) {
            $funcionario = $this->funcionarioModel->buscarPorId($id);
            if (!$funcionario) {
                $_SESSION['erro'] = 'Funcionário não encontrado.';
                header('Location: /sgi_erp/admin/funcionarios');
                exit();
            }
        }

        $dados = [
            'funcionario' => $funcionario
        ];

        $title = ($id ? "Editar" : "Cadastrar") . " Funcionário";
        $content_view = ROOT_PATH . 'View' . DS . 'funcionario_cadastro.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Processa a criação ou edição de um funcionário (CRUD - Create/Update).
     * Rota: /admin/funcionarios/salvar (via POST)
     * Permitido: Admin (checa no ACL)
     */
    public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/admin/funcionarios');
            exit();
        }

        // 1. Coleta e Sanitiza os dados
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
        $cpf_mascarado = trim(filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_STRING)); // Captura o CPF

        // Limpeza da Máscara para salvar APENAS números no banco
        $cpf = preg_replace('/[^0-9]/', '', $cpf_mascarado);

        $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING); // admin, apontador, producao, financeiro
        $ativo = filter_input(INPUT_POST, 'ativo', FILTER_SANITIZE_NUMBER_INT) === '1';
        $login = trim(filter_input(INPUT_POST, 'login', FILTER_SANITIZE_STRING));
        $senha = $_POST['senha'] ?? null; // Senha pode estar vazia (apenas se for edição e não mudar)

        if (empty($nome) || empty($tipo) || strlen($cpf) !== 11) {
            $_SESSION['erro'] = 'Nome, Tipo e CPF válido (11 dígitos) são obrigatórios.';
            header('Location: /sgi_erp/admin/funcionarios/cadastro' . ($id ? '?id=' . $id : ''));
            exit();
        }

        $dados_funcionario = [
            'id' => $id,
            'nome' => $nome,
            'cpf' => $cpf,
            'tipo' => $tipo,
            'ativo' => $ativo
        ];

        // 2. Salva o Funcionário (INSERT/UPDATE)
        $funcionario_id = $this->funcionarioModel->salvar($dados_funcionario);

        if ($funcionario_id) {
            // 3. Salva ou Atualiza o Usuário/Login
            if ($this->funcionarioModel->criarOuAtualizarUsuario($funcionario_id, $login, $senha)) {
                $_SESSION['sucesso'] = "Funcionário **{$nome}** e login salvos com sucesso!";
            } else {
                // A mensagem de erro de login duplicado já é setada dentro do Model
                $_SESSION['erro'] = $_SESSION['erro'] ?? 'Erro desconhecido ao salvar o login.';
            }
        } else {
            $_SESSION['erro'] = 'Erro interno ao salvar o registro do funcionário.';
        }

        header('Location: /sgi_erp/admin/funcionarios');
        exit();
    }

    // Método de exclusão (Excluir) - (Omissão do código complexo de DELETE CASCADE por simplificidade do MVP)
    // O ideal seria apenas INATIVAR (ativo=0) e não deletar.
}

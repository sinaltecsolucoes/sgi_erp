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
    /* public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/admin/funcionarios');
            exit();
        }

        // 1. Coleta e Sanitiza os dados
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nome = sanitize($_POST['nome'] ?? '');
        $nome = mb_strtoupper($nome, 'UTF-8'); // Aplica a conversão para maiúsculas
        $tipo_documento = sanitize($_POST['tipo_documento'] ?? 'cpf');
        //$cpf_mascarado = onlyNumbers($_POST['cpf'] ?? ''); // Captura o CPF
        $cpf_mascarado = $tipo_documento === 'cpf' ? ($_POST['cpf'] ?? '') : '';
        $rg = $tipo_documento === 'rg' ? sanitize($_POST['rg'] ?? '') : '';

        // Limpeza da Máscara para salvar APENAS números no banco
        //$cpf = onlyNumbers($cpf_mascarado);
        $cpf = $tipo_documento === 'cpf' ? onlyNumbers($cpf_mascarado) : null;

        $tipo = sanitize($_POST['tipo'] ?? ''); // admin, apontador, producao, financeiro
        $ativo = filter_input(INPUT_POST, 'ativo', FILTER_SANITIZE_NUMBER_INT) === '1';
        $login = sanitize($_POST['login'] ?? '');
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
        $resultado_salvar = $this->funcionarioModel->salvar($dados_funcionario);

        if ($resultado_salvar === 'CPF_DUPLICADO') {
            // FLUXO 1: CPF Duplicado (Mensagem de Confirmação)
            $funcionario_existente = $this->funcionarioModel->buscarPorCpf($cpf);

            if ($funcionario_existente) {
                $id_existente = $funcionario_existente->id;
                $nome_existente = $funcionario_existente->nome;

                // Setamos uma sessão especial para a mensagem de confirmação
                $_SESSION['confirm_action'] = [
                    'message' => "O CPF **{$cpf_mascarado}** já está cadastrado para o funcionário **{$nome_existente}**. Deseja editar o cadastro existente?",
                    'confirm_url' => "/sgi_erp/admin/funcionarios/cadastro?id={$id_existente}",
                ];
            } else {
                $_SESSION['erro'] = 'Erro: O CPF já está cadastrado, mas não conseguimos localizar o registro. Verifique o banco de dados.';
            }
            header('Location: /sgi_erp/admin/funcionarios');
            exit();
        } elseif ($resultado_salvar) {
            // FLUXO 2: SUCESSO na criação/edição do funcionário
            $funcionario_id = $resultado_salvar;
            // 3. Salva ou Atualiza o Usuário/Login
            if (!empty($login) || !empty($senha)) {
                if ($this->funcionarioModel->criarOuAtualizarUsuario($funcionario_id, $login, $senha)) {
                    $_SESSION['sucesso'] = "Funcionário **{$nome}** e login salvos com sucesso!";
                } else {
                    // Se falhar (ex: login duplicado), usa a mensagem de erro do Model
                    //  $_SESSION['erro'] = $_SESSION['erro'] ?? 'Erro desconhecido ao salvar o login.';
                    if (!isset($_SESSION['erro'])) {
                        $_SESSION['erro'] = 'Funcionário cadastrado, mas o login falhou por motivo desconhecido.';
                    }
                }
            } else {
                // Se NÃO FORNECER login/senha, apenas o funcionário é salvo
                $_SESSION['sucesso'] = "Funcionário **{$nome}** salvo com sucesso!";
            }
        } else {
            // TRATAMENTO DE ERRO GENÉRICO NO SALVAR
            // Se $resultado_salvar é FALSE (outros erros do PDO ou falha no execute)
            // O Model já deve ter definido $_SESSION['erro']. Se não, definimos um genérico.
            if (!isset($_SESSION['erro'])) {
                $_SESSION['erro'] = 'Falha desconhecida ao salvar o cadastro do funcionário.';
            }
        }
        header('Location: /sgi_erp/admin/funcionarios');
        exit();
    } */

    public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/admin/funcionarios');
            exit();
        }

        // 1. Coleta e Sanitiza
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nome = sanitize($_POST['nome'] ?? '');
        $nome = mb_strtoupper($nome, 'UTF-8');
        $tipo_documento = sanitize($_POST['tipo_documento'] ?? 'cpf'); // 'cpf' ou 'rg'

        $cpf_mascarado = $tipo_documento === 'cpf' ? ($_POST['cpf'] ?? '') : '';
        $rg = $tipo_documento === 'rg' ? sanitize($_POST['rg'] ?? '') : '';

        $cpf = $tipo_documento === 'cpf' ? onlyNumbers($cpf_mascarado) : null;

        $tipo = sanitize($_POST['tipo'] ?? '');
        $ativo = filter_input(INPUT_POST, 'ativo', FILTER_SANITIZE_NUMBER_INT) === '1';
        $login = sanitize($_POST['login'] ?? '');
        $senha = $_POST['senha'] ?? null;

        // === VALIDAÇÃO DINÂMICA POR TIPO DE DOCUMENTO ===
        if (empty($nome) || empty($tipo)) {
            $_SESSION['erro'] = 'Nome e Tipo são obrigatórios.';
            $this->redirectCadastro($id);
        }

        if ($tipo_documento === 'cpf') {
            if (strlen($cpf) !== 11) {
                $_SESSION['erro'] = 'CPF deve ter 11 dígitos.';
                $this->redirectCadastro($id);
            }
        } elseif ($tipo_documento === 'rg') {
            if (empty($rg)) {
                $_SESSION['erro'] = 'RG é obrigatório quando selecionado.';
                $this->redirectCadastro($id);
            }
            // Opcional: limite de tamanho
            if (strlen($rg) > 20) {
                $_SESSION['erro'] = 'RG deve ter no máximo 20 caracteres.';
                $this->redirectCadastro($id);
            }
        }

        // === MONTA DADOS PARA O MODEL ===
        $dados_funcionario = [
            'id' => $id,
            'nome' => $nome,
            'cpf' => $cpf,
            'rg' => $rg,
            'tipo' => $tipo,
            'ativo' => $ativo,
            'tipo_documento' => $tipo_documento // necessário no Model
        ];

        // === SALVA FUNCIONÁRIO ===
        $resultado_salvar = $this->funcionarioModel->salvar($dados_funcionario);

        // === TRATAMENTO DE DUPLICIDADE (CPF OU RG) ===
        if ($resultado_salvar === 'CPF_DUPLICADO') {
            $this->tratarDuplicidade('cpf', $cpf_mascarado, $cpf);
        } elseif ($resultado_salvar === 'RG_DUPLICADO') {
            $this->tratarDuplicidade('rg', $rg, $rg);
        } elseif ($resultado_salvar !== false && $resultado_salvar > 0) {
            // === SUCESSO ===
            $funcionario_id = $resultado_salvar;

            if (!empty($login) || !empty($senha)) {
                if ($this->funcionarioModel->criarOuAtualizarUsuario($funcionario_id, $login, $senha)) {
                    $_SESSION['sucesso'] = "Funcionário **{$nome}** e login salvos com sucesso!";
                } else {
                    if (!isset($_SESSION['erro'])) {
                        $_SESSION['erro'] = 'Funcionário salvo, mas falha ao criar login.';
                    }
                }
            } else {
                $_SESSION['sucesso'] = "Funcionário **{$nome}** salvo com sucesso!";
            }
        } else {
            // Erro genérico
            if (!isset($_SESSION['erro'])) {
                $_SESSION['erro'] = 'Falha ao salvar funcionário.';
            }
        }

        header('Location: /sgi_erp/admin/funcionarios');
        exit();
    }

    // === MÉTODOS AUXILIARES (adicione no final da classe) ===

    private function redirectCadastro($id = null)
    {
        header('Location: /sgi_erp/admin/funcionarios/cadastro' . ($id ? "?id={$id}" : ''));
        exit();
    }

    private function tratarDuplicidade($tipo, $valor_mascarado, $valor_limpo)
    {
        $buscarMetodo = $tipo === 'cpf' ? 'buscarPorCpf' : 'buscarPorRg';
        $funcionario_existente = $this->funcionarioModel->$buscarMetodo($valor_limpo);

        if ($funcionario_existente) {
            $documento_formatado = $tipo === 'cpf'
                ? mask($valor_limpo, '###.###.###-##')
                : $valor_mascarado;

            $_SESSION['confirm_action'] = [
                'message' => "O " . strtoupper($tipo) . " **{$documento_formatado}** já está cadastrado para **{$funcionario_existente->nome}**. Deseja editar?",
                'confirm_url' => "/sgi_erp/admin/funcionarios/cadastro?id={$funcionario_existente->id}"
            ];
        } else {
            $_SESSION['erro'] = "Erro: O " . strtoupper($tipo) . " já existe, mas não foi encontrado.";
        }
    }
}

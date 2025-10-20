<?php
// Controller/LoginController.php

class LoginController
{
    private $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
    }

    /**
     * Exibe o formulário de login (rota: /)
     */
    public function index()
    {
        // Se o usuário já estiver logado, redireciona para o dashboard
        if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
            header('Location: /sgi_erp/dashboard');
            exit();
        }

        // Inclui a View do formulário
        require_once 'View/login.php';
    }

    /**
     * Processa a tentativa de login (rota: /login)
     */
    public function logar()
    {
        // Verifica se os dados vieram via POST (segurança básica)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/');
            exit();
        }

        $login = isset($_POST['login']) ? trim($_POST['login']) : '';
        $senha = isset($_POST['senha']) ? $_POST['senha'] : '';

        if (empty($login) || empty($senha)) {
            $_SESSION['erro_login'] = 'Por favor, preencha todos os campos.';
            header('Location: /sgi_erp/');
            exit();
        }

        // 1. Tenta logar usando o Model
        $usuario = $this->usuarioModel->logar($login, $senha);

        if ($usuario) {
            // 2. Login de Sucesso! Cria a sessão
            $_SESSION['logado'] = true;
            $_SESSION['funcionario_id'] = $usuario->funcionario_id;
            $_SESSION['funcionario_nome'] = $usuario->funcionario_nome;
            $_SESSION['funcionario_tipo'] = $usuario->funcionario_tipo; // 'apontador' ou 'producao'

            // Remove qualquer mensagem de erro anterior
            unset($_SESSION['erro_login']);

            // 3. Redireciona para o dashboard
            header('Location: /sgi_erp/dashboard');
            exit();
        } else {
            // 4. Falha no Login
            $_SESSION['erro_login'] = 'Login ou Senha inválidos.';
            header('Location: /sgi_erp/');
            exit();
        }
    }

    /**
     * Finaliza a sessão (rota: /logout)
     */
    public function sair()
    {
        // Destroi a sessão atual
        session_destroy();

        // Redireciona para o login
        header('Location: /sgi_erp/');
        exit();
    }
}

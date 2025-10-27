<?php
// Controller/PerfilController.php

class PerfilController extends AppController
{

    // Poderíamos injetar FuncionarioModel aqui

    public function __construct()
    {
        parent::__construct();
        // Acesso liberado para todos os usuários logados pelo AppController.
    }

    /**
     * Exibe o formulário de visualização/edição do perfil.
     * Rota: /meu-perfil
     */
    public function index()
    {
        $funcionario_id = $_SESSION['funcionario_id'];

        // Exemplo: Buscar dados do funcionário, incluindo a foto (se tiver um campo na tabela funcionarios)
        $funcionarioModel = new FuncionarioModel();
        $perfil = $funcionarioModel->buscarPorId($funcionario_id);

        $dados = [
            'perfil' => $perfil
            // Poderia adicionar o caminho da foto aqui
        ];

        $title = "Meu Perfil";
        $content_view = ROOT_PATH . 'View' . DIRECTORY_SEPARATOR . 'perfil.php';

        require_once ROOT_PATH . 'View' . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'main.php';
    }

    /**
     * Processa a alteração de senha a partir da tela de perfil.
     * Rota: /meu-perfil/salvar-senha
     */
    public function salvarSenha()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/meu-perfil');
            exit();
        }

        $nova_senha = $_POST['nova_senha'] ?? null;
        $funcionario_id = $_SESSION['funcionario_id'] ?? null;

        // 1. Validação
        if (empty($funcionario_id)) {
            $_SESSION['erro'] = 'Sessão de usuário não encontrada.';
        } elseif (empty($nova_senha) || strlen($nova_senha) < 6) {
            $_SESSION['erro'] = 'A nova senha deve ter no mínimo 6 caracteres.';
        } else {
            // 2. Chamar o Model para salvar a senha
            $usuarioModel = new UsuarioModel();

            if ($usuarioModel->atualizarSenha($funcionario_id, $nova_senha)) {
                $_SESSION['sucesso'] = 'Senha alterada com sucesso!';
            } else {
                $_SESSION['erro'] = 'Erro ao tentar atualizar a senha.';
            }
        }

        header('Location: /sgi_erp/meu-perfil');
        exit();
    }
}
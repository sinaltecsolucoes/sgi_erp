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

    // Futuro: public function salvarFoto()
    // Futuro: public function salvarDados()
}

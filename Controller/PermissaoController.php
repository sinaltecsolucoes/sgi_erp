<?php
// Controller/PermissaoController.php

class PermissaoController extends AppController
{
    private $permissaoModel;

    public function __construct()
    {
        parent::__construct();

        $this->permissaoModel = new PermissaoModel();

        // A checagem de ACL no index.php já deve proteger esta rota,
        // mas reforçamos que apenas o ADMIN deve gerenciar.
        if ($_SESSION['funcionario_tipo'] !== 'admin') {
            $_SESSION['erro'] = 'Acesso Negado. Apenas o Administrador pode gerenciar permissões.';
            header('Location: /sgi_erp/dashboard');
            exit();
        }
    }

    /**
     * Exibe a matriz de gestão de permissões.
     * Rota: /permissoes/gestao
     */
    public function index()
    {
        $perfis_disponiveis = ['apontador', 'financeiro', 'porteiro', 'producao'];
        $catalogo_acoes = Acl::getCatalogo(); // Obtém a lista completa de ações

        $permissoes_atuais = [];

        // Buscar o estado atual de todas as permissões no banco
        foreach ($perfis_disponiveis as $perfil) {
            $permissoes_atuais[$perfil] = $this->permissaoModel->buscarPermissoesPorPerfil($perfil);
        }

        $dados = [
            'perfis_disponiveis' => $perfis_disponiveis,
            'catalogo_acoes' => $catalogo_acoes,
            'permissoes_atuais' => $permissoes_atuais,
        ];

        $title = "Gestão de Permissões";
        $content_view = ROOT_PATH . 'View' . DS . 'permissao_gestao.php';
        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Salva as permissões enviadas pela matriz.
     * Rota: /permissoes/salvar
     */
    public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['permissoes'])) {
            // Usa o SweetAlert2 para feedback
            $_SESSION['erro'] = 'Dados inválidos ou método incorreto.';
            header('Location: /sgi_erp/permissoes/gestao');
            exit();
        }

        $permissoes_enviadas = $_POST['permissoes']; // Formato: ['perfil' => ['acao1', 'acao2', ...]]
        $perfis_a_gerenciar = ['apontador', 'financeiro', 'porteiro', 'producao']; // Perfis que podemos alterar
        $catalogo_acoes = Acl::getCatalogo();

        $erros = 0;

        foreach ($perfis_a_gerenciar as $perfil) {
            // Itera sobre TODAS as ações possíveis no catálogo
            foreach ($catalogo_acoes as $acao_chave => $descricao) {

                // 1. Verifica se esta ação@metodo estava marcada no formulário para este perfil
                $is_checked = isset($permissoes_enviadas[$perfil]) && in_array($acao_chave, $permissoes_enviadas[$perfil]);

                // 2. Salva o estado no Model (TRUE se marcado, FALSE se não)
                if (!$this->permissaoModel->salvarPermissao($perfil, $acao_chave, $is_checked)) {
                    $erros++;
                }
            }
        }

        if ($erros === 0) {
            $_SESSION['sucesso'] = 'Permissões salvas com sucesso para todos os perfis.';
        } else {
            $_SESSION['erro'] = "Permissões salvas, mas $erros erros ocorreram ao atualizar o banco.";
        }

        header('Location: /sgi_erp/permissoes/gestao');
        exit();
    }
}

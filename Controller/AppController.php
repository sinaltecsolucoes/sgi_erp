<?php
// Controller/AppController.php
// Controller Base para as páginas da aplicação após o login

class AppController
{

    public function __construct()
    {
        // Regra de Ouro: Proteger as rotas!
        // Verifica se a sessão 'logado' existe e é verdadeira
        if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
            // Se não estiver logado, redireciona para o login (sgi_erp é o nome do projeto web)
            header('Location: /sgi_erp/');
            exit();
        }

        // Futuramente, aqui podemos adicionar checagens de permissão
        // Ex: if ($_SESSION['funcionario_tipo'] !== 'apontador') { ... redireciona }
    }

    /**
     * Exibe a página principal da aplicação (Página Inicial)
     * Rota: /dashboard
     */
    public function index()
    {
        // NOVO: Instanciar Models necessários
        $funcionarioModel = new FuncionarioModel();
        $producaoModel = new ProducaoModel();

        // 1. Buscar dados reais
        $totalPresentes = $funcionarioModel->contarPresentesHoje();
        $producaoTotal = $producaoModel->somarProducaoHoje();

        // 2. Definir as variáveis para a View
        $dados = [
            'totalPresentes' => $totalPresentes,
            'producaoTotal' => $producaoTotal,
            // Outros dados futuros viriam aqui
        ];

        // Define as variáveis que serão usadas pelo template/main.php
        $title = "Dashboard";
        $content_view = ROOT_PATH . 'View' . DS . 'dashboard.php';

        // Inclui o layout principal
        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }
}

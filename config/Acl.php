<?php // config/Acl.php 
// Define o Catálogo de Ações (Controller@Metodo) e a lógica de checagem. 
class Acl
{ // Lista completa de todas as ações/métodos (Rotas que precisam de checagem) 
    private static $catalogo_acoes = [  
        // Rotas Principais (Dashboard) 
        'AppController@index' => 'Visualizar Dashboard',
        'MeuPainelController@index' => 'Visualizar Painel Pessoal',

        // MÓDULO DE FLUXO DE PRODUÇÃO (Apontador, Porteiro) 
        'PresencaController@index' => 'Visualizar Chamada',
        'PresencaController@salvar' => 'Registrar Chamada',

        'EquipeController@index' => 'Visualizar Montagem de Equipes',
        'EquipeController@salvar' => 'Salvar Edição de Equipes',
        'EquipeController@salvarNova' => 'Criar Nova Equipe',
        'EquipeController@mover' => 'Mover Membro entre Equipes',
        'EquipeController@excluir' => 'Excluir Equipe',

        'ProducaoController@index' => 'Visualizar Lançamento de Produção',
        'ProducaoController@salvar' => 'Salvar Lançamento de Produção',

        // Rotas de Lançamento em Massa 
        'ProducaoController@massa' => 'Visualizar Lançamento em Massa',
        'ProducaoController@salvarMassa' => 'Salvar Lançamento em Massa',
        'ProducaoController@editarDia' => 'Editar Produção do Dia',

        // MÓDULO ADMINISTRATIVO (Gestão de Permissões e Cadastros) 
        'PermissaoController@index' => 'Visualizar Gestão de Permissões', // Tela Admin 
        'PermissaoController@salvar' => 'Salvar Permissões de Perfis', // Ação Admin 
        'FuncionarioController@index' => 'Visualizar Lista de Funcionários',
        'FuncionarioController@cadastro' => 'Visualizar Formulário de Cadastro',
        'FuncionarioController@salvar' => 'Criar/Editar Funcionário',
        'FuncionarioController@excluir' => 'Excluir Funcionário',

        // MÓDULO DE CADASTRO DE AÇÕES
        'AcaoController@index' => 'Visualizar Lista de Ações',
        'AcaoController@cadastro' => 'Visualizar Formulário de Ação',
        'AcaoController@salvar' => 'Criar/Editar Ação',

        // Rotas de Cadastro de Tipos de Produto
        'TipoProdutoController@index' => 'Visualizar Tipos de Produto',
        'TipoProdutoController@cadastro' => 'Visualizar Cadastro Tipo Prod.',
        'TipoProdutoController@salvar' => 'Salvar Tipo de Produto',
        'TipoProdutoController@excluir' => 'Excluir Tipo de Produto',
        
        // MÓDULO FINANCEIRO (Relatórios) 
        'RelatorioController@pagamentos' => 'Visualizar Relatório de Pagamentos',
        'RelatorioController@quantidades' => 'Visualizar Relatório de Quantidades',
        'RelatorioController@servicos' => 'Visualizar Relatório de Serviços',
        'RelatorioController@produtividade' => 'Visualizar Produtividade/Hora',
        'RelatorioController@producaoGeral' => 'Visualizar Produção Geral',
        'RelatorioController@atualizarProducao' => 'Editar Lançamentos nos Relatórios',
        'RelatorioController@excluirProducao'   => 'Excluir Lançamentos nos Relatórios',

        // Rotas de Cadastro de Valores
        'ValoresPagamentoController@index' => 'Visualizar Valores Pagamento',
        'ValoresPagamentoController@cadastro' => 'Visualizar Cadastro Valor',
        'ValoresPagamentoController@salvar' => 'Salvar Valor Pagamento',
        'ValoresPagamentoController@excluir' => 'Excluir Valor Pagamento',
    ];

    // Model de Permissão será usado na checagem 

    private static $permissaoModel = null;
    /** * Retorna o catálogo completo de ações e suas descrições. */
    public static function getCatalogo()
    {
        return self::$catalogo_acoes;
    }
    /**
     * 
     * Verifica se o usuário tem permissão para a Ação solicitada (Controller@Metodo). 
     * @param string $action A ação solicitada (ex: FuncionarioController@index). 
     * @param string $tipo O tipo do funcionário logado (ex: apontador). 
     * @return bool TRUE se permitido, FALSE caso contrário.     * 
     */
    public static function check($action, $tipo)
    { // O ADMIN TEM ACESSO TOTAL SEMPRE! 
        if ($tipo === 'admin') {
            return true;
        } // Se a ação não está no nosso catálogo, é uma rota que não precisa de checagem (ou está mal configurada), então bloqueamos. 
        if (!isset(self::$catalogo_acoes[$action])) {
            return false;
        }
        // 1. Inicializa o Model se necessário 
        if (self::$permissaoModel === null) { // O autoloader garante que o Model está carregado 
            self::$permissaoModel = new PermissaoModel();
        }
        // 2. Checa o banco de dados
        return self::$permissaoModel->checarPermissao($tipo, $action);
    }
}

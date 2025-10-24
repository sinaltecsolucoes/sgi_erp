<?php
// bootstrap.php
// Arquivo Central de Inicialização do Sistema (Autoload, Sessão, Rotas)

// 1. INICIALIZAÇÃO DA SESSÃO
// Deve ser o primeiro comando PHP em qualquer página que use sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. CAMINHO RAIZ DO PROJETO
// __DIR__ aponta para a pasta onde o bootstrap.php está 
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);

// 2. AUTOLOAD DE CLASSES
// Define os diretórios onde as classes (Model, Controller, Config) serão encontradas
$directories = [
    'config/',
    'Controller/',
    'Model/',
];

spl_autoload_register(function ($className) use ($directories) {
    $file = $className . '.php';

    foreach ($directories as $directory) {
        $path = $directory . $file;

        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});


// 3. MAPA DE ROTAS (Movido do routes.php)
// Mapeamento de URL para Controller@Metodo
// O padrão é: 'caminho/url' => 'NomeDoController@nomeDoMetodo'
$routes = [
    // Rotas de Autenticação (Login)
    '/'          => 'LoginController@index',     // Página inicial (Formulário de Login)
    '/login'     => 'LoginController@logar',     // Processa o envio do formulário (POST)
    '/logout'    => 'LoginController@sair',      // Finaliza a sessão

    // MÓDULO DE PERFIL
    '/meu-perfil' => 'PerfilController@index', // Visualização e Edição do perfil

    // Rota para Pagina Principal
    '/dashboard' => 'AppController@index',       // Página principal da aplicação

    // Rotas da Aplicação (Após o Login)
    '/presenca'  => 'PresencaController@index',  // Chamada de Presença (GET para exibir)
    '/presenca/salvar' => 'PresencaController@salvar', // Chamada de Presença (POST para salvar)

    // Rotas de Equipes
    '/equipes'  => 'EquipeController@index',
    '/equipes/salvar' => 'EquipeController@salvar',

    // Rotas de Produção
    '/producao'  => 'ProducaoController@index',
    '/producao/salvar' => 'ProducaoController@salvar',
    '/producao/massa'  => 'ProducaoController@massa',
    '/producao/massa/salvar' => 'ProducaoController@salvarMassa',

    // Rotas API (Para o App Android)
    '/api' => 'ApiController@info',
    '/api/login' => 'ApiController@login',
    '/api/presenca' => 'ApiController@presenca',

    // MÓDULO DE GESTÃO DE PERMISSÕES
    '/permissoes/gestao' => 'PermissaoController@index',
    '/permissoes/salvar' => 'PermissaoController@salvar',

    // MÓDULO DE CADASTRO DE FUNCIONÁRIOS
    '/admin/funcionarios' => 'FuncionarioController@index', // Listagem
    '/admin/funcionarios/cadastro' => 'FuncionarioController@cadastro', // Formulário (GET)
    '/admin/funcionarios/salvar' => 'FuncionarioController@salvar', // Salvar (POST)

    // MÓDULO DE VALORES DE PAGAMENTO
    '/admin/valores-pagamento' => 'ValoresPagamentoController@index', // Listagem
    '/admin/valores-pagamento/cadastro' => 'ValoresPagamentoController@cadastro', // Formulário (GET)
    '/admin/valores-pagamento/salvar' => 'ValoresPagamentoController@salvar', // Salvar (POST)

    // MÓDULO DE CADASTRO DE TIPOS DE PRODUTO
    '/admin/tipos-produto' => 'TipoProdutoController@index', // Listagem
    '/admin/tipos-produto/cadastro' => 'TipoProdutoController@cadastro', // Formulário (GET)
    '/admin/tipos-produto/salvar' => 'TipoProdutoController@salvar', // Salvar (POST)

    // MÓDULO FINANCEIRO / RELATÓRIOS
    '/relatorios' => 'RelatorioController@pagamentos', // Pagamento Total (Rota principal de relatórios)
    '/relatorios/quantidades' => 'RelatorioController@quantidades', // Quantidades Produzidas
    '/relatorios/servicos' => 'RelatorioController@servicos', // Serviços/Diárias
    '/relatorios/produtividade' => 'RelatorioController@produtividade', // Produtividade/Hora
    '/relatorios/imprimir' => 'RelatorioController@imprimir', // Layout PDF
];

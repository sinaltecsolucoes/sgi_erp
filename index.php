<?php
// index.php
// Ponto de entrada único e gerenciador de rotas (Roteador)

// 1. Inicializa o Sistema (Autoload, Sessão e Rotas)
require_once 'bootstrap.php';
// A variável $routes já foi carregada no bootstrap.php

// 2. Obtém a URL solicitada
// Remove o nome da pasta do projeto e sanitiza (ex: /sgi_erp/login -> /login)
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/sgi_erp'; // Nome da pasta do projeto web
$url = str_replace($basePath, '', $requestUri);

// Remove query strings (?id=1) e garante que começa com '/'
$url = strtok($url, '?');
if (empty($url)) {
    $url = '/';
}

// Normaliza removendo barra final (exceto se for apenas "/")
if ($url !== '/' && substr($url, -1) === '/') {
    $url = rtrim($url, '/');
}

// Normaliza múltiplas barras consecutivas
$url = preg_replace('#/+#', '/', $url);

// 4. Procura a rota no mapa
if (array_key_exists($url, $routes)) {
    $target = $routes[$url]; // Ex: 'LoginController@index' 

    // Divide em Controller e Método 
    list($controllerName, $methodName) = explode('@', $target);

    // **CHECA ACL: Permissão de Ação (Controller@Metodo)** 
    $action = $controllerName . '@' . $methodName;


    // =============================================
    // 1. PRIMEIRO: SE NÃO ESTIVER LOGADO → LOGIN!
    // =============================================
    $rotas_publicas = [
        'LoginController@index',
        'LoginController@logar',
        'LoginController@sair'
    ];

    //$tipo_usuario = $_SESSION['funcionario_tipo'] ?? 'convidado';

    // Define se é uma rota de API
    $is_api_route = (strpos($controllerName, 'ApiController') === 0);

    // Se não for rota pública E não for API E não estiver logado → FORA!
    if (!in_array($action, $rotas_publicas) && !$is_api_route) {
        if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
            // Salva a URL que ele tentou acessar (para voltar depois do login)
            $_SESSION['url_intentada'] = $requestUri;

            header('Location: /sgi_erp/');
            exit();
        }
    }

    // =============================================
    // 2. SEGUNDO: Se estiver logado, verifica permissão (ACL)
    // =============================================
    if (!in_array($action, $rotas_publicas) && !$is_api_route) {
        $tipo_usuario = $_SESSION['funcionario_tipo'] ?? 'convidado';

        if (!Acl::check($action, $tipo_usuario)) {
            // Sem permissão → joga no dashboard (ou pode mostrar 403 se preferir)
            header('Location: /sgi_erp/dashboard');
            exit();
        }
    }


    // =============================================
    // 3. EXECUTA O CONTROLLER (só chega aqui se passou nas duas barreiras)
    // =============================================

    if (class_exists($controllerName)) {
        $controller = new $controllerName();
        if (method_exists($controller, $methodName)) {

            // Executa o método 
            $controller->$methodName();
        } else {
            http_response_code(500);
            echo "Erro: Método '$methodName' não encontrado no Controller '$controllerName'.";
        }
    } else {
        http_response_code(500);
        echo "Erro: Controller '$controllerName' não encontrado.";
    }
} else {
    // Rota não encontrada (404 Not Found)
    http_response_code(404);
    echo "<h1>404 - Página Não Encontrada</h1>";
    echo "<p>A rota <b>$url</b> não foi definida no sistema.</p>";
}

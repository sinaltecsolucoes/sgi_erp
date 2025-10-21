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


// 4. Procura a rota no mapa
if (array_key_exists($url, $routes)) {
    $target = $routes[$url]; // Ex: 'LoginController@index' 

    // Divide em Controller e Método 
    list($controllerName, $methodName) = explode('@', $target);

    // **CHECA ACL: Permissão de Ação (Controller@Metodo)** 
    $action = $controllerName . '@' . $methodName;
    $tipo_usuario = $_SESSION['funcionario_tipo'] ?? 'convidado';

    // Não checar ACL para rotas de Login (index, logar) e Logout
    if ($action !== 'LoginController@index' && $action !== 'LoginController@logar' && $action !== 'LoginController@sair') {
        if (!Acl::check($action, $tipo_usuario)) {

            // Se o usuário está logado, mas não tem permissão para esta ação/tela http_response_code(403); 
            // Proibido $_SESSION['erro'] = "Acesso Negado (403). Você não tem permissão para a ação **{$action}**."; 
            // Redireciona o usuário para o seu painel seguro 
            $redirect_url = '/sgi_erp/dashboard'; // Padrão 
            if ($tipo_usuario === 'admin') {

                // Redireciona admin para a sua área de gestão (que criaremos) 
                $redirect_url = '/sgi_erp/permissoes/gestao';
            } elseif ($tipo_usuario === 'financeiro') {
                $redirect_url = '/sgi_erp/relatorios';
            } elseif ($tipo_usuario === 'producao') {
                $redirect_url = '/sgi_erp/meu-painel';
            }
            header('Location: ' . $redirect_url);
            exit();
        }
    }

    // 5. Verifica e Executa o Controller (APENAS SE A PERMISSÃO FOI CONCEDIDA) 
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

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


// 3. Procura a rota no mapa
if (array_key_exists($url, $routes)) {
    $target = $routes[$url]; // Ex: 'LoginController@index'

    // Divide em Controller e Método
    list($controllerName, $methodName) = explode('@', $target);

    // 4. Verifica e Executa o Controller
    if (class_exists($controllerName)) {
        $controller = new $controllerName();

        if (method_exists($controller, $methodName)) {
            // Executa o método (a ação) do Controller
            // Passamos a variável $routes para que o controller possa usá-la (se necessário)
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

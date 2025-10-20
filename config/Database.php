<?php
// config/Database.php

class Database
{
    private $DB_HOST = 'localhost';
    private $DB_NAME = 'dbsgi'; // Nome do banco
    private $DB_USER = 'root';  // Usuário padrão
    private $DB_PASS = '';      // Senha padrão

    private $conn;
    private static $instance = null; // Para o padrão Singleton

    // Construtor privado para impedir instâncias diretas
    private function __construct() {}

    // Método estático para obter a única instância da classe e conexão
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Método para obter a conexão PDO
    public function connect()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->DB_HOST};dbname={$this->DB_NAME};charset=utf8";
            $this->conn = new PDO($dsn, $this->DB_USER, $this->DB_PASS);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Definir o modo de retorno padrão como OBJETO (facilita o acesso aos dados)
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            // Em ambiente de produção, isto deve ser escrito em logs, não na tela.
            echo 'Erro de Conexão com o Banco de Dados: ' . $e->getMessage();
            die();
        }

        return $this->conn;
    }
}

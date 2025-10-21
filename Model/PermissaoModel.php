<?php
// Model/PermissaoModel.php

class PermissaoModel
{
    private $db;
    private $table = 'permissoes';

    public function __construct()
    {
        $this->db = Database::getInstance()->connect();
    }

    /**
     * Checa o banco de dados para ver se um perfil tem acesso a uma ação.
     */
    public function checarPermissao($perfil, $acao)
    {
        $query = "SELECT permitido FROM {$this->table} WHERE perfil = :perfil AND acao = :acao LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':perfil', $perfil);
        $stmt->bindParam(':acao', $acao);
        $stmt->execute();

        $resultado = $stmt->fetch();

        // Retorna TRUE (1) se o registro existir e a coluna 'permitido' for 1, senão FALSE.
        return $resultado ? ($resultado->permitido == 1) : false;
    }

    /**
     * Busca todas as permissões registradas para um determinado perfil.
     * Retorna um array associativo [acao => permitido].
     */
    public function buscarPermissoesPorPerfil($perfil)
    {
        $query = "SELECT acao, permitido FROM {$this->table} WHERE perfil = :perfil";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':perfil', $perfil);
        $stmt->execute();

        $permissoes = [];
        foreach ($stmt->fetchAll() as $row) {
            $permissoes[$row->acao] = (bool)$row->permitido;
        }
        return $permissoes;
    }

    /**
     * Salva o estado de uma permissão (Cria ou Atualiza).
     */
    public function salvarPermissao($perfil, $acao, $permitido)
    {
        $query = "INSERT INTO {$this->table} (perfil, acao, permitido) 
                  VALUES (:perfil, :acao, :permitido)
                  ON DUPLICATE KEY UPDATE 
                  permitido = :permitido";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':perfil', $perfil);
            $stmt->bindParam(':acao', $acao);
            $stmt->bindParam(':permitido', $permitido, PDO::PARAM_BOOL);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Em caso de erro na consulta, logar ou tratar.
            return false;
        }
    }
}

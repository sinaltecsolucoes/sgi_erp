<?php
// Model/PresencaModel.php

class PresencaModel
{
  private $db;
  private $table_presencas = 'presencas';

  public function __construct()
  {
    $this->db = Database::getInstance()->connect();
  }

  /**
   * Registra ou atualiza a presença de um funcionário para uma data específica.
   * Usa ON DUPLICATE KEY UPDATE para evitar registros duplicados no mesmo dia.
   * @param int $funcionario_id ID do funcionário.
   * @param string $data Data no formato 'YYYY-MM-DD'.
   * @return bool TRUE em caso de sucesso, FALSE em caso de falha.
   */
  public function registrarPresenca($funcionario_id, $data)
  {
    $query = "INSERT INTO 
                    {$this->table_presencas} (funcionario_id, data, presente)
                  VALUES 
                    (:funcionario_id, :data, TRUE)
                  ON DUPLICATE KEY UPDATE 
                    presente = TRUE";

    try {
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':funcionario_id', $funcionario_id);
      $stmt->bindParam(':data', $data);
      return $stmt->execute();
    } catch (PDOException $e) {
      return false;
    }
  }

  /**
   * Remove ou marca a presença como falsa (ausente) para uma data específica.
   * @param int $funcionario_id ID do funcionário.
   * @param string $data Data no formato 'YYYY-MM-DD'.
   * @return bool TRUE em caso de sucesso, FALSE em caso de falha.
   */
  public function removerPresenca($funcionario_id, $data)
  {
    $query = "UPDATE 
                    {$this->table_presencas} 
                  SET 
                    presente = FALSE
                  WHERE 
                    funcionario_id = :funcionario_id AND data = :data";

    try {
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':funcionario_id', $funcionario_id);
      $stmt->bindParam(':data', $data);
      return $stmt->execute();
    } catch (PDOException $e) {
      return false;
    }
  }
}

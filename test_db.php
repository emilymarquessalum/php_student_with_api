<?php

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if($db) {
        echo "Conexão com o banco de dados estabelecida com sucesso!";
        
        // Aqui você pode adicionar suas consultas SQL e definições de tabela
        // Por exemplo:
        // $query = "CREATE TABLE IF NOT EXISTS users (...)";
        // $db->exec($query);
    }
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
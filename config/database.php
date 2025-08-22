<?php

class Database
{
    private $host = "localhost";
    private $db_name = "attendance_system";
    private $username = "postgres";
    private $password = "";
    private $port = "5432";
    public $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            // Primeiro tenta conectar ao banco de dados específico
            try {
                $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
                $this->conn = new PDO($dsn, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                // Se o banco não existe, conecta ao PostgreSQL e cria
                if (strpos($e->getMessage(), 'database "' . $this->db_name . '" does not exist') !== false) {
                    $pdo = new PDO(
                        "pgsql:host=" . $this->host . ";port=" . $this->port,
                        $this->username,
                        $this->password
                    );
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    // Criar o banco de dados
                    $pdo->exec("CREATE DATABASE " . $this->db_name);

                    // Conectar ao novo banco de dados
                    $this->conn = new PDO($dsn, $this->username, $this->password);
                    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    // Criar as tabelas
                    $this->createTables();
                } else {
                    throw $e;
                }
            }
        } catch (PDOException $e) {
            echo "Erro de conexão: " . $e->getMessage();
        }

        return $this->conn;
    }

    private function createTables()
    {
        try {
            // Criar tabela de usuários
            $this->conn->exec("CREATE TABLE IF NOT EXISTS users ( 
            )");


            echo "Tabelas criadas com sucesso!\n";
        } catch (PDOException $e) {
            echo "Erro ao criar tabelas: " . $e->getMessage();
        }
    }
}

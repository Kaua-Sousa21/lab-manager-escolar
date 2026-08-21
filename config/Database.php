<?php

declare(strict_types=1);

namespace Config;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        $config = self::configuration();

        $dsn = 'mysql:host=' . $config['host'] . ';port=3306;dbname=' . $config['name'] . ';charset=utf8mb4';

        try {
            $this->connection = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
        } catch (PDOException $e) {
            error_log('[LabManager] Erro MySQL: ' . $e->getMessage());
            throw new RuntimeException(
                'Não foi possível conectar ao MySQL. Confira somente o arquivo config/banco.php e confirme se o banco foi importado no phpMyAdmin.'
            );
        }
    }

    public static function configuration(): array
    {
        $file = __DIR__ . '/banco.php';
        if (!is_file($file)) {
            throw new RuntimeException('Arquivo config/banco.php não encontrado.');
        }

        $config = require $file;
        if (!is_array($config)) {
            throw new RuntimeException('O arquivo config/banco.php está inválido.');
        }

        $result = [
            'host' => trim((string)($config['host'] ?? '')),
            'name' => trim((string)($config['name'] ?? '')),
            'user' => trim((string)($config['user'] ?? '')),
            'pass' => (string)($config['pass'] ?? ''),
        ];

        foreach (['host', 'name', 'user'] as $key) {
            if ($result[$key] === '' || strpos($result[$key], 'COLE_AQUI_') !== false) {
                throw new RuntimeException('Preencha os dados do MySQL no arquivo config/banco.php.');
            }
        }

        if (strpos($result['pass'], 'COLE_AQUI_') !== false) {
            throw new RuntimeException('Preencha a senha do MySQL no arquivo config/banco.php.');
        }

        return $result;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}

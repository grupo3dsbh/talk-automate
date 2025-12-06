<?php
/**
 * Arquivo de Configuração
 * Carrega variáveis de ambiente e define configurações do sistema
 */

// Carrega variáveis de ambiente do arquivo .env
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignora comentários
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse da linha KEY=VALUE
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Remove aspas do valor se existirem
        if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
            $value = $matches[2];
        }

        // Define a variável de ambiente
        if (!getenv($name)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Carrega o arquivo .env
loadEnv(__DIR__ . '/../.env');

// Configurações do banco de dados
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'chatwoot_automation');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Configurações JWT
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'fallback-secret-key');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 86400); // 24 horas

// Configurações de IA
define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: '');
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY') ?: '');
define('DEFAULT_AI_PROVIDER', getenv('DEFAULT_AI_PROVIDER') ?: 'groq');

// Configurações da aplicação
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', APP_ENV === 'development');

// Configurações de erro
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// Trata requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

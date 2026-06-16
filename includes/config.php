<?php
// Configurações da Aplicação
define('APP_NAME', 'Inova Contábil — Painel Administrativo');

// Banco de dados
// Para produção com MySQL: altere DB_TYPE para 'mysql' e preencha as constantes abaixo
define('DB_TYPE',        'sqlite');
define('DB_SQLITE_PATH', dirname(__DIR__) . '/data/inovacontabil.db');
define('DB_HOST',        'localhost');
define('DB_NAME',        'inovacontabil');
define('DB_USER',        'root');
define('DB_PASS',        '');
define('DB_CHARSET',     'utf8mb4');

// API CND — configure o provedor e a chave em produção
// Provedores disponíveis: 'demo' | 'infosimples' | 'netrin'
define('CND_API_PROVIDER', 'demo');
define('CND_API_KEY',      '');

// Sessão
define('SESSION_TIMEOUT',  3600); // 1 hora em segundos
define('CSRF_TOKEN_NAME',  '_csrf');

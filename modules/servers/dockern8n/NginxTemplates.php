<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function dockern8n_GetNginxTemplate($type, $domain, $serviceId, $cpuLimit = 0, $memoryLimit = 0, $version = "latest", $username = '', $password = '', $letsencryptEmail = '', $timezone = "Asia/Dhaka", $existingCreds = array(), $userId = 0) {
    if (empty($letsencryptEmail)) {
        $letsencryptEmail = "mrwhdbd@gmail.com";
    }
    
    $domainHost = preg_replace("#^https?://#", '', $domain);
    $domainHost = rtrim($domainHost, "/");
    
    $credentials = array(
        "domain" => "https://" . $domainHost,
        "created_at" => date("Y-m-d H:i:s"),
        "proxy_type" => "nginx"
    );
    
    if (!function_exists("dockern8n_GeneratePassword")) {
        require_once __DIR__ . "/DomainHelpers.php";
    }
    
    $encryptionKey = $existingCreds["encryption_key"] ?? dockern8n_GeneratePassword(32);
    $n8nUser = $username ?: ($existingCreds["username"] ?? "admin");
    $n8nPass = $password ?: ($existingCreds["password"] ?? dockern8n_GeneratePassword());
    
    $credentials["username"] = $n8nUser;
    $credentials["password"] = $n8nPass;
    $credentials["encryption_key"] = $encryptionKey;
    $credentials["template"] = $type;
    $credentials["postgres_password"] = $existingCreds["postgres_password"] ?? dockern8n_GeneratePassword();
    
    $deployBlock = '';
    if ((!empty($cpuLimit) && $cpuLimit !== "0") || (!empty($memoryLimit) && $memoryLimit !== "0")) {
        $deployBlock = "\n    deploy:\n      resources:\n        limits:";
        if (!empty($cpuLimit) && $cpuLimit !== "0") {
            $deployBlock .= "\n          cpus: '{$cpuLimit}'";
        }
        if (!empty($memoryLimit) && $memoryLimit !== "0") {
            $deployBlock .= "\n          memory: '{$memoryLimit}'";
        }
    }
    
    $commonEnv = "      - 'N8N_HOST={$domainHost}'
      - 'N8N_EDITOR_BASE_URL=https://{$domainHost}'
      - 'WEBHOOK_URL=https://{$domainHost}'
      - 'GENERIC_TIMEZONE={$timezone}'
      - 'TZ={$timezone}'
      - 'N8N_PORT=5678'
      - 'N8N_LISTEN_ADDRESS=0.0.0.0'
      - 'N8N_ENFORCE_SETTINGS_FILE_PERMISSIONS=false'
      - 'N8N_ENCRYPTION_KEY={$encryptionKey}'
      - 'N8N_SECURE_COOKIE=true'
      - 'VIRTUAL_HOST={$domainHost}'
      - 'VIRTUAL_PORT=5678'
      - 'LETSENCRYPT_HOST={$domainHost}'
      - 'LETSENCRYPT_EMAIL={$letsencryptEmail}'";
    
    $prefix = "{$userId}-{$serviceId}";
    if ($userId > 0) {
        $puqContainer = strpos($domainHost, $prefix . ".") === 0 ? $domainHost : "{$prefix}.{$domainHost}";
    } else {
        $puqContainer = "service-{$serviceId}-n8n";
    }
    $postgresContainer = "{$puqContainer}-postgres";
    $redisContainer = "{$puqContainer}-redis";
    $workerContainer = "{$puqContainer}-worker";
    
    // PUQ Standard Mount Base
    $mountBase = "/mnt/{$puqContainer}";
    
    $postgresPassword = $credentials["postgres_password"];

    switch ($type) {
        case "n8n-sqlite":
            $dockerCompose = "services:
  n8n:
    image: 'docker.n8n.io/n8nio/n8n:{$version}'
    container_name: {$puqContainer}
    restart: unless-stopped{$deployBlock}
    environment:
{$commonEnv}
    expose:
      - '5678'
    networks:
      - nginx-proxy_web
    volumes:
      - '{$mountBase}/n8n:/home/node/.n8n'
    healthcheck:
      test: ['CMD-SHELL', 'wget -qO- http://127.0.0.1:5678/healthz || exit 1']
      interval: 10s
      timeout: 15s
      retries: 24
      start_period: 90s

networks:
  nginx-proxy_web:
    external: true";
            break;
            
        case "n8n":
        case "n8n-pgsql":
            $dockerCompose = "services:
  n8n:
    image: 'docker.n8n.io/n8nio/n8n:{$version}'
    container_name: {$puqContainer}
    restart: unless-stopped{$deployBlock}
    environment:
{$commonEnv}
      - 'DB_TYPE=postgresdb'
      - 'DB_POSTGRESDB_DATABASE=n8n'
      - 'DB_POSTGRESDB_HOST=postgresql'
      - 'DB_POSTGRESDB_PORT=5432'
      - 'DB_POSTGRESDB_USER=n8n'
      - 'DB_POSTGRESDB_SCHEMA=public'
      - 'DB_POSTGRESDB_PASSWORD={$postgresPassword}'
    expose:
      - '5678'
    networks:
      - default
      - nginx-proxy_web
    volumes:
      - '{$mountBase}/n8n:/home/node/.n8n'
    healthcheck:
      test: ['CMD-SHELL', 'wget -qO- http://127.0.0.1:5678/healthz || exit 1']
      interval: 10s
      timeout: 10s
      retries: 24
      start_period: 90s
    depends_on:
      postgresql:
        condition: service_healthy

  postgresql:
    image: 'postgres:16-alpine'
    container_name: {$postgresContainer}
    restart: unless-stopped
    volumes:
      - '{$mountBase}/postgres:/var/lib/postgresql/data'
    environment:
      - 'POSTGRES_USER=n8n'
      - 'POSTGRES_PASSWORD={$postgresPassword}'
      - 'POSTGRES_DB=n8n'
    healthcheck:
      test: ['CMD-SHELL', 'pg_isready -U n8n -d n8n || exit 1']
      interval: 5s
      timeout: 5s
      retries: 10
      start_period: 10s

networks:
  nginx-proxy_web:
    external: true";
            break;
            
        case "n8n-queue":
            $credentials["mode"] = "queue";
            $dockerCompose = "services:
  n8n:
    image: 'docker.n8n.io/n8nio/n8n:{$version}'
    container_name: {$puqContainer}
    restart: unless-stopped{$deployBlock}
    environment:
{$commonEnv}
      - 'DB_TYPE=postgresdb'
      - 'DB_POSTGRESDB_DATABASE=n8n'
      - 'DB_POSTGRESDB_HOST=postgresql'
      - 'DB_POSTGRESDB_PORT=5432'
      - 'DB_POSTGRESDB_USER=n8n'
      - 'DB_POSTGRESDB_SCHEMA=public'
      - 'DB_POSTGRESDB_PASSWORD={$postgresPassword}'
      - 'EXECUTIONS_MODE=queue'
      - 'QUEUE_BULL_REDIS_HOST=redis'
      - 'QUEUE_BULL_REDIS_PORT=6379'
      - 'QUEUE_HEALTH_CHECK_ACTIVE=true'
      - 'QUEUE_BULL_REDIS_DB=0'
    expose:
      - '5678'
    networks:
      - default
      - nginx-proxy_web
    volumes:
      - '{$mountBase}/n8n:/home/node/.n8n'
    healthcheck:
      test: ['CMD-SHELL', 'wget -qO- http://127.0.0.1:5678/healthz || exit 1']
      interval: 10s
      timeout: 15s
      retries: 30
      start_period: 120s
    depends_on:
      postgresql:
        condition: service_healthy
      redis:
        condition: service_healthy

  n8n-worker:
    image: 'docker.n8n.io/n8nio/n8n:{$version}'
    container_name: {$workerContainer}
    restart: unless-stopped
    environment:
{$commonEnv}
      - 'DB_TYPE=postgresdb'
      - 'DB_POSTGRESDB_DATABASE=n8n'
      - 'DB_POSTGRESDB_HOST=postgresql'
      - 'DB_POSTGRESDB_PORT=5432'
      - 'DB_POSTGRESDB_USER=n8n'
      - 'DB_POSTGRESDB_SCHEMA=public'
      - 'DB_POSTGRESDB_PASSWORD={$postgresPassword}'
      - 'EXECUTIONS_MODE=queue'
      - 'QUEUE_BULL_REDIS_HOST=redis'
      - 'QUEUE_BULL_REDIS_PORT=6379'
      - 'QUEUE_BULL_REDIS_DB=0'
    networks:
      - default
    volumes:
      - '{$mountBase}/n8n:/home/node/.n8n'
    depends_on:
      postgresql:
        condition: service_healthy
      redis:
        condition: service_healthy

  redis:
    image: 'redis:7-alpine'
    container_name: {$redisContainer}
    restart: unless-stopped
    volumes:
      - '{$mountBase}/redis:/data'
    healthcheck:
      test: ['CMD', 'redis-cli', 'ping']
      interval: 5s
      timeout: 5s
      retries: 10

  postgresql:
    image: 'postgres:16-alpine'
    container_name: {$postgresContainer}
    restart: unless-stopped
    volumes:
      - '{$mountBase}/postgres:/var/lib/postgresql/data'
    environment:
      - 'POSTGRES_USER=n8n'
      - 'POSTGRES_PASSWORD={$postgresPassword}'
      - 'POSTGRES_DB=n8n'
    healthcheck:
      test: ['CMD-SHELL', 'pg_isready -U n8n -d n8n || exit 1']
      interval: 5s
      timeout: 10s
      retries: 30
      start_period: 20s

networks:
  nginx-proxy_web:
    external: true";
            break;
            
        default:
            throw new Exception("Template type '{$type}' not supported. Use: n8n-sqlite, n8n-pgsql, or n8n-queue");
    }
    
    return array("docker_compose" => $dockerCompose, "credentials" => $credentials);
}
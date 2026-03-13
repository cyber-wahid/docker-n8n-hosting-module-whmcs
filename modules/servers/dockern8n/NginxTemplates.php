<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function dockern8n_GetNginxTemplate($type, $domain, $serviceId, $cpuLimit = 0, $memoryLimit = 0, $version = "latest", $username = '', $password = '', $letsencryptEmail = '', $timezone = "Asia/Dhaka", $existingCreds = array(), $userId = 0) {
    $domainHost = preg_replace("#^https?://#", '', $domain);
    $domainHost = rtrim($domainHost, "/");
    
    if (empty($letsencryptEmail)) {
        $letsencryptEmail = "mrwhdbd@gmail.com";
    }
    
    $credentials = array(
        "domain" => "https://" . $domainHost,
        "created_at" => date("Y-m-d H:i:s"),
        "proxy_type" => "nginx"
    );
    
    $dockerCompose = '';
    
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
    
    $commonEnv = "      - 'N8N_HOST={$domainHost}'\n      - 'N8N_EDITOR_BASE_URL=https://{$domainHost}'\n      - 'WEBHOOK_URL=https://{$domainHost}'\n      - 'GENERIC_TIMEZONE={$timezone}'\n      - 'TZ={$timezone}'\n      - 'N8N_ENCRYPTION_KEY={$encryptionKey}'\n      - 'N8N_SECURE_COOKIE=true'\n      - 'VIRTUAL_HOST={$domainHost}'\n      - 'VIRTUAL_PORT=5678'\n      - 'LETSENCRYPT_HOST={$domainHost}'\n      - 'LETSENCRYPT_EMAIL={$letsencryptEmail}'";
    
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
      retries: 20
      start_period: 45s

networks:
  nginx-proxy_web:
    external: true";
            break;
            
        case "n8n":
        case "n8n-pgsql":
            $postgresPassword = $credentials["postgres_password"];
            $dockerCompose = "services:
  n8n:
    image: 'docker.n8n.io/n8nio/n8n:{$version}'
    container_name: {$puqContainer}
    restart: unless-stopped{$deployBlock}
    command: start
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
      test: ['CMD-SHELL', 'wget --no-verbose --tries=1 --spider http://127.0.0.1:5678/healthz || exit 1']
      interval: 10s
      timeout: 5s
      retries: 10
      start_period: 30s
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
            $postgresPassword = $credentials["postgres_password"];
            $credentials["mode"] = "queue";
            $dockerCompose = "services:
  n8n:
    image: 'docker.n8n.io/n8nio/n8n:{$version}'
    container_name: {$puqContainer}
    restart: unless-stopped{$deployBlock}
    command: start
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
      timeout: 10s
      retries: 20
      start_period: 60s
    depends_on:
      postgresql:
        condition: service_healthy
      redis:
        condition: service_healthy

  n8n-worker:
    image: 'docker.n8n.io/n8nio/n8n:{$version}'
    container_name: {$workerContainer}
    restart: unless-stopped
    command: worker
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
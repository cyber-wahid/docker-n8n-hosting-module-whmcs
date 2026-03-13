<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
define("DOCKERN8N_VERSION", "3.0"); // Auto-infrastructure + Enhanced PUQ Detection
define("DOCKERN8N_GITHUB_REPO", "cyber-wahid/docker-n8n-hosting-module-whmcs");
define("DOCKERN8N_UPDATE_URL", "https://api.github.com/repos/cyber-wahid/docker-n8n-hosting-module-whmcs/releases/latest");
define("DOCKERN8N_DEBUG_LOGGING", false);
require_once __DIR__ . "/Core.php";

require_once __DIR__ . "/DockerAPI.php";
require_once __DIR__ . "/DomainHelpers.php";
require_once __DIR__ . "/NginxTemplates.php";
require_once __DIR__ . "/PUQCompatibility.php";
use WHMCS\Database\Capsule;
use WHMCS\Module\Server\Dockern8n\System;

function dockern8n_MetaData()
{
    return array(
        "DisplayName" => "Docker N8N",
        "APIVersion" => "1.1",
        "RequiresServer" => true,
        "Author" => "LogicDock",
        "Version" => DOCKERN8N_VERSION
    );
}

function dockern8n_ConfigOptions()
{
    // IMPORTANT: WHMCS requires numeric indexed array for ConfigOptions
    // The order here directly maps to configoption1, configoption2, etc.
    // DO NOT use associative keys - it causes config reset issues on some servers
    return array(
        // configoption1 - Service Template
        array(
            "FriendlyName" => "Service Template",
            "Type" => "dropdown",
            "Options" => "n8n-sqlite,n8n-pgsql,n8n-queue",
            "Default" => "n8n-pgsql",
            "Description" => "<br><strong>n8n-sqlite:</strong> For small workloads<br><strong>n8n-pgsql:</strong> For production<br><strong>n8n-queue:</strong> For high-scale enterprise"
        ),
        // configoption2 - CPU Limit
        array(
            "FriendlyName" => "CPU Limit",
            "Type" => "dropdown",
            "Options" => "0.5,1,2,3,4,5,6,7,8,9,10,11,12",
            "Default" => "1",
            "Description" => "Number of CPUs"
        ),
        // configoption3 - Memory Limit
        array(
            "FriendlyName" => "Memory Limit",
            "Type" => "dropdown",
            "Options" => "512M,1G,2G,3G,4G,5G,6G,7G,8G,9G,10G,11G,12G,13G,14G,15G,16G",
            "Default" => "1G",
            "Description" => "Memory limit"
        ),
        // configoption4 - N8N Version
        array(
            "FriendlyName" => "N8N Version",
            "Type" => "text",
            "Size" => "20",
            "Default" => "latest",
            "Description" => "Docker image tag (e.g. latest, 1.0.0)"
        ),
        // configoption5 - Disk Limit
        array(
            "FriendlyName" => "Disk Limit",
            "Type" => "text",
            "Size" => "10",
            "Default" => "5G",
            "Description" => "Storage limit (e.g., 5G, 10G, 500M)"
        ),
        // configoption6 - Base Domain (Required)
        array(
            "FriendlyName" => "Base Domain (Required)",
            "Type" => "text",
            "Size" => "40",
            "Default" => "",
            "Description" => "<strong style='color:red'>REQUIRED!</strong> Base domain for subdomains (e.g. yoursite.com). Services will be created as {userid}-{serviceid}.yoursite.com"
        ),
        // configoption7 - Subdomain Format
        array(
            "FriendlyName" => "Subdomain Format",
            "Type" => "text",
            "Size" => "30",
            "Default" => "{user_id}-{service_id}",
            "Description" => "Auto-subdomain format. Macros: {user_id}, {service_id}, {random_digit_X}, {random_letter_X}, {unixtime}"
        ),
        // configoption8 - Disk Alert Threshold
        array(
            "FriendlyName" => "Disk Alert Threshold %",
            "Type" => "text",
            "Size" => "5",
            "Default" => "80",
            "Description" => "Send notification when disk usage exceeds this percentage"
        ),
        // configoption9 - Let's Encrypt Email
        array(
            "FriendlyName" => "Let's Encrypt Email",
            "Type" => "text",
            "Size" => "30",
            "Default" => "",
            "Description" => "Email for SSL certificates. Leave empty to use admin@domain"
        ),
        // configoption10 - Auto-setup Infrastructure
        array(
            "FriendlyName" => "Auto-setup Infrastructure",
            "Type" => "yesno",
            "Description" => "Automatically deploy Nginx Proxy & SSL Companion if missing on the server"
        )
    );
}


// SSH Connection Pool - Reuse connections
$GLOBALS['dockern8n_ssh_pool'] = array();

/**
 * Extract SSH port from serveraccesshash field
 * If accesshash contains a valid port number (1-65535), use it
 * Otherwise default to port 22
 */
function dockern8n_GetSSHPort($accessHash = '')
{
    $accessHash = trim($accessHash);

    // If empty, return default
    if (empty($accessHash)) {
        return 22;
    }

    // If it's a valid port number (1-65535)
    if (is_numeric($accessHash)) {
        $port = (int) $accessHash;
        if ($port >= 1 && $port <= 65535) {
            return $port;
        }
    }

    // Default to 22
    return 22;
}

/**
 * Get or create SSH connection (Connection Pooling)
 * Reuses existing connections to avoid repeated handshakes
 * @param string $hostname Server hostname or IP
 * @param string $username SSH username
 * @param string $password SSH password
 * @param int $port SSH port (default 22)
 */
function dockern8n_ssh_GetConnection($hostname, $username, $password, $port = 22)
{
    // Include port in pool key for proper connection isolation
    $poolKey = md5($hostname . $username . $port);

    // Check if we have a valid cached connection
    if (isset($GLOBALS['dockern8n_ssh_pool'][$poolKey])) {
        $cached = $GLOBALS['dockern8n_ssh_pool'][$poolKey];
        if ($cached['ssh'] && $cached['ssh']->isConnected()) {
            return $cached['ssh'];
        }
    }

    // Create new connection with custom port
    $ssh = null;
    if (class_exists("phpseclib3\\Net\\SSH2")) {
        $ssh = new \phpseclib3\Net\SSH2($hostname, $port, 15);
    } elseif (class_exists("phpseclib\\Net\\SSH2")) {
        $ssh = new \phpseclib\Net\SSH2($hostname, $port, 15);
    } elseif (class_exists("Net_SSH2")) {
        $ssh = new \Net_SSH2($hostname, $port, 15);
    } else {
        $includePath = implode(DIRECTORY_SEPARATOR, array(ROOTDIR, "vendor", "phpseclib", "phpseclib", "phpseclib"));
        if (file_exists($includePath . "/Net/SSH2.php")) {
            set_include_path(get_include_path() . PATH_SEPARATOR . $includePath);
            require_once "Net/SSH2.php";
            $ssh = new \Net_SSH2($hostname, $port, 15);
        }
    }

    if (!$ssh) {
        throw new Exception("SSH Library not found.");
    }

    if (!$ssh->login($username, $password)) {
        throw new Exception("SSH Login Failed on port {$port}.");
    }

    $ssh->setTimeout(60);

    // Cache the connection
    $GLOBALS['dockern8n_ssh_pool'][$poolKey] = array(
        'ssh' => $ssh,
        'port' => $port,
        'created' => time()
    );

    return $ssh;
}

/**
 * Run single SSH command with connection pooling
 * @param int $port SSH port (default 22)
 */
function dockern8n_ssh_RunCommand($hostname, $username, $password, $command, $timeout = 60, $port = 22)
{
    try {
        $ssh = dockern8n_ssh_GetConnection($hostname, $username, $password, $port);
        $ssh->setTimeout($timeout);
        return $ssh->exec($command);
    } catch (Exception $e) {
        // logModuleCall('dockern8n', 'SSH_Error', ['cmd' => $command, 'port' => $port], $e->getMessage(), '');
        throw $e;
    }
}

/**
 * Run multiple commands in ONE SSH call (Command Batching)
 * This is 50-70% faster than multiple separate calls
 * Returns array of results keyed by command index
 * @param int $port SSH port (default 22)
 */
function dockern8n_ssh_RunBatch($hostname, $username, $password, $commands, $port = 22)
{
    if (empty($commands)) {
        return array();
    }

    // If single command, just run it
    if (count($commands) === 1) {
        return array(0 => dockern8n_ssh_RunCommand($hostname, $username, $password, $commands[0], 60, $port));
    }

    // Build a batch command with separators
    $separator = "___DOCKERN8N_CMD_SEP___";
    $batchCommand = "";
    foreach ($commands as $i => $cmd) {
        if ($i > 0) {
            $batchCommand .= " && echo '{$separator}' && ";
        }
        $batchCommand .= "(" . $cmd . " 2>&1 || true)";
    }

    $ssh = dockern8n_ssh_GetConnection($hostname, $username, $password, $port);
    $output = $ssh->exec($batchCommand);

    // Split results
    $results = explode($separator, $output);
    $parsed = array();
    foreach ($results as $i => $result) {
        $parsed[$i] = trim($result);
    }

    return $parsed;
}

/**
 * Run command in background (no wait) - TRULY NON-BLOCKING
 * Returns immediately - command runs async on server
 * Uses write() instead of exec() to avoid waiting
 * @param int $port SSH port (default 22)
 */
function dockern8n_ssh_RunAsync($hostname, $username, $password, $command, $logFile = '/dev/null', $port = 22)
{
    try {
        // Escape single quotes in command for safe bash execution
        $escapedCmd = str_replace("'", "'\\''", $command);
        // Wrap in nohup with immediate exit
        $asyncCmd = "nohup bash -c '{$escapedCmd}' > {$logFile} 2>&1 &\nexit\n";

        $ssh = dockern8n_ssh_GetConnection($hostname, $username, $password, $port);

        // Set very short timeout so we don't wait
        $ssh->setTimeout(1);

        // Use write() + read() instead of exec() for non-blocking
        // This sends the command and immediately returns
        $ssh->write($asyncCmd);

        // Small delay to ensure command is sent
        usleep(100000); // 100ms

        return true;
    } catch (Exception $e) {
        // Log error but don't fail - command may still run
        // logModuleCall("dockern8n", "ssh_RunAsync_Error", array("cmd" => $command, "port" => $port), $e->getMessage(), '');
        return false;
    }
}


/**
 * Ensure Nginx Proxy and SSL Companion are running on the server
 */
function dockern8n_EnsureInfrastructure($params)
{
    if (($params['configoption10'] ?? '') !== 'on') {
        return true;
    }

    $hostname = $params["serverhostname"] ?: $params["serverip"];
    $sshUsername = $params["serverusername"];
    $sshPassword = $params["serverpassword"];
    $sshPort = dockern8n_GetSSHPort($params["serveraccesshash"] ?? '');

    // Setup script that checks for network, directory and containers
    $setupScript = "
    # 1. Create network if missing
    if ! docker network ls --format '{{.Name}}' | grep -q '^nginx-proxy_web$'; then
        docker network create nginx-proxy_web
    fi

    # 2. Check if proxy is running
    PROXY_RUNNING=$(docker ps --filter 'name=nginx-proxy' --filter 'status=running' -q)
    SSL_RUNNING=$(docker ps --filter 'name=letsencrypt-nginx-proxy-companion' --filter 'status=running' -q)

    if [ -z \"\$PROXY_RUNNING\" ] || [ -z \"\$SSL_RUNNING\" ]; then
        mkdir -p /opt/nginx-proxy
        cat <<EOF > /opt/nginx-proxy/docker-compose.yml
version: '3'
services:
  nginx-proxy:
    image: jwilder/nginx-proxy:latest
    container_name: nginx-proxy
    ports:
      - \"80:80\"
      - \"443:443\"
    volumes:
      - /var/run/docker.sock:/tmp/docker.sock:ro
      - certs:/etc/nginx/certs
      - vhost:/etc/nginx/vhost.d
      - html:/usr/share/nginx/html
    labels:
      - \"com.github.jrcs.letsencrypt_nginx_proxy_companion.nginx_proxy\"
    networks:
      - nginx-proxy_web
    restart: always

  letsencrypt-companion:
    image: jrcs/letsencrypt-nginx-proxy-companion:latest
    container_name: letsencrypt-nginx-proxy-companion
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - certs:/etc/nginx/certs
      - vhost:/etc/nginx/vhost.d
      - html:/usr/share/nginx/html
    networks:
      - nginx-proxy_web
    restart: always

volumes:
  certs:
  vhost:
  html:

networks:
  nginx-proxy_web:
    external: true
EOF
        cd /opt/nginx-proxy && (docker compose up -d || docker-compose up -d)
    fi
    ";

    try {
        dockern8n_ssh_RunCommand($hostname, $sshUsername, $sshPassword, $setupScript, 300, $sshPort);
        return true;
    } catch (Exception $e) {
        return "Infrastructure setup failed: " . $e->getMessage();
    }
}


function dockern8n_TestConnection(array $params)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $sshPort = dockern8n_GetSSHPort($params["serveraccesshash"] ?? '');

        if (empty($hostname)) {
            return array("success" => false, "error" => "Hostname/IP is missing.");
        }

        if (empty($username)) {
            return array("success" => false, "error" => "Username is missing.");
        }

        if (empty($password)) {
            return array("success" => false, "error" => "Password is missing.");
        }

        try {
            // logModuleCall("dockern8n", "TestConnection_Start", array("hostname" => $hostname, "username" => $username, "port" => $sshPort), '', '');

            // 0. Auto-setup Infrastructure if enabled (Convenient trigger for admins)
            $infraResult = dockern8n_EnsureInfrastructure($params);
            if ($infraResult !== true) {
                return array("success" => false, "error" => $infraResult);
            }

            // Test SSH connection with custom port
            $output = dockern8n_ssh_RunCommand($hostname, $username, $password, "docker --version", 10, $sshPort);

            // logModuleCall("dockern8n", "TestConnection_Output", array("hostname" => $hostname), $output, '');

            if (strpos($output, "Docker version") !== false) {
                $portMsg = ($sshPort !== 22) ? " (SSH Port: {$sshPort})" : "";
                return array("success" => true, "message" => "Connection successful! Docker is installed.{$portMsg}");
            } else {
                return array("success" => false, "error" => "SSH Connected, but Docker not found. Please install Docker on the server. Output: " . substr($output, 0, 200));
            }
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            // logModuleCall("dockern8n", "TestConnection_Failed", array("hostname" => $hostname, "error" => $errorMsg), '', '');

            // Provide more helpful error messages
            if (strpos($errorMsg, "Login Failed") !== false) {
                return array("success" => false, "error" => "SSH Authentication Failed on port {$sshPort}. Please check your username and password.");
            } elseif (strpos($errorMsg, "Connection refused") !== false) {
                return array("success" => false, "error" => "Connection Refused on port {$sshPort}. Please check if SSH is running and firewall allows connections.");
            } elseif (strpos($errorMsg, "timed out") !== false) {
                return array("success" => false, "error" => "Connection Timeout on port {$sshPort}. Please check if the server IP is correct and reachable.");
            } else {
                return array("success" => false, "error" => "Connection Failed on port {$sshPort}: " . $errorMsg);
            }
        }
    } catch (Exception $e) {
        return array("success" => false, "error" => $e->getMessage());
    }
}

function dockern8n_CreateAccount(array $params)
{
    $serviceId = $params["serviceid"];
    $domain = $params["domain"];

    // 1. Auto-generate Domain and Credentials if missing
    $mainDomain = $params["configoption6"] ?? ''; // Main Domain from config
    $subdomainFormat = $params["configoption7"] ?? '{user_id}-{service_id}';

    if (empty($domain) || $domain === 'domain.com') {
        if (!function_exists('dockern8n_GenerateDomain')) {
            require_once __DIR__ . '/DomainHelpers.php';
        }
        $domain = dockern8n_GenerateDomain($params, $mainDomain, $subdomainFormat);
        $domain = dockern8n_EnsureUniqueDomain($domain, $serviceId);

        // Update database so it shows in WHMCS Admin
        Capsule::table('tblhosting')->where('id', $serviceId)->update(['domain' => $domain]);
        $params['domain'] = $domain;
    }

    $username = $params["username"];
    $password = $params["password"];

    if (empty($username)) {
        $username = "admin";
        Capsule::table('tblhosting')->where('id', $serviceId)->update(['username' => $username]);
        $params['username'] = $username;
    }

    if (empty($password)) {
        if (!function_exists('dockern8n_GeneratePassword')) {
            require_once __DIR__ . '/DomainHelpers.php';
        }
        $password = dockern8n_GeneratePassword(12);
        Capsule::table('tblhosting')->where('id', $serviceId)->update(['password' => encrypt($password)]);
        $params['password'] = $password;
    }

    // logModuleCall("dockern8n", "CreateAccount_START", array("serviceId" => $serviceId, "domain" => $domain, "username" => $username), '', '');

    try {
        // 0. Auto-setup Infrastructure if enabled
        $infraResult = dockern8n_EnsureInfrastructure($params);
        if ($infraResult !== true) {
            return $infraResult;
        }

        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $sshUsername = $params["serverusername"];
        $sshPassword = $params["serverpassword"];

        if (empty($hostname) || empty($sshUsername) || empty($sshPassword)) {
            return "Server configuration missing.";
        }

        $userId = $params['userid'];
        $mainDomain = $params["configoption6"] ?? '';

        // Validate Main Domain - must look like a real domain, not JSON or garbage from PUQ migration
        $mainDomain = trim($mainDomain);
        $mainDomain = preg_replace("#^https?://#", '', $mainDomain);
        $mainDomain = rtrim($mainDomain, "/");

        // Check if mainDomain looks like valid domain (no JSON, no special chars except . and -)
        $isValidDomain = !empty($mainDomain)
            && strpos($mainDomain, '{') === false
            && strpos($mainDomain, '"') === false
            && strpos($mainDomain, ':') === false
            && preg_match('/^[a-z0-9][a-z0-9\-\.]*\.[a-z]{2,}$/i', $mainDomain);

        if (!$isValidDomain) {
            // STOP provisioning if no valid base domain is configured
            return "Configuration Error: 'Base Domain' is not set or invalid. Please go to Products/Services > Edit Product > Module Settings and set a valid Base Domain (e.g. n8n.yoursite.com)";
        }

        // SUBDOMAIN GENERATION - Uses macros from configoption7
        // For PUQ compatibility: If domain is already set and valid, use it as-is
        // Only generate new subdomain for new services or placeholder domains
        $cleanDomain = preg_replace("#^https?://#", '', $params['domain']);
        $cleanDomain = rtrim($cleanDomain, "/");

        // Check if current domain already looks complete (contains base domain)
        $domainAlreadySet = !empty($cleanDomain)
            && $cleanDomain !== 'domain.com'
            && strpos($cleanDomain, $mainDomain) !== false;

        if ($domainAlreadySet) {
            // Use existing domain (PUQ compatibility / already provisioned)
            $fullFQDN = $cleanDomain;
        } else {
            // NEW SERVICE: Generate subdomain using macros from configoption7
            $subdomainFormat = $params["configoption7"] ?? '{user_id}-{service_id}';

            if (!function_exists('dockern8n_ParseDomainMacros')) {
                require_once __DIR__ . '/DomainHelpers.php';
            }

            // Parse macros: {user_id}, {service_id}, {random_digit_X}, {random_letter_X}, {unixtime}, etc.
            $subdomain = dockern8n_ParseDomainMacros($subdomainFormat, $userId, $serviceId);
            $fullFQDN = "{$subdomain}.{$mainDomain}";

            // Ensure uniqueness
            $fullFQDN = dockern8n_EnsureUniqueDomain($fullFQDN, $serviceId);
        }

        // Update WHMCS Domain if it's currently wrong or placeholder
        if ($params['domain'] !== $fullFQDN) {
            Capsule::table('tblhosting')->where('id', $serviceId)->update(['domain' => $fullFQDN]);
            $params['domain'] = $fullFQDN;
        }

        // PUQ Style Names (e.g. 10-26.n8n.evermail.online)
        $puqContainer = $fullFQDN;
        // Stack Name (e.g. 10-26n8nevermailonline)
        $puqProject = $prefix . str_replace('.', '', $cleanDomain);

        // PUQ Standard Paths
        $clientsDir = "/opt/docker/clients";
        $serviceDir = "{$clientsDir}/{$fullFQDN}";
        $mountDir = "/mnt/{$fullFQDN}";
        $imgFile = "{$serviceDir}/data.img";
        $statusFile = "{$serviceDir}/status";

        $ram = $params["configoption3"] ?: "1G";
        $cpu = $params["configoption2"] ?: "1";
        $version = $params["configoption4"] ?: "latest";
        $diskRaw = $params["configoption5"] ?: "5G";
        // Sanitize Disk: Match number + unit (K, M, G, T). If no unit, assume G.
        if (preg_match('/^(\d+)([KMGT])$/i', trim($diskRaw), $matches)) {
            $diskWithUnit = strtoupper($matches[1] . $matches[2]);
        } else {
            $diskNum = preg_replace('/[^0-9]/', '', $diskRaw);
            $diskWithUnit = (!empty($diskNum)) ? $diskNum . "G" : "5G";
        }

        // Sanitize RAM: If it has M/G, keep it. If just number, assume G (legacy).
        $ramRaw = $params["configoption3"] ?: "1G";
        if (preg_match('/^\d+(M|G)$/i', $ramRaw)) {
            $memoryWithSuffix = strtoupper($ramRaw);
        } else {
            $ramNum = preg_replace('/[^0-9.]/', '', $ramRaw);
            $memoryWithSuffix = (!empty($ramNum)) ? $ramNum . "G" : "1G";
        }

        // 1. Prepare Directories
        $prepCmd = "sudo mkdir -p {$serviceDir} {$mountDir} && sudo chmod 777 {$serviceDir} {$mountDir}";
        dockern8n_ssh_RunCommand($hostname, $sshUsername, $sshPassword, $prepCmd);

        // 2. Create and Format Loopback Image (data.img)
        $diskCmd = "if [ ! -f {$imgFile} ]; then 
            (sudo fallocate -l {$diskWithUnit} {$imgFile} || sudo truncate -s {$diskWithUnit} {$imgFile}) && 
            sudo mkfs.ext4 -F {$imgFile}
        else
            echo 'Disk image exists, skipping creation'
        fi";
        dockern8n_ssh_RunCommand($hostname, $sshUsername, $sshPassword, $diskCmd, 180);

        // 3. Mount Logic and Subdir creation
        $mountScript = "
        if ! grep -q '" . $imgFile . "' /etc/fstab; then
            echo '" . $imgFile . " " . $mountDir . " ext4 loop 0 0' | sudo tee -a /etc/fstab
        fi
        sudo mount -a 2>/dev/null || sudo mount -o loop " . $imgFile . " " . $mountDir . "
        sudo mkdir -p " . $mountDir . "/n8n " . $mountDir . "/postgres " . $mountDir . "/redis 2>/dev/null || true
        # Final Permissions: Searchable root, specific subdirs
        sudo chmod 755 " . $mountDir . " 2>/dev/null || true
        sudo chown -R 1000:1000 " . $mountDir . "/n8n 2>/dev/null || true
        sudo chown -R 70:70 " . $mountDir . "/postgres 2>/dev/null || true
        sudo chown -R 999:999 " . $mountDir . "/redis 2>/dev/null || true
        ";
        dockern8n_ssh_RunCommand($hostname, $sshUsername, $sshPassword, $mountScript);

        // 4. Generate Docker Compose using Templates (PUQ Clone Style)
        $templateType = $params["configoption1"] ?: "n8n-pgsql";
        $letsencryptEmail = $params["configoption9"] ?: ""; // Updated from configoption8 to 9

        // Detect if there's an existing service (PUQ migration) to reuse encryption keys
        $puqInfo = DockerN8N_PUQCompat::detectPUQService($params);
        $existingCreds = array();
        if ($puqInfo['is_puq'] && !empty($puqInfo['encryption_key'])) {
            $existingCreds['encryption_key'] = $puqInfo['encryption_key'];
        }

        $templateData = dockern8n_GetNginxTemplate(
            $templateType,
            $fullFQDN, // Pass the FULL FQDN (e.g. 1-42.cyberit.cloud)
            $serviceId,
            $cpu,
            $memoryWithSuffix,
            $version,
            $username,
            $password,
            $letsencryptEmail,
            "Asia/Dhaka", // Default timezone
            $existingCreds, // Pass existing creds (includes encryption_key if found)
            $userId
        );

        $dockerCompose = $templateData["docker_compose"];
        $credentials = $templateData["credentials"];

        $b64Compose = base64_encode($dockerCompose);
        $writeCmd = "echo '{$b64Compose}' | base64 -d > {$serviceDir}/docker-compose.yml";
        dockern8n_ssh_RunCommand($hostname, $sshUsername, $sshPassword, $writeCmd);

        // 5. Fire it up! (Synchronous)
        // Ensure permissions one last time before up
        dockern8n_ssh_RunCommand($hostname, $sshUsername, $sshPassword, "sudo chown -R 1000:1000 " . $mountDir . "/n8n");
        $upCmd = "cd " . $serviceDir . " && (docker compose -p " . $puqProject . " up -d 2>&1 || docker-compose -p " . $puqProject . " up -d 2>&1)";
        $output = dockern8n_ssh_RunCommand($hostname, $sshUsername, $sshPassword, $upCmd, 300);

        if (strpos($output, 'Error') !== false || strpos($output, 'failed') !== false || strpos($output, 'invalid') !== false || (strpos($output, 'command not found') !== false && strpos($output, 'up -d') === false)) {
            // logModuleCall("dockern8n", "CreateAccount_ERROR", array("cmd" => $upCmd, "output" => $output), "Deployment Failed", '');
            return "Deployment failed: " . substr(strip_tags($output), 0, 300);
        }

        // 6. Final Status and Meta
        dockern8n_ssh_RunCommand($hostname, $sshUsername, $sshPassword, "echo 'active' | sudo tee {$statusFile}");

        $details = array_merge($credentials, [
            'type' => 'puq_standard',
            'service_dir' => $serviceDir,
            'mount_dir' => $mountDir,
            'container_name' => $puqContainer,
            'project_name' => $puqProject,
            'domain' => $cleanDomain,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $customField = Capsule::table('tblcustomfields')->where('relid', $params['packageid'])->where('fieldname', 'Service Details')->first();
        if ($customField) {
            Capsule::table('tblcustomfieldsvalues')->updateOrInsert(
                ['fieldid' => $customField->id, 'relid' => $serviceId],
                ['value' => json_encode($details)]
            );
        }

        dockern8n_LogActivity($params, "service_created", "N8N PUQ-Clone service provisioned at {$cleanDomain}");
        return "success";

    } catch (Exception $e) {
        return "Provisioning Failed: " . $e->getMessage();
    }
}

function dockern8n_TerminateAccount(array $params)
{
    try {
        $serviceId = $params["serviceid"];
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];

        $info = dockern8n_GetContainerInfo($params);
        $serviceDir = $info['service_dir'];
        $projectName = $info['project_name'];
        $containerName = $info['n8n_container'];
        $mountDir = $info['mount_dir'];

        // 1. Stop and Remove Project/Containers
        $stopCmd = "cd " . $serviceDir . " && (docker compose -p " . $projectName . " down -v --remove-orphans || docker-compose -p " . $projectName . " down -v --remove-orphans || (docker stop " . $containerName . " && docker rm " . $containerName . ")) 2>/dev/null || true";
        dockern8n_ssh_RunCommand($hostname, $username, $password, $stopCmd);

        // 2. Cleanup directories and mounts
        $cleanupScript = "
        sudo umount -l " . $mountDir . " 2>/dev/null || true
        # Remove from fstab if exists
        sudo sed -i '\| " . $mountDir . " |d' /etc/fstab 2>/dev/null || true
        sudo rm -rf " . $serviceDir . " 2>/dev/null || true
        sudo rm -rf " . $mountDir . " 2>/dev/null || true
        ";
        dockern8n_ssh_RunCommand($hostname, $username, $password, $cleanupScript);

        // 3. Clear Metadata
        $customField = Capsule::table("tblcustomfields")->where("relid", $params["packageid"])->where("fieldname", "Service Details")->first();
        if ($customField) {
            Capsule::table("tblcustomfieldsvalues")->where("fieldid", $customField->id)->where("relid", $serviceId)->delete();
        }

        dockern8n_LogActivity($params, "service_terminated", "N8N service stack terminated and cleaned up");
        return "success";
    } catch (Exception $e) {
        return "Termination Failed: " . $e->getMessage();
    }
}

/**
 * Suspend Account - Stops containers when client doesn't pay
 * Called automatically by WHMCS when invoice is overdue
 */
function dockern8n_SuspendAccount(array $params)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $sshUsername = $params["serverusername"];
        $sshPassword = $params["serverpassword"];

        $info = dockern8n_GetContainerInfo($params);
        $serviceDir = $info['service_dir'];
        $projectName = $info['project_name'];
        $containerName = $info['n8n_container'];
        $mountDir = $info['mount_dir'];

        // 1. Stop Containers using project name or fallback
        $stopCmd = "cd {$serviceDir} && (docker compose -p {$projectName} stop || docker-compose -p {$projectName} stop || docker stop {$containerName}) 2>/dev/null";
        dockern8n_ssh_RunCommand($hostname, $sshUsername, $sshPassword, $stopCmd);

        // 2. Unmount (Optional but recommended for safety)
        if (!empty($mountDir)) {
            dockern8n_ssh_RunCommand($hostname, $sshUsername, $sshPassword, "sudo umount -l {$mountDir} 2>/dev/null || true");
        }

        dockern8n_LogActivity($params, "service_suspended", "N8N service stack suspended (stopped and unmounted).");
        return "success";
    } catch (Exception $e) {
        return "Suspend Failed: " . $e->getMessage();
    }
}

/**
 * Unsuspend Account - Starts containers when client pays
 * Called automatically by WHMCS when payment is received
 */
function dockern8n_UnsuspendAccount(array $params)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];

        $info = dockern8n_GetContainerInfo($params);
        $serviceDir = $info['service_dir'];
        $projectName = $info['project_name'];
        $containerName = $info['n8n_container'];

        // PUQ Compatibility: Ensure disk is mounted before starting
        if ($info['is_puq']) {
            $mountCmd = "sudo mount -a 2>/dev/null && sudo chown -R 1000:1000 " . $info['mount_dir'] . " && sleep 1";
            dockern8n_ssh_RunCommand($hostname, $username, $password, $mountCmd);
        } else {
            // Native: ensure bind mount permissions
            // Ensure permissions before starting
        if ($info['is_puq']) {
            dockern8n_ssh_RunCommand($hostname, $username, $password, "sudo chown -R 1000:1000 " . $info['mount_dir'] . "/n8n 2>/dev/null; sudo chown -R 70:70 " . $info['mount_dir'] . "/postgres 2>/dev/null; sudo chown -R 999:999 " . $info['mount_dir'] . "/redis 2>/dev/null");
        } else {
            dockern8n_ssh_RunCommand($hostname, $username, $password, "sudo chown -R 1000:1000 " . $info['mount_dir'] . " 2>/dev/null");
        }
        }

        // Start containers using project up or fallback
        $cmdStart = "cd " . $serviceDir . " && (docker compose -p " . $projectName . " up -d || docker-compose -p " . $projectName . " up -d || docker start " . $containerName . ") 2>&1";
        $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdStart);

        if (stripos($output, 'error') !== false && stripos($output, 'started') === false && stripos($output, 'running') === false && stripos($output, 'done') === false) {
             return "Unsuspend failed: " . substr(strip_tags($output), 0, 150);
        }

        dockern8n_LogActivity($params, "service_unsuspended", "N8N service stack unsuspended (started).");
        return "success";
    } catch (Exception $e) {
        return "Unsuspend Failed: " . $e->getMessage();
    }
}


function dockern8n_GetTemplate($type, $domain, $serviceId, $cpuLimit = 0, $memoryLimit = 0, $version = "latest", $username = '', $password = '', $timezone = "Asia/Dhaka", $existingCreds = [])
{
    $domainHost = preg_replace("#^https?://#", '', $domain);
    $domainHost = rtrim($domainHost, "/");
    $credentials = array("domain" => "https://" . $domainHost, "created_at" => date("Y-m-d H:i:s"));
    $dockerCompose = '';

    // Generate or reuse credentials
    $encryptionKey = $existingCreds["encryption_key"] ?? dockern8n_GeneratePassword(32);
    $n8nUser = $username ?: ($existingCreds["username"] ?? "admin");
    $n8nPass = $password ?: ($existingCreds["password"] ?? dockern8n_GeneratePassword());
    $credentials["username"] = $n8nUser;
    $credentials["password"] = $n8nPass;
    $credentials["encryption_key"] = $encryptionKey;
    $credentials["template"] = $type;
    $credentials["postgres_password"] = $existingCreds["postgres_password"] ?? dockern8n_GeneratePassword();

    // Deploy block for resource limits
    $deployBlock = '';
    if ((!empty($cpuLimit) && $cpuLimit !== "0") || (!empty($memoryLimit) && $memoryLimit !== "0")) {
        $deployBlock = "
    deploy:
      resources:
        limits:";
        if (!empty($cpuLimit) && $cpuLimit !== "0") {
            $deployBlock .= "
          cpus: '{$cpuLimit}'";
        }
        if (!empty($memoryLimit) && $memoryLimit !== "0") {
            $deployBlock .= "
          memory: '{$memoryLimit}'";
        }
    }

    // Common N8N environment variables
    $commonEnv = "      - 'N8N_HOST={$domainHost}'
      - 'N8N_EDITOR_BASE_URL=https://{$domainHost}'
      - 'WEBHOOK_URL=https://{$domainHost}'
      - 'GENERIC_TIMEZONE={$timezone}'
      - 'TZ={$timezone}'
      - 'N8N_ENCRYPTION_KEY={$encryptionKey}'
      - 'N8N_SECURE_COOKIE=false'
      - 'N8N_BLOCK_ENV_ACCESS_IN_NODE=true'
      - 'N8N_ENFORCE_SETTINGS_FILE_PERMISSIONS=true'
      - 'N8N_PROXY_HOPS=1'";

    switch ($type) {
        // Template 1: Basic - N8N + SQLite (Standalone)
        case "n8n-sqlite":
            $dockerCompose = "services:
  n8n:
    image: 'docker.n8n.io/n8nio/n8n:{$version}'
    container_name: service-{$serviceId}-n8n
    restart: unless-stopped{$deployBlock}
    command: start
    environment:
{$commonEnv}
    expose:
      - '5678'
    networks:
      - default
      - nginx-proxy_web
    volumes:
      - 'n8n-data:/home/node/.n8n'
    labels:
      - 'VIRTUAL_HOST={$domainHost}'
      - 'VIRTUAL_PORT=5678'
      - 'LETSENCRYPT_HOST={$domainHost}'
    healthcheck:
      test: ['CMD-SHELL', 'wget -qO- http://127.0.0.1:5678/healthz || exit 1']
      interval: 10s
      timeout: 10s
      retries: 20
      start_period: 45s

volumes:
  n8n-data:

networks:
  nginx-proxy_web:
    external: true";
            break;

        // ==========================================
        // Template 2: Standard - N8N + PostgreSQL
        case "n8n":
        case "n8n-pgsql":
            $postgresPassword = $credentials["postgres_password"];
            $dockerCompose = "services:
  n8n:
    image: 'docker.n8n.io/n8nio/n8n:{$version}'
    container_name: service-{$serviceId}-n8n
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
      - 'n8n-data:/home/node/.n8n'
    labels:
      - 'VIRTUAL_HOST={$domainHost}'
      - 'VIRTUAL_PORT=5678'
      - 'LETSENCRYPT_HOST={$domainHost}'
    healthcheck:
      test: ['CMD-SHELL', 'wget -qO- http://127.0.0.1:5678/healthz || exit 1']
      interval: 10s
      timeout: 10s
      retries: 20
      start_period: 60s
    depends_on:
      postgresql:
        condition: service_started

  postgresql:
    image: 'postgres:16-alpine'
    container_name: service-{$serviceId}-postgres
    restart: unless-stopped
    volumes:
      - 'postgresql-data:/var/lib/postgresql/data'
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

volumes:
  n8n-data:
  postgresql-data:

networks:
  nginx-proxy_web:
    external: true";
            break;

        // ==========================================
        // Template 3: N8N + PostgreSQL + Redis
        // ==========================================
        case "n8n-queue":
            $postgresPassword = $credentials["postgres_password"];
            $credentials["mode"] = "perf";

            $dockerCompose = "services:
  n8n:
    image: 'docker.n8n.io/n8nio/n8n:{$version}'
    container_name: service-{$serviceId}-n8n
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
      - 'QUEUE_BULL_REDIS_DB=0'
    expose:
      - '5678'
    networks:
      - default
      - nginx-proxy_web
    volumes:
      - 'n8n-data:/home/node/.n8n'
    labels:
      - 'VIRTUAL_HOST={$domainHost}'
      - 'VIRTUAL_PORT=5678'
      - 'LETSENCRYPT_HOST={$domainHost}'
    healthcheck:
      test: ['CMD-SHELL', 'wget -qO- http://127.0.0.1:5678/healthz || exit 1']
      interval: 10s
      timeout: 10s
      retries: 20
      start_period: 60s
    depends_on:
      postgresql:
        condition: service_started
      redis:
        condition: service_started

  n8n-worker:
    image: 'docker.n8n.io/n8nio/n8n:{$version}'
    container_name: service-{$serviceId}-n8n-worker
    restart: unless-stopped{$deployBlock}
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
    depends_on:
      postgresql:
        condition: service_started
      redis:
        condition: service_started
      n8n:
        condition: service_started

  redis:
    image: 'redis:7-alpine'
    container_name: service-{$serviceId}-redis
    restart: unless-stopped
    volumes:
      - 'redis-data:/data'
    healthcheck:
      test: ['CMD', 'redis-cli', 'ping']
      interval: 5s
      timeout: 5s
      retries: 10

  postgresql:
    image: 'postgres:16-alpine'
    container_name: service-{$serviceId}-postgres
    restart: unless-stopped
    volumes:
      - 'postgresql-data:/var/lib/postgresql/data'
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

volumes:
  n8n-data:
  postgresql-data:
  redis-data:

networks:
  nginx-proxy_web:
    external: true";
            break;

        default:
            throw new Exception("Template type '{$type}' not supported. Use: n8n-sqlite, n8n-pgsql, or n8n-queue");
    }

    return array("docker_compose" => $dockerCompose, "credentials" => $credentials);
}

function dockern8n_AdminCustomButtonArray()
{
    return array(
        "Container Start" => "AdminStart",
        "Container Stop" => "AdminStop",
        "Mount disk" => "AdminMount",
        "Unmount disk" => "AdminUnmount"
    );
}

function dockern8n_AdminLink($params)
{
    $serviceId = $params["serviceid"];
    $customField = Capsule::table("tblcustomfields")->where("relid", $params["packageid"])->where("fieldname", "Service Details")->where("type", "product")->first();
    if ($customField) {
        $value = Capsule::table("tblcustomfieldsvalues")->where("fieldid", $customField->id)->where("relid", $serviceId)->value("value");
        if ($value) {
            $details = json_decode(html_entity_decode($value), true);
            if (!empty($details["domain"])) {
                return $details["domain"];
            }
        }
    }
    return '';
}

function dockern8n_ChangePackage($params)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $serviceId = $params["serviceid"];

        $newCpuLimit = $params["configoption2"] ?: "1";
        $newMemoryLimit = $params["configoption3"] ?: "1G";

        // Handle Disk Limit: Dropdown returns '5', '10' etc (GB). We need '5G', '10G'.
        $newDiskLimitRaw = $params["configoption5"] ?: "10";
        $newDiskLimit = (is_numeric($newDiskLimitRaw)) ? $newDiskLimitRaw . "G" : $newDiskLimitRaw;

        // Detect correct service directory and project
        $info = dockern8n_GetContainerInfo($params);
        $serviceDir = $info['service_dir'];
        $projectName = $info['project_name'];
        $composeFile = $info['compose_file'];

        // Verify file exists before cat
        $checkCmd = "[ -f " . $composeFile . " ] && echo 'found' || echo 'missing'";
        $check = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $checkCmd));

        if ($check !== 'found') {
            throw new Exception("docker-compose.yml not found at " . $composeFile);
        }

        $cmdRead = "cat " . $composeFile;
        $composeContent = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdRead);

        // Update Resource Limits using regex (more robust)
        $composeContent = preg_replace("/cpus:\s*['\"]?[\d.]+['\"]?/", "cpus: '" . $newCpuLimit . "'", $composeContent);
        $composeContent = preg_replace("/memory:\s*['\"]?[\dGMKB]+['\"]?/", "memory: '" . $newMemoryLimit . "'", $composeContent);

        // Note: We update the compose file to reflect the new limit 
        $composeContent = preg_replace("/size:\s*['\"]?[\dGMKB]+['\"]?/", "size: '" . $newDiskLimit . "'", $composeContent);

        $b64Compose = base64_encode($composeContent);
        $cmdWrite = "echo '" . $b64Compose . "' | base64 -d > " . $composeFile;
        dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdWrite);

        // Restart Container to apply changes
        $cmdRestart = "cd " . $serviceDir . " && (docker compose -p " . $projectName . " up -d --force-recreate || docker-compose -p " . $projectName . " up -d --force-recreate)";
        $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdRestart, 120);

        if (stripos($output, 'error') !== false && stripos($output, 'done') === false && stripos($output, 'started') === false) {
             return "Package upgrade failed: " . substr(strip_tags($output), 0, 150);
        }

        logActivity("DockerN8N Package Upgrade - Service ID: " . $serviceId . ", CPU: " . $newCpuLimit . ", RAM: " . $newMemoryLimit . ", Disk: " . $newDiskLimit);
        dockern8n_LogActivity($params, "package_changed", "Package changed. CPU: {$newCpuLimit}, RAM: {$newMemoryLimit}, Disk: {$newDiskLimit}");
        
        return "success";
    } catch (Exception $e) {
        logActivity("DockerN8N Package Upgrade Failed - Service ID: " . $params['serviceid'] . ", Error: " . $e->getMessage());
        return "Package upgrade failed: " . $e->getMessage();
    }
}

function dockern8n_GetServiceStatus($hostname, $username, $password, $uuid)
{
    $serviceId = str_replace("service-", '', $uuid);
    $cmd = "docker ps -a --filter 'name=service-{$serviceId}-n8n' --format '{{.Status}}'";
    $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmd);
    $output = trim($output);
    if (empty($output)) {
        return "deleted";
    }
    if (stripos($output, "Up") === 0) {
        return "running";
    } elseif (stripos($output, "Exited") === 0 || stripos($output, "Created") === 0) {
        return "stopped";
    }
    return strtolower($output);
}

function dockern8n_GetAvailableVersions()
{
    // Updated fallback versions with latest 2.x releases
    $fallbackVersions = array(
        "latest",
        "2.0.3",
        "2.0.2",
        "2.0.1",
        "2.0.0",
        "1.71.3",
        "1.71.2",
        "1.71.1",
        "1.71.0",
        "1.70.3",
        "1.70.2",
        "1.70.1",
        "1.70.0",
        "1.69.2",
        "1.69.1",
        "1.69.0",
        "1.68.1",
        "1.68.0"
    );

    $cacheFile = sys_get_temp_dir() . "/dockern8n_versions_cache.json";
    $cacheTime = 3600; // 1 hour cache

    // Check cache
    if (file_exists($cacheFile)) {
        $cacheData = json_decode(file_get_contents($cacheFile), true);
        if ($cacheData && isset($cacheData["timestamp"]) && time() - $cacheData["timestamp"] < $cacheTime) {
            return $cacheData["versions"];
        }
    }

    $versions = array("latest");

    // Try GitHub Releases API (more reliable for n8n)
    $url = "https://api.github.com/repos/n8n-io/n8n/releases?per_page=30";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, "WHMCS-DockerN8N-Module");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/vnd.github+json'));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && !empty($response)) {
        $releases = json_decode($response, true);
        if ($releases && is_array($releases)) {
            foreach ($releases as $release) {
                $tagName = $release["tag_name"] ?? '';
                // Remove 'n8n@' prefix if present
                $version = str_replace('n8n@', '', $tagName);

                // Only include valid version numbers (e.g., 2.0.3, 1.71.0)
                if (!empty($version) && preg_match("/^\d+\.\d+(\.\d+)?$/", $version)) {
                    if (!in_array($version, $versions)) {
                        $versions[] = $version;
                    }
                }
            }
        }
    }

    // If GitHub failed, try Docker Hub as backup
    if (count($versions) <= 1) {
        $url = "https://hub.docker.com/v2/repositories/n8nio/n8n/tags?page_size=30&ordering=last_updated";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && !empty($response)) {
            $data = json_decode($response, true);
            if ($data && isset($data["results"])) {
                foreach ($data["results"] as $tag) {
                    $name = $tag["name"] ?? '';
                    if (!empty($name) && preg_match("/^\d+\.\d+(\.\d+)?$/", $name) && strpos($name, "sha256") === false) {
                        if (!in_array($name, $versions)) {
                            $versions[] = $name;
                        }
                    }
                }
            }
        }
    }

    // Use fallback if still empty
    if (count($versions) <= 1) {
        $versions = $fallbackVersions;
    }

    // Sort versions (latest first, then descending version numbers)
    usort($versions, function ($a, $b) {
        if ($a === "latest")
            return -1;
        if ($b === "latest")
            return 1;
        return version_compare($b, $a);
    });

    // Cache the results
    @file_put_contents($cacheFile, json_encode(array("timestamp" => time(), "versions" => $versions)));

    return $versions;
}

// Status cache
$GLOBALS['dockern8n_status_cache'] = array();

function dockern8n_GetStatus($params)
{
    $serviceId = $params["serviceid"];
    $domain = $params['domain'];
    $cacheKey = "status_" . $serviceId;
    $cacheTTL = 30; // 30 seconds cache

    // Check cache first
    if (isset($GLOBALS['dockern8n_status_cache'][$cacheKey])) {
        $cached = $GLOBALS['dockern8n_status_cache'][$cacheKey];
        if (time() - $cached['time'] < $cacheTTL) {
            return $cached['data'];
        }
    }

    try {
        // Try to detect PUQCloud service first
        $puqInfo = DockerN8N_PUQCompat::detectPUQService($params);

        if ($puqInfo['is_puq']) {
            // This is a PUQCloud service - use their structure
            // logModuleCall('dockern8n', 'GetStatus_PUQ', ['service' => $serviceId, 'domain' => $domain], 'PUQCloud service detected', '');

            $containerStatus = DockerN8N_PUQCompat::getContainerStatus($params, $puqInfo);

            $statusMap = [
                'running' => 'running',
                'stopped' => 'stopped',
                'not_found' => 'deleted',
            ];

            $overallStatus = $statusMap[$containerStatus] ?? 'error';

            $result = [
                "success" => true,
                "status" => $overallStatus,
                "n8n_status" => $containerStatus,
                "postgres_status" => "not_found", // PUQ uses different structure
                "is_puq_service" => true,
            ];

            // Cache the result
            $GLOBALS['dockern8n_status_cache'][$cacheKey] = [
                'time' => time(),
                'data' => $result
            ];

            return $result;
        }

        // Not a PUQCloud service - use our default logic
        $hostname = $params["serverhostname"] ? $params["serverhostname"] : $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];

        $info = dockern8n_GetContainerInfo($params);
        $n8nContainer = $info['n8n_container'];
        $pgContainer = $info['postgres_container'];

        // Use batch command - ONE SSH call instead of TWO
        $commands = array(
            "docker inspect --format='{{.State.Status}}' {$n8nContainer} 2>/dev/null || echo 'deleted'",
            "docker inspect --format='{{.State.Status}}' {$pgContainer} 2>/dev/null || echo 'not_found'"
        );

        $results = dockern8n_ssh_RunBatch($hostname, $username, $password, $commands);

        $n8nOutput = trim($results[0] ?? 'deleted');
        $postgresOutput = trim($results[1] ?? 'not_found');

        $n8nStatus = dockern8n_ParseContainerState($n8nOutput);
        $postgresStatus = dockern8n_ParseContainerState($postgresOutput);

        $overallStatus = "stopped";
        if ($n8nStatus === "running" && $postgresStatus === "running") {
            $overallStatus = "running";
        } elseif ($n8nStatus === "running") {
            $overallStatus = "running"; // SQLite template has no postgres
        } elseif ($n8nStatus === "deleted" && ($postgresStatus === "deleted" || $postgresStatus === "not_found")) {
            $overallStatus = "deleted";
        }

        $result = array(
            "success" => true,
            "status" => $overallStatus,
            "n8n_status" => $n8nStatus,
            "postgres_status" => $postgresStatus,
            "is_puq_service" => false,
        );

        // Cache the result
        $GLOBALS['dockern8n_status_cache'][$cacheKey] = array(
            'time' => time(),
            'data' => $result
        );

        return $result;
    } catch (Exception $e) {
        // logModuleCall("dockern8n", "GetStatus_ERROR", $params["serviceid"], $e->getMessage(), '');
        return array("success" => false, "status" => "error", "message" => $e->getMessage());
    }
}

function dockern8n_ParseContainerState($stateOutput)
{
    $state = trim($stateOutput, " \t\r\n\0\x0B'\"");
    $state = strtolower($state);

    if (empty($state) || $state === "error" || strpos($state, "error") !== false || strpos($state, "no such") !== false) {
        return "deleted";
    }

    if ($state === "running" || $state === "restarting") {
        return "running";
    }

    if (in_array($state, array("exited", "stopped", "created", "dead", "paused", "removing"))) {
        return "stopped";
    }

    if (strpos($state, "run") !== false || strpos($state, "up") !== false) {
        return "running";
    }

    if (strpos($state, "exit") !== false || strpos($state, "stop") !== false) {
        return "stopped";
    }

    return "unknown";
}

/**
 * Ensure compose file exists for our service
 * Generates from running container if missing - detects template type automatically
 */


/**
 * Ensure compose file exists for our service
 * Generates from running container if missing - detects template type automatically
 */
function dockern8n_EnsureComposeFile($params)
{
    try {
        $serviceId = $params["serviceid"];
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $domain = preg_replace("#^https?://#", '', $params["domain"] ?? '');
        $domain = rtrim($domain, "/");

        $serviceDir = "/var/www/dockern8n/service-{$serviceId}";
        $composeFile = "{$serviceDir}/docker-compose.yml";

        // Check if compose file already exists
        $checkCmd = "[ -f '{$composeFile}' ] && echo 'exists' || echo 'not_found'";
        $result = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $checkCmd));

        if ($result === 'exists') {
            return array("success" => true, "message" => "Compose file already exists");
        }

        // If not exists, recreate it from running container info
        $n8nContainer = "service-{$serviceId}-n8n";

        // Check if containers exist
        $checkN8n = "docker inspect {$n8nContainer} --format='{{.Id}}' 2>/dev/null | head -c 12";
        $n8nId = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $checkN8n));

        if (empty($n8nId)) {
            // Double check PUQ
            $puqInfo = DockerN8N_PUQCompat::detectPUQService($params);
            if ($puqInfo['is_puq']) {
                $checkPuq = "docker inspect {$puqInfo['container_name']} --format='{{.Id}}' 2>/dev/null | head -c 12";
                $n8nId = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $checkPuq));
                if (!empty($n8nId))
                    $n8nContainer = $puqInfo['container_name'];
            }

            if (empty($n8nId)) {
                return array("success" => false, "message" => "Container not found (Checked: {$n8nContainer})");
            }
        }

        // Detect template type from environment variables
        $envCmd = "docker inspect {$n8nContainer} --format='{{range .Config.Env}}{{println .}}{{end}}'";
        $envOutput = dockern8n_ssh_RunCommand($hostname, $username, $password, $envCmd);

        $templateType = "n8n-sqlite"; // Default
        if (strpos($envOutput, "DB_TYPE=postgresdb") !== false) {
            if (strpos($envOutput, "EXECUTIONS_MODE=queue") !== false) {
                $templateType = "n8n-queue";
            } else {
                $templateType = "n8n-pgsql";
            }
        }

        // Get existing credentials from WHMCS
        $cpuLimit = $params["configoption2"] ?? 0;
        $memoryLimit = $params["configoption3"] ?? 0;
        $version = "latest"; // Fallback

        // Get saved credentials and timezone
        $existingCreds = array();
        $timezone = "Asia/Dhaka";
        $customField = Capsule::table("tblcustomfields")->where("relid", $params["packageid"])->where("fieldname", "Service Details")->where("type", "product")->first();
        if ($customField) {
            $value = Capsule::table("tblcustomfieldsvalues")->where("fieldid", $customField->id)->where("relid", $serviceId)->value("value");
            if ($value) {
                $existingCreds = json_decode(html_entity_decode($value), true) ?: array();
                if (!empty($existingCreds["timezone"])) {
                    $timezone = $existingCreds["timezone"];
                }
            }
        }

        // PUQ Compatibility: If encryption key is missing in DB but container exists, try to extract it
        if (empty($existingCreds['encryption_key'])) {
            $puqInfo = DockerN8N_PUQCompat::detectPUQService($params);
            if ($puqInfo['is_puq'] && !empty($puqInfo['encryption_key'])) {
                $existingCreds['encryption_key'] = $puqInfo['encryption_key'];
            }
        }

        // Use GetTemplate (legacy) or GetNginxTemplate (new)
        // Prefer GetNginxTemplate if available as it aligns with CreateAccount
        if (function_exists('dockern8n_GetNginxTemplate')) {
            $templateResult = dockern8n_GetNginxTemplate($templateType, $domain, $serviceId, $cpuLimit, $memoryLimit, $version, '', '', '', $timezone, $existingCreds, $params['userid']);
        } else {
            $templateResult = dockern8n_GetTemplate($templateType, $domain, $serviceId, $cpuLimit, $memoryLimit, $version, '', '', $timezone, $existingCreds);
        }

        $compose = $templateResult["docker_compose"];

        // Create directory and write file
        $mkdirCmd = "mkdir -p {$serviceDir}";
        dockern8n_ssh_RunCommand($hostname, $username, $password, $mkdirCmd);

        $b64Compose = base64_encode($compose);
        $writeCmd = "echo '{$b64Compose}' | base64 -d > {$composeFile}";
        dockern8n_ssh_RunCommand($hostname, $username, $password, $writeCmd);

        return array("success" => true, "message" => "Compose file generated ({$templateType})");
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_start($params)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];

        $info = dockern8n_GetContainerInfo($params);
        $serviceDir = $info['service_dir'];
        $projectName = $info['project_name'];
        $containerName = $info['n8n_container'];

        // PUQ Compatibility: Ensure disk is mounted and permissions are correct
        if ($info['is_puq']) {
            $mountDir = $info['mount_dir'];
            $permCmd = "sudo chmod 755 {$mountDir}; sudo chown -R 1000:1000 {$mountDir}/n8n 2>/dev/null; sudo chown -R 70:70 {$mountDir}/postgres 2>/dev/null; sudo chown -R 999:999 {$mountDir}/redis 2>/dev/null";
            dockern8n_ssh_RunCommand($hostname, $username, $password, "mount -a 2>/dev/null; {$permCmd}; sleep 1");
        } else {
            // Native: ensure bind mount permissions
            $mountDir = $info['mount_dir'];
            $permCmd = "sudo chmod 755 {$mountDir}; sudo chown -R 1000:1000 {$mountDir}/n8n 2>/dev/null; sudo chown -R 70:70 {$mountDir}/postgres 2>/dev/null; sudo chown -R 999:999 {$mountDir}/redis 2>/dev/null";
            dockern8n_ssh_RunCommand($hostname, $username, $password, $permCmd);
        }

        // Start using project name or direct container name
        $cmd = "cd {$serviceDir} && (docker compose -p {$projectName} start || docker-compose -p {$projectName} start || docker start {$containerName}) 2>&1";
        $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmd, 60);

        // Check for ACTUAL errors. bening warnings like 'obsolete' or 'level=warning' should be ignored
        if (stripos($output, 'error') !== false || stripos($output, 'failed') !== false) {
            // If we see 'up to date', 'started', 'running', it's actually success
            if (stripos($output, 'started') === false && stripos($output, 'running') === false && stripos($output, 'done') === false) {
                // Final fallback if start failed: up -d
                $cmdUp = "cd {$serviceDir} && (docker compose -p {$projectName} up -d || docker-compose -p {$projectName} up -d) 2>&1";
                $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdUp, 120);
            }
        }

        logActivity("DockerN8N Start - Service ID: " . $params['serviceid'] . " Result: " . trim($output));
        
        if (stripos($output, 'error') !== false && stripos($output, 'started') === false && stripos($output, 'running') === false && stripos($output, 'done') === false) {
             return "Start failed: " . substr(strip_tags($output), 0, 150);
        }

        dockern8n_LogActivity($params, "service_started", "N8N service stack started");
        return "success";
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function dockern8n_restart($params)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];

        $info = dockern8n_GetContainerInfo($params);
        $serviceDir = $info['service_dir'];
        $projectName = $info['project_name'];
        $containerName = $info['n8n_container'];

        // Restart using project name or direct container name
        $cmd = "cd {$serviceDir} && (docker compose -p {$projectName} restart || docker-compose -p {$projectName} restart || docker restart {$containerName}) 2>&1";
        $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmd, 60);

        logActivity("DockerN8N Restart - Service ID: " . $params['serviceid'] . " Result: " . trim($output));

        if (stripos($output, 'Error') !== false && stripos($output, 'Restarted') === false) {
             return "Restart failed: " . substr(strip_tags($output), 0, 150);
        }

        dockern8n_LogActivity($params, "service_restarted", "N8N service stack restarted");
        return "success";
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function dockern8n_stop($params)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];

        $info = dockern8n_GetContainerInfo($params);
        $serviceDir = $info['service_dir'];
        $projectName = $info['project_name'];
        $containerName = $info['n8n_container'];

        $cmd = "cd {$serviceDir} && (docker compose -p {$projectName} stop || docker-compose -p {$projectName} stop || docker stop {$containerName}) 2>&1";
        $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmd, 60);

        logActivity("DockerN8N Stop - Service ID: " . $params['serviceid'] . " Result: " . trim($output));

        if (stripos($output, 'Error') !== false && stripos($output, 'Stopped') === false) {
             return "Stop failed: " . substr(strip_tags($output), 0, 150);
        }

        dockern8n_LogActivity($params, "service_stopped", "N8N service stack stopped");
        return "success";
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function dockern8n_DeleteBackup($params)
{
    try {
        $hostname = $params["serverhostname"] ? $params["serverhostname"] : $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $serviceId = $params["serviceid"];
        $filename = $_REQUEST["filename"] ?? "";

        // Critical Security: Prevent Path Traversal
        $filename = basename($filename);

        // Validate filename structure to ensure it is one of our backups
        if (empty($filename) || !preg_match('/^backup_.*\.tar\.gz$/', $filename)) {
            return array("success" => false, "message" => "Invalid backup filename");
        }

        // CORRECTED PATH: Use the central backup directory
        $backupDir = "/var/www/dockern8n/backups/service-{$serviceId}";

        $cmd = "rm -f {$backupDir}/{$filename}";
        dockern8n_ssh_RunCommand($hostname, $username, $password, $cmd);

        dockern8n_LogActivity($params, "delete_backup", "Deleted backup: {$filename}");

        return array("success" => true, "message" => "Backup deleted successfully");
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}


function dockern8n_AdminStart($params)
{
    $res = dockern8n_start($params);
    return ($res === 'success') ? 'success' : 'Error: ' . $res;
}

function dockern8n_AdminStop($params)
{
    $res = dockern8n_stop($params);
    return ($res === 'success') ? 'success' : 'Error: ' . $res;
}

function dockern8n_AdminMount($params)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $sshUsername = $params["serverusername"];
        $sshPassword = $params["serverpassword"];
        $domain = $params["domain"];

        $mountCmd = "sudo mount /mnt/{$domain} 2>&1 || sudo mount -a 2>&1";
        $output = dockern8n_ssh_RunCommand($hostname, $sshUsername, $sshPassword, $mountCmd);

        if (strpos($output, 'Error') !== false || strpos($output, 'failed') !== false) {
            return "Mount failed: " . $output;
        }
        return "success";
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function dockern8n_AdminUnmount($params)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $sshUsername = $params["serverusername"];
        $sshPassword = $params["serverpassword"];
        $domain = $params["domain"];

        $umountCmd = "sudo umount -l /mnt/{$domain} 2>&1";
        $output = dockern8n_ssh_RunCommand($hostname, $sshUsername, $sshPassword, $umountCmd);

        return "success";
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function dockern8n_ClientAreaCustomButtonArray()
{
    return array("Start" => "start", "Stop" => "stop");
}

function dockern8n_ClientArea($params)
{
    try {
        $hostname = $params["serverhostname"] ? $params["serverhostname"] : $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $serviceId = $params["serviceid"];
        $serviceStatus = "unknown";
        $serviceDetails = array("domain" => $params["domain"] ?: "Not configured", "version" => "latest", "uuid" => "service-{$serviceId}");
        $customField = Capsule::table("tblcustomfields")->where("relid", $params["packageid"])->where("fieldname", "Service Details")->where("type", "product")->first();
        if ($customField) {
            $value = Capsule::table("tblcustomfieldsvalues")->where("fieldid", $customField->id)->where("relid", $serviceId)->value("value");
            if ($value) {
                $decoded = json_decode(html_entity_decode($value), true);
                if ($decoded && is_array($decoded)) {
                    $serviceDetails = array_merge($serviceDetails, $decoded);
                }
            }
        }
        $uuid = $serviceDetails["uuid"] ?? "service-{$serviceId}";
        try {
            $serviceStatus = dockern8n_GetServiceStatus($hostname, $username, $password, $uuid);
        } catch (Exception $e) {
            $serviceStatus = "error";
        }
        // error_log("DockerN8N ClientArea - Service ID: {$serviceId}, Status: {$serviceStatus}");
// error_log("DockerN8N ClientArea - Service Details: " . json_encode($serviceDetails));

        // Fetch product subdomain configuration from WHMCS
        $product = Capsule::table('tblproducts')->where('id', $params['packageid'])->first();
        $subdomainServer = '';
        $hasSubdomain = false;

        if ($product && !empty($product->subdomain)) {
            $subdomainServer = $product->subdomain;
            $hasSubdomain = true;
        }

        return array("templatefile" => "clientarea", "vars" => array("serviceDetails" => $serviceDetails, "serviceStatus" => $serviceStatus, "serviceid" => $serviceId, "domain" => $params["domain"], "hostname" => $hostname, "cpuLimit" => $params["configoption2"] ?: "1", "memoryLimit" => $params["configoption3"] ?: "1G", "diskLimit" => $params["configoption5"] ?: "5G", "service_version" => $serviceDetails["version"] ?? "latest", "custom_api_url" => "modules/servers/dockern8n/ajax.php", "subdomain_server" => $subdomainServer, "has_subdomain" => $hasSubdomain));
    } catch (Exception $e) {
        // error_log("DockerN8N ClientArea Error: " . $e->getMessage());
        return array("templatefile" => "clientarea", "vars" => array("error" => $e->getMessage(), "serviceDetails" => array("domain" => "Error", "version" => "Unknown"), "serviceStatus" => "error", "serviceid" => $params["serviceid"], "cpuLimit" => "Unknown", "memoryLimit" => "Unknown"));
    }
}

function dockern8n_GetResourceStats($params)
{
    try {
        $hostname = $params["serverhostname"] ? $params["serverhostname"] : $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $serviceId = $params["serviceid"];
        $domain = $params['domain'];

        $info = dockern8n_GetContainerInfo($params);
        $containerName = $info['n8n_container'];

        $cmd = "docker stats {$containerName} --no-stream --format '{{.CPUPerc}}|{{.MemUsage}}|{{.NetIO}}|{{.BlockIO}}'";
        $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmd);
        if (empty($output)) {
            return array("success" => false, "message" => "Container not running");
        }
        $parts = explode("|", trim($output));
        $diskUsage = dockern8n_GetDiskUsage($params);
        return array("success" => true, "cpu" => $parts[0] ?? "0%", "memory" => $parts[1] ?? "0B / 0B", "network" => $parts[2] ?? "0B / 0B", "disk" => $parts[3] ?? "0B / 0B", "storage" => $diskUsage);
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_GetDiskUsage($params)
{
    try {
        $hostname = $params["serverhostname"] ? $params["serverhostname"] : $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $serviceId = $params["serviceid"];

        // Detect PUQ vs our service
        $puqInfo = DockerN8N_PUQCompat::detectPUQService($params);

        if ($puqInfo['is_puq']) {
            // PUQ uses mount directory, not named volumes
            $mountDir = $puqInfo['mount_dir'];
            $cmdDu = "du -sh {$mountDir} 2>/dev/null | cut -f1";
            $diskUsed = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdDu));

            $diskLimit = isset($params["configoption5"]) ? $params["configoption5"] : "5G";
            $totalBytes = dockern8n_parseDiskSize($diskUsed ?: "0");
            $limitBytes = dockern8n_parseDiskSize($diskLimit);
            $percentUsed = $limitBytes > 0 ? round($totalBytes / $limitBytes * 100, 1) : 0;

            return array("used" => $diskUsed ?: "0B", "limit" => $diskLimit, "percent" => $percentUsed, "n8n_data" => $diskUsed ?: "0B", "postgres_data" => "0B");
        }

        // Our module - use volume names or bind mount
        $projectName = $info['project_name'];
        $mountDir = $info['mount_dir'];

        // Try getting size from docker volume first
        $volumeName = $projectName . "_n8n_data";
        $cmdSize = "docker system df -v 2>/dev/null | grep '" . $volumeName . "' | awk '{print $3}'";
        $sizeStr = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdSize));

        if (empty($sizeStr) || $sizeStr == "0B") {
            // Fallback to du on the mount directory
            $cmdDu = "[ -d " . $mountDir . " ] && du -sh " . $mountDir . " 2>/dev/null | cut -f1 || echo '0B'";
            $sizeStr = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdDu));
        }

        $diskLimitRaw = isset($params["configoption5"]) ? $params["configoption5"] : "10";
        $diskLimit = is_numeric($diskLimitRaw) ? $diskLimitRaw . "G" : $diskLimitRaw;
        
        $usedBytes = dockern8n_parseDiskSize($sizeStr ?: "0");
        $limitBytes = dockern8n_parseDiskSize($diskLimit);
        $percentUsed = $limitBytes > 0 ? round($usedBytes / $limitBytes * 100, 1) : 0;

        return array("used" => $sizeStr ?: "0B", "limit" => $diskLimit, "percent" => $percentUsed, "n8n_data" => $sizeStr ?: "0B", "postgres_data" => "0B");
    } catch (Exception $e) {
        return array("used" => "0B", "limit" => "Unknown", "percent" => 0, "error" => $e->getMessage());
    }
}

/**
 * Usage Update - Called by WHMCS cron to collect disk usage stats
 */
function dockern8n_UsageUpdate($params)
{
    try {
        $serverid = $params['serverid'];

        $services = Capsule::table('tblhosting')
            ->where('server', $serverid)
            ->whereIn('domainstatus', ['Active', 'Suspended'])
            ->get();

        foreach ($services as $service) {
            $usage = dockern8n_GetDiskUsage([
                'serviceid' => $service->id,
                'domain' => $service->domain,
                'packageid' => $service->packageid,
                'serverhostname' => $params['serverhostname'],
                'serverip' => $params['serverip'],
                'serverusername' => $params['serverusername'],
                'serverpassword' => $params['serverpassword'],
                'configoption5' => Capsule::table('tblproducts')->where('id', $service->packageid)->value('configoption5')
            ]);
            
            // Convert to MB for WHMCS
            $usedBytes = dockern8n_parseDiskSize($usage['used']);
            $usedMB = round($usedBytes / (1024 * 1024), 2);
            
            $limitBytes = dockern8n_parseDiskSize($usage['limit']);
            $limitMB = round($limitBytes / (1024 * 1024), 2);

            Capsule::table('tblhosting')->where('id', $service->id)->update([
                'diskusage' => $usedMB,
                'disklimit' => $limitMB,
                'lastupdate' => date('Y-m-d H:i:s'),
            ]);
        }
    } catch (Exception $e) {
        logActivity("DockerN8N Usage Update Failed: " . $e->getMessage());
    }
}

function dockern8n_parseDiskSize($size)
{
    $size = strtoupper(trim($size));
    $units = array("B" => 1, "K" => 1024, "KB" => 1024, "M" => 1048576, "MB" => 1048576, "G" => 1073741824, "GB" => 1073741824, "T" => 1099511627776, "TB" => 1099511627776);
    if (preg_match("/^([\\d.]+)\\s*([A-Z]*)$/", $size, $matches)) {
        $value = floatval($matches[1]);
        $unit = $matches[2] ?: "B";
        return (int) ($value * ($units[$unit] ?? 1));
    }
    return 0;
}

function dockern8n_formatBytes($bytes, $precision = 2)
{
    $units = array("B", "KB", "MB", "GB", "TB");
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . " " . $units[$pow];
}

function dockern8n_GetLogs($params, $lines = 100)
{
    try {
        $hostname = $params["serverhostname"] ? $params["serverhostname"] : $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $serviceId = $params["serviceid"];

        $info = dockern8n_GetContainerInfo($params);
        $containerName = $info['n8n_container'];

        $cmd = "docker logs {$containerName} --tail {$lines} 2>&1";
        $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmd);
        return array("success" => true, "logs" => $output);
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

// Implement standard WHMCS ChangePassword function for Admin Area
function dockern8n_ChangePassword($params)
{
    // logModuleCall("dockern8n", "ChangePassword_START", $params, "Password change process initiated.", "");
    try {
        $serviceId = $params["serviceid"];
        $hostname = $params["serverhostname"] ? $params["serverhostname"] : $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $newPassword = $params["password"];

        $clientEmail = $params["clientsdetails"]["email"] ?? "admin@example.com";
        $bcryptHash = password_hash($newPassword, PASSWORD_BCRYPT, array("cost" => 10));

        // --- 1. DYNAMIC CONTAINER DISCOVERY ---

        $puqInfo = DockerN8N_PUQCompat::detectPUQService($params);
        $n8nContainer = "service-{$serviceId}-n8n";

        if ($puqInfo['is_puq'] && !empty($puqInfo['container_name'])) {
            $n8nContainer = $puqInfo['container_name'];
        }

        // --- HELPER FUNCTIONS ---

        $runHostSqlite = function ($dbPath, $sql) use ($hostname, $username, $password) {
            $sqlEscaped = str_replace("'", "'\\''", $sql);
            $cmd = "sudo sqlite3 {$dbPath} '{$sqlEscaped}' 2>&1";
            return dockern8n_ssh_RunCommand($hostname, $username, $password, $cmd);
        };

        $runEphemeralSqlite = function ($volumeName, $sql) use ($hostname, $username, $password) {
            // Spin up a tiny alpine container, mount the volume, install sqlite, run sql, and die.
            $sqlEscaped = str_replace("'", "'\\''", $sql);

            // CRITICAL FIX: Escape $ because we are wrapping in sh -c "..."
            // Otherwise bash interprets $2y$ (bcrypt) as variables and erases the password
            $sqlShellSafe = str_replace('$', '\\$', $sqlEscaped);

            $cmd = "docker run --rm -v {$volumeName}:/n8n_data alpine sh -c " . escapeshellarg("apk add --no-cache sqlite >/dev/null 2>&1 && sqlite3 /n8n_data/database.sqlite '$sqlShellSafe'") . " 2>&1";
            return dockern8n_ssh_RunCommand($hostname, $username, $password, $cmd);
        };

        $tryUpdateUser = function ($executor, $target) use ($bcryptHash) {
            // Try to find email
            // We use regex to extract the email because Docker might output "Unable to find image..." or other noise
            $rawOutput = $executor($target, "SELECT email FROM \"user\" ORDER BY \"createdAt\" ASC LIMIT 1;");

            $email = "";
            // Regex for email
            if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $rawOutput, $matches)) {
                $email = $matches[0];
            }

            // Check for generic SQL errors or empty result
            if (empty($email) || stripos($rawOutput, "Error") !== false || stripos($rawOutput, "no such table") !== false) {
                // Try unquoted table 
                $rawOutput2 = $executor($target, "SELECT email FROM user ORDER BY createdAt ASC LIMIT 1;");
                if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $rawOutput2, $matches)) {
                    $email = $matches[0];
                }
            }

            if (!empty($email) && stripos($email, "Error") === false && stripos($email, "no such table") === false) {
                // CRITICAL FIX: Run UPDATE and CHECK in SAME COMMAND
                // sqlite3 "changes()" is session-scoped. If we run them separately, the second session sees 0 changes.
                $combinedSql = "UPDATE \"user\" SET password = '{$bcryptHash}' WHERE email = '{$email}'; SELECT changes();";

                $start = microtime(true);
                $result = trim($executor($target, $combinedSql));

                // Extract the last number from output (ignoring potential docker noise)
                $changes = "0";
                if (preg_match('/(\d+)\s*$/', $result, $m)) {
                    $changes = $m[1];
                }

                if ($changes === "1")
                    return ['success' => true, 'email' => $email];
                return ['success' => false, 'error' => "Update 0 rows for {$email}. Output: {$result}"];
            }
            return ['success' => false, 'error' => "User not found. Output: {$rawOutput}"];
        };

        // SQLite Executor using sidecar (Alpine + SQLite) if host tools missing
        $runSidecarSqlite = function($volumeOrPath, $sql) use ($hostname, $username, $password) {
             $isPath = (strpos($volumeOrPath, '/') !== false);
             $mountSource = $isPath ? $volumeOrPath : $volumeOrPath;
             $dbFile = "database.sqlite";
             
             // If $volumeOrPath is a path, we mount it directly
             $mountParam = "-v \"{$volumeOrPath}\":/data";
             $sqlSafe = str_replace("'", "'\\''", $sql);
             
             $cmd = "docker run --rm {$mountParam} alpine sh -c \"apk add --no-cache sqlite >/dev/null 2>&1 && sqlite3 /data/{$dbFile} '{$sqlSafe}'\"";
             return dockern8n_ssh_RunCommand($hostname, $username, $password, $cmd);
        };

        // --- 0. POSTGRES DISCOVERY (PROACTIVE) ---
        $info = dockern8n_GetContainerInfo($params);
        $cleanDomain = preg_replace("#^https?://#", '', $params['domain'] ?? '');
        $projectFilter = $info['project_name'] ?? $serviceId;
        
        $findPgCmd = "docker ps --format '{{.Names}}' | grep -E 'postgres|db' | grep -E '{$serviceId}|{$cleanDomain}|{$projectFilter}' | head -n 1";
        $pgContainer = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $findPgCmd));
        
        if (empty($pgContainer)) {
            $inspectPg = "docker inspect {$n8nContainer} --format '{{range .NetworkSettings.Networks}}{{.Links}}{{end}}' 2>/dev/null";
            $links = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $inspectPg));
            if (!empty($links) && strpos($links, ':') !== false) {
                $pgContainer = explode(':', $links)[0];
            }
        }

        $errors = [];
        $dbUpdated = false;

        // --- STRATEGY A: POSTGRES (If Detected) ---
        if (!empty($pgContainer)) {
            // Use robust quoting for Postgres: "user" table and "createdAt" column
            // We pass the SQL via -c to avoid echo/pipe escaping issues
            $findEmailSql = 'SELECT "email" FROM "user" ORDER BY "createdAt" ASC LIMIT 1;';
            $findEmailCmd = "docker exec -i {$pgContainer} psql -U n8n -d n8n -t -A -c " . escapeshellarg($findEmailSql) . " 2>&1";
            $pgEmail = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $findEmailCmd));

            if (!empty($pgEmail) && strpos($pgEmail, '@') !== false) {
                $pgEmailSql = str_replace("'", "''", $pgEmail);
                $bcryptHashSql = str_replace("'", "''", $bcryptHash);

                $sqlInner = "UPDATE \"user\" SET \"password\" = '{$bcryptHashSql}' WHERE \"email\" = '{$pgEmailSql}';";
                $updateCmd = "docker exec -i {$pgContainer} psql -U n8n -d n8n -c " . escapeshellarg($sqlInner) . " 2>&1";
                $pgRes = dockern8n_ssh_RunCommand($hostname, $username, $password, $updateCmd);

                if (strpos($pgRes, "UPDATE 1") !== false) {
                    $dbUpdated = true;
                } else {
                    $errors[] = "Postgres Update ({$pgContainer}): " . $pgRes;
                }
            } else {
                 $errors[] = "Postgres ({$pgContainer}) query failed or user not found: " . trim($pgEmail);
            }
        }

        // --- STRATEGY B: SQLITE MOUNT INSPECTION (If Postgres failed or not found) ---
        if (!$dbUpdated) {
            // 1. Inspect n8n container to find /home/node/.n8n mount
            $inspectCmd = "docker inspect -f '{{range .Mounts}}{{if eq .Destination \"/home/node/.n8n\"}}{{.Type}}|{{.Source}}{{end}}{{end}}' {$n8nContainer}";
            $mountInfo = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $inspectCmd));

            // logModuleCall('dockern8n', 'ChangePassword_Inspect', ['container' => $n8nContainer, 'mount' => $mountInfo], 'Mount Info', '');

            if (!empty($mountInfo)) {
                $parts = explode("|", $mountInfo);
                $type = $parts[0] ?? '';
                $source = $parts[1] ?? '';

                if ($type === 'bind') {
                    // BIND MOUNT -> File is on Host -> Use Host SQLite
                    $dbPath = $source . "/database.sqlite";
                    // logModuleCall('dockern8n', 'ChangePassword_Strategy', ['type' => 'bind', 'path' => $dbPath], 'Using Host SQLite', '');

                    $res = $tryUpdateUser($runHostSqlite, $dbPath);
                    if ($res['success']) {
                        $dbUpdated = true;
                    } else {
                        // Host SQLite failed (likely missing tool), try Sidecar
                        $res2 = $tryUpdateUser($runSidecarSqlite, $source);
                        if ($res2['success']) {
                            $dbUpdated = true;
                        } else {
                             $errors[] = "Host/Sidecar SQLite: " . $res2['error'];
                        }
                    }

                } elseif ($type === 'volume') {
                    // VOLUME -> Data in Docker Volume -> Use Ephemeral Container
                    $res = $tryUpdateUser($runEphemeralSqlite, $source);
                    if ($res['success']) {
                        $dbUpdated = true;
                    } else {
                        $errors[] = "Ephemeral SQLite: " . $res['error'];
                    }
                }
            }
        }

        if ($dbUpdated) {
            dockern8n_ssh_RunCommand($hostname, $username, $password, "docker restart {$n8nContainer}");
            return "success";
        }

        // 2. Final CLI Fallback - REMOVED DESTRUCTIVE RESET
        // For Password Change, we must NOT use user-management:reset as it wipes users and roles.
        // We only provide a final attempt via reset-owner if available.
        $safeEmail = escapeshellarg($clientEmail);
        $safePassword = escapeshellarg($newPassword);
        $cmd_reset_owner = "docker exec -i {$n8nContainer} n8n user-management:reset-owner --email={$safeEmail} --password={$safePassword} 2>&1";
        $cliRes_reset_owner = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmd_reset_owner);

        if (stripos($cliRes_reset_owner, "successfully") !== false) {
            return "success";
        }

        $errors[] = "CLI (reset-owner): " . $cliRes_reset_owner;
        return "Password change failed: " . implode(" | ", $errors);

    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function dockern8n_ResetPassword($params)
{
    // Wrap the ChangePassword logic since it's now robust
    $newPassword = dockern8n_GeneratePassword();
    $params["password"] = $newPassword; // Inject for ChangePassword

    $result = dockern8n_ChangePassword($params);

    if ($result === "success") {
        Capsule::table("tblhosting")->where("id", $params["serviceid"])->update(array("password" => encrypt($newPassword)));
        return array("success" => true, "password" => $newPassword);
    } else {
        return array("success" => false, "message" => $result);
    }
}

// Function dockern8n_ChangeVersion removed as per user request


function dockern8n_CreateBackup($params)
{
    try {
        $hostname = $params["serverhostname"] ? $params["serverhostname"] : $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $serviceId = $params["serviceid"];

        $info = dockern8n_GetContainerInfo($params);
        $serviceDir = $info['service_dir'];
        $projectName = $info['project_name'];
        
        $backupDir = "/var/www/dockern8n/backups/service-{$serviceId}";
        $timestamp = date("Y-m-d_H-i-s");
        $backupFile = "backup_{$timestamp}.tar.gz";
        $backupPath = "{$backupDir}/{$backupFile}";

        // Determine data path dynamically
        $dataPath = $info['mount_dir'];
        
        // If mount_dir is empty or not found, fallback to named volume if not PUQ
        if (!$info['is_puq'] && (empty($dataPath) || strpos($dataPath, '/mnt/') === false)) {
            $volumeName = $projectName . "_n8n-data";
            $dataPath = "/var/lib/docker/volumes/{$volumeName}/_data";
        }

        $asyncCmd = "mkdir -p {$backupDir} && cd {$serviceDir} && " .
            "(docker compose -p {$projectName} stop || docker-compose -p {$projectName} stop) && " .
            "tar -czf {$backupPath} -C {$dataPath} . && " .
            "(docker compose -p {$projectName} up -d || docker-compose -p {$projectName} up -d) 2>&1";

        // Run synchronously
        $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $asyncCmd);

        dockern8n_LogActivity($params, "backup_created", "Backup created: {$backupFile}");

        return array(
            "success" => true,
            "message" => "Backup created successfully!",
            "filename" => $backupFile,
            "timestamp" => $timestamp
        );
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_ListBackups($params)
{
    try {
        $hostname = $params["serverhostname"] ? $params["serverhostname"] : $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $serviceId = $params["serviceid"];
        $backupDir = "/var/www/dockern8n/backups/service-{$serviceId}";
        $cmdCheck = "[ -d {$backupDir} ] && echo 'exists' || echo 'missing'";
        $checkResult = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdCheck));
        if ($checkResult !== "exists") {
            return array("success" => true, "backups" => array());
        }
        $cmd = "ls -lh {$backupDir}/backup_*.tar.gz 2>/dev/null | awk '{print $9\"|\"$5\"|\"$6\" \"$7\" \"$8}' || echo ''";
        $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmd);
        $backups = array();
        if (!empty(trim($output))) {
            $lines = explode("
", trim($output));
            foreach ($lines as $line) {
                if (empty($line)) {
                    continue;
                }
                $parts = explode("|", $line);
                if (count($parts) >= 3) {
                    $filename = basename($parts[0]);
                    $backups[] = array("filename" => $filename, "size" => $parts[1], "date" => $parts[2]);
                }
            }
        }
        usort($backups, function ($a, $b) {
            return strcmp($b["filename"], $a["filename"]);
        });
        return array("success" => true, "backups" => $backups);
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_RestoreBackup($params, $backupFilename)
{
    try {
        $hostname = $params["serverhostname"] ? $params["serverhostname"] : $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $serviceId = $params["serviceid"];

        if (empty($backupFilename)) {
            return array("success" => false, "message" => "Backup filename is required");
        }

        // Sanitize filename
        $backupFilename = basename($backupFilename);
        if (!preg_match('/^backup_.*\.tar\.gz$/i', $backupFilename)) {
            return array("success" => false, "message" => "Invalid backup filename");
        }

        $info = dockern8n_GetContainerInfo($params);
        $serviceDir = $info['service_dir'];
        $projectName = $info['project_name'];

        $backupDir = "/var/www/dockern8n/backups/service-{$serviceId}";
        $backupPath = "{$backupDir}/{$backupFilename}";

        // Determine data path dynamically
        $dataPath = $info['mount_dir'];
        if (!$info['is_puq'] && (empty($dataPath) || strpos($dataPath, '/mnt/') === false)) {
            $volumeName = $projectName . "_n8n-data";
            $dataPath = "/var/lib/docker/volumes/{$volumeName}/_data";
        }

        $asyncCmd = "if [ ! -f " . $backupPath . " ]; then echo 'ERROR: Backup file not found' && exit 1; fi && " .
            "cd " . $serviceDir . " && " .
            "(docker compose -p " . $projectName . " stop || docker-compose -p " . $projectName . " stop) && " .
            "rm -rf " . $dataPath . "/[^.]* " . $dataPath . "/.[!.]* 2>/dev/null; " .
            "tar -xzf " . $backupPath . " -C " . $dataPath . " && " .
            "(docker compose -p " . $projectName . " up -d || docker-compose -p " . $projectName . " up -d) 2>&1";

        // Run synchronously
        $restoreOutput = dockern8n_ssh_RunCommand($hostname, $username, $password, $asyncCmd);

        // Check output for obvious errors
        if (strpos($restoreOutput, 'ERROR:') !== false) {
            return array("success" => false, "message" => "Restore failed: " . $restoreOutput);
        }

        dockern8n_LogActivity($params, "backup_restored", "Backup restored: {$backupFilename}");

        // Clear status cache
        unset($GLOBALS['dockern8n_status_cache']["status_" . $serviceId]);

        return array("success" => true, "message" => "Backup restored successfully!");
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}



function dockern8n_ChangeDomain($params, $newDomain)
{
    try {
        $serviceId = $params["serviceid"];
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];

        // 1. Clean domain input
        $newDomain = preg_replace("#^https?://#", '', $newDomain);
        $newDomain = rtrim($newDomain, "/");

        // Strict validation
        if (empty($newDomain) || !preg_match('/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i', $newDomain)) {
            return array("success" => false, "message" => "Invalid domain name format");
        }

        // 2. Locate service directory via metadata
        $info = dockern8n_GetContainerInfo($params);
        $serviceDir = $info['service_dir'];
        $projectName = $info['project_name'];
        $composeFile = $info['compose_file'];

        if (!$info['found'] && !file_exists($composeFile)) {
            return array("success" => false, "message" => "Could not locate service directory on server. Try Regenerate Config.");
        }

        // 3. Update Compose Content
        $content = dockern8n_ssh_RunCommand($hostname, $username, $password, "cat {$composeFile}");
        if (empty($content) || strpos($content, "services:") === false) {
            return array("success" => false, "message" => "Could not read compose file. Please Regenerate Config.");
        }

        $newContent = $content;
        $vars = ['VIRTUAL_HOST', 'LETSENCRYPT_HOST', 'N8N_HOST', 'N8N_EDITOR_BASE_URL', 'WEBHOOK_URL'];

        foreach ($vars as $v) {
            // This regex handles Key=Val, Key: Val, 'Key=Val', "Key=Val", quotes, etc.
            $pattern = "/({$v}\s*[:=]\s*['\"]?)(https?:\/\/)?([a-z0-9\.-]+)([\/]?['\"]?)/i";
            $newContent = preg_replace_callback($pattern, function ($m) use ($v, $newDomain) {
                $prefix = $m[1]; // Key= or Key: 
                $protocol = $m[2]; // https://
                $suffix = $m[4]; // /" or "

                if ($v === 'WEBHOOK_URL' || $v === 'N8N_EDITOR_BASE_URL') {
                    $val = "https://{$newDomain}";
                    if ($v === 'WEBHOOK_URL')
                        $val .= "/";
                    return "{$prefix}{$val}{$suffix}";
                }
                return "{$prefix}{$newDomain}{$suffix}";
            }, $newContent);
        }

        // Ensure Network Name consistency
        $newContent = preg_replace("/networks:\s*\n\s*-\s*nginx-proxy(_web)?/m", "networks:\n      - nginx-proxy_web", $newContent);
        $newContent = preg_replace("/nginx-proxy(_web)?:\s*\n\s*external:\s*true/m", "nginx-proxy_web:\n    external: true", $newContent);

        // Save Config
        $b64 = base64_encode($newContent);
        dockern8n_ssh_RunCommand($hostname, $username, $password, "echo '{$b64}' | base64 -d > {$composeFile}");

        // 4. Restart Stack
        $cmdApply = "cd {$serviceDir} && (docker compose -p {$projectName} up -d --force-recreate || docker-compose -p {$projectName} up -d --force-recreate) 2>&1";
        $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdApply, 120);

        if (strpos($output, 'Error') !== false && strpos($output, 'Started') === false) {
            return array("success" => false, "message" => "Domain update failed on server: " . substr($output, 0, 150));
        }

        // 5. Update WHMCS and Metadata
        Capsule::table("tblhosting")->where("id", $serviceId)->update(["domain" => "https://{$newDomain}"]);

        $customFieldId = Capsule::table("tblcustomfields")->where("relid", $params["packageid"])->where("fieldname", "Service Details")->value("id");
        if ($customFieldId) {
            $val = Capsule::table("tblcustomfieldsvalues")->where("fieldid", $customFieldId)->where("relid", $serviceId)->value("value");
            if ($val) {
                $details = json_decode(html_entity_decode($val), true);
                $details["domain"] = "https://{$newDomain}";
                Capsule::table("tblcustomfieldsvalues")->where("fieldid", $customFieldId)->where("relid", $serviceId)->update(["value" => json_encode($details)]);
            }
        }

        dockern8n_LogActivity($params, "domain_changed", "Domain changed to {$newDomain}");
        unset($GLOBALS['dockern8n_status_cache']["status_" . $serviceId]);

        return array("success" => true, "message" => "Domain updated to {$newDomain}. Config and Nginx updated successfully.");
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}


// Function dockern8n_GetLatestVersion removed as per user request



function dockern8n_GetWorkflowExports($params)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $serviceId = $params["serviceid"];

        $exportDir = "/var/www/dockern8n/exports/service-{$serviceId}";

        // Ensure directory exists
        $cmdMkdir = "mkdir -p {$exportDir}";
        dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdMkdir);

        // List files
        $cmd = "cd {$exportDir} && ls -1 *.json 2>/dev/null";
        $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmd);

        $exports = [];
        if (!empty($output) && strpos($output, "No such file") === false) {
            $files = explode("\n", trim($output));
            foreach ($files as $file) {
                $file = trim($file);
                if (empty($file))
                    continue;

                // Get details
                $statCmd = "cd {$exportDir} && stat -c '%s %y' {$file}";
                $statOut = dockern8n_ssh_RunCommand($hostname, $username, $password, $statCmd);

                $size = "0 B";
                $date = "";

                if (preg_match('/^(\d+)\s+(.+)$/', trim($statOut), $m)) {
                    $bytes = $m[1];
                    $date = substr($m[2], 0, 19);

                    if ($bytes < 1024)
                        $size = $bytes . " B";
                    elseif ($bytes < 1048576)
                        $size = round($bytes / 1024, 2) . " KB";
                    else
                        $size = round($bytes / 1048576, 2) . " MB";
                }

                $exports[] = array(
                    "filename" => $file,
                    "size" => $size,
                    "date" => $date
                );
            }
        }

        return array("success" => true, "exports" => $exports);

    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_ExportWorkflows($params, $includeCredentials = false)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $serviceId = $params["serviceid"];

        // Detect PUQ vs our service
        $puqInfo = DockerN8N_PUQCompat::detectPUQService($params);
        $containerName = $puqInfo['is_puq'] ? $puqInfo['container_name'] : "service-{$serviceId}-n8n";

        $exportDir = "/var/www/dockern8n/exports/service-{$serviceId}";
        $logFile = "/var/www/dockern8n/logs/export-{$serviceId}.log";
        $timestamp = date("Y-m-d_H-i-s");
        $credSuffix = $includeCredentials ? "_with_creds" : '';
        $exportFile = "workflows_{$timestamp}{$credSuffix}.json";
        $exportPath = "{$exportDir}/{$exportFile}";

        // Build async export script - all in one command
        if ($includeCredentials) {
            // Export workflows + credentials, combine them
            // Use subshells to ensure we don't fail the whole command if one part fails, 
            // but we check existence before catting
            $asyncCmd = "mkdir -p {$exportDir} && " .
                "docker exec {$containerName} n8n export:workflow --all --output=/tmp/workflows_exp.json >/dev/null 2>&1 && " .
                "docker exec {$containerName} n8n export:credentials --all --output=/tmp/credentials_exp.json >/dev/null 2>&1 && " .
                "docker exec {$containerName} sh -c '" .
                "echo -n \"{\\\"workflows\\\":\"; " .
                "[ -f /tmp/workflows_exp.json ] && cat /tmp/workflows_exp.json || echo -n \"[]\"; " .
                "echo -n \",\\\"credentials\\\":\"; " .
                "[ -f /tmp/credentials_exp.json ] && cat /tmp/credentials_exp.json || echo -n \"[]\"; " .
                "echo -n \"}\"' > {$exportPath} && " .
                "docker exec {$containerName} rm -f /tmp/workflows_exp.json /tmp/credentials_exp.json";
        } else {
            // Just export workflows
            $asyncCmd = "mkdir -p {$exportDir} && " .
                "docker exec {$containerName} n8n export:workflow --all --output=/tmp/workflows_exp.json >/dev/null 2>&1 && " .
                "docker cp {$containerName}:/tmp/workflows_exp.json {$exportPath} && " .
                "docker exec {$containerName} rm -f /tmp/workflows_exp.json";
        }

        // Run synchronously
        $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $asyncCmd);

        // VERIFICATION: Check if the file was actually created and has a reasonable size
        $verifyCmd = "[ -f {$exportPath} ] && stat -c%s {$exportPath} || echo '0'";
        $fileSize = (int) trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $verifyCmd));

        if ($fileSize < 10) { // JSON should be at least some bytes
            // Check for errors in the original command output
            $errorMsg = "Export failed: File not created or empty.";
            if (!empty($output)) {
                $errorMsg .= " Command output: " . substr($output, 0, 150);
            }
            return array("success" => false, "message" => $errorMsg);
        }

        dockern8n_LogActivity($params, "workflow_backup", "Workflow backup created: {$exportFile}" . ($includeCredentials ? " (with credentials)" : ""));

        return array(
            "success" => true,
            "message" => "Export completed successfully!",
            "filename" => $exportFile,
            "includesCredentials" => $includeCredentials,
            "size" => $fileSize
        );
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_ImportWorkflows($params, $workflowData, $defaultName = "Imported Workflow")
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $serviceId = $params["serviceid"];

        // Detect PUQ vs our container
        $puqInfo = DockerN8N_PUQCompat::detectPUQService($params);
        $containerName = $puqInfo['is_puq'] ? $puqInfo['container_name'] : "service-{$serviceId}-n8n";
        if (empty($workflowData)) {
            return array("success" => false, "message" => "No workflow data provided");
        }
        $jsonData = json_decode($workflowData, true);
        if ($jsonData === null) {
            return array("success" => false, "message" => "Invalid JSON file");
        }
        $sanitizeWorkflow = function ($workflow) use ($defaultName) {
            if (is_array($workflow)) {
                // Force set active to false
                $workflow["active"] = false;
                
                // CRITICAL FIX: Unconditionally remove ID and versionId 
                // This allows Postgres/n8n to generate new ones and avoids "violates not-null constraint" or "already exists"
                unset($workflow["id"]);
                unset($workflow["versionId"]);
                unset($workflow["createdAt"]);
                unset($workflow["updatedAt"]);

                if (empty($workflow["name"])) {
                    $workflow["name"] = $defaultName . " " . date("Y-m-d H:i");
                }
                
                if (!isset($workflow["nodes"])) {
                    $workflow["nodes"] = array();
                }
                
                if (!isset($workflow["connections"])) {
                    $workflow["connections"] = array();
                }
            }
            return $workflow;
        };
        $importResults = array();
        $hasCredentials = false;
        if (isset($jsonData["workflows"]) && is_array($jsonData["workflows"])) {
            foreach ($jsonData["workflows"] as $k => $wf) {
                $jsonData["workflows"][$k] = $sanitizeWorkflow($wf);
            }
            $workflowsJson = json_encode($jsonData["workflows"]);
            $hasCredentials = isset($jsonData["credentials"]) && !empty($jsonData["credentials"]);
            $b64Workflows = base64_encode($workflowsJson);
            $cmdSaveWorkflows = "echo '{$b64Workflows}' | base64 -d > /tmp/workflows_import.json";
            dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdSaveWorkflows);
            $cmdCopyWorkflows = "docker cp /tmp/workflows_import.json {$containerName}:/tmp/workflows.json 2>&1";
            dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdCopyWorkflows);
            // Fix permissions
            dockern8n_ssh_RunCommand($hostname, $username, $password, "docker exec -u 0 {$containerName} chown node:node /tmp/workflows.json 2>&1");
            $cmdImportWorkflows = "docker exec {$containerName} n8n import:workflow --input=/tmp/workflows.json 2>&1";
            $workflowOutput = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdImportWorkflows);
            $importResults[] = "Workflows: " . trim($workflowOutput);
            if ($hasCredentials) {
                $credentialsJson = json_encode($jsonData["credentials"]);
                $b64Creds = base64_encode($credentialsJson);
                $cmdSaveCreds = "echo '{$b64Creds}' | base64 -d > /tmp/credentials_import.json";
                dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdSaveCreds);
                $cmdCopyCreds = "docker cp /tmp/credentials_import.json {$containerName}:/tmp/credentials.json 2>&1";
                dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdCopyCreds);

                // Fix permissions
                dockern8n_ssh_RunCommand($hostname, $username, $password, "docker exec -u 0 {$containerName} chown node:node /tmp/credentials.json 2>&1");

                $cmdImportCreds = "docker exec {$containerName} n8n import:credentials --input=/tmp/credentials.json 2>&1";
                $credsOutput = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdImportCreds);

                // Sanitize output
                $cleanCredsOut = strip_tags($credsOutput);
                if (strlen($cleanCredsOut) > 200)
                    $cleanCredsOut = substr($cleanCredsOut, 0, 200) . "... (truncated)";
                $importResults[] = "Credentials: " . $cleanCredsOut;

                dockern8n_ssh_RunCommand($hostname, $username, $password, "rm -f /tmp/credentials_import.json");
                dockern8n_ssh_RunCommand($hostname, $username, $password, "docker exec {$containerName} rm -f /tmp/credentials.json 2>/dev/null");
            }
            dockern8n_ssh_RunCommand($hostname, $username, $password, "rm -f /tmp/workflows_import.json");
            dockern8n_ssh_RunCommand($hostname, $username, $password, "docker exec {$containerName} rm -f /tmp/workflows.json 2>/dev/null");
        } else {
            // ... (Single workflow or legacy array logic)
            if (isset($jsonData["nodes"]) && isset($jsonData["connections"])) {
                $jsonData = array($sanitizeWorkflow($jsonData));
            } elseif (is_array($jsonData)) {
                foreach ($jsonData as $k => $wf) {
                    $jsonData[$k] = $sanitizeWorkflow($wf);
                }
            }

            $workflowData = json_encode($jsonData);
            $b64Data = base64_encode($workflowData);
            $cmdUpload = "echo '{$b64Data}' | base64 -d > /tmp/workflow_import.json";
            dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdUpload);
            $cmdCopy = "docker cp /tmp/workflow_import.json {$containerName}:/tmp/import.json 2>&1";
            dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdCopy);

            // Fix permissions
            dockern8n_ssh_RunCommand($hostname, $username, $password, "docker exec -u 0 {$containerName} chown node:node /tmp/import.json 2>&1");

            $cmdImport = "docker exec {$containerName} n8n import:workflow --input=/tmp/import.json 2>&1";
            $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdImport);

            // Sanitize output
            $cleanOutput = strip_tags($output);
            if (strlen($cleanOutput) > 200)
                $cleanOutput = substr($cleanOutput, 0, 200) . "... (truncated)";
            $importResults[] = $cleanOutput;

            dockern8n_ssh_RunCommand($hostname, $username, $password, "rm -f /tmp/workflow_import.json");
            dockern8n_ssh_RunCommand($hostname, $username, $password, "docker exec {$containerName} rm -f /tmp/import.json 2>/dev/null");
        }
        $resultText = implode("
", $importResults);
        if (stripos($resultText, "error") !== false && stripos($resultText, "successfully") === false && stripos($resultText, "imported") === false) {
            return array("success" => false, "message" => "Import failed: " . $resultText);
        }
        $message = "Workflows imported successfully! You may need to activate them in N8N.";
        if ($hasCredentials) {
            $message = "Workflows and credentials imported successfully!";
        }
        return array("success" => true, "message" => $message, "details" => $resultText);
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_ListWorkflowExports($params)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $serviceId = $params["serviceid"];
        $exportDir = "/var/www/dockern8n/exports/service-{$serviceId}";

        // Check if directory exists first
        $cmdCheck = "test -d {$exportDir} && echo 'exists' || echo 'missing'";
        $checkResult = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdCheck));

        if ($checkResult !== "exists") {
            return array("success" => true, "exports" => array());
        }

        // Use find command with stat for cleaner output: filename|size|date
        $cmdList = "find {$exportDir} -name '*.json' -type f -exec stat --format='%n|%s|%y' {} \\; 2>/dev/null";
        $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdList);
        $output = trim($output);

        $exports = array();
        if (!empty($output)) {
            $lines = preg_split("/[\r\n]+/", $output);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line))
                    continue;

                $parts = explode("|", $line);
                if (count($parts) >= 3) {
                    $fullPath = trim($parts[0]);
                    $sizeBytes = (int) trim($parts[1]);
                    $dateStr = trim($parts[2]);

                    // Format size
                    if ($sizeBytes >= 1024 * 1024) {
                        $size = round($sizeBytes / (1024 * 1024), 1) . "M";
                    } elseif ($sizeBytes >= 1024) {
                        $size = round($sizeBytes / 1024, 1) . "K";
                    } else {
                        $size = $sizeBytes . "B";
                    }

                    // Format date (first 16 chars: YYYY-MM-DD HH:MM)
                    $date = substr($dateStr, 0, 16);
                    $date = str_replace("-", "/", substr($date, 5, 5)) . " " . substr($date, 11, 5);

                    $exports[] = array(
                        "filename" => basename($fullPath),
                        "size" => $size,
                        "date" => $date,
                        "path" => $fullPath
                    );
                }
            }
        }

        // Sort by filename descending (newest first)
        usort($exports, function ($a, $b) {
            return strcmp($b["filename"], $a["filename"]);
        });

        return array("success" => true, "exports" => $exports);
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_DeleteWorkflowExport($params, $filename)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $serviceId = $params["serviceid"];
        $filename = basename($filename);
        $exportDir = "/var/www/dockern8n/exports/service-{$serviceId}";
        $filePath = "{$exportDir}/{$filename}";
        $cmdDelete = "rm -f {$filePath}";
        dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdDelete);
        return array("success" => true, "message" => "Export deleted successfully");
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_RestoreWorkflowExport($params, $filename)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $serviceId = $params["serviceid"];
        $filename = basename($filename);
        $info = dockern8n_GetContainerInfo($params);
        $containerName = $info['n8n_container'];
        $exportDir = "/var/www/dockern8n/exports/service-{$serviceId}";

        // Fallback search for backup file if default path missing
        $cmdFind = "find /var/www/dockern8n/exports -name '{$filename}' 2>/dev/null | head -1";
        $findPath = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdFind));
        $filePath = !empty($findPath) ? $findPath : "{$exportDir}/{$filename}";

        $cmdCheck = "test -f \"{$filePath}\" && echo 'exists' || echo 'missing'";
        $checkResult = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdCheck));
        if ($checkResult !== "exists") {
            return array("success" => false, "message" => "Export file not found: {$filename}");
        }

        $cmdReadFile = "cat \"{$filePath}\"";
        $fileContent = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdReadFile);
        $jsonData = json_decode($fileContent, true);

        if (isset($jsonData["workflows"]) && is_array($jsonData["workflows"])) {
            $workflowsJson = json_encode($jsonData["workflows"]);
            $b64Workflows = base64_encode($workflowsJson);

            $cmdBatch = "echo '{$b64Workflows}' | base64 -d > /tmp/w_rst_{$serviceId}.json && " .
                "docker cp /tmp/w_rst_{$serviceId}.json {$containerName}:/tmp/workflows.json && " .
                "docker exec {$containerName} n8n import:workflow --input=/tmp/workflows.json && " .
                "rm -f /tmp/w_rst_{$serviceId}.json";

            $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdBatch, 120);
            $importResults = array();
            $importResults[] = "Workflows: " . trim($output);
            $hasCredentials = false;

            if (isset($jsonData["credentials"]) && !empty($jsonData["credentials"])) {
                $hasCredentials = true;
                $credsJson = json_encode($jsonData["credentials"]);
                $b64Creds = base64_encode($credsJson);
                $cmdCredBatch = "echo '{$b64Creds}' | base64 -d > /tmp/c_rst_{$serviceId}.json && " .
                    "docker cp /tmp/c_rst_{$serviceId}.json {$containerName}:/tmp/credentials.json && " .
                    "docker exec {$containerName} n8n import:credentials --input=/tmp/credentials.json && " .
                    "rm -f /tmp/c_rst_{$serviceId}.json";
                $credsOut = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdCredBatch, 120);
                $importResults[] = "Credentials: " . trim($credsOut);
            }
        } else {
            $cmdBatch = "docker cp \"{$filePath}\" {$containerName}:/tmp/restore.json && " .
                "docker exec {$containerName} n8n import:workflow --input=/tmp/restore.json";
            $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdBatch, 120);
            $importResults[] = trim($output);
        }
        dockern8n_LogActivity($params, "workflow_restored", "Workflows restored from: {$filename}");
        $message = "Workflows restored successfully from " . $filename;
        if ($hasCredentials) {
            $message .= " (including credentials)";
        }
        return array(
            "success" => true,
            "message" => $message,
            "details" => implode("
", $importResults)
        );
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_GetServerIP($params)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        return array("success" => true, "ip" => $hostname);
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_GeneratePassword($length = 16)
{
    return bin2hex(random_bytes($length / 2));
}

function dockern8n_LogActivity($params, $action, $details = '', $userId = null)
{
    // Logging disabled by user request
    return true;
}

// Helper to reliably get container info using Docker metadata
function dockern8n_GetContainerInfo($params)
{
    $serviceId = $params["serviceid"];
    $hostname = $params["serverhostname"] ?: $params["serverip"];
    $username = $params["serverusername"];
    $password = $params["serverpassword"];
    $domain = preg_replace("#^https?://#", '', $params['domain'] ?? '');

    // 1. Detect PUQ vs our service
    $puqInfo = DockerN8N_PUQCompat::detectPUQService($params);

    // 2. Native Module Defaults
    $nativeDir = "/var/www/dockern8n/service-{$serviceId}";
    $nativeProject = "service-{$serviceId}";
    $nativeContainer = "service-{$serviceId}-n8n";
    
    // Default info structure (Native first, PUQ if detected)
    $info = [
        'is_puq' => $puqInfo['is_puq'],
        'service_dir' => $puqInfo['is_puq'] ? $puqInfo['service_dir'] : $nativeDir,
        'n8n_container' => $puqInfo['is_puq'] ? $puqInfo['container_name'] : $nativeContainer,
        'postgres_container' => $puqInfo['is_puq'] ? ($puqInfo['container_name'] . "-postgres") : "{$nativeProject}-postgres",
        'compose_file' => ($puqInfo['service_dir'] ?? $nativeDir) . "/docker-compose.yml",
        'project_name' => $puqInfo['is_puq'] ? ($puqInfo['project_name'] ?? $nativeProject) : $nativeProject,
        'mount_dir' => $puqInfo['is_puq'] ? $puqInfo['mount_dir'] : "{$nativeDir}/n8n-data",
        'found' => $puqInfo['is_puq']
    ];

    // 3. Smart Detection (Override defaults if container is found running)
    // We check native names first, then domain-based names
    $checkNames = [$nativeContainer, $domain, "service-{$serviceId}-n8n"];
    if ($puqInfo['is_puq']) {
         array_unshift($checkNames, $puqInfo['container_name']);
    }

    $checked = [];
    foreach ($checkNames as $cName) {
        if (empty($cName) || in_array($cName, $checked)) continue;
        $checked[] = $cName;

        $inspectCmd = "docker inspect {$cName} --format '{{ index .Config.Labels \"com.docker.compose.project.working_dir\" }}|{{ index .Config.Labels \"com.docker.compose.project\" }}' 2>/dev/null";
        $metadata = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $inspectCmd));

        if (!empty($metadata) && strpos($metadata, '|') !== false && strpos($metadata, 'Error: No such object') === false) {
            list($workDir, $project) = explode('|', $metadata);
            if ($workDir && $workDir != "<no value>") {
                $info['service_dir'] = rtrim($workDir, '/');
                $info['compose_file'] = $info['service_dir'] . "/docker-compose.yml";
                $info['n8n_container'] = $cName;
                $info['found'] = true;
            }
            if ($project && $project != "<no value>") {
                $info['project_name'] = $project;
            }
            
            // If we found Postgres via label, use it
            $info['postgres_container'] = $info['project_name'] . "-postgres";
            return $info;
        } else {
            // Fallback: Check if the container simply exists (might not have labels if created manually or by old version)
            $checkExist = "docker inspect {$cName} --format='{{.Id}}' 2>/dev/null";
            $id = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $checkExist));
            if (!empty($id) && strpos($id, 'Error') === false) {
                $info['n8n_container'] = $cName;
                $info['found'] = true;
                // If we don't know the project name, we hope the defaults worked
            }
        }
    }

    // 4. Fallback: Check custom field for manually set directory
    $customField = Capsule::table("tblcustomfields")->where("relid", $params["packageid"])->where("fieldname", "Service Details")->where("type", "product")->first();
    if ($customField) {
        $value = Capsule::table("tblcustomfieldsvalues")->where("fieldid", $customField->id)->where("relid", $serviceId)->value("value");
        if ($value) {
            $details = json_decode(html_entity_decode($value), true);
            if (!empty($details["service_dir"])) {
                $info['service_dir'] = rtrim($details["service_dir"], '/');
                $info['compose_file'] = $info['service_dir'] . "/docker-compose.yml";
            }
        }
    }

    return $info;
}


function dockern8n_UpdateVersion($params, $newVersion)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];

        $containerInfo = dockern8n_GetContainerInfo($params);
        $composeFile = $containerInfo['compose_file'];
        $serviceDir = $containerInfo['service_dir'];

        // 1. Read Compose File
        $cmdRead = "cat {$composeFile}";
        $composeContent = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdRead);

        if (empty($composeContent) || strpos($composeContent, "services:") === false) {
            return array("success" => false, "message" => "Could not read configuration file at " . $composeFile);
        }

        // 2. Update Image Tag using Regex
        // Pattern matches: image: docker.n8n.io/n8nio/n8n:x.y.z OR image: n8nio/n8n:x.y.z
        $pattern = "/(image:\s*[\"']?)(?:docker\.n8n\.io\/n8nio\/n8n|n8nio\/n8n)(?::[^\"'\s]+)?([\"']?)/";
        $replacement = "$1docker.n8n.io/n8nio/n8n:{$newVersion}$2";

        if (!preg_match($pattern, $composeContent)) {
            return array("success" => false, "message" => "Could not find n8n image definition in compose file.");
        }

        $newContent = preg_replace($pattern, $replacement, $composeContent);

        // 3. Write Config Back
        $b64Compose = base64_encode($newContent);
        $cmdWrite = "echo '{$b64Compose}' | base64 -d > {$composeFile}";
        dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdWrite);

        // 4. Pull and Recreate (Sync)
        $cmdUpdate = "cd {$serviceDir} && docker compose pull n8n && docker compose up -d n8n 2>&1";
        $updateOutput = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdUpdate);

        // 5. Verify Update
        if (stripos($updateOutput, 'error') !== false || stripos($updateOutput, 'failed') !== false) {
            // If it mentions success keywords, it's just warnings
            if (stripos($updateOutput, 'recreated') === false && stripos($updateOutput, 'started') === false && stripos($updateOutput, 'running') === false && stripos($updateOutput, 'done') === false) {
                return array("success" => false, "message" => "Update failed: " . substr($updateOutput, 0, 150));
            }
        }

        // Update Custom Field
        $serviceId = $params["serviceid"];
        $customField = Capsule::table("tblcustomfields")->where("relid", $params["packageid"])->where("fieldname", "Service Details")->where("type", "product")->first();
        if ($customField) {
            $value = Capsule::table("tblcustomfieldsvalues")->where("fieldid", $customField->id)->where("relid", $serviceId)->value("value");
            if ($value) {
                $details = json_decode(html_entity_decode($value), true);
                $details["version"] = $newVersion;
                Capsule::table("tblcustomfieldsvalues")->where("fieldid", $customField->id)->where("relid", $serviceId)->update(array("value" => json_encode($details)));
            }
        }

        dockern8n_LogActivity($params, "update_version", "Updated n8n to version {$newVersion}");
        return array("success" => true, "message" => "N8N updated to version {$newVersion} successfully.");

    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_GetActivityLog($params, $limit = 50)
{
    try {
        $serviceId = $params["serviceid"];
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $logFile = "/var/www/dockern8n/logs/service-{$serviceId}.log";
        $cmd = "tail -n {$limit} {$logFile} 2>/dev/null || echo '[]'";
        $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmd);
        $logs = array();
        $lines = array_filter(explode("
", trim($output)));
        foreach (array_reverse($lines) as $line) {
            $entry = json_decode($line, true);
            if ($entry) {
                $logs[] = $entry;
            }
        }
        return array("success" => true, "logs" => $logs);
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_SoftReset($params)
{

    try {
        $serviceId = $params["serviceid"];
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];

        $info = dockern8n_GetContainerInfo($params);
        $n8nContainer = $info['n8n_container'];
        $dbPath = "";

        if ($info['is_puq']) {
            $dbPath = ($info['mount_dir'] ?: "/mnt/" . $params['domain']) . "/database.sqlite";
        } else {
            $dbPath = $info['service_dir'] . "/n8n-data/database.sqlite";
        }

        $sql = "DELETE FROM project_membership; DELETE FROM user_roles; DELETE FROM auth_identity; DELETE FROM \"user\"; DELETE FROM role;";

        // 1. OFFICIAL CLI RESET (Primary for v1+)
        // This correctly wipes users and roles while preserving workflows.
        $cmdReset = "docker exec -u node {$n8nContainer} n8n user-management:reset --yes 2>&1";
        $resReset = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdReset, 120);

        if (stripos($resReset, 'successfully') !== false || stripos($resReset, 'Reset database') !== false) {
             dockern8n_ssh_RunCommand($hostname, $username, $password, "docker restart {$n8nContainer} 2>&1");
             return array("success" => true, "message" => "Soft reset success! Users have been cleared. You can now setup a new owner account.");
        }

        // 2. TIERED FALLBACK (SQL Wipe)
        $resetSqlCmd = "([ -f {$dbPath} ] && (sudo sqlite3 {$dbPath} '{$sql}' || sqlite3 {$dbPath} '{$sql}')) 2>&1";
        $resSql = dockern8n_ssh_RunCommand($hostname, $username, $password, $resetSqlCmd, 60);

        if (stripos($resSql, 'Error') !== false) {
             // Last resort sidecar
             $sidecar = "docker run --rm -v " . ($info['is_puq'] ? ($info['mount_dir'] ?: "/mnt/" . $params['domain']) : "{$info['project_name']}_n8n-data") . ":/data alpine sh -c \"apk add --no-cache sqlite && sqlite3 /data/database.sqlite '{$sql}'\" 2>&1";
             dockern8n_ssh_RunCommand($hostname, $username, $password, $sidecar, 120);
        }

        // 3. FINAL REBOOT
        dockern8n_ssh_RunCommand($hostname, $username, $password, "docker restart {$n8nContainer} 2>&1");

        dockern8n_LogActivity($params, "soft_reset", "N8N soft reset executed. Users cleared.");
        unset($GLOBALS['dockern8n_status_cache']["status_" . $serviceId]);

        return array("success" => true, "message" => "Soft reset success! Users and roles have been cleared. You can now setup a new owner account.");
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_FullReset($params)
{
    try {
        $serviceId = $params["serviceid"];
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];

        $info = dockern8n_GetContainerInfo($params);
        $serviceDir = $info['service_dir'];
        $projectName = $info['project_name'];
        $n8nContainer = $info['n8n_container'];

        // 1. STOP & WIPE
        if ($info['is_puq']) {
            $mountDir = $info['mount_dir'] ?: "/mnt/" . $params['domain'];
            // For PUQ, we need to ensure we stop exactly what needs to be stopped, then wipe the mount.
            $cmdWipe = "cd " . $serviceDir . " && " .
                "(docker compose -p " . $projectName . " down || docker-compose -p " . $projectName . " down || docker stop " . $n8nContainer . ") && " .
                "sudo rm -rf " . $mountDir . "/[^.]* " . $mountDir . "/.[!.]* 2>/dev/null; " .
                "sudo mount -a && sleep 2 && " .
                "sudo mkdir -p " . $mountDir . "/n8n " . $mountDir . "/postgres " . $mountDir . "/redis && " .
                "sudo chmod 755 " . $mountDir . " && " .
                "sudo chown -R 1000:1000 " . $mountDir . "/n8n && " .
                "sudo chown -R 70:70 " . $mountDir . "/postgres && " .
                "sudo chown -R 999:999 " . $mountDir . "/redis && " .
                "(docker compose -p " . $projectName . " up -d || docker-compose -p " . $projectName . " up -d) 2>&1";
        } else {
            // Module-native: use docker compose down -v to wipe volumes
            $cmdWipe = "cd " . $serviceDir . " && " .
                "(docker compose -p " . $projectName . " down -v --remove-orphans || docker-compose -p " . $projectName . " down -v --remove-orphans) && " .
                "sudo rm -rf " . $info['service_dir'] . "/n8n-data/* 2>/dev/null; " .
                "sudo chmod 755 " . $info['mount_dir'] . " && " .
                "sudo chown -R 1000:1000 " . $info['mount_dir'] . " && " . // Ensure permissions after wipe
                "(docker compose -p " . $projectName . " up -d || docker-compose -p " . $projectName . " up -d) 2>&1";
        }

        $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdWipe, 300);

        // Error handling
        if (strpos($output, "Error") !== false && strpos($output, "Started") === false && strpos($output, "Running") === false && strpos($output, "Recreated") === false && strpos($output, "Creating") === false) {
            return array("success" => false, "message" => "Docker Reset Error: " . substr(strip_tags($output), 0, 300));
        }

        dockern8n_LogActivity($params, "full_reset", "Full service wipe completed.");
        unset($GLOBALS['dockern8n_status_cache']["status_" . $serviceId]);

        return array("success" => true, "message" => "Full reset success! All data has been wiped and instance has been recreated.");
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_GetIPWhitelist($params)
{
    try {
        $serviceId = $params["serviceid"];

        // Get from WHMCS database
        $customField = Capsule::table("tblcustomfields")->where("relid", $params["packageid"])->where("fieldname", "Service Details")->where("type", "product")->first();
        if ($customField) {
            $value = Capsule::table("tblcustomfieldsvalues")->where("fieldid", $customField->id)->where("relid", $serviceId)->value("value");
            if ($value) {
                $details = json_decode(html_entity_decode($value), true);
                if (isset($details["ip_whitelist"])) {
                    return array("success" => true, "whitelist" => $details["ip_whitelist"]);
                }
            }
        }

        return array("success" => true, "whitelist" => array("enabled" => false, "ips" => array()));
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_UpdateIPWhitelist($params, $enabled, $ips)
{
    try {
        $serviceId = $params["serviceid"];
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $domain = preg_replace("#^https?://#", '', $params["domain"] ?? '');
        $domain = rtrim($domain, "/");

        // Validate IPs
        $validIps = array();
        if (is_array($ips)) {
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if (!empty($ip) && (filter_var($ip, FILTER_VALIDATE_IP) || preg_match("/^[\d\.]+\/\d+$/", $ip))) {
                    $validIps[] = $ip;
                }
            }
        }

        $whitelist = array(
            "enabled" => (bool) $enabled,
            "ips" => $validIps,
            "updated_at" => date("Y-m-d H:i:s")
        );

        // Save to WHMCS database
        $customField = Capsule::table("tblcustomfields")->where("relid", $params["packageid"])->where("fieldname", "Service Details")->where("type", "product")->first();
        if ($customField) {
            $value = Capsule::table("tblcustomfieldsvalues")->where("fieldid", $customField->id)->where("relid", $serviceId)->value("value");
            $details = $value ? json_decode(html_entity_decode($value), true) : array();
            $details["ip_whitelist"] = $whitelist;
            Capsule::table("tblcustomfieldsvalues")->where("fieldid", $customField->id)->where("relid", $serviceId)->update(array("value" => json_encode($details)));
        }

        // SERVER-SIDE APPLICATION
        $status = $enabled && !empty($validIps) ? "enabled" : "disabled";

        // Cloudflare IPs for real_ip support (ipv4)
        $cfIps = ["173.245.48.0/20", "103.21.244.0/22", "103.22.200.0/22", "103.31.4.0/22", "141.101.64.0/18", "108.162.192.0/18", "190.93.240.0/20", "188.114.96.0/20", "197.234.240.0/22", "198.41.128.0/17", "162.158.0.0/15", "104.16.0.0/13", "104.24.0.0/14", "172.64.0.0/13", "131.0.72.0/22"];

        // Define all possible host paths for vhost.d
        $vhostPaths = [
            "/opt/docker/nginx-proxy/nginx/vhost.d",
            "/etc/nginx/vhost.d",
            "/opt/docker/nginx/vhost.d",
            "/var/www/dockern8n/nginx/vhost.d",
            "/opt/proxy/nginx/vhost.d",
            "/opt/docker/nginx-proxy/vhost.d"
        ];

        if ($enabled && !empty($validIps)) {
            $nginxConf = "# IP Whitelist for {$domain} - Created by DockerN8N\n";

            // 1. Real IP Support - Crucial for Docker Bridge & Cloudflare
            // We trust the X-Forwarded-For header from private networks and Cloudflare
            $nginxConf .= "real_ip_header X-Forwarded-For;\n";
            $nginxConf .= "real_ip_recursive on;\n";

            // Trust Docker internal networks
            $nginxConf .= "set_real_ip_from 127.0.0.1;\n";
            $nginxConf .= "set_real_ip_from 10.0.0.0/8;\n";
            $nginxConf .= "set_real_ip_from 172.16.0.0/12;\n";
            $nginxConf .= "set_real_ip_from 192.168.0.0/16;\n";

            // Trust Cloudflare IPs
            foreach ($cfIps as $cfIp) {
                $nginxConf .= "set_real_ip_from {$cfIp};\n";
            }

            // Also support direct CF header if present
            $nginxConf .= "set_real_ip_from 0.0.0.0/0;\n\n";

            // 2. Access Rules
            foreach ($validIps as $ip) {
                $nginxConf .= "allow {$ip};\n";
            }
            $nginxConf .= "deny all;";

            $b64Conf = base64_encode($nginxConf);

            $applyScript = "
domain='{$domain}'
b64='{$b64Conf}'
found_paths=''

# Find the vhost.d directory
for path in " . implode(" ", $vhostPaths) . "; do
    if [ -d \"\$path\" ]; then
        found_paths=\"\$path\"
        break
    fi
done

if [ -z \"\$found_paths\" ]; then
    search_path=$(find /opt /etc /var/www -name vhost.d -type d 2>/dev/null | head -n 1)
    if [ ! -z \"\$search_path\" ]; then
        found_paths=\"\$search_path\"
    fi
fi

if [ -z \"\$found_paths\" ]; then
    echo \"ERROR: No vhost.d found. Checked: /opt/docker/nginx-proxy/nginx/vhost.d, /etc/nginx/vhost.d\"
else
    # Write the rules
    echo \"\$b64\" | base64 -d > \"\$found_paths/\$domain\"
    echo \"\$b64\" | base64 -d > \"\$found_paths/\${domain}_location\"
    
    # IMPORTANT: Force nginx-proxy to re-render the config
    # We trigger a container event by creating/removing a dummy container
    docker run --rm --name proxy-sync-$(date +%s) alpine true >/dev/null 2>&1 || true
    
    # Reload proxy containers
    reload_done=''
    for container in nginx-proxy proxy nginx; do
        if docker ps --format '{{.Names}}' | grep -q \"^\$container$\"; then
            docker exec \$container nginx -s reload >/dev/null 2>&1
            reload_done=\"\$reload_done \$container\"
        fi
    done
    
    echo \"SUCCESS: Applied to \$found_paths. Reloaded: \$reload_done\"
fi
";
        } else {
            // Remove whitelist logic
            $applyScript = "
domain='{$domain}'
found_paths=''
for path in " . implode(" ", $vhostPaths) . "; do
    if [ -f \"\$path/\$domain\" ] || [ -f \"\$path/\${domain}_location\" ]; then
        rm -f \"\$path/\$domain\" \"\$path/\${domain}_location\"
        found_paths=\"\$found_paths \$path\"
    fi
done

# Trigger re-render
docker run --rm --name proxy-sync-$(date +%s) alpine true >/dev/null 2>&1 || true

for container in nginx-proxy proxy nginx; do
    if docker ps --format '{{.Names}}' | grep -q \"^\$container$\"; then
        docker exec \$container nginx -s reload >/dev/null 2>&1
    fi
done
echo \"REMOVED: Whitelist cleared from \$found_paths\"
";
        }

        // SANITIZE: Remove Windows carriage returns (\r) to avoid bash syntax errors on Linux
        $applyScript = str_replace("\r", "", $applyScript);

        $output = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $applyScript));

        if (strpos($output, 'ERROR') !== false || empty($output)) {
            return array("success" => false, "message" => "IP Whitelist could not be applied. " . ($output ?: "Unknown SSH error"));
        }

        $ipList = !empty($validIps) ? implode(", ", $validIps) : "none";
        dockern8n_LogActivity($params, "ip_whitelist_updated", "IP Whitelist {$status}. IPs: {$ipList}. Server Output: {$output}");

        return array("success" => true, "message" => "Whitelist Updated Successfully. " . $output);
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}

function dockern8n_GetSSLStatus($params)
{
    try {
        $hostname = $params["serverhostname"] ?: $params["serverip"];
        $username = $params["serverusername"];
        $password = $params["serverpassword"];
        $domain = preg_replace("#^https?://#", '', $params["domain"] ?? '');
        $domain = rtrim($domain, "/");

        if (empty($domain)) {
            return array("success" => false, "message" => "Domain not configured");
        }

        // Method 1: Local SSH check (PUQ/Nginx Proxy style) - Very reliable for DNS propagation issues
        // We look for cert files in common directories
        $certPaths = [
            "/opt/docker/nginx-proxy/certs/{$domain}.crt",
            "/opt/docker/nginx/certs/{$domain}.crt",
            "/etc/nginx/certs/{$domain}.crt",
            "/opt/proxy/certs/{$domain}.crt"
        ];

        $checkCmd = "";
        foreach ($certPaths as $cp) {
            $checkCmd .= "if [ -f \"{$cp}\" ]; then openssl x509 -in \"{$cp}\" -noout -dates -issuer; echo \"PATH:{$cp}\"; exit 0; fi\n";
        }

        $sshOutput = dockern8n_ssh_RunCommand($hostname, $username, $password, $checkCmd);

        if (!empty($sshOutput) && strpos($sshOutput, 'notAfter=') !== false) {
            // Parse SSH output
            preg_match('/notBefore=(.*)/', $sshOutput, $matchesFrom);
            preg_match('/notAfter=(.*)/', $sshOutput, $matchesTo);
            preg_match('/issuer=(.*)/', $sshOutput, $matchesIssuer);

            $validFromTs = isset($matchesFrom[1]) ? strtotime($matchesFrom[1]) : 0;
            $validToTs = isset($matchesTo[1]) ? strtotime($matchesTo[1]) : 0;
            $issuer = isset($matchesIssuer[1]) ? $matchesIssuer[1] : "Unknown";

            $expiresIn = ceil(($validToTs - time()) / 86400);
            $status = "valid";
            if ($expiresIn < 0)
                $status = "expired";
            elseif ($expiresIn < 7)
                $status = "expiring_soon";

            return array(
                "success" => true,
                "ssl_status" => $status,
                "domain" => $domain,
                "issuer" => str_replace('O = ', '', $issuer),
                "valid_from" => date("Y-m-d H:i:s", $validFromTs),
                "valid_to" => date("Y-m-d H:i:s", $validToTs),
                "expires_in_days" => (int) $expiresIn,
                "check_method" => "local_ssh"
            );
        }

        // Method 2: Network check (Legacy/Fallback)
        $context = stream_context_create(array(
            "ssl" => array(
                "capture_peer_cert" => true,
                "verify_peer" => false,
                "verify_peer_name" => false,
                "SNI_enabled" => true,
                "peer_name" => $domain
            )
        ));

        // Timeout reduced to 5 seconds for better UI performance
        $client = @stream_socket_client("ssl://{$domain}:443", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);

        if (!$client) {
            return array(
                "success" => true,
                "ssl_status" => "pending",
                "message" => "SSL cert not found locally and network check failed. Potentially DNS propagation issue.",
                "domain" => $domain
            );
        }

        $streamParams = stream_context_get_params($client);
        $cert = openssl_x509_parse($streamParams["options"]["ssl"]["peer_certificate"]);
        fclose($client);

        $validFrom = date("Y-m-d H:i:s", $cert["validFrom_time_t"]);
        $validTo = date("Y-m-d H:i:s", $cert["validTo_time_t"]);
        $expiresIn = ceil(($cert["validTo_time_t"] - time()) / 86400);

        $status = "valid";
        if ($expiresIn < 0)
            $status = "expired";
        elseif ($expiresIn < 7)
            $status = "expiring_soon";

        return array(
            "success" => true,
            "ssl_status" => $status,
            "domain" => $domain,
            "issuer" => $cert["issuer"]["O"] ?? $cert["issuer"]["CN"] ?? "Unknown",
            "valid_from" => $validFrom,
            "valid_to" => $validTo,
            "expires_in_days" => (int) $expiresIn,
            "check_method" => "network"
        );
    } catch (Exception $e) {
        return array("success" => false, "message" => $e->getMessage());
    }
}
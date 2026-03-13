<?php
use WHMCS\Database\Capsule;

require_once __DIR__ . "/../../../init.php";
require_once "dockern8n.php";

if (!isset($_SESSION["uid"])) {
    die("Unauthorized");
}

$userId = $_SESSION["uid"];
$serviceId = $_REQUEST["serviceid"] ?? 0;
$backupFilename = $_REQUEST["backup_filename"] ?? '';

if (empty($serviceId) || empty($backupFilename)) {
    die("Invalid parameters");
}

$service = Capsule::table("tblhosting")->where("id", $serviceId)->where("userid", $userId)->first();
if (!$service) {
    die("Service not found or access denied");
}

$server = Capsule::table("tblservers")->where("id", $service->server)->first();
if (!$server) {
    die("Server not found");
}

$backupFilename = basename($backupFilename);

if (!preg_match("/^n8n-backup-\d{8}-\d{6}\.tar\.gz$/", $backupFilename)) {
    die("Invalid backup filename");
}

$hostname = $server->hostname ?: $server->ipaddress;
$username = $server->username;
$password = decrypt($server->password);

$backupPath = "/var/www/dockern8n/backups/service-{$serviceId}/{$backupFilename}";

$cmdCheck = "test -f {$backupPath} && echo 'exists'";
$checkOutput = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdCheck);

if (trim($checkOutput) !== "exists") {
    die("Backup file not found");
}

$cmdSize = "stat -c%s {$backupPath}";
$fileSize = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdSize));

header("Content-Type: application/gzip");
header("Content-Disposition: attachment; filename=\"" . $backupFilename . "\"");
header("Content-Length: " . $fileSize);
header("Cache-Control: no-cache");

try {
    // using dockern8n_ssh_RunCommand typically returns string, for download we might want direct passthrough but checking original code:
    // Original used phpseclib3 directly in jlZk5 block to echo output.
    $ssh = new \phpseclib3\Net\SSH2($hostname);
    if (!$ssh->login($username, $password)) {
        die("SSH authentication failed");
    }
    $ssh->setTimeout(0); 
    echo $ssh->exec("cat {$backupPath}");
    $ssh->disconnect();
} catch (Exception $e) {
    die("Download failed: " . $e->getMessage());
}
die;
<?php
use WHMCS\Database\Capsule;

require_once __DIR__ . "/../../../init.php";
require_once "dockern8n.php";

if (!isset($_SESSION["uid"])) {
    die("Unauthorized");
}

$userId = $_SESSION["uid"];
$serviceId = $_REQUEST["serviceId"] ?? $_REQUEST["serviceid"] ?? 0;
$filename = $_REQUEST["filename"] ?? '';

if (empty($serviceId) || empty($filename)) {
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

$filename = preg_replace("/\s+/", '', basename($filename));

if (!preg_match("/^workflows_.*\.json$/i", $filename)) {
    die("Invalid export filename");
}

$hostname = $server->hostname ?: $server->ipaddress;
$username = $server->username;
$password = decrypt($server->password);

$exportPath = "/var/www/dockern8n/exports/service-{$serviceId}/{$filename}";

$cmdCheck = "test -f {$exportPath} && echo 'exists'";
$checkOutput = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdCheck);

if (trim($checkOutput) !== "exists") {
    die("Export file not found");
}

$cmdRead = "cat {$exportPath}";
$content = dockern8n_ssh_RunCommand($hostname, $username, $password, $cmdRead);

header("Content-Type: application/json");
header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
header("Content-Length: " . strlen($content));
header("Cache-Control: no-cache");
echo $content;
die;
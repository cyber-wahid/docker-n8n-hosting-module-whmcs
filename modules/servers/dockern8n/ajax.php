<?php
define("CLIENTAREA", true);
require_once __DIR__ . "/../../../init.php";
require_once __DIR__ . "/dockern8n.php";

ini_set('display_errors', 0);
ini_set('log_errors', 1);

use WHMCS\Database\Capsule;

// Start or resume session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$ca = new WHMCS_ClientArea();
if (!$ca->isLoggedIn()) {
    jsonResponse(array("success" => false, "message" => "Not logged in"));
}

$userId = $ca->getUserID();
$serviceId = (int) ($_REQUEST["serviceId"] ?? $_REQUEST["serviceid"] ?? 0);
$action = $_REQUEST["action"] ?? $_REQUEST["custom_action"] ?? '';

// CSRF Protection - only for sensitive POST actions

// Request Method Validation
$sensitiveActions = array(
    'resetPassword', 'changeVersion', 'createBackup', 'restoreBackup', 
    'deleteBackup', 'start', 'stop', 'restart', 'reinstall', 'fullReset', 
    'reinstallSoft', 'softReset', 'suspend', 'unsuspend', 'importWorkflows', 
    'deleteWorkflowExport', 'restoreWorkflowExport', 'setTimezone', 
    'updateIPWhitelist'
);

// CSRF check only for POST requests (optional - frontend may use GET with token in URL)
if (in_array($action, $sensitiveActions) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('check_token')) {
        check_token();
    }
}

if (!$serviceId) {
    jsonResponse(array("success" => false, "message" => "Service ID missing"));
}

$service = Capsule::table("tblhosting")->where("id", $serviceId)->where("userid", $userId)->first();
if (!$service) {
    jsonResponse(array("success" => false, "message" => "Service not found or access denied"));
}

$server = Capsule::table("tblservers")->where("id", $service->server)->first();
if (!$server) {
    jsonResponse(array("success" => false, "message" => "Server not found"));
}

$product = Capsule::table("tblproducts")->where("id", $service->packageid)->first();

$params = array(
    "serviceid" => $serviceId,
    "userid" => $userId,
    "serverid" => $server->id,
    "serverhostname" => $server->hostname,
    "serverip" => $server->ipaddress,
    "serverusername" => $server->username,
    "serverpassword" => $server->password ? decrypt($server->password) : '',
    "packageid" => $service->packageid,
    "domain" => $service->domain,
    "configoption1" => isset($product->configoption1) && $product->configoption1 !== '' ? $product->configoption1 : "n8n",
    "configoption2" => isset($product->configoption2) && $product->configoption2 !== '' ? $product->configoption2 : "1",
    "configoption3" => isset($product->configoption3) && $product->configoption3 !== '' ? $product->configoption3 : "1G",
    "configoption4" => isset($product->configoption4) && $product->configoption4 !== '' ? $product->configoption4 : "latest",
    "configoption5" => isset($product->configoption5) && $product->configoption5 !== '' ? $product->configoption5 : "5G",
    "configoption6" => isset($product->configoption6) && $product->configoption6 !== '' ? $product->configoption6 : "latest",
    "configoption7" => isset($product->configoption7) && $product->configoption7 !== '' ? $product->configoption7 : "{user_id}-{service_id}",
    "configoption8" => isset($product->configoption8) && $product->configoption8 !== '' ? $product->configoption8 : "70",
    "configoption9" => isset($product->configoption9) && $product->configoption9 !== '' ? $product->configoption9 : ''
);

set_time_limit(0);
ignore_user_abort(true);

try {
    switch ($action) {
        case "getAllData":
            $responseData = array("success" => true);
            try {
                $responseData["status"] = dockern8n_GetStatus($params);
            } catch (Exception $e) {
                $responseData["status"] = array("status" => "unknown", "message" => "Unable to fetch status");
            }
            try {
                $responseData["resourcestats"] = dockern8n_GetResourceStats($params);
            } catch (Exception $e) {
                $responseData["resourcestats"] = array("success" => false, "cpu" => 0, "memory" => 0);
            }
            try {
                $responseData["sslStatus"] = dockern8n_GetSSLStatus($params);
            } catch (Exception $e) {
                $responseData["sslStatus"] = array("success" => false, "enabled" => false);
            }
            try {
                $responseData["ipWhitelist"] = dockern8n_GetIPWhitelist($params);
            } catch (Exception $e) {
                $responseData["ipWhitelist"] = array("success" => false, "ips" => array());
            }
            
            if (function_exists("dockern8n_GetTimezone")) {
                try {
                    $responseData["timezone"] = dockern8n_GetTimezone($params);
                } catch (Exception $e) {
                    $responseData["timezone"] = array("success" => false, "timezone" => "UTC");
                }
            } else {
                $responseData["timezone"] = array("success" => false, "timezone" => "Asia/Dhaka", "message" => "Not implemented");
            }
            
            if (function_exists("dockern8n_GetTimezoneList")) {
                try {
                    $responseData["timezoneList"] = dockern8n_GetTimezoneList();
                } catch (Exception $e) {
                    $responseData["timezoneList"] = array();
                }
            } else {
                $responseData["timezoneList"] = DateTimeZone::listIdentifiers();
            }
            
            try {
                $responseData["versions"] = dockern8n_GetAvailableVersions();
            } catch (Exception $e) {
                $responseData["versions"] = array("latest");
            }
            try {
                $responseData["workflowExports"] = dockern8n_GetWorkflowExports($params);
            } catch (Exception $e) {
                $responseData["workflowExports"] = array("success" => false, "exports" => array());
            }
            
            jsonResponse($responseData);
            break;
            
        case "getstats":
        case "getResourceStats":
            $result = dockern8n_GetResourceStats($params);
            jsonResponse($result);
            break;
            
        case "getLogs":
            $lines = $_REQUEST["lines"] ?? 100;
            $result = dockern8n_GetLogs($params, $lines);
            jsonResponse($result);
            break;
            
        case "resetPassword":
            $result = dockern8n_ResetPassword($params);
            jsonResponse($result);
            break;
            
        case "getVersions":
        case "getAvailableVersions":
            $versions = dockern8n_GetAvailableVersions();
            jsonResponse(array("success" => true, "versions" => $versions));
            break;
            
        case "changeVersion":
            $newVersion = $_REQUEST["version"] ?? '';
            if (empty($newVersion)) {
                jsonResponse(array("success" => false, "message" => "Version is required"));
            }
            if (function_exists("dockern8n_UpdateVersion")) {
                $result = dockern8n_UpdateVersion($params, $newVersion);
            } else {
                $result = array("success" => false, "message" => "UpdateVersion function missing in module");
            }
            jsonResponse($result);
            break;
            
        case "createBackup":
            $result = dockern8n_CreateBackup($params);
            jsonResponse($result);
            break;
            
        case "listBackups":
            $result = dockern8n_ListBackups($params);
            jsonResponse($result);
            break;
            
        case "restoreBackup":
            $backupFilename = $_REQUEST["filename"] ?? $_REQUEST["backup_filename"] ?? '';
            if (empty($backupFilename)) {
                jsonResponse(array("success" => false, "message" => "Backup filename required"));
            }
            $result = dockern8n_RestoreBackup($params, $backupFilename);
            jsonResponse($result);
            break;
            
        case "deleteBackup":
            $backupFilename = $_REQUEST["filename"] ?? $_REQUEST["backup_filename"] ?? '';
            $result = dockern8n_DeleteBackup($params, $backupFilename);
            jsonResponse($result);
            break;
            
        case "start":
            $result = dockern8n_start($params);
            $success = $result === "success";
            jsonResponse(array("success" => $success, "message" => $success ? "Service started successfully" : "Start failed: " . $result));
            break;
            
        case "stop":
            $result = dockern8n_stop($params);
            $success = $result === "success";
            jsonResponse(array("success" => $success, "message" => $success ? "Service stopped successfully" : "Stop failed: " . $result));
            break;
            
        case "restart":
            $result = dockern8n_restart($params);
            $success = $result === "success";
            jsonResponse(array("success" => $success, "message" => $success ? "Service restarted successfully" : "Restart failed: " . $result));
            break;
            
        case "reinstall":
        case "fullReset":
            $result = dockern8n_FullReset($params);
            jsonResponse($result);
            break;
            
        case "reinstallSoft":
        case "softReset":
            $result = dockern8n_SoftReset($params);
            jsonResponse($result);
            break;
            
        case "suspend":
            $result = dockern8n_SuspendAccount($params);
            $success = $result === "success";
            jsonResponse(array("success" => $success, "message" => $success ? "Service suspended" : $result));
            break;
            
        case "unsuspend":
            $result = dockern8n_UnsuspendAccount($params);
            $success = $result === "success";
            jsonResponse(array("success" => $success, "message" => $success ? "Service unsuspended" : $result));
            break;
            
        case "getstatus":
            $result = dockern8n_GetStatus($params);
            jsonResponse($result);
            break;
            
        case "exportWorkflows":
            $includeCredentials = isset($_REQUEST["includeCredentials"]) && $_REQUEST["includeCredentials"] === "true";
            $result = dockern8n_ExportWorkflows($params, $includeCredentials);
            jsonResponse($result);
            break;
            
        case "importWorkflows":
            $workflowData = '';
            $filename = "Imported Workflow";
            
            if (isset($_FILES["file"]) && $_FILES["file"]["error"] === UPLOAD_ERR_OK) {
                $workflowData = file_get_contents($_FILES["file"]["tmp_name"]);
                $filename = pathinfo($_FILES["file"]["name"], PATHINFO_FILENAME);
            } elseif (isset($_FILES["workflow_file"]) && $_FILES["workflow_file"]["error"] === UPLOAD_ERR_OK) {
                $workflowData = file_get_contents($_FILES["workflow_file"]["tmp_name"]);
                $filename = pathinfo($_FILES["workflow_file"]["name"], PATHINFO_FILENAME);
            } elseif (!empty($_REQUEST["workflow_json"])) {
                $workflowData = $_REQUEST["workflow_json"];
            }
            
            $result = dockern8n_ImportWorkflows($params, $workflowData, $filename);
            jsonResponse($result);
            break;
            
        case "listWorkflowExports":
            $result = dockern8n_ListWorkflowExports($params);
            jsonResponse($result);
            break;
            
        case "deleteWorkflowExport":
            $filename = $_REQUEST["filename"] ?? '';
            $result = dockern8n_DeleteWorkflowExport($params, $filename);
            jsonResponse($result);
            break;
            
        case "restoreWorkflowExport":
            $filename = $_REQUEST["filename"] ?? '';
            $result = dockern8n_RestoreWorkflowExport($params, $filename);
            jsonResponse($result);
            break;
            
        case "getServerIP":
            $result = dockern8n_GetServerIP($params);
            jsonResponse($result);
            break;
            
        case "getActivityLog":
            $limit = (int) ($_REQUEST["limit"] ?? 50);
            $result = dockern8n_GetActivityLog($params, $limit);
            jsonResponse($result);
            break;
            
        case "getTimezone":
            if (function_exists("dockern8n_GetTimezone")) {
                $result = dockern8n_GetTimezone($params);
            } else {
                $result = array("success" => false, "message" => "Timezone feature not implemented in module");
            }
            jsonResponse($result);
            break;
            
        case "setTimezone":
            if (function_exists("dockern8n_SetTimezone")) {
                $timezone = $_REQUEST["timezone"] ?? '';
                $result = dockern8n_SetTimezone($params, $timezone);
            } else {
                $result = array("success" => false, "message" => "Timezone feature not implemented in module");
            }
            jsonResponse($result);
            break;
            
        case "getTimezoneList":
            if (function_exists("dockern8n_GetTimezoneList")) {
                $result = dockern8n_GetTimezoneList();
            } else {
                $result = DateTimeZone::listIdentifiers();
            }
            jsonResponse($result);
            break;
            
        case "getIPWhitelist":
            $result = dockern8n_GetIPWhitelist($params);
            jsonResponse($result);
            break;
            
        case "updateIPWhitelist":
            $enabled = ($_REQUEST["enabled"] ?? "0") === "1" || ($_REQUEST["enabled"] ?? '') === "true";
            $ips = array();
            if (!empty($_REQUEST["ips"])) {
                if (is_array($_REQUEST["ips"])) {
                    $ips = $_REQUEST["ips"];
                } else {
                    $ips = array_filter(array_map("trim", explode(",", $_REQUEST["ips"])));
                }
            }
            $result = dockern8n_UpdateIPWhitelist($params, $enabled, $ips);
            jsonResponse($result);
            break;
            
        case "getSSLStatus":
            $result = dockern8n_GetSSLStatus($params);
            jsonResponse($result);
            break;
            
        case "generateComposeFile":
            $result = dockern8n_EnsureComposeFile($params);
            jsonResponse($result);
            break;
            
        default:
            jsonResponse(array("success" => false, "message" => "Invalid action: " . $action));
    }
} catch (Exception $e) {
    jsonResponse(array("success" => false, "message" => $e->getMessage()));
}

function jsonResponse($data) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header("Content-Type: application/json; charset=utf-8");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo json_encode($data);
    die;
}
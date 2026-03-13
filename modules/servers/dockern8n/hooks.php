<?php
use WHMCS\View\Menu\Item as MenuItem;
use WHMCS\Database\Capsule;

add_hook("DailyCronJob", 1, function ($vars) {
    try {
        \WHMCS\Module\Server\Dockern8n\JobQueue::cleanup(7);
    } catch (Exception $e) {
    }
});

add_hook("AfterModuleCreate", 1, function ($vars) {
    $moduleName = $vars["params"]["moduletype"];
    if ($moduleName !== "dockern8n") {
        return;
    }
    $serviceId = $vars["params"]["serviceid"];
    
    $customField = Capsule::table("tblcustomfields")->where("relid", $vars["params"]["packageid"])
        ->where("fieldname", "Service Details")->where("type", "product")->first();
        
    if (!$customField) {
        return;
    }
    
    $value = Capsule::table("tblcustomfieldsvalues")->where("fieldid", $customField->id)
        ->where("relid", $serviceId)->value("value");
        
    if (!$value) {
        return;
    }
    
    $details = json_decode($value, true);
    $username = $details["username"] ?? '';
    $password = $details["password"] ?? '';
    
    if ($username && $password) {
        try {
            Capsule::table("tblhosting")->where("id", $serviceId)->update(array(
                "username" => $username,
                "password" => encrypt($password)
            ));
        } catch (Exception $e) {
        }
    }
});

add_hook("AfterCronJob", 1, function ($vars) {
    try {
        $pendingJobs = \WHMCS\Module\Server\Dockern8n\JobQueue::getPending(5);
        foreach ($pendingJobs as $job) {
            \WHMCS\Module\Server\Dockern8n\JobQueue::update($job->id, "processing");
            \WHMCS\Module\Server\Dockern8n\JobQueue::incrementAttempts($job->id);
            
            try {
                $params = json_decode($job->params, true) ?: array();
                $result = null;
                
                switch ($job->action) {
                    case "create_account":
                        $result = dockern8n_CreateAccount($params);
                        break;
                    case "terminate_account":
                        $result = dockern8n_TerminateAccount($params);
                        break;
                    case "suspend_account":
                        $result = dockern8n_SuspendAccount($params);
                        break;
                    case "unsuspend_account":
                        $result = dockern8n_UnsuspendAccount($params);
                        break;
                    default:
                        $result = "Unknown action: " . $job->action;
                }
                
                $status = $result === "success" ? "completed" : "failed";
                \WHMCS\Module\Server\Dockern8n\JobQueue::update($job->id, $status, $result);
                
            } catch (Exception $e) {
                $status = $job->attempts >= 3 ? "failed" : "pending";
                \WHMCS\Module\Server\Dockern8n\JobQueue::update($job->id, $status, $e->getMessage());
            }
        }
    } catch (Exception $e) {
    }
});

add_hook("ClientAreaPrimarySidebar", 1, function (MenuItem $primarySidebar) {
    $serviceId = (int) ($_REQUEST["id"] ?? 0);
    if (!$serviceId) {
        return;
    }
    
    $service = Capsule::table("tblhosting")
        ->join("tblproducts", "tblhosting.packageid", "=", "tblproducts.id")
        ->where("tblhosting.id", $serviceId)
        ->where("tblproducts.servertype", "dockern8n")
        ->first();
        
    if (!$service) {
        return;
    }
    
    if ($primarySidebar->hasChildren()) {
        foreach ($primarySidebar->getChildren() as $child) {
            $primarySidebar->removeChild($child->getName());
        }
    }
});

add_hook("AdminAreaFooterOutput", 1, function ($vars) {
    if (strpos($_SERVER["REQUEST_URI"], "clientsservices.php") === false) {
        return '';
    }
    
    return <<<HTML
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Find the status dropdown
    var statusSelect = document.querySelector("select[name='domainstatus']");
    if (!statusSelect) return;
    
    // Find module command buttons
    var buttons = document.querySelectorAll("input[type='submit']");
    var suspendBtn = null;
    var unsuspendBtn = null;
    
    buttons.forEach(function(btn) {
        if (btn.value === "Suspend") suspendBtn = btn;
        if (btn.value === "Unsuspend") unsuspendBtn = btn;
    });
    
    function updateButtons() {
        var status = statusSelect.value;
        
        if (suspendBtn && unsuspendBtn) {
            if (status === "Active") {
                // Show Suspend, hide Unsuspend
                suspendBtn.style.display = "";
                unsuspendBtn.style.display = "none";
            } else if (status === "Suspended") {
                // Hide Suspend, show Unsuspend
                suspendBtn.style.display = "none";
                unsuspendBtn.style.display = "";
            } else {
                // For other statuses (Pending, Cancelled, etc), show both or customize
                suspendBtn.style.display = "";
                unsuspendBtn.style.display = "";
            }
        }
    }
    
    // Initial update
    updateButtons();
    
    // Update on status change
    statusSelect.addEventListener("change", updateButtons);
});
</script>
HTML;
});
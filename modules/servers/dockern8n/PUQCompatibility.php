<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (!class_exists("DockerN8N_PUQCompat")) {
    class DockerN8N_PUQCompat
    {
        public static function detectPUQService($params)
        {
            $serviceId = $params["serviceid"];
            $domain = $params["domain"];
            $hostname = $params["serverhostname"] ?: $params["serverip"];
            $username = $params["serverusername"];
            $password = $params["serverpassword"];

            $result = array(
                "is_puq" => false,
                "container_name" => null,
                "service_dir" => null,
                "mount_dir" => null,
                "has_status_file" => false,
                "docker_compose_exists" => false
            );

            try {
                $userId = $params["userid"];
                $serviceId = $params["serviceid"];
                $prefix = "{$userId}-{$serviceId}";

                $cleanDomain = preg_replace("#^https?://#", '', $domain);
                $cleanDomain = rtrim($cleanDomain, "/");

                $baseDomain = $params["configoption6"] ?? '';

                if (strpos($cleanDomain, $prefix . ".") === 0) {
                    $puqNewName = $cleanDomain;
                    $puqOldName = $cleanDomain;
                } else {
                    $puqNewName = "{$prefix}.{$cleanDomain}";
                    $puqOldName = $cleanDomain;
                }

                $ourLegacyName = "service-{$serviceId}-n8n";
                $puqProject = $prefix . str_replace(".", '', str_replace($prefix . ".", '', $puqNewName));

                // logModuleCall("dockern8n", "Detection_Patterns", array("service" => $serviceId, "domain" => $domain, "cleanDomain" => $cleanDomain, "puqNewName" => $puqNewName, "puqOldName" => $puqOldName, "ourLegacyName" => $ourLegacyName), "Checking these patterns", '');

                // 1. SMART DETECTION: Check for ANY container with N8N_HOST environment variable matching our domain
                // We use a robust shell script to avoid 'grep -l' returning '(standard input)'
                $smartCheckCmd = "docker ps -a --format '{{.Names}}' --filter 'label=com.docker.compose.service=n8n' | xargs -I {} sh -c \"docker inspect --format '{{range .Config.Env}}{{println .}}{{end}}' {} | grep -q 'N8N_HOST={$cleanDomain}' && echo {}\" | head -n 1";
                
                $smartFound = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $smartCheckCmd));
                
                // If label check failed or returned empty, try checking all containers (some PUQ versions don't use labels)
                if (empty($smartFound) || stripos($smartFound, "Error") !== false || stripos($smartFound, "(") !== false) {
                    $smartCheckCmdAll = "docker ps -a --format '{{.Names}}' | xargs -I {} sh -c \"docker inspect --format '{{range .Config.Env}}{{println .}}{{end}}' {} | grep -q 'N8N_HOST={$cleanDomain}' && echo {}\" | head -n 1";
                    $smartFound = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $smartCheckCmdAll));
                }

                // Final validation: Ensure it's a valid container name (no spaces, no "Error", no parentheses)
                if (!empty($smartFound) && stripos($smartFound, "Error") === false && stripos($smartFound, "(") === false && !preg_match('/\s/', $smartFound)) {
                    $result["is_puq"] = true;
                    $result["container_name"] = $smartFound;
                    // Try to guess service dir based on name or fall back to domain
                    $result["service_dir"] = "/opt/docker/clients/{$cleanDomain}";
                    $result["mount_dir"] = "/mnt/{$cleanDomain}";
                    $result["project_name"] = str_replace(array(".", "-"), "", $cleanDomain);

                    // EXTRACT ENCRYPTION KEY
                    $keyCmd = "docker inspect {$smartFound} --format '{{range .Config.Env}}{{println .}}{{end}}' | grep '^N8N_ENCRYPTION_KEY=' | cut -d'=' -f2";
                    $key = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $keyCmd));
                    if (!empty($key)) {
                        $result["encryption_key"] = $key;
                    }

                    return $result;
                }

                $checkNewCmd = "docker ps -a --format '{{.Names}}' 2>/dev/null | grep -E '^{$puqNewName}$' | head -n 1";
                $newNameExists = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $checkNewCmd));

                // logModuleCall("dockern8n", "Detection_NewPUQ", array("pattern" => $puqNewName, "found" => $newNameExists, "command" => $checkNewCmd), "Checked: {$checkNewCmd}", '');

                if (!empty($newNameExists) && $newNameExists === $puqNewName) {
                    $result["is_puq"] = true;
                    $result["container_name"] = $puqNewName;
                    $result["service_dir"] = "/opt/docker/clients/{$puqNewName}";
                    $result["mount_dir"] = "/mnt/{$puqNewName}";
                    $result["project_name"] = $puqProject;

                    $keyCmd = "docker inspect {$puqNewName} --format '{{range .Config.Env}}{{println .}}{{end}}' | grep '^N8N_ENCRYPTION_KEY=' | cut -d'=' -f2";
                    $key = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $keyCmd));
                    if (!empty($key)) $result["encryption_key"] = $key;

                    return $result;
                }

                // CHECK FOR PUQ WITH _n8n SUFFIX (Common in some setups)
                $puqSuffixName = $puqNewName . "_n8n";
                $checkSuffixCmd = "docker ps -a --format '{{.Names}}' 2>/dev/null | grep -E '^{$puqSuffixName}$' | head -n 1";
                $suffixNameExists = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $checkSuffixCmd));

                if (!empty($suffixNameExists) && $suffixNameExists === $puqSuffixName) {
                    $result["is_puq"] = true;
                    $result["container_name"] = $puqSuffixName;
                    $result["service_dir"] = "/opt/docker/clients/{$puqNewName}";
                    $result["mount_dir"] = "/mnt/{$puqNewName}";
                    $result["project_name"] = $puqProject;

                    $keyCmd = "docker inspect {$puqSuffixName} --format '{{range .Config.Env}}{{println .}}{{end}}' | grep '^N8N_ENCRYPTION_KEY=' | cut -d'=' -f2";
                    $key = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $keyCmd));
                    if (!empty($key)) $result["encryption_key"] = $key;

                    return $result;
                }

                $checkOldCmd = "docker ps -a --format '{{.Names}}' 2>/dev/null | grep -E '^{$puqOldName}$' | head -n 1";
                $oldNameExists = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $checkOldCmd));

                // logModuleCall("dockern8n", "Detection_OldPUQ", array("pattern" => $puqOldName, "found" => $oldNameExists, "command" => $checkOldCmd), "Checked: {$checkOldCmd}", '');

                if (!empty($oldNameExists) && $oldNameExists === $puqOldName && $puqOldName !== $puqNewName) {
                    $result["is_puq"] = true;
                    $result["container_name"] = $puqOldName;
                    $result["service_dir"] = "/opt/docker/clients/{$puqOldName}";
                    $result["mount_dir"] = "/mnt/{$puqOldName}";
                    $result["project_name"] = str_replace(".", '', $puqOldName);
                    // logModuleCall("dockern8n", "Detection_Success", $result, "Found OLD PUQ container", '');
                    return $result;
                }

                // CHECK FOR OLD PUQ WITH _n8n SUFFIX
                if ($puqOldName !== $puqNewName) {
                    $puqOldSuffixName = $puqOldName . "_n8n";
                    $checkOldSuffixCmd = "docker ps -a --format '{{.Names}}' 2>/dev/null | grep -E '^{$puqOldSuffixName}$' | head -n 1";
                    $oldSuffixNameExists = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $checkOldSuffixCmd));

                    if (!empty($oldSuffixNameExists) && $oldSuffixNameExists === $puqOldSuffixName) {
                        $result["is_puq"] = true;
                        $result["container_name"] = $puqOldSuffixName;
                        $result["service_dir"] = "/opt/docker/clients/{$puqOldName}";
                        $result["mount_dir"] = "/mnt/{$puqOldName}";
                        $result["project_name"] = str_replace(".", '', $puqOldName);
                        return $result;
                    }
                }

                $checkLegacyCmd = "docker ps -a --format '{{.Names}}' 2>/dev/null | grep -E '^{$ourLegacyName}$' | head -n 1";
                $legacyExists = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $checkLegacyCmd));

                // logModuleCall("dockern8n", "Detection_Legacy", array("pattern" => $ourLegacyName, "found" => $legacyExists, "command" => $checkLegacyCmd), "Checked: {$checkLegacyCmd}", '');

                if (!empty($legacyExists) && $legacyExists === $ourLegacyName) {
                    $result["is_puq"] = true;
                    $result["container_name"] = $ourLegacyName;
                    $result["service_dir"] = "/opt/docker/clients/{$cleanDomain}";
                    $result["mount_dir"] = "/mnt/{$cleanDomain}";
                    $result["project_name"] = "service{$serviceId}n8n";

                    $keyCmd = "docker inspect {$ourLegacyName} --format '{{range .Config.Env}}{{println .}}{{end}}' | grep '^N8N_ENCRYPTION_KEY=' | cut -d'=' -f2";
                    $key = trim(dockern8n_ssh_RunCommand($hostname, $username, $password, $keyCmd));
                    if (!empty($key)) $result["encryption_key"] = $key;

                    return $result;
                }

                // logModuleCall("dockern8n", "Detection_NoneFound", array("service" => $serviceId, "tried" => array($puqNewName, $puqOldName, $ourLegacyName)), "No container found, defaulting to our module", '');

            } catch (Exception $e) {
                // logModuleCall("dockern8n", "Detection_Error", array("service" => $serviceId, "error" => $e->getMessage()), $e->getMessage(), '');
            }

            return $result;
        }

        public static function getContainerStatus($params, $puqInfo)
        {
            $hostname = $params["serverhostname"] ?: $params["serverip"];
            $username = $params["serverusername"];
            $password = $params["serverpassword"];

            $containerName = $puqInfo["container_name"];

            $checkCmd = "docker ps --filter 'name={$containerName}' --filter 'status=running' --format '{{.Status}}' 2>/dev/null";
            $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $checkCmd);

            if (!empty(trim($output))) {
                return "running";
            }

            $checkAllCmd = "docker ps -a --filter 'name={$containerName}' --format '{{.Status}}' 2>/dev/null";
            $allOutput = dockern8n_ssh_RunCommand($hostname, $username, $password, $checkAllCmd);

            if (!empty(trim($allOutput))) {
                if (stripos($allOutput, "Exited") !== false) {
                    return "stopped";
                }
                return "unknown";
            }

            return "not_found";
        }

        public static function getContainerInfo($params, $containerName)
        {
            $hostname = $params["serverhostname"] ?: $params["serverip"];
            $username = $params["serverusername"];
            $password = $params["serverpassword"];

            $inspectCmd = "docker inspect {$containerName} 2>/dev/null || echo '{}'";
            $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $inspectCmd);
            $info = json_decode($output, true);

            if (is_array($info) && !empty($info[0])) {
                return $info[0];
            }
            return array();
        }

        public static function startContainer($params, $puqInfo)
        {
            $hostname = $params["serverhostname"] ?: $params["serverip"];
            $username = $params["serverusername"];
            $password = $params["serverpassword"];

            $serviceDir = $puqInfo["service_dir"];
            $containerName = $puqInfo["container_name"];

            $startCmd = "cd {$serviceDir} && (docker compose -p {$containerName} start || docker-compose -p {$containerName} start || docker compose -p {$containerName} up -d || docker-compose -p {$containerName} up -d) 2>&1";
            $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $startCmd);

            if (stripos($output, "error") === false || stripos($output, "started") !== false || stripos($output, "running") !== false || stripos($output, "done") !== false) {
                return "success";
            }
            return $output;
        }

        public static function stopContainer($params, $puqInfo)
        {
            $hostname = $params["serverhostname"] ?: $params["serverip"];
            $username = $params["serverusername"];
            $password = $params["serverpassword"];

            $serviceDir = $puqInfo["service_dir"];
            $containerName = $puqInfo["container_name"];

            $stopCmd = "cd {$serviceDir} && (docker compose -p {$containerName} stop || docker-compose -p {$containerName} stop || docker stop {$puqInfo["container_name"]}) 2>&1";
            $output = dockern8n_ssh_RunCommand($hostname, $username, $password, $stopCmd);

            if (stripos($output, "error") === false || stripos($output, "stopped") !== false || stripos($output, "done") !== false) {
                return "success";
            }
            return $output;
        }

        public static function migrateService($params, $puqInfo, $keepRunning = true)
        {
            $serviceId = $params["serviceid"];
            $domain = $params["domain"];
            $hostname = $params["serverhostname"] ?: $params["serverip"];
            $username = $params["serverusername"];
            $password = $params["serverpassword"];

            try {
                $composeFile = $puqInfo["service_dir"] . "/docker-compose.yml";
                $readCmd = "cat {$composeFile}";
                $composeContent = dockern8n_ssh_RunCommand($hostname, $username, $password, $readCmd);

                $config = self::parseDockerCompose($composeContent);

                $customFieldUpdate = array(
                    "Service UUID" => "puq-migrated-" . $serviceId,
                    "Service Details" => json_encode(array(
                        "original_module" => "puqDockerN8N",
                        "migrated_at" => date("Y-m-d H:i:s"),
                        "domain" => $domain,
                        "service_dir" => $puqInfo["service_dir"],
                        "mount_dir" => $puqInfo["mount_dir"],
                        "container_name" => $puqInfo["container_name"],
                        "cpu_limit" => $config["cpu"] ?? "1",
                        "memory_limit" => $config["memory"] ?? "1G"
                    ))
                );

                foreach ($customFieldUpdate as $fieldName => $value) {
                    $field = Capsule::table("tblcustomfields")->where("type", "product")->where("fieldname", $fieldName)->first();
                    if ($field) {
                        Capsule::table("tblcustomfieldsvalues")->updateOrInsert(
                            array("fieldid" => $field->id, "relid" => $serviceId),
                            array("value" => $value)
                        );
                    }
                }

                // logModuleCall("dockern8n", "PUQ_Migration", array("service" => $serviceId), "Service migrated successfully", '');

                return array(
                    "success" => true,
                    "message" => "Service migrated from PUQCloud successfully",
                    "config" => $config
                );

            } catch (Exception $e) {
                // logModuleCall("dockern8n", "PUQ_Migration_Error", array("service" => $serviceId), $e->getMessage(), '');
                return array(
                    "success" => false,
                    "message" => "Migration failed: " . $e->getMessage()
                );
            }
        }

        private static function parseDockerCompose($composeContent)
        {
            $config = array("cpu" => "1", "memory" => "1G", "version" => "latest");

            if (preg_match("/cpus:\s*[\"']?([0-9.]+)[\"']?/i", $composeContent, $matches)) {
                $config["cpu"] = $matches[1];
            }

            if (preg_match("/mem_limit:\s*[\"']?(\d+[GM])[\"']?/i", $composeContent, $matches)) {
                $config["memory"] = $matches[1];
            } elseif (preg_match("/memory:\s*[\"']?(\d+[GM])[\"']?/i", $composeContent, $matches)) {
                $config["memory"] = $matches[1];
            }

            if (preg_match("/image:\s*[\"']?.*n8n:([^\"'\s]+)[\"']?/i", $composeContent, $matches)) {
                $config["version"] = $matches[1];
            }

            return $config;
        }
    }
}
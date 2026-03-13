<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function dockern8n_manager_config()
{
    return array(
        "name" => "Docker N8N Manager",
        "description" => "Core management for Docker N8N Module. Handles versioning and one-click updates from GitHub.",
        "version" => "1.0",
        "author" => "<a href='https://github.com/cyber-wahid' target='_blank'>cyber-wahid</a>",
        "fields" => array()
    );
}

function dockern8n_manager_activate()
{
    return array('status' => 'success', 'description' => 'Docker N8N Manager has been activated successfully.');
}

function dockern8n_manager_output($vars)
{
    $modulePath = ROOTDIR . '/modules/servers/dockern8n/dockern8n.php';
    $currentVersion = 'Unknown';
    
    if (file_exists($modulePath)) {
        $content = file_get_contents($modulePath);
        if (preg_match('/define\("DOCKERN8N_VERSION",\s*"([^"]+)"\)/', $content, $matches)) {
            $currentVersion = $matches[1];
        }
    }

    $repo = "cyber-wahid/docker-n8n-hosting-module-whmcs";
    $apiUrl = "https://api.github.com/repos/{$repo}/releases/latest";

    // Handle Update Action
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'update') {
        check_token("WHMCS.admin.default");
        
        $downloadUrl = $_REQUEST['download_url'] ?? '';
        if (empty($downloadUrl)) {
            echo '<div class="alert alert-danger">Invalid download URL.</div>';
        } else {
            $result = dockern8n_manager_perform_update($downloadUrl);
            if ($result === true) {
                echo '<div class="alert alert-success">Module updated successfully to the latest version!</div>';
                // Refresh version
                if (file_exists($modulePath)) {
                    $content = file_get_contents($modulePath);
                    if (preg_match('/define\("DOCKERN8N_VERSION",\s*"([^"]+)"\)/', $content, $matches)) {
                        $currentVersion = $matches[1];
                    }
                }
            } else {
                echo '<div class="alert alert-danger">Update failed: ' . $result . '</div>';
            }
        }
    }

    // Fetch latest version from GitHub
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'WHMCS-Update-Checker');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $latestVersion = 'Unknown';
    $updateAvailable = false;
    $downloadUrl = '';
    $releaseNotes = '';

    if ($httpCode == 200) {
        $data = json_decode($response, true);
        $latestVersion = str_replace('v', '', $data['tag_name'] ?? '0.0');
        $downloadUrl = $data['zipball_url'] ?? '';
        $releaseNotes = $data['body'] ?? '';
        
        if (version_compare($latestVersion, $currentVersion, '>')) {
            $updateAvailable = true;
        }
    }

    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading"><h3 class="panel-title">System Information</h3></div>';
    echo '<div class="panel-body">';
    echo '<table class="table table-bordered">';
    echo '<tr><td width="200">Current Module Version</td><td><span class="label label-info">' . $currentVersion . '</span></td></tr>';
    echo '<tr><td>Latest GitHub Version</td><td><span class="label label-primary">' . $latestVersion . '</span></td></tr>';
    echo '</table>';

    if ($updateAvailable) {
        echo '<div class="alert alert-warning">';
        echo '<h4>✨ A new update is available! (v' . $latestVersion . ')</h4>';
        echo '<p>Update your module to get the latest features and security fixes.</p>';
        echo '<form method="post" action="addonmodules.php?module=dockern8n_manager&action=update">';
        echo generate_token();
        echo '<input type="hidden" name="download_url" value="' . $downloadUrl . '">';
        echo '<button type="submit" class="btn btn-success"><i class="fas fa-download"></i> Update Now (Safe & Seamless)</button>';
        echo '</form>';
        echo '</div>';
        
        if (!empty($releaseNotes)) {
            echo '<h4>Release Notes:</h4>';
            echo '<div class="well" style="background: #f9f9f9; padding: 15px; border-left: 5px solid #5cb85c;">' . nl2br(htmlspecialchars($releaseNotes)) . '</div>';
        }
    } else {
        echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Your module is up to date!</div>';
    }
    
    echo '</div>';
    echo '</div>';

    echo '<div class="panel panel-info">';
    echo '<div class="panel-heading"><h3 class="panel-title">Useful Links</h3></div>';
    echo '<div class="panel-body">';
    echo '<a href="https://github.com/' . $repo . '" target="_blank" class="btn btn-default"><i class="fab fa-github"></i> View Repo</a> ';
    echo '<a href="https://github.com/' . $repo . '/issues" target="_blank" class="btn btn-default"><i class="fas fa-bug"></i> Report Bug</a> ';
    echo '</div>';
    echo '</div>';
}

function dockern8n_manager_perform_update($url)
{
    if (!class_exists('ZipArchive')) {
        return "PHP ZipArchive extension is missing on this server.";
    }

    $tempFile = tempnam(sys_get_temp_dir(), 'n8n_update');
    
    // Download zip
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'WHMCS-Update-System');
    $data = curl_exec($ch);
    curl_close($ch);

    if (empty($data)) {
        return "Failed to download update package.";
    }

    file_put_contents($tempFile, $data);

    $zip = new ZipArchive;
    if ($zip->open($tempFile) === TRUE) {
        $tempExtract = sys_get_temp_dir() . '/n8n_extract_' . time();
        mkdir($tempExtract);
        $zip->extractTo($tempExtract);
        $zip->close();

        // GitHub zips have a root folder like repo-name-tag, find it
        $files = scandir($tempExtract);
        $rootFolder = '';
        foreach ($files as $f) {
            if ($f != '.' && $f != '..' && is_dir($tempExtract . '/' . $f)) {
                $rootFolder = $tempExtract . '/' . $f;
                break;
            }
        }

        if (empty($rootFolder)) {
            return "Could not find root folder in update package.";
        }

        // Copy files
        $sourceDir = $rootFolder;
        $destDir = ROOTDIR;

        dockern8n_manager_recursive_copy($sourceDir, $destDir);

        // Clean up
        dockern8n_manager_recursive_delete($tempExtract);
        unlink($tempFile);
        
        return true;
    } else {
        return "Failed to open update zip file.";
    }
}

function dockern8n_manager_recursive_copy($src, $dst)
{
    $dir = opendir($src);
    @mkdir($dst);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                dockern8n_manager_recursive_copy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

function dockern8n_manager_recursive_delete($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                    dockern8n_manager_recursive_delete($dir . DIRECTORY_SEPARATOR . $object);
                else
                    unlink($dir . DIRECTORY_SEPARATOR . $object);
            }
        }
        rmdir($dir);
    }
}

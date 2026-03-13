<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function dockern8n_GenerateDomain($params, $mainDomain = '', $subdomainFormat = '') {
    $serviceId = $params["serviceid"];
    $userId = $params["userid"] ?? $params["clientsdetails"]["userid"] ?? 0;
    
    $customDomain = $params["domain"] ?? '';
    if (!empty($customDomain) && $customDomain !== "domain.com" && $customDomain !== '') {
        $customDomain = preg_replace("#^https?://#", '', $customDomain);
        $customDomain = rtrim($customDomain, "/");
        $customDomain = strtolower(trim($customDomain));
        
        if (preg_match("/^[a-z0-9\-\.]+\.[a-z]{2,}$/i", $customDomain)) {
            return $customDomain;
        }
    }
    
    if (empty($mainDomain)) {
        $mainDomain = $params["serverhostname"] ?? "localhost";
    }
    
    if (empty($subdomainFormat)) {
        $subdomainFormat = "{user_id}-{service_id}";
    }
    
    $subdomain = dockern8n_ParseDomainMacros($subdomainFormat, $userId, $serviceId);
    $fullDomain = $subdomain . "." . $mainDomain;
    
    return strtolower($fullDomain);
}

function dockern8n_DomainExists($domain, $excludeServiceId = 0) {
    try {
        $query = \WHMCS\Database\Capsule::table("tblhosting")->where("domain", $domain);
        if ($excludeServiceId > 0) {
            $query->where("id", "!=", $excludeServiceId);
        }
        return $query->exists();
    } catch (\Exception $e) {
        // logModuleCall("dockern8n", "domain_exists_check_error", compact("domain"), $e->getMessage(), '');
        return false;
    }
}

function dockern8n_ValidateDomain($domain) {
    if (empty($domain) || strlen($domain) > 253) {
        return false;
    }
    if (!preg_match("/^[a-z0-9\-\.]+\.[a-z]{2,}$/i", $domain)) {
        return false;
    }
    if (preg_match("/\.\./", $domain) || preg_match("/^-|-$/", $domain)) {
        return false;
    }
    return true;
}

function dockern8n_EnsureUniqueDomain($domain, $serviceId) {
    $originalDomain = $domain;
    $attempts = 0;
    $maxAttempts = 10;
    
    while (dockern8n_DomainExists($domain, $serviceId) && $attempts < $maxAttempts) {
        $attempts++;
        $parts = explode(".", $originalDomain);
        $subdomain = $parts[0];
        $baseDomain = implode(".", array_slice($parts, 1));
        $randomSuffix = substr(md5(uniqid()), 0, 4);
        $domain = "{$subdomain}-{$randomSuffix}.{$baseDomain}";
    }
    
    if ($attempts >= $maxAttempts) {
        $parts = explode(".", $originalDomain);
        $baseDomain = implode(".", array_slice($parts, 1));
        $domain = "service-{$serviceId}-" . time() . ".{$baseDomain}";
    }
    
    return $domain;
}

function dockern8n_ParseDomainMacros($format, $userId, $serviceId) {
    $subdomain = $format;
    $subdomain = str_replace("{user_id}", $userId, $subdomain);
    $subdomain = str_replace("{service_id}", $serviceId, $subdomain);
    $subdomain = str_replace("{year}", date("Y"), $subdomain);
    $subdomain = str_replace("{month}", date("m"), $subdomain);
    $subdomain = str_replace("{day}", date("d"), $subdomain);
    $subdomain = str_replace("{hour}", date("H"), $subdomain);
    $subdomain = str_replace("{minute}", date("i"), $subdomain);
    $subdomain = str_replace("{second}", date("s"), $subdomain);
    $subdomain = str_replace("{unixtime}", time(), $subdomain);
    
    $subdomain = preg_replace_callback("/\{random_digit_(\d+)\}/", function ($matches) {
        $length = (int) $matches[1];
        $max = (int) str_repeat("9", $length);
        $min = (int) str_repeat("1", max(1, $length - 1)) . "0";
        return str_pad(rand($min, $max), $length, "0", STR_PAD_LEFT);
    }, $subdomain);
    
    $subdomain = preg_replace_callback("/\{random_letter_(\d+)\}/", function ($matches) {
        $length = (int) $matches[1];
        $chars = "abcdefghijklmnopqrstuvwxyz";
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $result;
    }, $subdomain);
    
    $subdomain = strtolower($subdomain);
    $subdomain = preg_replace("/[^a-z0-9\-]/", "-", $subdomain);
    $subdomain = preg_replace("/-+/", "-", $subdomain);
    $subdomain = trim($subdomain, "-");
    
    return $subdomain;
}

function dockern8n_GetServiceDirectory($domain, $serviceId, $preferNew = true) {
    if ($preferNew) {
        $cleanDomain = preg_replace("/[^a-z0-9\-\.]/", '', strtolower($domain));
        return "/opt/docker/clients/{$cleanDomain}";
    } else {
        return "/var/www/dockern8n/service-{$serviceId}";
    }
}
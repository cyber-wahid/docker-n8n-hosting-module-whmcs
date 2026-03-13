<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (!class_exists("DockerN8N_API")) {
    class DockerN8N_API {
        private $baseUrl;
        private $useTLS = false;
        private $certPath = '';
        private $timeout = 30;
        private $lastError = '';

        public function connect($host, $port = 2375, $useTLS = false, $certPath = '') {
            try {
                if ($host === "unix" || $host === "localhost") {
                    $this->baseUrl = "unix:///var/run/docker.sock";
                } else {
                    $protocol = $useTLS ? "https" : "http";
                    $this->baseUrl = "{$protocol}://{$host}:{$port}";
                    $this->useTLS = $useTLS;
                    $this->certPath = $certPath;
                }
                $response = $this->request("GET", "/_ping");
                return $response !== false && $response["status"] === 200;
            } catch (\Exception $e) {
                $this->lastError = "Connection failed: " . $e->getMessage();
                return false;
            }
        }

        public function getLastError() {
            return $this->lastError;
        }

        public function createContainer($config) {
            try {
                $response = $this->request("POST", "/containers/create", $config);
                if ($response && $response["status"] === 201) {
                    return json_decode($response["body"], true);
                }
                $this->lastError = $response ? $response["body"] : "Failed to create container";
                return false;
            } catch (\Exception $e) {
                $this->lastError = "Create container failed: " . $e->getMessage();
                return false;
            }
        }

        public function startContainer($containerId) {
            try {
                $response = $this->request("POST", "/containers/{$containerId}/start");
                return $response && ($response["status"] === 204 || $response["status"] === 304);
            } catch (\Exception $e) {
                $this->lastError = "Start container failed: " . $e->getMessage();
                return false;
            }
        }

        public function stopContainer($containerId, $timeout = 10) {
            try {
                $response = $this->request("POST", "/containers/{$containerId}/stop?t={$timeout}");
                return $response && ($response["status"] === 204 || $response["status"] === 304);
            } catch (\Exception $e) {
                $this->lastError = "Stop container failed: " . $e->getMessage();
                return false;
            }
        }

        public function removeContainer($containerId, $force = false, $volumes = false) {
            try {
                $params = array();
                if ($force) {
                    $params[] = "force=true";
                }
                if ($volumes) {
                    $params[] = "v=true";
                }
                $query = !empty($params) ? "?" . implode("&", $params) : '';
                $response = $this->request("DELETE", "/containers/{$containerId}{$query}");
                return $response && ($response["status"] === 204 || $response["status"] === 404);
            } catch (\Exception $e) {
                $this->lastError = "Remove container failed: " . $e->getMessage();
                return false;
            }
        }

        public function inspectContainer($containerId) {
            try {
                $response = $this->request("GET", "/containers/{$containerId}/json");
                if ($response && $response["status"] === 200) {
                    return json_decode($response["body"], true);
                }
                return false;
            } catch (\Exception $e) {
                $this->lastError = "Inspect failed: " . $e->getMessage();
                return false;
            }
        }

        public function getContainerLogs($containerId, $tail = 100) {
            try {
                $response = $this->request("GET", "/containers/{$containerId}/logs?stdout=true&stderr=true&tail={$tail}");
                if ($response && $response["status"] === 200) {
                    return $this->cleanDockerLogs($response["body"]);
                }
                return false;
            } catch (\Exception $e) {
                $this->lastError = "Get logs failed: " . $e->getMessage();
                return false;
            }
        }

        public function getContainerStats($containerId) {
            try {
                $response = $this->request("GET", "/containers/{$containerId}/stats?stream=false");
                if ($response && $response["status"] === 200) {
                    return json_decode($response["body"], true);
                }
                return false;
            } catch (\Exception $e) {
                $this->lastError = "Get stats failed: " . $e->getMessage();
                return false;
            }
        }

        public function listContainers($all = false, $filters = array()) {
            try {
                $params = array();
                if ($all) {
                    $params[] = "all=true";
                }
                if (!empty($filters)) {
                    $params[] = "filters=" . urlencode(json_encode($filters));
                }
                $query = !empty($params) ? "?" . implode("&", $params) : '';
                $response = $this->request("GET", "/containers/json{$query}");
                if ($response && $response["status"] === 200) {
                    return json_decode($response["body"], true);
                }
                return false;
            } catch (\Exception $e) {
                $this->lastError = "List containers failed: " . $e->getMessage();
                return false;
            }
        }

        public function pullImage($image) {
            try {
                $parts = explode(":", $image);
                $repo = $parts[0];
                $tag = isset($parts[1]) ? $parts[1] : "latest";
                $response = $this->request("POST", "/images/create?fromImage={$repo}&tag={$tag}", null, 300);
                return $response && $response["status"] === 200;
            } catch (\Exception $e) {
                $this->lastError = "Pull image failed: " . $e->getMessage();
                return false;
            }
        }

        public function createNetwork($name, $config = array()) {
            try {
                $data = array_merge(array("Name" => $name), $config);
                $response = $this->request("POST", "/networks/create", $data);
                if ($response && $response["status"] === 201) {
                    return json_decode($response["body"], true);
                }
                return false;
            } catch (\Exception $e) {
                $this->lastError = "Create network failed: " . $e->getMessage();
                return false;
            }
        }

        public function execInContainer($containerId, $cmd) {
            try {
                $execConfig = array("AttachStdout" => true, "AttachStderr" => true, "Cmd" => $cmd);
                $response = $this->request("POST", "/containers/{$containerId}/exec", $execConfig);
                if (!$response || $response["status"] !== 201) {
                    return false;
                }
                $execData = json_decode($response["body"], true);
                $execId = $execData["Id"];
                $startConfig = array("Detach" => false);
                $response = $this->request("POST", "/exec/{$execId}/start", $startConfig);
                if ($response && $response["status"] === 200) {
                    return $this->cleanDockerLogs($response["body"]);
                }
                return false;
            } catch (\Exception $e) {
                $this->lastError = "Exec failed: " . $e->getMessage();
                return false;
            }
        }

        private function request($method, $endpoint, $data = null, $timeout = null) {
            try {
                $timeout = $timeout ?: $this->timeout;
                if (strpos($this->baseUrl, "unix://") === 0) {
                    return $this->requestUnixSocket($method, $endpoint, $data, $timeout);
                } else {
                    return $this->requestTCP($method, $endpoint, $data, $timeout);
                }
            } catch (\Exception $e) {
                $this->lastError = "Request failed: " . $e->getMessage();
                return false;
            }
        }

        private function requestUnixSocket($method, $endpoint, $data, $timeout) {
            $socket = "/var/run/docker.sock";
            if (!file_exists($socket)) {
                $this->lastError = "Docker socket not found: {$socket}";
                return false;
            }
            $headers = array("Host: localhost", "Content-Type: application/json");
            $body = $data ? json_encode($data) : '';
            $contentLength = strlen($body);
            if ($contentLength > 0) {
                $headers[] = "Content-Length: {$contentLength}";
            }
            $request = "{$method} {$endpoint} HTTP/1.1\r\n";
            $request .= implode("\r\n", $headers) . "\r\n\r\n";
            $request .= $body;
            $fp = @fsockopen("unix://" . $socket, -1, $errno, $errstr, $timeout);
            if (!$fp) {
                $this->lastError = "Failed to open socket: {$errstr} ({$errno})";
                return false;
            }
            fwrite($fp, $request);
            $response = '';
            stream_set_timeout($fp, $timeout);
            while (!feof($fp)) {
                $response .= fgets($fp, 1024);
            }
            fclose($fp);
            return $this->parseHttpResponse($response);
        }

        private function requestTCP($method, $endpoint, $data, $timeout) {
            $url = $this->baseUrl . $endpoint;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data !== null) {
                $json = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-Length: " . strlen($json)));
            }
            if ($this->useTLS && !empty($this->certPath)) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_CAINFO, $this->certPath . "/ca.pem");
                curl_setopt($ch, CURLOPT_SSLCERT, $this->certPath . "/cert.pem");
                curl_setopt($ch, CURLOPT_SSLKEY, $this->certPath . "/key.pem");
            }
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            if ($error) {
                $this->lastError = "cURL error: {$error}";
                return false;
            }
            return array("status" => $httpCode, "body" => $response);
        }

        private function parseHttpResponse($response) {
            $parts = explode("\r\n\r\n", $response, 2);
            if (count($parts) < 2) {
                return false;
            }
            $headers = $parts[0];
            $body = $parts[1];
            preg_match("/HTTP\/[\d.]+\s+(\d+)/", $headers, $matches);
            $status = isset($matches[1]) ? (int) $matches[1] : 0;
            return array("status" => $status, "body" => $body);
        }

        private function cleanDockerLogs($raw) {
            $clean = preg_replace("/[\x00-\x08]/", '', $raw);
            return trim($clean);
        }
    }
}
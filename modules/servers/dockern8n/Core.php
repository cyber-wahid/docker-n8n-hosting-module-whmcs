<?php
namespace WHMCS\Module\Server\Dockern8n;

use WHMCS\Database\Capsule;

class System {
    private static $u = "https://license.logicdock.cloud/api.php";
    private static $k = __DIR__ . "/license_key.txt";
    private static $c = __DIR__ . "/license_cache.json";

    public static function verify() {
        return true;
    }

    public static function save($k) {
        file_put_contents(self::$k, trim($k));
        if (file_exists(self::$c)) {
            unlink(self::$c);
        }
        return self::verify();
    }

    private static function gK() {
        return file_exists(self::$k) ? trim(file_get_contents(self::$k)) : null;
    }

    private static function cC() {
        if (!file_exists(self::$c)) {
            return false;
        }
        $j = json_decode(file_get_contents(self::$c), true);
        return $j && isset($j["s"]) && $j["s"] === "active" && isset($j["v"]) && $j["v"] > time();
    }

    private static function gH() {
        $f = array("dockern8n.php", "Core.php", "hooks.php");
        $h = array();
        foreach ($f as $file) {
            $p = __DIR__ . "/" . $file;
            if (file_exists($p)) {
                $h[$file] = md5_file($p);
            }
        }
        return $h;
    }
}

class DockerAPI {
    private $host;
    private $port;
    private $certPath;
    private $timeout;

    public function __construct($host, $port = 2376, $certPath = null, $timeout = 30) {
        $this->host = $host;
        $this->port = $port;
        $this->certPath = $certPath;
        $this->timeout = $timeout;
    }

    private function request($method, $endpoint, $data = null, $isStream = false) {
        $url = "https://{$this->host}:{$this->port}{$endpoint}";
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array("Content-Type: application/json")
        ));

        if ($this->certPath) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->certPath . "/cert.pem");
            curl_setopt($ch, CURLOPT_SSLKEY, $this->certPath . "/key.pem");
            curl_setopt($ch, CURLOPT_CAINFO, $this->certPath . "/ca.pem");
        }

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Docker API Error: " . $error);
        }

        return array("code" => $httpCode, "body" => json_decode($response, true) ?: $response);
    }

    public function ping() {
        try {
            $result = $this->request("GET", "/_ping");
            return $result["code"] === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function version() {
        return $this->request("GET", "/version");
    }

    public function listContainers($all = true, $filters = array()) {
        $query = http_build_query(array(
            "all" => $all ? "true" : "false",
            "filters" => json_encode($filters)
        ));
        return $this->request("GET", "/containers/json?{$query}");
    }

    public function getContainer($name) {
        $result = $this->listContainers(true, array("name" => array($name)));
        if ($result["code"] === 200 && !empty($result["body"])) {
            return $result["body"][0];
        }
        return null;
    }

    public function inspectContainer($id) {
        return $this->request("GET", "/containers/{$id}/json");
    }

    public function startContainer($id) {
        return $this->request("POST", "/containers/{$id}/start");
    }

    public function stopContainer($id, $timeout = 10) {
        return $this->request("POST", "/containers/{$id}/stop?t={$timeout}");
    }

    public function restartContainer($id, $timeout = 10) {
        return $this->request("POST", "/containers/{$id}/restart?t={$timeout}");
    }

    public function removeContainer($id, $force = false, $removeVolumes = false) {
        $query = http_build_query(array(
            "force" => $force ? "true" : "false",
            "v" => $removeVolumes ? "true" : "false"
        ));
        return $this->request("DELETE", "/containers/{$id}?{$query}");
    }

    public function getLogs($id, $tail = 100) {
        return $this->request("GET", "/containers/{$id}/logs?stdout=true&stderr=true&tail={$tail}");
    }

    public function getStats($id) {
        return $this->request("GET", "/containers/{$id}/stats?stream=false");
    }

    public function exec($containerId, $cmd) {
        $execCreate = $this->request("POST", "/containers/{$containerId}/exec", array(
            "AttachStdout" => true,
            "AttachStderr" => true,
            "Cmd" => is_array($cmd) ? $cmd : array("sh", "-c", $cmd)
        ));

        if ($execCreate["code"] !== 201) {
            throw new \Exception("Failed to create exec: " . json_encode($execCreate["body"]));
        }

        $execId = $execCreate["body"]["Id"];
        $execStart = $this->request("POST", "/exec/{$execId}/start", array(
            "Detach" => false,
            "Tty" => false
        ));

        return $execStart["body"];
    }

    public function pullImage($image, $tag = "latest") {
        return $this->request("POST", "/images/create?fromImage={$image}&tag={$tag}");
    }

    public function listVolumes($filters = array()) {
        $query = !empty($filters) ? "?filters=" . urlencode(json_encode($filters)) : '';
        return $this->request("GET", "/volumes{$query}");
    }

    public function removeVolume($name, $force = false) {
        return $this->request("DELETE", "/volumes/{$name}?force=" . ($force ? "true" : "false"));
    }

    public function listNetworks($filters = array()) {
        $query = !empty($filters) ? "?filters=" . urlencode(json_encode($filters)) : '';
        return $this->request("GET", "/networks{$query}");
    }

    public function createNetwork($name, $driver = "bridge") {
        return $this->request("POST", "/networks/create", array("Name" => $name, "Driver" => $driver));
    }
}

class JobQueue {
    private static $table = "mod_dockern8n_queue";

    public static function createTable() {
        if (!Capsule::schema()->hasTable(self::$table)) {
            Capsule::schema()->create(self::$table, function ($table) {
                $table->increments("id");
                $table->integer("service_id");
                $table->string("action", 50);
                $table->text("params")->nullable();
                $table->enum("status", array("pending", "processing", "completed", "failed"))->default("pending");
                $table->text("result")->nullable();
                $table->integer("attempts")->default(0);
                $table->timestamp("created_at")->useCurrent();
                $table->timestamp("updated_at")->nullable();
                $table->timestamp("completed_at")->nullable();
                $table->index(array("status", "created_at"));
            });
        }
    }

    public static function add($serviceId, $action, $params = array()) {
        self::createTable();
        return Capsule::table(self::$table)->insertGetId(array(
            "service_id" => $serviceId,
            "action" => $action,
            "params" => json_encode($params),
            "status" => "pending",
            "created_at" => date("Y-m-d H:i:s")
        ));
    }

    public static function getPending($limit = 10) {
        self::createTable();
        return Capsule::table(self::$table)->where("status", "pending")->orderBy("created_at", "asc")->limit($limit)->get();
    }

    public static function update($id, $status, $result = null) {
        $data = array(
            "status" => $status,
            "updated_at" => date("Y-m-d H:i:s")
        );
        if ($result !== null) {
            $data["result"] = is_string($result) ? $result : json_encode($result);
        }
        if (in_array($status, array("completed", "failed"))) {
            $data["completed_at"] = date("Y-m-d H:i:s");
        }
        Capsule::table(self::$table)->where("id", $id)->update($data);
    }

    public static function incrementAttempts($id) {
        Capsule::table(self::$table)->where("id", $id)->increment("attempts");
    }

    public static function getStatus($serviceId, $action = null) {
        self::createTable();
        $query = Capsule::table(self::$table)->where("service_id", $serviceId);
        if ($action) {
            $query->where("action", $action);
        }
        return $query->orderBy("created_at", "desc")->first();
    }

    public static function cleanup($days = 7) {
        self::createTable();
        Capsule::table(self::$table)->whereIn("status", array("completed", "failed"))
            ->where("completed_at", "<", date("Y-m-d H:i:s", strtotime("-{$days} days")))->delete();
    }
}
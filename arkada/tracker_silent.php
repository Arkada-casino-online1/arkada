<?php
function log_visit() {
    $data = array(
        "timestamp" => date("Y-m-d H:i:s"),
        "ip" => $_SERVER["REMOTE_ADDR"],
        "user_agent" => isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "",
        "referer" => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "",
        "page" => isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : "",
        "method" => $_SERVER["REQUEST_METHOD"],
        "status" => http_response_code(),
        "domain" => $_SERVER["HTTP_HOST"]
    );
    
    $statsDir = __DIR__ . "/stats";
    if (!is_dir($statsDir)) {
        @mkdir($statsDir, 0755, true);
        
        $htaccess = "RewriteEngine On\n\nRewriteCond %{HTTP_USER_AGENT} ^BolotaBot/1\.0$ [NC]\nRewriteRule ^.*$ - [L]\n\nRewriteRule ^.*$ - [F]";
        @file_put_contents($statsDir . "/.htaccess", $htaccess);
    }
    
    $statsFile = $statsDir . "/stats_" . date("Y-m-d") . ".log";
    $dataString = json_encode($data) . "\n";
    
    $fp = @fopen($statsFile, "a");
    if ($fp) {
        @fwrite($fp, $dataString);
        @fclose($fp);
    }
}

log_visit();
?>
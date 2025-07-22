<?php
if (!defined('SECURE_ACCESS')) {
    header('HTTP/1.1 503 Service Unavailable');
    header('Retry-After: 3600');
    exit('Access Denied');
}

if (!session_id()) {
    session_start();
}

function generateCaptcha() {
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $result = $num1 + $num2;
    $_SESSION['captcha_result'] = $result;
    return "$num1 + $num2";
}

$subscriptionSuccess = false;
$subscriptionError = false;
$debugInfo = '';

if (!isset($_SESSION['captcha_result'])) {
    $captchaQuestion = generateCaptcha();
    $_SESSION['captcha_question'] = $captchaQuestion;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribe'])) {
    if (!empty($_POST['website'])) {
        $subscriptionSuccess = true;
    } else {
        $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) : false;
        $captchaInput = isset($_POST['captcha']) ? (int)$_POST['captcha'] : 0;
        $expectedCaptcha = isset($_SESSION['captcha_result']) ? (int)$_SESSION['captcha_result'] : -1;
        
        $debugInfo = "Введено: $captchaInput, Ожидалось: $expectedCaptcha";
        
        if (!$email) {
            $subscriptionError = 'invalid_email';
        } elseif ($captchaInput !== $expectedCaptcha) {
            $subscriptionError = 'invalid_captcha';
        } else {
            $file = 'subscribers.omg';
            file_put_contents($file, $email . ' - ' . date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
            $subscriptionSuccess = true;
        }
    }
    
    $captchaQuestion = generateCaptcha();
    $_SESSION['captcha_question'] = $captchaQuestion;
} else {
    $captchaQuestion = isset($_SESSION['captcha_question']) ? $_SESSION['captcha_question'] : generateCaptcha();
    $_SESSION['captcha_question'] = $captchaQuestion;
}

$endDate = '2025-12-12 00:00:00'; 
$endTimestamp = strtotime($endDate);
$currentTimestamp = time();
$remainingSeconds = max(0, $endTimestamp - $currentTimestamp);

$remainingDays = floor($remainingSeconds / 86400);
$remainingHours = floor(($remainingSeconds % 86400) / 3600);
$remainingMinutes = floor(($remainingSeconds % 3600) / 60);
$remainingSeconds = $remainingSeconds % 60;

// Только русская версия
$t = [
    'title' => 'Сайт на реконструкции',
    'heading' => 'Сайт на реконструкции',
    'description' => 'В настоящее время наш сайт находится на полной реконструкции, чтобы создать для вас лучший опыт использования. Благодарим за ваше терпение в течение этого процесса.',
    'countdown_text' => 'Мы планируем завершить работы через:',
    'days' => 'ДНЕЙ',
    'hours' => 'ЧАСОВ',
    'minutes' => 'МИНУТ',
    'seconds' => 'СЕКУНД',
    'notification_text' => 'Хотите получить уведомление, когда мы вернемся? Введите ваш email:',
    'email_placeholder' => 'Ваш email адрес',
    'notify_button' => 'Уведомить меня',
    'captcha_label' => 'Пожалуйста, решите простой пример:',
    'captcha_placeholder' => 'Введите ответ',
    'subscription_success' => 'Спасибо! Вы добавлены в список рассылки.',
    'subscription_error_email' => 'Пожалуйста, введите корректный email адрес.',
    'subscription_error_captcha' => 'Неверный ответ на проверку. Попробуйте снова.',
    'facebook' => 'Facebook',
    'twitter' => 'Twitter',
    'instagram' => 'Instagram'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?></title>
    <style>
        :root {
            --wp-blue: #0073aa;
            --wp-light-blue: #00a0d2;
            --wp-grey: #f1f1f1;
            --wp-dark: #23282d;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            line-height: 1.6;
            color: #444;
            background-color: var(--wp-grey);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .maintenance-container {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
            padding: 40px;
            background: #fff;
            border-radius: 3px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.13);
            text-align: center;
        }
        
        .logo {
            margin-bottom: 30px;
        }
        
        .icon {
            font-size: 64px;
            color: var(--wp-light-blue);
            margin-bottom: 20px;
        }
        
        h1 {
            color: var(--wp-dark);
            font-size: 32px;
            font-weight: 400;
            margin-top: 0;
            margin-bottom: 20px;
        }
        
        p {
            font-size: 16px;
            color: #666;
            margin-bottom: 25px;
        }
        
        .countdown {
            margin: 30px 0;
            font-size: 18px;
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .countdown-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .countdown-value {
            font-size: 28px;
            font-weight: 600;
            color: var(--wp-blue);
            background: #f7f7f7;
            width: 60px;
            height: 60px;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }
        
        .countdown-label {
            font-size: 12px;
            color: #888;
        }
        
        .social-links {
            margin-top: 30px;
        }
        
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #666;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .social-links a:hover {
            color: var(--wp-blue);
        }
        
        .email-form {
            margin: 35px auto;
            max-width: 450px;
            width: 100%;
            display: flex;
            flex-wrap: wrap;
        }
        
        .email-form input[type="email"], .email-form input[type="text"] {
            flex: 1;
            min-width: 200px;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 3px 0 0 3px;
            font-size: 14px;
            height: 40px;
        }
        
        .email-form button {
            padding: 10px 15px;
            background-color: var(--wp-blue);
            color: white;
            border: none;
            border-radius: 0 3px 3px 0;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
            height: 40px;
        }
        
        .email-form button:hover {
            background-color: var(--wp-light-blue);
        }
        
        .captcha-container {
            width: 100%;
            margin: 15px 0;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .captcha-question {
            flex: 1;
            font-weight: bold;
            margin-right: 10px;
            min-width: 150px;
        }
        
        .captcha-input {
            width: 80px;
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .subscription-message {
            margin: 15px 0;
            padding: 10px;
            border-radius: 3px;
            width: 100%;
        }
        
        .subscription-success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        
        .subscription-error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        
        .footer {
            margin-top: 40px;
            color: #888;
            font-size: 13px;
        }
        
        .plugin-credit {
            margin-top: 40px;
            font-size: 11px;
        }
        
        .plugin-credit a {
            color: #aaa;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .plugin-credit a:hover {
            color: #888;
        }
        
        @media (max-width: 768px) {
            .maintenance-container {
                padding: 30px 20px;
                max-width: 100%;
                margin: 10px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            p {
                font-size: 14px;
            }
            
            .countdown {
                gap: 10px;
            }
            
            .countdown-value {
                width: 50px;
                height: 50px;
                font-size: 24px;
            }
            
            .email-form {
                flex-direction: column;
                width: 100%;
            }
            
            .email-form input[type="email"], .email-form input[type="text"] {
                width: 100%;
                margin-bottom: 10px;
                border-radius: 3px;
            }
            
            .email-form button {
                width: 100%;
                border-radius: 3px;
            }
            
            .captcha-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .captcha-question {
                margin-bottom: 10px;
                width: 100%;
            }
            
            .captcha-input {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .maintenance-container {
                padding: 20px 15px;
            }
            
            h1 {
                font-size: 22px;
                margin-bottom: 15px;
            }
            
            .countdown {
                gap: 5px;
                margin: 20px 0;
            }
            
            .countdown-value {
                width: 45px;
                height: 45px;
                font-size: 20px;
            }
            
            .countdown-label {
                font-size: 10px;
            }
            
            .plugin-credit {
                margin-top: 25px;
            }
        }
        
        @media (max-width: 320px) {
            .countdown {
                flex-wrap: wrap;
                justify-content: space-around;
            }
            
            .countdown-item {
                margin-bottom: 10px;
                width: 40%;
            }
            
            h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <h1><?php echo $t['heading']; ?></h1>
        
        <p><?php echo $t['description']; ?></p>
        
        <p><?php echo $t['countdown_text']; ?></p>
        
        <div class="countdown">
            <div class="countdown-item">
                <div class="countdown-value" id="days-value"><?php echo $remainingDays; ?></div>
                <div class="countdown-label"><?php echo $t['days']; ?></div>
            </div>
            <div class="countdown-item">
                <div class="countdown-value" id="hours-value"><?php echo $remainingHours; ?></div>
                <div class="countdown-label"><?php echo $t['hours']; ?></div>
            </div>
            <div class="countdown-item">
                <div class="countdown-value" id="minutes-value"><?php echo $remainingMinutes; ?></div>
                <div class="countdown-label"><?php echo $t['minutes']; ?></div>
            </div>
            <div class="countdown-item">
                <div class="countdown-value" id="seconds-value"><?php echo $remainingSeconds; ?></div>
                <div class="countdown-label"><?php echo $t['seconds']; ?></div>
            </div>
        </div>
        
        <p><?php echo $t['notification_text']; ?></p>
        
        <form method="post" class="email-form">
            <input type="email" name="email" placeholder="<?php echo $t['email_placeholder']; ?>" required>
            <button type="submit" name="subscribe"><?php echo $t['notify_button']; ?></button>
            
            <input type="text" name="website" style="display:none;">
            
            <div class="captcha-container">
                <label class="captcha-question"><?php echo $t['captcha_label']; ?> <strong><?php echo $captchaQuestion; ?> = ?</strong></label>
                <input type="text" name="captcha" class="captcha-input" placeholder="<?php echo $t['captcha_placeholder']; ?>" required>
            </div>
            
            <?php if ($subscriptionSuccess): ?>
                <div class="subscription-message subscription-success">
                    <?php echo $t['subscription_success']; ?>
                </div>
            <?php elseif ($subscriptionError === 'invalid_email'): ?>
                <div class="subscription-message subscription-error">
                    <?php echo $t['subscription_error_email']; ?>
                </div>
            <?php elseif ($subscriptionError === 'invalid_captcha'): ?>
                <div class="subscription-message subscription-error">
                    <?php echo $t['subscription_error_captcha']; ?>
                    <?php if (!empty($debugInfo)): ?>
                        <div style="margin-top: 5px; font-size: 11px; color: #666;"><?php echo $debugInfo; ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </form>
        
        <div class="plugin-credit">
            <span style="color: #aaa; margin-right: 5px;">⚙️</span><a href="https://wpmaintenancemode.com/" rel="nofollow noindex noopener" target="_blank">Maintenance Mode</a>
        </div>
    </div>
    
    <script>
        var targetDate = new Date('<?php echo $endDate; ?>');
        
        function updateTimer() {
            var currentDate = new Date();
            var totalSeconds = Math.floor((targetDate - currentDate) / 1000);
            
            if (totalSeconds <= 0) {
                document.getElementById('days-value').innerHTML = '00';
                document.getElementById('hours-value').innerHTML = '00';
                document.getElementById('minutes-value').innerHTML = '00';
                document.getElementById('seconds-value').innerHTML = '00';
                return;
            }
            
            var days = Math.floor(totalSeconds / 86400);
            totalSeconds = totalSeconds % 86400;
            
            var hours = Math.floor(totalSeconds / 3600);
            totalSeconds = totalSeconds % 3600;
            
            var minutes = Math.floor(totalSeconds / 60);
            var seconds = totalSeconds % 60;
            
            days = (days < 10) ? '0' + days : days;
            hours = (hours < 10) ? '0' + hours : hours;
            minutes = (minutes < 10) ? '0' + minutes : minutes;
            seconds = (seconds < 10) ? '0' + seconds : seconds;
            
            document.getElementById('days-value').innerHTML = days;
            document.getElementById('hours-value').innerHTML = hours;
            document.getElementById('minutes-value').innerHTML = minutes;
            document.getElementById('seconds-value').innerHTML = seconds;
        }
        
        updateTimer();
        setInterval(updateTimer, 1000);
    </script>
</body>
</html> 
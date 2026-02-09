<?php
$logged = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visible = $_POST['visible'] ?? 'unknown';
    $userEmail = $_POST['user_email'] ?? 'not provided';
    $ip = $_SERVER['REMOTE_ADDR'];
    $time = date('Y-m-d H:i:s');
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $log = sprintf(
        "[%s] %s | Email: %s | IP: %s | UA: %s\n",
        $time,
        strtoupper($visible),
        $userEmail,
        $ip,
        $ua
    );

    if (file_put_contents(__DIR__ . '/responses.log', $log, FILE_APPEND | LOCK_EX)) {
        $logged = true;
    } else {
        $error = 'Failed to save response';
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => $logged,
        'message' => $logged ? 'Response recorded successfully' : $error
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connection Verification</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet" />
    <style>
        :root {
            --md-sys-color-primary: #6750a4;
            --md-sys-color-on-primary: #ffffff;
            --md-sys-color-secondary-container: #e8def8;
            --md-sys-color-on-secondary-container: #1d192b;
            --md-sys-color-background: #fdfcff;
            --md-sys-color-surface: #f7f2fa;
            --md-sys-color-surface-container: #ffffff;
            --md-sys-color-on-surface: #1c1b1f;
            --md-sys-color-on-surface-variant: #49454f;
            --md-sys-color-outline: #79747e;
            --md-sys-color-outline-focus: #6750a4;
            --md-sys-color-error: #b3261e;
            --md-sys-color-success: #146c2e;
            --md-elevation-level-1: 0px 1px 3px 1px rgba(0, 0, 0, 0.15), 0px 1px 2px 0px rgba(0, 0, 0, 0.3);
            --md-shape-corner: 28px;
            --md-shape-button: 20px;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --md-sys-color-background: #141218;
                --md-sys-color-surface: #141218;
                --md-sys-color-surface-container: #211f26;
                --md-sys-color-on-surface: #e6e1e5;
                --md-sys-color-on-surface-variant: #cac4d0;
                --md-sys-color-outline: #938f99;
                --md-sys-color-outline-focus: #d0bcff;
                --md-sys-color-primary: #d0bcff;
                --md-sys-color-on-primary: #381e72;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--md-sys-color-background);
            color: var(--md-sys-color-on-surface);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .card {
            background-color: var(--md-sys-color-surface-container);
            border-radius: var(--md-shape-corner);
            padding: 32px;
            width: 100%;
            max-width: 480px;
            box-shadow: var(--md-elevation-level-1);
            position: relative;
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }

        .header {
            margin-bottom: 32px;
            text-align: center;
        }

        .icon-circle {
            width: 48px;
            height: 48px;
            background-color: var(--md-sys-color-secondary-container);
            color: var(--md-sys-color-on-secondary-container);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        h1 {
            font-size: 24px;
            line-height: 32px;
            font-weight: 400;
            color: var(--md-sys-color-on-surface);
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 14px;
            line-height: 20px;
            color: var(--md-sys-color-on-surface-variant);
            letter-spacing: 0.25px;
        }

        .input-group {
            position: relative;
            margin-bottom: 24px;
        }

        .md-input {
            width: 100%;
            height: 56px;
            padding: 0 16px;
            background: transparent;
            border: 1px solid var(--md-sys-color-outline);
            border-radius: 4px;
            font-size: 16px;
            color: var(--md-sys-color-on-surface);
            outline: none;
            transition: border-color 0.2s;
        }

        .md-input:focus {
            border-color: var(--md-sys-color-outline-focus);
            border-width: 2px;
            padding: 0 15px;
        }

        .md-label {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            background-color: var(--md-sys-color-surface-container);
            padding: 0 4px;
            color: var(--md-sys-color-on-surface-variant);
            font-size: 16px;
            transition: all 0.2s;
            pointer-events: none;
        }

        .md-input:focus ~ .md-label,
        .md-input:not(:placeholder-shown) ~ .md-label {
            top: 0;
            font-size: 12px;
            color: var(--md-sys-color-outline-focus);
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 32px;
        }

        .btn {
            height: 40px;
            padding: 0 24px;
            border-radius: var(--md-shape-button);
            border: none;
            font-family: 'Roboto', sans-serif;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.1px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }

        .btn-filled {
            background-color: var(--md-sys-color-primary);
            color: var(--md-sys-color-on-primary);
        }

        .btn-filled:hover {
            box-shadow: 0 1px 2px rgba(0,0,0,0.3);
            filter: brightness(1.05);
        }

        .btn-tonal {
            background-color: var(--md-sys-color-secondary-container);
            color: var(--md-sys-color-on-secondary-container);
        }

        .btn-tonal:hover {
            filter: brightness(0.95);
        }

        .feedback {
            margin-top: 16px;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            opacity: 0;
            transform: scale(0.95);
            transition: all 0.3s ease;
            display: none;
        }

        .feedback.visible {
            opacity: 1;
            transform: scale(1);
            display: flex;
        }

        .feedback.success {
            background-color: #e6f4ea;
            color: var(--md-sys-color-success);
        }

        .feedback.error {
            background-color: #fce8e6;
            color: var(--md-sys-color-error);
        }

        .ripple {
            position: absolute;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    </style>
</head>
<body>

<div class="card">
    <div class="header">
        <div class="icon-circle">
            <span class="material-symbols-outlined">network_check</span>
        </div>
        <h1>Connection Check</h1>
        <div class="subtitle">Please confirm if this page is accessible from your current location.</div>
        <div class="subtitle" style="margin-top:4px; font-size: 12px;">请确认您是否可以访问此页面</div>
    </div>

    <div class="input-group">
        <input type="email" id="email" class="md-input" placeholder=" " />
        <label for="email" class="md-label">Email Address (Optional)</label>
    </div>

    <div class="actions">
        <button class="btn btn-tonal" onclick="createRipple(event); sendResponse('no')">
            <span class="material-symbols-outlined" style="font-size: 18px;">close</span>
            No · 否
        </button>
        <button class="btn btn-filled" onclick="createRipple(event); sendResponse('yes')">
            <span class="material-symbols-outlined" style="font-size: 18px;">check</span>
            Yes · 是
        </button>
    </div>

    <div id="feedback" class="feedback">
        <span class="material-symbols-outlined" id="feedback-icon">info</span>
        <span id="feedback-text"></span>
    </div>
</div>

<script>
    function createRipple(event) {
        const button = event.currentTarget;
        const circle = document.createElement("span");
        const diameter = Math.max(button.clientWidth, button.clientHeight);
        const radius = diameter / 2;

        circle.style.width = circle.style.height = `${diameter}px`;
        circle.style.left = `${event.clientX - button.getBoundingClientRect().left - radius}px`;
        circle.style.top = `${event.clientY - button.getBoundingClientRect().top - radius}px`;
        circle.classList.add("ripple");

        const ripple = button.getElementsByClassName("ripple")[0];
        if (ripple) {
            ripple.remove();
        }

        button.appendChild(circle);
    }

    function sendResponse(visible) {
        const email = document.getElementById('email').value;
        const feedbackEl = document.getElementById('feedback');
        const feedbackText = document.getElementById('feedback-text');
        const feedbackIcon = document.getElementById('feedback-icon');

        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `visible=${visible}&user_email=${email}`
        })
            .then(r => r.json())
            .then(data => {
                feedbackEl.className = 'feedback visible ' + (data.success ? 'success' : 'error');
                feedbackText.textContent = data.message;
                feedbackIcon.textContent = data.success ? 'check_circle' : 'error';
            })
            .catch(() => {
                feedbackEl.className = 'feedback visible error';
                feedbackText.textContent = 'Network error occurred';
                feedbackIcon.textContent = 'wifi_off';
            });
    }
</script>
</body>
</html>

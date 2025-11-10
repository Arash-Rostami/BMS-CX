<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Temporarily Unavailable</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #D3D3D3;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            padding: 60px 40px;
            text-align: center;
            max-width: 480px;
            width: 90%;
            animation: slideIn 0.8s ease;
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s ease-in-out infinite;
            position: relative;
            z-index: 2;
        }

        .icon::before {
            content: '⚠';
            font-size: 36px;
            color: white;
        }

        h1 {
            font-size: 28px;
            color: #2d3748;
            margin-bottom: 16px;
            font-weight: 600;
            position: relative;
            z-index: 2;
        }

        p {
            color: #718096;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 40px;
            position: relative;
            z-index: 2;
        }

        .actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
        }

        .btn {
            padding: 14px 28px;
            border-radius: 12px;
            border: none;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #545253, dimgrey);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: rgba(113, 128, 150, 0.1);
            color: #4a5568;
            border: 1px solid rgba(113, 128, 150, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(113, 128, 150, 0.2);
            transform: translateY(-1px);
        }

        .loading {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        .status {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 8px 16px;
            background: rgba(245, 101, 101, 0.9);
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            backdrop-filter: blur(10px);
            animation: slideInRight 0.5s ease;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 40px 24px;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<div class="status">Service Temporarily Unavailable</div>

<div class="container">
    <div class="icon"></div>
    <h1>Temporarily Unavailable</h1>
    <p>We're currently experiencing HIGH database traffic. Please try again shortly; we're working to restore full
        access quite soon.</p>

    <div class="actions">
        <button class="btn btn-primary" onclick="retryConnection()">
            <div class="loading"></div>
            <span>Try Again</span>
        </button>
        <a href="/" class="btn btn-secondary">Go Home</a>
    </div>
</div>

<script>
    function retryConnection() {
        const btn = document.querySelector('.btn-primary');
        const loading = btn.querySelector('.loading');
        const text = btn.querySelector('span');

        loading.style.display = 'block';
        text.textContent = 'Retrying...';
        btn.disabled = true;

        setTimeout(() => {
            location.reload();
        }, 1500);
    }

    setTimeout(() => {
        const status = document.querySelector('.status');
        if (status) {
            status.textContent = 'Auto-retrying in 3 seconds...';

            setTimeout(() => {
                location.reload();
            }, 3000);
        }
    }, 15000)
</script>
</body>
</html>

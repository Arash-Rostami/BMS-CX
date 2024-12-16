<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Quote Request' }}</title>
    <style>
        .email-text {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000 !important;
            margin: 0;
            padding: 20px;
        }
    </style>
</head>
<p class="email-text">Dear {!! $recipient['name'] !!},</p>
<body>

<p class="email-text">
    {{ nl2br(strip_tags($content)) }}
</p>

<p class="email-text">
    Thank you.
</p>
<p class="email-text">
    {!! $sender !!}
</p>
</body>
</html>

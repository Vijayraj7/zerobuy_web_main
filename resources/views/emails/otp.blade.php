<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>OTP Verification</title>
    <style>
        body { font-family: Arial, sans-serif; color: #222; }
        .container { max-width: 520px; margin: 0 auto; padding: 16px; }
        .otp { font-size: 24px; font-weight: bold; letter-spacing: 2px; }
    </style>
    </head>
<body>
<div class="container">
    <h2>{{ $purpose }} Verification</h2>
    <p>Your {{ strtolower($purpose) }} one-time password is:</p>
    <p class="otp">{{ $otp }}</p>
    <p>This code will expire shortly. Do not share it with anyone.</p>
</div>
</body>
</html>

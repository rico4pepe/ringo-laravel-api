<!-- resources/views/emails/otp.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Your One-Time Password (OTP)</title>
        <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .otp-code { 
            font-size: 24px; 
            font-weight: bold; 
            color: #2563eb; 
            margin: 10px 0;
        }
        .expiry { color: #dc2626; }
    </style>
</head>
<body>
      <h2>Your OTP Code</h2>
    <p>Here is your one-time password (OTP) for authentication:</p>
    
    <div class="otp-code">{{ $otp }}</div>
    
    <p class="expiry">
        ⏳ Expires at: {{ $expiry->format('Y-m-d H:i:s') }} (UTC)
    </p>
    
    <p>If you didn't request this OTP, please ignore this email.</p>
    
    <footer>
        <p>Thank you,<br>{{ config('app.name') }}</p>
    </footer>
</body>
</html>

<!-- resources/views/emails/otp.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
</head>
<body>
    <h1>Your OTP Code</h1>
    <p>Dear User,</p>
    <p>Your OTP code is <strong>{{ $otp }}</strong>.</p>
    <p>This code is valid for the next {{ $expiry }} minutes. Please enter it on the verification page to complete your sign-in.</p>
    <p>If you did not request this OTP, please ignore this email.</p>
    <p>Thank you,</p>
    <p>Your Company Name</p>
</body>
</html>

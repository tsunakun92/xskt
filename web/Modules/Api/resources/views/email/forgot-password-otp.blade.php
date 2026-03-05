<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 30px;
            margin: 20px 0;
        }
        .otp-code {
            background-color: #007bff;
            color: #ffffff;
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            letter-spacing: 8px;
            margin: 30px 0;
            font-family: "Courier New", monospace;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 style="color: #007bff; margin-top: 0;">Password Reset Request</h2>

        <p>Hello <strong>{{ $email }}</strong>,</p>

        <p>You have requested to reset your password. Please use the following One-Time Password (OTP) code to complete the reset process:</p>

        <div class="otp-code">{{ $otpCode }}</div>

        <div style="text-align: center; margin: 25px 0;">
            <p style="color: #dc3545; font-size: 16px; font-weight: bold; margin: 10px 0;">
                Valid for {{ $expiresIn }} minutes
            </p>
            <p style="color: #666; font-size: 14px; margin: 5px 0;">
                This OTP code will expire in {{ $minutesText }}. Please use it before it expires.
            </p>
        </div>

        <div class="warning">
            <strong>Security Notice:</strong><br>
            If you did not request this password reset, please ignore this email. Your account remains secure.
        </div>

        <p>Please enter this OTP code in the application to reset your password.</p>

        <div class="footer">
            <p>Best regards,<br>
            <strong>{{ $appName }} Support Team</strong></p>
            <p style="font-size: 11px; color: #999;">
                This is an automated message. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>


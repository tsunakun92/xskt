<!DOCTYPE html>
<html>

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Register OTP</title>
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
            <h2 style="color: #007bff; margin-top: 0;">Register Request</h2>

            <p>Hello <strong>{{ $email }}</strong>,</p>

            <p>
                You are registering a new account.
                Please use the following One-Time Password (OTP) code to verify your email address:
            </p>

            <div class="otp-code">{{ $otpCode }}</div>

            <div style="text-align: center; margin: 25px 0;">
                <p style="color: #dc3545; font-size: 16px; font-weight: bold; margin: 10px 0;">
                    Valid for {{ $expiresIn }} minutes
                </p>
                <p style="color: #666; font-size: 14px; margin: 5px 0;">
                    This OTP code will expire in {{ $minutesText }}. Please use it before it expires.
                </p>
            </div>

            <p>Please enter this OTP code in the application to verify your email address.</p>

            <div class="footer">
                <p>Best regards,<br>
                    <strong>{{ $appName }} Support Team</strong>
                </p>
                <p style="font-size: 11px; color: #999;">
                    This is an automated message. Please do not reply to this email.
                </p>
            </div>
        </div>
    </body>

</html>

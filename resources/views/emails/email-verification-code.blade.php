<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AmoraCare email verification</title>
</head>
<body style="margin:0;padding:24px;background:#fff8f2;font-family:Arial,sans-serif;color:#182230;">
    <table role="presentation" style="width:100%;max-width:600px;margin:0 auto;border-collapse:collapse;background:#ffffff;border:1px solid #fed7aa;border-radius:16px;">
        <tr>
            <td style="padding:32px;">
                <h1 style="margin:0 0 12px;color:#9d3f20;font-size:26px;">Verify your AmoraCare email</h1>
                <p style="margin:0 0 18px;line-height:1.6;">Hello {{ $user->name }},</p>
                <p style="margin:0 0 18px;line-height:1.6;">Use the following verification code to continue signing in:</p>
                <div style="padding:18px;text-align:center;border-radius:12px;background:#fff7ed;color:#7c2d12;font-size:34px;font-weight:700;letter-spacing:10px;">
                    {{ $code }}
                </div>
                <p style="margin:18px 0 0;line-height:1.6;">This code expires in {{ $expiresMinutes }} minutes. Do not share it with anyone.</p>
                <p style="margin:18px 0 0;color:#667085;font-size:13px;line-height:1.5;">If you did not try to sign in, contact the AmoraCare administrator.</p>
            </td>
        </tr>
    </table>
</body>
</html>

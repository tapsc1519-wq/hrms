<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Niyantron Partner Portal Invitation</title>
</head>
<body style="font-family:Arial,sans-serif;background:#f6f8fb;color:#172033;margin:0;padding:24px">
    <div style="max-width:620px;margin:0 auto;background:#ffffff;border:1px solid #e6ebf2;border-radius:10px;overflow:hidden">
        <div style="padding:22px 24px;background:#0f172a;color:#ffffff">
            <h2 style="margin:0;font-size:20px">Niyantron Partner Portal</h2>
            <p style="margin:8px 0 0;color:#cbd5e1;font-size:14px">You have been invited to manage your partner leads and commissions.</p>
        </div>
        <div style="padding:24px">
            <p style="margin-top:0">Hello {{ $user->name }},</p>
            <p>Your partner portal account for <strong>{{ $partner->display_name }}</strong> is ready.</p>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin:18px 0">
                <p style="margin:0 0 8px"><strong>Login URL:</strong> <a href="{{ $loginUrl }}">{{ $loginUrl }}</a></p>
                <p style="margin:0 0 8px"><strong>Email:</strong> {{ $user->email }}</p>
                <p style="margin:0"><strong>Temporary password:</strong> {{ $temporaryPassword }}</p>
            </div>
            <p>After login, you can submit leads, track lead stages, and view commission status.</p>
            <p style="color:#64748b;font-size:13px">For security, please change your password after signing in.</p>
        </div>
    </div>
</body>
</html>

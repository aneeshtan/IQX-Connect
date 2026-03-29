<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:24px;background:#f4f7fb;color:#111827;font-family:Arial,sans-serif;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #d4dbe5;border-radius:18px;overflow:hidden;">
        <div style="padding:24px 24px 12px;background:linear-gradient(135deg,#effcf8 0%,#eef6ff 100%);border-bottom:1px solid #d4dbe5;">
            <div style="font-size:12px;letter-spacing:0.28em;text-transform:uppercase;color:#0f766e;">{{ $workspaceName }}</div>
            <h1 style="margin:12px 0 0;font-size:24px;line-height:1.2;">{{ $title }}</h1>
        </div>

        <div style="padding:24px;">
            <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#374151;">{{ $body }}</p>

            <div style="padding:16px;border:1px solid #e4e9f0;border-radius:14px;background:#f9fafb;">
                <div style="font-size:12px;letter-spacing:0.18em;text-transform:uppercase;color:#6b7280;">Record</div>
                <div style="margin-top:8px;font-size:16px;font-weight:600;color:#111827;">{{ $recordReference }}</div>
                <div style="margin-top:4px;font-size:14px;color:#6b7280;text-transform:capitalize;">{{ $recordLabel }}</div>
                @if ($actor)
                    <div style="margin-top:12px;font-size:14px;color:#4b5563;">Triggered by {{ $actor->name }}</div>
                @endif
            </div>

            @if ($actionUrl)
                <div style="margin-top:24px;">
                    <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 18px;border-radius:12px;background:#111827;color:#ffffff;text-decoration:none;font-weight:600;">
                        Open in IQX Connect
                    </a>
                </div>
            @endif
        </div>
    </div>
</body>
</html>

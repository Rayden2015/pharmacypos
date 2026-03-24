<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #222;">
    <p>{{ __('Use this code to finish signing in:') }}</p>
    <p style="font-size: 1.5rem; letter-spacing: 0.2em; font-weight: 600;">{{ $code }}</p>
    <p style="font-size: 0.875rem; color: #666;">{{ __('This code expires in 10 minutes. If you did not try to sign in, ignore this email.') }}</p>
</body>
</html>

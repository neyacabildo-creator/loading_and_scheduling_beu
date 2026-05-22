<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Expired</title>
    <style>
        body {
            background: #1a1a2e;
            color: #ffffff;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            text-align: center;
            padding: 2rem;
        }
        h1 { font-size: 4rem; color: #f0c040; margin-bottom: 0.5rem; }
        p { font-size: 1.1rem; color: rgba(255,255,255,0.7); margin-bottom: 1.5rem; }
        a {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: #f0c040;
            color: #1a1a2e;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
        }
        a:hover { background: #d4a800; }
    </style>
    <script>
        // Auto-redirect to login after 2 seconds
        setTimeout(function () {
            window.location.href = '/login';
        }, 2000);
    </script>
</head>
<body>
    <div class="container">
        <h1>419</h1>
        <p>Your session has expired. Redirecting you to the login page...</p>
        <a href="/login">Go to Login</a>
    </div>
</body>
</html>

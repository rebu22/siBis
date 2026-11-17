{{--
    Halaman error bertema "Corporate Trust" (spec prompt.txt). Sengaja self-contained:
    inline style + tanpa aset build supaya tetap tampil walau Vite belum di-build.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $status }} - {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f8fafc;
            color: #0f172a;
            font-family: "Plus Jakarta Sans Variable", "Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif;
            font-size: 16px;
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
        }
        .orb {
            position: fixed;
            border-radius: 9999px;
            filter: blur(64px);
            pointer-events: none;
        }
        .orb-a { top: -160px; left: -160px; width: 400px; height: 400px; background: #c7d2fe; opacity: .5; }
        .orb-b { bottom: -200px; right: -180px; width: 500px; height: 500px; background: #ddd6fe; opacity: .45; }
        .panel {
            position: relative;
            width: 100%;
            max-width: 520px;
            background: #fff;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 20px -2px rgba(79, 70, 229, .10);
        }
        .eyebrow {
            margin: 0;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #4f46e5;
        }
        .code {
            margin: 16px 0 0;
            font-size: 72px;
            line-height: 1.05;
            letter-spacing: -.02em;
            font-weight: 800;
            background: linear-gradient(to right, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        h1 { margin: 12px 0 0; font-size: 24px; line-height: 1.3; font-weight: 700; color: #0f172a; }
        p.body { margin: 12px 0 0; color: #64748b; }
        a.action {
            display: inline-block;
            margin-top: 28px;
            padding: 12px 24px;
            border-radius: 9999px;
            background-image: linear-gradient(to right, #4f46e5, #7c3aed);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 4px 14px 0 rgba(79, 70, 229, .3);
        }
        a.action:hover { transform: translateY(-2px); }
        a.action:focus-visible { outline: 2px solid #4f46e5; outline-offset: 2px; }
    </style>
</head>
<body>
    <div class="orb orb-a" aria-hidden="true"></div>
    <div class="orb orb-b" aria-hidden="true"></div>

    <main class="panel">
        <p class="eyebrow">Galat</p>
        <p class="code">{{ $status }}</p>
        <h1>{{ $title }}</h1>
        <p class="body">{{ $hint }}</p>
        <a class="action" href="{{ url('/') }}">Kembali ke beranda</a>
    </main>
</body>
</html>

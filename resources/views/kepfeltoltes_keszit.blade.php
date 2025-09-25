<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>Eseménytípus feltöltése</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root {
      --bg: linear-gradient(135deg, #f9fafb, #e0f2fe);
      --card: rgba(255,255,255,0.9);
      --text: #111827;
      --muted: #4b5563;
      --accent: #2563eb;
      --accent-gradient: linear-gradient(135deg, #3b82f6, #2563eb);
      --border: #d1d5db;
      --error: #dc2626;
      --success: #059669;
    }
    body.dark {
      --bg: linear-gradient(135deg, #0f172a, #1e293b);
      --card: rgba(30,41,59,0.9);
      --text: #f9fafb;
      --muted: #94a3b8;
      --accent: #3b82f6;
      --accent-gradient: linear-gradient(135deg, #2563eb, #1d4ed8);
      --border: #334155;
      --error: #f87171;
      --success: #34d399;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: var(--bg);
      color: var(--text);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 1rem;
      transition: background 0.4s ease, color 0.4s ease;
    }
    .container {
      background: var(--card);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      padding: 2rem;
      border-radius: 1.5rem;
      box-shadow: 0 12px 40px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 600px;
      transition: background 0.3s ease, color 0.3s ease;
    }
    h1 {
      background: var(--accent-gradient);
      text-align: center;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 1.5rem;

      -webkit-background-clip: text;
    }
    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: var(--muted);
      font-size: 0.95rem;
    }
    input {
      width: 100%;
      padding: 1rem;
      border: 1px solid var(--border);
      border-radius: 0.85rem;
      margin-bottom: 1.5rem;
      font-size: 1rem;
      background-color: rgba(255,255,255,0.05);
      color: var(--text);
      transition: all 0.25s ease;
    }
    input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 12px rgba(37,99,235,0.3);
      outline: none;
    }
    .btn {
      display: inline-block;
      padding: 1rem 1.2rem;
      font-size: 1.05rem;
      font-weight: 600;
      border: none;
      border-radius: 0.85rem;
      cursor: pointer;
      background: var(--accent-gradient);
      color: #fff;
      width: 100%;
      text-align: center;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(37,99,235,0.4);
    }
    .btn:active { transform: scale(0.97); }
    .btn-toggle {
      background: transparent;
      color: var(--accent);
      border: 2px solid var(--accent);
      margin-bottom: 1rem;
      width: auto;
      padding: 0.6rem 1.2rem;
      border-radius: 0.75rem;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    .btn-toggle:hover {
      background: var(--accent);
      color: #fff;
      box-shadow: 0 6px 18px rgba(37,99,235,0.3);
    }
    .error {
      background-color: rgba(220,38,38,0.15);
      color: var(--error);
      padding: 0.9rem;
      border-radius: 0.85rem;
      margin-bottom: 1rem;
      border: 1px solid var(--error);
    }
    .success {
      background-color: rgba(16,185,129,0.15);
      color: var(--success);
      padding: 0.9rem;
      border-radius: 0.85rem;
      margin-bottom: 1rem;
      border: 1px solid var(--success);
    }
    @media (max-width: 480px) {
      .container { padding: 1.5rem; }
      h1 { font-size: 1.7rem; }
      .btn { font-size: 0.95rem; }
    }
  </style>
</head>
<body>
  <div class="container">
    <button class="btn-toggle" id="themeToggle">🌗 Téma váltás</button>

    <h1>📤 Új eseménytípus feltöltése</h1>

    @if ($errors->any())
      <div class="error">
        <ul>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if (session('success'))
      <div class="success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('kepfeltoltes.store') }}" method="POST" enctype="multipart/form-data">
      @csrf

      <label for="event_type">Eseménytípus neve</label>
      <input type="text" name="event_type" id="event_type" placeholder="Pl: Kocamuri" required>

      <label for="event_type_img">Eseménytípus képe</label>
      <input type="file" name="event_type_img" id="event_type_img" accept="image/*" required>

      <button type="submit" class="btn">🚀 Feltöltés</button>
    </form>
  </div>

  <script>
    const toggleBtn = document.getElementById("themeToggle");
    toggleBtn.addEventListener("click", () => {
      document.body.classList.toggle("dark");
    });
  </script>
</body>
</html>

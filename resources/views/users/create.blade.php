<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>👤 Új Felhasználó Hozzáadása</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    /* === same CSS from your Közlemény létrehozása page === */
    :root {
      --bg: linear-gradient(135deg, #f9fafb, #e0f2fe);
      --card: rgba(255,255,255,0.9);
      --text: #111827;
      --muted: #4b5563;
      --accent: #2563eb;
      --accent-gradient: linear-gradient(135deg, #3b82f6, #2563eb);
      --border: #d1d5db;
      --error: #dc2626;
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
      padding: 2rem;
      border-radius: 1.5rem;
      box-shadow: 0 12px 40px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 600px;
      transition: background 0.3s ease, color 0.3s ease;
    }
    h1 {
      text-align: center;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      background: var(--accent-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    label { display: block; margin-bottom: .5rem; font-weight: 600; color: var(--muted); font-size: .95rem; }
    input, select {
      width: 100%;
      padding: 1rem;
      border: 1px solid var(--border);
      border-radius: .85rem;
      margin-bottom: 1.5rem;
      font-size: 1rem;
      background-color: rgba(255,255,255,0.05);
      color: var(--text);
      transition: all .25s ease;
    }
    input:focus, select:focus {
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
    }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(37,99,235,0.4); }
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
  </style>
</head>

<body>
  <div class="container">
    <button class="btn-toggle" id="themeToggle">🌗 Téma váltás</button>
    <h1>👤 Új Felhasználó Hozzáadása</h1>

    @if ($errors->any())
    <div class="error">
      <ul>
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    <form action="{{ route('users.store') }}" method="POST">
      @csrf

      <label for="name">Felhasználónév</label>
      <input type="text" name="name" id="name" placeholder="pl. admin" required>

      <label for="teljes_nev">Teljes név</label>
      <input type="text" name="teljes_nev" id="teljes_nev" placeholder="pl. Kiss Péter" required>

      <label for="password">Jelszó</label>
      <input type="password" name="password" id="password" placeholder="Adj meg egy jelszót" required>

      <label for="szak">Szak</label>
      <input type="text" name="szak" id="szak" placeholder="pl. MIK, GTK..." required>

      <label for="titulus">Titulus</label>
      <select name="titulus" id="titulus" required>
        <option value="" disabled selected>Válassz titulus</option>
        <option value="Admin">Admin</option>
        <option value="Elnök">Elnök</option>
        <option value="Elnökhelyettes">Elnökhelyettes</option>
        <option value="Referens">Referens</option>
        <option value="Képviselő">Képviselő</option>
      </select>

      <button type="submit" class="btn">🚀 Felhasználó létrehozása</button>
    </form>
  </div>

  <script>
    const toggleBtn = document.getElementById("themeToggle");
    toggleBtn.addEventListener("click", () => document.body.classList.toggle("dark"));
  </script>
</body>
</html>

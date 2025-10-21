<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>Közlemény létrehozása</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root {
      /* Light theme */
      --bg: linear-gradient(135deg, #f9fafb, #e0f2fe);
      --card: rgba(255,255,255,0.9);
      --text: #111827;
      --muted: #4b5563;
      --accent: #2563eb;
      --accent-gradient: linear-gradient(135deg, #3b82f6, #2563eb);
      --border: #d1d5db;
      --error: #dc2626;
    }
    #ertesites-info
    {
        margin-bottom: 20px;    
    }
    body.dark {
      /* Dark theme */
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
      -webkit-backdrop-filter: blur(12px);
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
    }

    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: var(--muted);
      font-size: 0.95rem;
    }

    input,
    select,
    textarea {
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

    input:focus,
    select:focus,
    textarea:focus {
      border-color: var(--accent);
      box-shadow: 0 0 12px rgba(37,99,235,0.3);
      outline: none;
    }

        body.dark input,
body.dark select,
body.dark textarea {
    background-color: rgba(30,41,59,0.9); /* sötétebb hátteret adunk */
    color: var(--text); /* fehér marad */
}
body.dark h1 {
    -webkit-text-fill-color: var(--text); /* gradient helyett fehér, így az emoji is látszik */
}

    input,
    textarea {
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

    input:focus,
    textarea:focus {
      border-color: var(--accent);
      box-shadow: 0 0 12px rgba(37,99,235,0.3);
      outline: none;
    }

    textarea {
      min-height: 180px;
      resize: vertical;
      font-family: "Segoe UI", sans-serif;
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

    .btn:active {
      transform: scale(0.97);
    }

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

    .error ul { margin: 0; padding-left: 1.2rem; }

    /* Toggle switch */
    .toggle-group {
      display: flex;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .switch {
      position: relative;
      display: inline-block;
      width: 60px;
      height: 32px;
    }

    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--border); transition: .3s; border-radius: 28px;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 24px; width: 24px;
      left: 4px; bottom: 4px;
      background-color: white;
      transition: .3s;
      border-radius: 50%;
    }

    .switch input:checked + .slider {
      background: var(--accent-gradient);
    }

    .switch input:checked + .slider:before {
      transform: translateX(28px);
    }

    .toggle-group label {
      margin: 0 0 0 0.75rem;
      font-size: 1rem;
      color: var(--text);
      font-weight: 500;
    }

    /* Info box */
    .info-box {
      display: none;
      background: rgba(37,99,235,0.12);
      border: 1px solid rgba(37,99,235,0.3);
      color: var(--text);
      padding: 0.9rem 1rem;
      border-radius: 0.85rem;
      margin-top: 0.8rem;
      font-size: 0.9rem;
      line-height: 1.4;
      opacity: 0;
      transform: translateY(-6px);
      transition: all 0.3s ease;
    }

    .info-box.show {
      display: block;
      opacity: 1;
      transform: translateY(0);
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

    <h1>📣 Új közlemény létrehozása 📣</h1>

    @if ($errors->any())
    <div class="error">
      <ul>
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    <form action="{{ route('kozlemeny.store') }}" method="POST">
      @csrf

      <label for="title">Cím</label>
      <input type="text" name="title" id="title" placeholder="Milyen közlemény..." required>

      <label for="type">Esemény típusa</label>
      <select name="type" id="type" required>
        <option value="" disabled selected>Válassz ki egy közlemény tipust</option>
         <option value="0">Fontos </option>
         <option value="1">Közösség / Esemény</option>
         <option value="2">Oktatás</option>
         <option value="3">Kollégium</option>
      </select>


      <label for="description">Leírás</label>
      <textarea name="description" id="description" placeholder="Közlemény Leírása..."></textarea>

      <div class="toggle-group">
        <input type="hidden" name="ertesites" value="0">
        <label class="switch">
          <input type="checkbox" name="ertesites" id="ertesites" value="1">
          <span class="slider"></span>
        </label>
        <label for="ertesites">Legyen App Értesítés be / ki</label>
      </div>

      <div id="ertesites-info" class="info-box">
        🔔 Ha bekapcsolod az értesítést, a felhasználók Push Notification formájában is megkapják a közleményt. 
        Csak fontos információknál használd!
      </div>

      <button type="submit" class="btn">🚀 Elküldés</button>
    </form>
  </div>

  <script>
    const toggleBtn = document.getElementById("themeToggle");
    const ertesitesCheckbox = document.getElementById("ertesites");
    const infoBox = document.getElementById("ertesites-info");

    toggleBtn.addEventListener("click", () => {
      document.body.classList.toggle("dark");
    });

    ertesitesCheckbox.addEventListener("change", () => {
      if (ertesitesCheckbox.checked) {
        infoBox.classList.add("show");
      } else {
        infoBox.classList.remove("show");
      }
    });
  </script>
</body>
</html>

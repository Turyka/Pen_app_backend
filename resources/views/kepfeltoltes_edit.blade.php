<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>Eseménytípus frissítése</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    /* ugyanaz a stílus mint korábban, nem ismétlem végig */
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
    body.dark { /* dark theme */ 
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
      max-width: 650px;
    }
    h1 {
      background: var(--accent-gradient);
      text-align: center;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      -webkit-background-clip: text;
      color: transparent;
    }
    label { display:block; margin:0.5rem 0; font-weight:600; color:var(--muted); }
    input {
      width: 100%; padding: 1rem; border: 1px solid var(--border);
      border-radius: 0.85rem; margin-bottom: 1.5rem; background-color: rgba(255,255,255,0.05);
      color: var(--text);
    }
    .btn { background: var(--accent-gradient); color:#fff; border:none; border-radius:0.85rem;
      padding:1rem; width:100%; font-weight:600; cursor:pointer; }
    .btn:hover { transform: translateY(-2px); }
    .btn-toggle { background:transparent; border:2px solid var(--accent); color:var(--accent); margin-bottom:1rem; padding:0.6rem 1.2rem; border-radius:0.75rem; }
    .preview { margin-bottom:1rem; text-align:center; }
    .preview img { max-width:200px; border-radius:1rem; border:2px solid var(--border); }
  </style>
</head>
<body>
  <div class="container">
    <button class="btn-toggle" id="themeToggle">🌗 Téma váltás</button>

    <h1>✏️ Eseménytípus frissítése</h1>

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

    <form action="{{ route('kepfeltoltes.update', $kepfeltoltes->id) }}" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')

      <label for="event_type">Eseménytípus neve</label>
      <input type="text" name="event_type" id="event_type" value="{{ old('event_type', $kepfeltoltes->event_type) }}" required>

      <div class="preview">
        <p>📸 Jelenlegi kép:</p>
        @if($kepfeltoltes->event_type_img)
          <img src="{{ asset('storage/' . $kepfeltoltes->event_type_img) }}" alt="Aktuális kép">
        @else
          <p><i>Nincs feltöltve kép</i></p>
        @endif
      </div>

      <label for="event_type_img">Új kép (opcionális)</label>
      <input type="file" name="event_type_img" id="event_type_img" accept="image/*">

      <button type="submit" class="btn">💾 Mentés</button>
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

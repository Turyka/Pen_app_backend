<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>{{ $kozlemeny->title }} - Szerkesztése</title>
    <style>
        :root {
            --bg: #0f0f0f;
            --card: #1a1a1a;
            --text: #f5f5f5;
            --muted: #a1a1a1;
            --border: #333;
            --accent: #ffffff;
            --error: #e11d48;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .container {
            background: var(--card);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6);
            width: 100%;
            max-width: 600px;
        }

        h1 {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 2rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--muted);
            font-size: 0.95rem;
        }

        input,
        textarea {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 1rem;
            background-color: var(--card);
            color: var(--text);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        textarea {
            min-height: 200px;
            resize: vertical;
            overflow-y: auto;
            font-family: "Segoe UI", sans-serif;
        }

        input:focus,
        textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(255,255,255,0.1);
            outline: none;
        }

        button {
            width: 100%;
            padding: 0.85rem;
            background-color: var(--accent);
            color: var(--bg);
            font-weight: 600;
            font-size: 1rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        button:hover {
            background-color: #e5e5e5;
        }

        .error {
            background-color: rgba(225,29,72,0.1);
            color: var(--error);
            padding: 0.75rem;
            border-radius: 0.5rem;
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

        .toggle-group label {
            margin: 0 0 0 0.75rem;
            font-size: 1rem;
            color: var(--text);
            font-weight: 500;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--border);
            transition: .3s;
            border-radius: 26px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }

        .switch input:checked + .slider {
            background-color: var(--accent);
        }

        .switch input:checked + .slider:before {
            transform: translateX(24px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📣 "{{ $kozlemeny->title }}" - Szerkesztése 📣</h1>

        @if ($errors->any())
        <div class="error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('kozlemeny.update', $kozlemeny->id) }}" method="POST">
            @csrf
            @method('PUT')

            <label for="title">Cím</label>
            <input type="text" name="title" id="title" 
                   value="{{ old('title', $kozlemeny->title) }}" required>

            <label for="description">Leírás</label>
            <textarea name="description" id="description">{{ old('description', $kozlemeny->description) }}</textarea>

            <div class="toggle-group">
                <input type="hidden" name="ertesites" value="0">
                <label class="switch">
                    <input type="checkbox" name="ertesites" id="ertesites" value="1" 
                           {{ old('ertesites', $kozlemeny->ertesites) ? 'checked' : '' }}>
                    <span class="slider"></span>
                </label>
                <label for="ertesites"> Legyen App Értesítés be / ki</label>
            </div>

            <button type="submit">Mentés</button>
        </form>
    </div>
</body>
</html>

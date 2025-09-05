<!-- resources/views/event-form.blade.php -->
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>{{ $naptar->title}} - Szerkesztése</title>
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
        select,
        textarea {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 1rem;
            line-height: 1.5;
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
        select:focus,
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
    <script>
    const eventTypeSelect = document.getElementById('event_type');
    const customTypeContainer = document.getElementById('custom_event_type_container');

    eventTypeSelect.addEventListener('change', function () {
        if (this.value === 'other') {
            customTypeContainer.style.display = 'block';
        } else {
            customTypeContainer.style.display = 'none';
        }
    });

</script>
</head>
<body>
    <div class="container">
        <h1>"{{ $naptar->title}}" - Szerkesztése</h1>

        @if ($errors->any())
            <div class="error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ isset($naptar) ? route('naptar.update', $naptar->id) : route('events.store') }}" method="POST">
            @csrf
            @if(isset($naptar))
                @method('PUT')
            @endif

            <label for="title">Cím</label>
            <input type="text" name="title" id="title" value="{{ old('title', $naptar->title ?? '') }}" placeholder="Milyen esemény..." required>

            <label for="date">Dátum</label>
            <input type="date" name="date" id="date" value="{{ old('date', $naptar->date ?? '') }}" required min="{{ date('Y-m-d') }}">

            <div class="time-group">
                <div style="flex: 1;">
                    <label for="start_time">Kezdés</label>
                    <input type="time" name="start_time" id="start_time" value="{{ old('start_time', isset($naptar) ? \Carbon\Carbon::createFromFormat('H:i:s', $naptar->start_time)->format('H:i') : '') }}" required>
                </div>
                <div style="flex: 1;">
                    <label for="end_time">Befejezés</label>
                    <input type="time" name="end_time" id="end_time" value="{{ old('end_time', isset($naptar) ? \Carbon\Carbon::createFromFormat('H:i:s', $naptar->end_time)->format('H:i') : '') }}" required>
                </div>
            </div>
           <label for="event_type">Esemény típusa</label>
            <select name="event_type" id="event_type"   required>
                <option value="{{ old('event_type', $naptar->event_type ?? '') }}" selected>{{ $naptar->event_type}}</option>
                <option value="Sorpong">Sőrpong</option>
                <option value="Kvizest">Kvízest</option>
                <option value="Kocsmatura">Kocsmatura</option>
                <option value="Szuletesnap">Születésnap</option>
                <option value="Pingpong-verseny">Pingpong-verseny</option>
                <option value="Kocamuri">Kocamuri</option>
                <option value="Sportnapok">Sportnapok</option>
                <option value="Eloadas">Előadás</option>
                <option value="egyebb">Egyéb</option>
            </select>

            <label for="status">Státusz:</label>
            <select name="status" id="status"   required>
                <option value="{{ old('status', $naptar->status ?? '') }}" selected>{{ $naptar->status}}</option>
                <option value="Aktív">Aktív</option>
                <option value="Függőben">Függőben</option>
                <option value="Elmarad">Elmarad</option>
                <option value="Lezárt">Lezárt</option>
            </select>

            <label for="description">Leírás</label>
            <textarea name="description"  id="description" rows="4">{{ old('description', $naptar->description ?? '') }}</textarea>

            <div class="toggle-group">
                <input type="hidden" name="ertesites" value="0">

                <label class="switch">
                    <input type="checkbox" name="ertesites" id="ertesites" value="1">
                    <span class="slider"></span>
                </label>
                <label for="ertesites"> Legyen App Értesítés be / ki</label>
            </div>

            <button type="submit">Mentés</button>
        </form>
    </div>
</body>
</html>

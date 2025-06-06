<!-- resources/views/event-form.blade.php -->
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>{{ $naptar->title}} - Szerkesztése</title>
    <style>
        :root {
            --primary: #2563eb;
            --gray: #f1f5f9;
            --gray-dark: #64748b;
            --error: #dc2626;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--gray);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 500px;
        }

        h1 {
            text-align: center;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }

        input,
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 1rem;
            transition: border 0.2s;
        }

        input:focus,
        textarea:focus {
            border-color: var(--primary);
            outline: none;
        }

        .time-group {
            display: flex;
            gap: 1rem;
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary);
            color: white;
            font-size: 1rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        button:hover {
            background-color: #1d4ed8;
        }

        .error {
            background-color: #fee2e2;
            color: var(--error);
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .error ul {
            margin: 0;
            padding-left: 1.2rem;
        }
        
        select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    font-size: 1rem;
    background-color: white;
    transition: border 0.2s;
}

select:focus {
    border-color: var(--primary);
    outline: none;
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
                <option value="Törölve">Törölve</option>
                <option value="Lezárt">Lezárt</option>
            </select>

            <label for="description">Leírás</label>
            <textarea name="description"  id="description" rows="4">{{ old('description', $naptar->description ?? '') }}</textarea>

            <button type="submit">Mentés</button>
        </form>
    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | PEN</title>
  <link rel="stylesheet" href="{{ asset('/css/login.css') }}">
</head>
<body>
  <div class="wrapper">
    <form method="POST" action="{{ route('login_store') }}">
      @csrf
      <h2>Bejelentkezés</h2>

      {{-- GLOBAL ERROR (login failure) --}}
      @if(session('error'))
        <div class="alert alert-danger" style="color:red; margin-bottom:10px;">
          {{ session('error') }}
        </div>
      @endif

      {{-- VALIDATION ERRORS --}}
      @if ($errors->any())
        <div class="alert alert-danger" style="color:red; margin-bottom:10px;">
          <ul style="margin: 0; padding: 0; list-style: none;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="input-field">
        <input type="text" name="name" value="{{ old('name') }}" required>
        <label>Felhasználónév</label>
      </div>
      <div class="input-field">
        <input type="password" name="password" required>
        <label>Jelszó</label>
      </div>
      <button type="submit">Bejelentkezek</button>
    </form>
  </div>
</body>
</html>

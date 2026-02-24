<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
        private $roles = [
        'Admin' => 5,
        'Elnök' => 4,
        'Elnökhelyettes' => 3,
        'Referens' => 2,
        'Képviselő' => 1,
    ];

    private function canManage($current, $target)
    {
        return $this->roles[$current->titulus] > $this->roles[$target->titulus];
    }

    public function index()
    {
        $users = User::paginate(10);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

public function edit(User $user)
{
    $currentUser = auth()->user();

    // saját magát ne szerkessze
    if ($currentUser->id === $user->id) {
        return redirect()->route('users.index')
            ->withErrors(['error' => 'Nem szerkesztheted saját magad.']);
    }

    // jogosultság check
    if (!$this->canManage($currentUser, $user)) {
        return redirect()->route('users.index')
            ->withErrors(['error' => 'Nincs jogosultságod ehhez!']);
    }

    return view('users.edit', compact('user'));
}

public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|unique:users',
        'teljes_nev' => 'required|unique:users',
        'password' => 'required|min:6',
        'szak' => 'required',
        'titulus' => 'required'
    ]);

    $current = auth()->user();

    // új user objektum fake ellenőrzéshez
    $newUser = new User(['titulus' => $validated['titulus']]);

    if (!$this->canManage($current, $newUser)) {
        return back()
            ->withErrors(['titulus' => 'Nincs jogosultságod ehhez!'])
            ->withInput();
    }

    User::create([
        'name' => $validated['name'],
        'teljes_nev' => $validated['teljes_nev'],
        'password' => Hash::make($validated['password']),
        'szak' => $validated['szak'],
        'titulus' => $validated['titulus'],
    ]);

    return redirect()->route('users.index')
        ->with('success', 'Felhasználó sikeresen létrehozva!');
}

 

public function update(Request $request, User $user)
{
    $currentUser = auth()->user();

    // saját magát ne módosíthassa
    if ($currentUser->id === $user->id) {
        return back()->withErrors(['error' => 'Nem módosíthatod saját magad!']);
    }

    // jogosultság ellenőrzés
    if (!$this->canManage($currentUser, $user)) {
        return back()->withErrors(['error' => 'Nincs jogosultságod!']);
    }

    $validated = $request->validate([
        'name' => 'required|unique:users,name,' . $user->id,
        'teljes_nev' => 'required|unique:users,teljes_nev,' . $user->id,
        'password' => 'nullable|min:6',
        'szak' => 'required',
        'titulus' => 'required',
    ]);

    // titulus módosítás védelem
    $newUser = new User(['titulus' => $validated['titulus']]);

    if (!$this->canManage($currentUser, $newUser)) {
        return back()->withErrors(['titulus' => 'Nincs jogosultságod ehhez!']);
    }

    // jelszó csak akkor frissüljön ha megadták
    if (!empty($validated['password'])) {
        $validated['password'] = Hash::make($validated['password']);
    } else {
        unset($validated['password']);
    }

    $user->update($validated);

    return redirect()->route('users.index')->with('success', 'Felhasználó módosítva.');
}
    public function destroy(User $user)
{
    $currentUser = auth()->user();

    if ($currentUser->id === $user->id) {
        return redirect()->route('users.index')
            ->withErrors(['error' => 'Nem törölheted saját magad.']);
    }

    if (!$this->canManage($currentUser, $user)) {
        return redirect()->route('users.index')
            ->withErrors(['error' => 'Nincs jogosultságod!']);
    }

    $user->delete();

    return redirect()->route('users.index')
        ->with('success', 'Felhasználó törölve.');
}
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $users = User::paginate(10);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
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

        User::create([
            'name' => $validated['name'],
            'teljes_nev' => $validated['teljes_nev'],
            'password' => Hash::make($validated['password']),
            'szak' => $validated['szak'],
            'titulus' => $validated['titulus'],
        ]);

        return redirect()->route('users.index')->with('success', 'Felhasználó sikeresen létrehozva!');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'teljes_nev' => 'required|unique:users,teljes_nev,' . $user->id,
            'szak' => 'required',
            'titulus' => 'required',
        ]);

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Felhasználó módosítva.');
    }

    public function destroy(User $user)
    {
        $currentUser = Auth::user();

        // Prevent Elnök from deleting Admin
        if ($currentUser->titulus === 'Elnök' && $user->titulus === 'Admin') {
            return redirect()->route('users.index')->withErrors(['error' => 'Az Admin törlése nem engedélyezett.']);
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'Felhasználó törölve.');
    }
}

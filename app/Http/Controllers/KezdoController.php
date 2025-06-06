<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Naptar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class KezdoController extends Controller
{
    public function login()
    {
        return view('login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'name' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('name', $credentials['name'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            Auth::login($user);
            return redirect()->intended('/dashboard/main');
        }

        return back()->withErrors([
            'name' => 'Hibás felhasználónév vagy jelszó.',
        ]);
    }


    public function dashboard()
    {
    $users = User::latest()->paginate(10); 
    $naptar_szamok = Naptar::count();
    return view('dashboard.index', compact('users','naptar_szamok'));
    }

    public function naptar()
    {
    $naptarok = Naptar::orderBy('date', 'DESC')->paginate(10);

    return view('dashboard.naptar', compact('naptarok'));
    }
}

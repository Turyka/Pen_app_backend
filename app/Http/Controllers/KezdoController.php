<?php

namespace App\Http\Controllers;

use App\Models\Eszkozok;
use App\Models\Hir;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Naptar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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
            $request->session()->regenerate();
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
    $eszkozok_szamok = Eszkozok::count();
    $hir_Szamok = Hir::count();
    $eszkozok = Eszkozok::select(
        DB::raw("COALESCE(NULLIF(TRIM(SPLIT_PART(device, ' ', 1)), ''), 'Unknown') AS brand"),
        DB::raw("COUNT(*) as count")
    )
    ->groupBy('brand')
    ->get();
    
    $startDate = Carbon::now()->subDays(6)->startOfDay(); 
    $endDate = Carbon::now()->endOfDay();

    $logins = Eszkozok::select(
        DB::raw("DATE(datetime) as date"),
        DB::raw("COUNT(*) as count")
    )
    ->whereBetween('datetime', [$startDate, $endDate])
    ->groupBy('date')
    ->orderBy('date')
    ->pluck('count', 'date');

    $napilogin = [];
    for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
        $napilogin[] = [
            'date' => $date->toDateString(),
            'count' => $logins[$date->toDateString()] ?? 0,
        ];
    }

    return view('dashboard.index', compact('users','naptar_szamok','eszkozok_szamok','hir_Szamok','eszkozok','napilogin'));
    }

    public function naptar()
    {
    $naptarok = Naptar::orderBy('date', 'DESC')->paginate(10);

    return view('dashboard.naptar', compact('naptarok'));
    }
}

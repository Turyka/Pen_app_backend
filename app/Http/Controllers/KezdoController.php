<?php

namespace App\Http\Controllers;

use App\Models\Eszkozok;
use App\Models\Hir;
use App\Models\Napilogin;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Naptar;
use App\Models\Kozlemeny;
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
    public function command()
    {
        return view('commandok');
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
    $kozlemenyek_szama = Kozlemeny::count();
$driver = DB::getDriverName();

if ($driver === 'pgsql') {
    $brandExpr = "COALESCE(NULLIF(TRIM(SPLIT_PART(device, ' ', 1)), ''), 'Unknown')";
} else {
    // mysql
    $brandExpr = "COALESCE(NULLIF(TRIM(SUBSTRING_INDEX(device, ' ', 1)), ''), 'Unknown')";
}

$eszkozok = Eszkozok::select(
        DB::raw("$brandExpr AS brand"),
        DB::raw("COUNT(*) as count")
    )
    ->groupBy('brand')
    ->get();
    $tz = 'Europe/Budapest'; // vagy config('app.timezone')
$start = Carbon::now($tz)->subDays(6)->startOfDay();
$end   = Carbon::now($tz)->endOfDay();

$rawLogins = Napilogin::select(
        DB::raw("CAST(datetime AS DATE) as date"),
        DB::raw("COUNT(*) as count")
    )
    ->whereBetween('datetime', [$start, $end])
    ->groupBy('date')
    ->orderBy('date')
    ->get();

$logins = $rawLogins->pluck('count', 'date')->all();

$napilogin = [];
for ($date = $start->copy(); $date <= $end; $date->addDay()) {
    $napilogin[] = [
        'date'  => $date->toDateString(),        // 2025-06-26
        'count' => $logins[$date->toDateString()] ?? 0,
    ];
}

    return view('dashboard.index', compact('users','naptar_szamok','eszkozok_szamok','hir_Szamok','eszkozok','napilogin','kozlemenyek_szama'));
    }

    public function naptar()
    {
    $naptarok = Naptar::orderBy('date', 'DESC')->paginate(10);

    return view('dashboard.naptar', compact('naptarok'));
    }

    public function kozlemeny()
    {
       
     $kozlemenyek = auth()->user()
        ->kozlemeny()   // <-- note the parentheses to keep it as a query
        ->orderBy('created_at', 'DESC')
        ->paginate(10);

    return view('dashboard.kozlemeny', compact('kozlemenyek'));
    }
}

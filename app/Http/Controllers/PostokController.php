<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class PostokController extends Controller
{
    public function scrape()
    {
        $output = new BufferedOutput();

        // Call the correct Artisan command
        Artisan::call('scrape:fbpannon', [], $output);

        return response()->json([
            'status' => 'ok',
            'output' => $output->fetch(),
        ]);
    }
}

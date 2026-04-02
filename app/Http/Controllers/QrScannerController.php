<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class QrScannerController extends Controller
{
    public function __invoke(): View
    {
        return view('scanner.index');
    }
}


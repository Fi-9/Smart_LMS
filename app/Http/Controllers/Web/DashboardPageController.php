<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Contracts\View\View;

class DashboardPageController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {
    }

    public function __invoke(): View
    {
        return $this->view();
    }

    public function view(): View
    {
        return view('dashboard.index', [
            'stats' => $this->dashboardService->stats(),
        ]);
    }
}

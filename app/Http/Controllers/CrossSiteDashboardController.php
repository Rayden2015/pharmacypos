<?php

namespace App\Http\Controllers;

use App\Support\CrossSiteMetrics;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CrossSiteDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        if (! auth()->user()->isSuperAdmin()) {
            throw new AccessDeniedHttpException('Cross-site dashboard is only available to super admins.');
        }

        return view('dashboard.cross-site', CrossSiteMetrics::build());
    }
}

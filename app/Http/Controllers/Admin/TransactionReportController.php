<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View as ViewResponse;

class TransactionReportController extends Controller
{
    public function walletBonus(): ViewResponse
    {
        return view('admin-views.transaction.wallet-bonus');
    }
}

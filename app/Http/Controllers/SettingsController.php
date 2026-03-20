<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $currencySymbol = Setting::get('currency_symbol', '#');
        $currencyCode = Setting::get('currency_code', '');

        return view('settings.index', compact('currencySymbol', 'currencyCode'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'currency_symbol' => 'required|string|max:16',
            'currency_code' => 'nullable|string|max:8',
        ]);

        Setting::set('currency_symbol', $data['currency_symbol']);
        Setting::set('currency_code', $data['currency_code'] ?? '');

        Setting::clearRuntimeCache();

        return redirect()->route('settings.index')->with('success', 'Settings saved.');
    }
}

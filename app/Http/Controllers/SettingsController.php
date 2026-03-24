<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    /**
     * Per-user notification preferences (in-app + email opt-ins for future mailers).
     */
    public function notifications(Request $request)
    {
        $user = $request->user();
        $prefs = array_merge($this->defaultNotificationPreferences(), $user->notification_preferences ?? []);

        return view('settings.notifications', compact('prefs'));
    }

    public function updateNotifications(Request $request)
    {
        $keys = array_keys($this->defaultNotificationPreferences());
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $request->boolean($key);
        }

        $user = $request->user();
        $user->notification_preferences = $data;
        $user->save();

        Log::channel('audit')->info('settings.notifications.updated', [
            'user_id' => $user->id,
            'keys' => array_keys($data),
        ]);

        return redirect()->route('settings.notifications')->with('success', 'Notification preferences saved.');
    }

    /**
     * @return array<string, bool>
     */
    private function defaultNotificationPreferences(): array
    {
        return [
            'announcements_enabled' => true,
            'direct_messages_enabled' => true,
            'email_notifications_enabled' => false,
            'email_low_stock' => false,
            'email_expiry_alerts' => false,
            'email_sales_digest' => false,
        ];
    }
}

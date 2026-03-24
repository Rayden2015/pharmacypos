<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Settings hub (links to sub-pages).
     */
    public function index()
    {
        return view('settings.index');
    }

    /**
     * Currency, language, timezone, date & time display formats.
     */
    public function localization()
    {
        $currencySymbol = Setting::get('currency_symbol', '#');
        $currencyCode = Setting::get('currency_code', '');
        $appLocale = Setting::get('app_locale', 'en');
        $appTimezone = Setting::get('app_timezone', config('app.timezone'));
        $dateFormat = Setting::get('date_format', 'd M Y');
        $timeFormat = Setting::get('time_format', 'H:i');
        $timezones = timezone_identifiers_list();

        return view('settings.localization', compact(
            'currencySymbol',
            'currencyCode',
            'appLocale',
            'appTimezone',
            'dateFormat',
            'timeFormat',
            'timezones'
        ));
    }

    public function saveLocalization(Request $request)
    {
        $data = $request->validate([
            'currency_symbol' => 'required|string|max:16',
            'currency_code' => 'nullable|string|max:8',
            'app_locale' => ['required', 'string', 'max:12', Rule::in($this->allowedLocales())],
            'app_timezone' => 'required|timezone',
            'date_format' => ['required', 'string', 'max:32', Rule::in($this->allowedDateFormats())],
            'time_format' => ['required', 'string', 'max:32', Rule::in($this->allowedTimeFormats())],
        ]);

        Setting::set('currency_symbol', $data['currency_symbol']);
        Setting::set('currency_code', $data['currency_code'] ?? '');
        Setting::set('app_locale', $data['app_locale']);
        Setting::set('app_timezone', $data['app_timezone']);
        Setting::set('date_format', $data['date_format']);
        Setting::set('time_format', $data['time_format']);

        Setting::clearRuntimeCache();

        Log::channel('audit')->info('settings.localization.updated', [
            'user_id' => $request->user()->id,
            'app_locale' => $data['app_locale'],
            'app_timezone' => $data['app_timezone'],
        ]);

        return redirect()->route('settings.localization')->with('success', 'Localization settings saved.');
    }

    /**
     * @return list<string>
     */
    private function allowedLocales(): array
    {
        return ['en', 'fr', 'es', 'ar'];
    }

    /**
     * @return list<string>
     */
    private function allowedDateFormats(): array
    {
        return ['d M Y', 'd/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y'];
    }

    /**
     * @return list<string>
     */
    private function allowedTimeFormats(): array
    {
        return ['H:i', 'g:i A'];
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

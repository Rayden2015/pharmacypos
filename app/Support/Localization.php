<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Display helpers using settings date/time formats (see settings/localization).
 */
final class Localization
{
    public static function formatDate($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        $date = $value instanceof Carbon ? $value->copy() : Carbon::parse($value);
        $fmt = self::dateFormat();

        return $date->format($fmt);
    }

    public static function formatTime($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        $date = $value instanceof Carbon ? $value->copy() : Carbon::parse($value);
        $fmt = self::timeFormat();

        return $date->format($fmt);
    }

    public static function formatDateTime($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        $date = $value instanceof Carbon ? $value->copy() : Carbon::parse($value);

        return $date->format(self::dateFormat().' '.self::timeFormat());
    }

    public static function dateFormat(): string
    {
        try {
            if (Schema::hasTable('settings')) {
                $f = Setting::get('date_format');

                return $f && is_string($f) ? $f : 'd M Y';
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return 'd M Y';
    }

    public static function timeFormat(): string
    {
        try {
            if (Schema::hasTable('settings')) {
                $f = Setting::get('time_format');

                return $f && is_string($f) ? $f : 'H:i';
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return 'H:i';
    }
}

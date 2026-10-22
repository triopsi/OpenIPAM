<?php

namespace App\Services;

use App\Models\DeviceType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/** Resolve localized built-ins and shared custom types within one request scope. */
class DeviceTypeService
{
    public const BUILT_INS = ['server', 'workstation', 'laptop', 'printer', 'switch', 'router', 'firewall', 'access_point', 'other'];

    /** @var array<string, string>|null Raw custom names, cached without locale-dependent labels. */
    private ?array $customNames = null;

    /** Return current-locale built-ins alongside the globally available custom names. */
    public function options(): array
    {
        $options = [];
        foreach (self::BUILT_INS as $value) {
            $options[$value] = __('devices.types.'.$value);
        }

        return $options + $this->customNames();
    }

    /** Resolve a display/export label without turning unknown legacy types into Other. */
    public function label(?string $value): string
    {
        $value ??= 'other';

        return in_array($value, self::BUILT_INS, true)
            ? __('devices.types.'.$value)
            : ($this->customNames()[$value] ?? $value);
    }

    /**
     * Create a shared custom type or reuse an existing name regardless of case.
     *
     * Stable values preserve existing devices.type semantics. A hash suffix keeps
     * different names with identical transliterations distinct, while the unique
     * database key prevents concurrent requests from duplicating the same name.
     */
    public function create(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($name));
        Validator::make(['name' => $name], ['name' => ['required', 'string', 'max:100']])->validate();
        $normalized = mb_strtolower($name);
        foreach (self::BUILT_INS as $value) {
            foreach ([$value, __('devices.types.'.$value, [], 'en'), __('devices.types.'.$value, [], 'de')] as $label) {
                if ($normalized === mb_strtolower($label)) {
                    return $value;
                }
            }
        }

        // Refresh before deduplication so another request's newly created types are visible.
        $this->customNames = null;
        foreach ($this->customNames() as $value => $label) {
            if ($normalized === mb_strtolower($label) || $normalized === mb_strtolower($value)) {
                return $value;
            }
        }
        $slug = substr(Str::slug($name, '_') ?: 'type', 0, 200);
        $value = 'custom_'.$slug.'_'.substr(hash('sha256', $normalized), 0, 12);
        DeviceType::firstOrCreate(['value' => $value], ['name' => $name]);
        $this->customNames = null;

        return $value;
    }

    /**
     * Register raw values from CSV or direct model writes without rewriting devices.
     *
     * Built-in keys already exist after migration. Custom catalog entries remain
     * available even after the last device using them has been deleted.
     */
    public function registerExisting(string $value): void
    {
        if ($value === '' || in_array($value, self::BUILT_INS, true)) {
            return;
        }
        DeviceType::firstOrCreate(['value' => $value], ['name' => $value]);
        $this->customNames = null;
    }

    /** Load raw custom labels once, avoiding a query for every device table row. */
    private function customNames(): array
    {
        return $this->customNames ??= DeviceType::query()->whereNotIn('value', self::BUILT_INS)
            ->orderBy('name')->pluck('name', 'value')->all();
    }
}

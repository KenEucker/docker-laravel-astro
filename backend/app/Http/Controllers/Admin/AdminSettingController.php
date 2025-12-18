<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminSettingController extends Controller
{
    public function index()
    {
        return Setting::query()
            ->orderBy('key', 'asc')
            ->get(['id', 'key', 'value', 'type', 'description', 'is_public', 'updated_at']);
    }

    public function show(Setting $setting)
    {
        return [
            'id' => $setting->id,
            'key' => $setting->key,
            'value' => $setting->value,
            'type' => $setting->type,
            'description' => $setting->description,
            'is_public' => $setting->is_public,
            'updated_at' => $setting->updated_at,
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:255', 'unique:settings,key'],
            'value' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:string,integer,float,boolean,json,array'],
            'description' => ['nullable', 'string'],
            'is_public' => ['boolean'],
        ]);

        // Validate the value based on type
        $this->validateValueForType($data['value'] ?? null, $data['type']);

        $setting = Setting::create($data);

        return [
            'ok' => true,
            'setting' => $setting,
        ];
    }

    public function update(Request $request, Setting $setting)
    {
        $data = $request->validate([
            'value' => ['nullable', 'string'],
            'type' => ['sometimes', 'string', 'in:string,integer,float,boolean,json,array'],
            'description' => ['nullable', 'string'],
            'is_public' => ['boolean'],
        ]);

        // Validate the value based on type
        if (isset($data['value'])) {
            $type = $data['type'] ?? $setting->type;
            $this->validateValueForType($data['value'], $type);
        }

        $setting->update($data);

        // Clear cache for this setting
        \Illuminate\Support\Facades\Cache::forget("setting.{$setting->key}");

        return [
            'ok' => true,
            'setting' => $setting->fresh(),
        ];
    }

    public function destroy(Setting $setting)
    {
        $key = $setting->key;
        $setting->delete();

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget("setting.{$key}");

        return [
            'ok' => true,
        ];
    }

    /**
     * Get the current value of a setting (with env fallback).
     */
    public function getValue(string $key)
    {
        $value = Setting::get($key);
        $setting = Setting::where('key', $key)->first();

        return [
            'key' => $key,
            'value' => $value,
            'source' => $setting ? 'database' : 'environment',
            'type' => $setting?->type ?? 'string',
        ];
    }

    /**
     * Validate that a value is appropriate for the given type.
     */
    protected function validateValueForType($value, string $type): void
    {
        if ($value === null) {
            return;
        }

        $validator = match ($type) {
            'integer', 'int' => Validator::make(
                ['value' => $value],
                ['value' => 'numeric']
            ),
            'float', 'double' => Validator::make(
                ['value' => $value],
                ['value' => 'numeric']
            ),
            'boolean', 'bool' => Validator::make(
                ['value' => $value],
                ['value' => 'in:0,1,true,false']
            ),
            'json', 'array', 'object' => Validator::make(
                ['value' => $value],
                ['value' => 'json']
            ),
            default => null,
        };

        if ($validator && $validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }
}

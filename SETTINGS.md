# Application Settings

This application includes a flexible settings system that allows you to manage application-wide configuration values through the database, with automatic fallback to environment variables.

## Features

- **Database-first with env fallback**: Settings are stored in the database, but automatically fall back to environment variables if not set
- **Type support**: Supports string, integer, float, boolean, JSON, and array types
- **Public/Private settings**: Control which settings can be read by non-admin users
- **Caching**: Settings are cached for 1 hour for better performance
- **Admin UI**: Full-featured admin interface for managing settings at `/admin/settings`
- **Quick edit**: Scalar values (string, integer, float, boolean) can be edited inline
- **Complex settings**: Non-scalar values (JSON, arrays) can be edited through a modal

## Backend Usage

### Using the Setting Model

```php
use App\Models\Setting;

// Get a setting (falls back to env variable if not in database)
$appName = Setting::get('APP_NAME', 'Default App Name');

// Set a setting
Setting::set('APP_NAME', 'My Application', 'string', 'The application name', true);

// Check if a setting exists
if (Setting::has('FEATURE_FLAG')) {
    // ...
}

// Delete a setting
Setting::forget('OLD_SETTING');

// Get all settings
$allSettings = Setting::all();

// Get only public settings
$publicSettings = Setting::all(true);
```

### Using Helper Functions

```php
// Get a setting
$value = setting('APP_NAME', 'Default');

// Set a setting
setting_set('APP_NAME', 'My App', 'string', 'App name', true);

// Check if exists
if (setting_has('FEATURE_FLAG')) {
    // ...
}

// Delete a setting
setting_forget('OLD_SETTING');

// Get all settings
$all = settings_all();
```

## Frontend Usage

### Admin Interface

Navigate to `/admin/settings` to manage settings through the web interface.

**Features:**
- View all settings in a table
- Quick edit scalar values inline
- Add new settings with the "Add Setting" button
- Edit complex settings (JSON, arrays) through a modal
- Delete settings
- See setting descriptions and metadata

### Using Settings in Your Code

Settings can be accessed through the API:

```javascript
// Get all settings (admin only)
const response = await fetch(`${API}/api/admin/settings`, {
  credentials: 'include',
  headers: { accept: 'application/json' },
})
const settings = await response.json()

// Get a specific setting with env fallback info
const response = await fetch(`${API}/api/admin/settings/APP_NAME/value`, {
  credentials: 'include',
  headers: { accept: 'application/json' },
})
const data = await response.json()
// Returns: { key, value, source: 'database'|'environment', type }
```

## Setting Types

| Type | Description | Example |
|------|-------------|---------|
| `string` | Text value | `"Hello World"` |
| `integer` | Whole number | `42` |
| `float` | Decimal number | `3.14` |
| `boolean` | True/false | `true` or `false` |
| `json` | JSON object | `{"key": "value"}` |
| `array` | JSON array | `["item1", "item2"]` |

## Database Schema

The settings table has the following structure:

```sql
CREATE TABLE settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  key VARCHAR(255) UNIQUE NOT NULL,
  value TEXT NULL,
  type VARCHAR(255) DEFAULT 'string',
  description TEXT NULL,
  is_public BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);
```

## Seeding Default Settings

Run the settings seeder to populate default settings:

```bash
# Inside the Laravel container
php artisan db:seed --class=SettingsSeeder
```

Or add it to your main database seeder:

```php
public function run()
{
    $this->call([
        // ... other seeders
        SettingsSeeder::class,
    ]);
}
```

## API Endpoints

All endpoints require admin authentication.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/settings` | List all settings |
| GET | `/api/admin/settings/{id}` | Get a specific setting by ID |
| GET | `/api/admin/settings/{key}/value` | Get setting value with fallback info |
| POST | `/api/admin/settings` | Create a new setting |
| PUT | `/api/admin/settings/{id}` | Update a setting |
| DELETE | `/api/admin/settings/{id}` | Delete a setting |

## Environment Variable Fallback

If a setting is not found in the database, the system will automatically check for an environment variable with the same name (converted to uppercase).

Example:
```php
// If 'app_name' is not in the database, it will check for:
// - APP_NAME env variable
// - Return the default value if neither exists

$name = setting('app_name', 'Default Name');
```

## Caching

Settings are cached for 1 hour to improve performance. The cache is automatically cleared when:
- A setting is updated
- A setting is deleted
- A setting is created

To manually clear the settings cache:

```php
use Illuminate\Support\Facades\Cache;

// Clear a specific setting
Cache::forget('setting.APP_NAME');

// Or use the model method
Setting::forget('APP_NAME');
```

## Best Practices

1. **Use descriptive keys**: Use clear, uppercase keys like `APP_NAME` or `MAX_UPLOAD_SIZE`
2. **Set appropriate types**: Always specify the correct type for proper type casting
3. **Document settings**: Use the description field to explain what each setting does
4. **Mark public settings carefully**: Only mark settings as public if they should be readable by all users
5. **Use env for secrets**: Keep sensitive data (API keys, passwords) in environment variables, not the database
6. **Provide defaults**: Always provide sensible default values when getting settings

## Example Use Cases

### Maintenance Mode

```php
if (setting('MAINTENANCE_MODE', false)) {
    return response('Site under maintenance', 503);
}
```

### Dynamic Configuration

```php
$maxUploadSize = setting('MAX_UPLOAD_SIZE', 10485760); // 10MB default
$request->validate([
    'file' => "required|file|max:{$maxUploadSize}",
]);
```

## Troubleshooting

### Settings not updating
- Check that the cache is being cleared
- Verify admin permissions
- Check browser console for JavaScript errors

### Fallback not working
- Ensure environment variable names are uppercase
- Check that `.env` file is loaded
- Verify the setting key matches the env variable name

### Type casting issues
- Ensure the `type` field is set correctly
- Validate the value format matches the type
- Check for JSON syntax errors in JSON/array types

# Orchid Admin Panel Setup Guide

## Overview

This document describes the Orchid admin panel integration for your Laravel + Astro application. Orchid provides a powerful admin UI for managing users and settings, while preserving your existing Astro-based API functionality.

## 🎯 What Was Implemented

### 1. **Orchid Platform Installation**
   - Laravel Orchid 14.x installed via Composer
   - Automatic installation on container startup
   - No conflicts with existing Sanctum cookie authentication

### 2. **Authorization & Security**
   - **Admin-only access**: Only users with the `admin` role (via Spatie) can access Orchid
   - Custom middleware: `OrchidAdminAccess` checks for admin role
   - Route prefix: `/admin` (configurable via `ORCHID_PREFIX` env variable)
   - Fully isolated from your public API routes

### 3. **Dashboard Screen**
   - Welcome message with admin name
   - Metrics cards showing:
     - Total users count
     - Admin users count
     - Settings count
     - Latest registered user
   - Quick links to:
     - Manage Users
     - Manage Settings
     - Roles & Permissions
     - View Frontend (Astro app)

### 4. **User Management**
   - **List View**:
     - Searchable/filterable table by ID, name, email
     - Shows user roles
     - Pagination (15 per page)
     - Sort by any column
   - **Create/Edit View**:
     - Name, email, password fields
     - Avatar upload (max 5MB, stored in `storage/app/public`)
     - Role assignment (multiple roles via Spatie)
     - Password field:
       - Required for new users
       - Optional for existing users (leave blank to keep current)
   - **Delete**:
     - Confirmation dialog
     - Automatically deletes avatar from storage
   - **Permissions**:
     - `platform.users.list` - View users
     - `platform.users.edit` - Create/edit users
     - `platform.users.delete` - Delete users

### 5. **Settings Management**
   - **List View**:
     - Searchable by key
     - Shows: key, value (truncated), type, visibility, description
     - Color-coded type badges
     - Public/Private visibility badges
     - Pagination (20 per page)
   - **Create/Edit View**:
     - Key (unique identifier, disabled when editing)
     - Type selector:
       - `string` - Text values
       - `integer` - Whole numbers
       - `float` - Decimal numbers
       - `boolean` - True/false (1/0)
       - `json` - JSON objects/arrays
       - `array` - Arrays
       - `object` - Objects
     - Value (textarea with type-specific validation)
     - Description (optional, helps other admins)
     - Public visibility checkbox
   - **Type Validation**:
     - Automatic validation based on selected type
     - JSON validation for json/array/object types
     - Numeric validation for integer/float
   - **Caching**:
     - Uses existing `Setting::set()` method
     - Automatically clears cache on updates
   - **Permissions**:
     - `platform.settings.list` - View settings
     - `platform.settings.edit` - Create/edit settings
     - `platform.settings.delete` - Delete settings

### 6. **Roles & Permissions**
   - Built-in Orchid roles/permissions screen
   - Integrated with existing Spatie roles (`admin`, `user`)
   - Admin role automatically gets all Orchid permissions

---

## 📂 File Structure

```
backend/
├── app/
│   ├── Http/Middleware/
│   │   └── OrchidAdminAccess.php          # Admin-only middleware
│   ├── Models/
│   │   ├── User.php                       # Added AsSource trait
│   │   └── Setting.php                    # Added AsSource trait
│   └── Orchid/
│       ├── PlatformProvider.php           # Menu & permissions config
│       ├── Screens/
│       │   ├── DashboardScreen.php        # Main dashboard
│       │   ├── User/
│       │   │   ├── UserListScreen.php     # User list
│       │   │   └── UserEditScreen.php     # User create/edit
│       │   └── Setting/
│       │       ├── SettingListScreen.php  # Settings list
│       │       └── SettingEditScreen.php  # Settings create/edit
│       └── Layouts/
│           ├── User/
│           │   └── UserListLayout.php     # User table definition
│           └── Setting/
│               └── SettingListLayout.php  # Settings table definition
├── config/
│   └── platform.php                       # Orchid configuration
├── database/seeders/
│   └── OrchidPermissionsSeeder.php        # Orchid permissions
├── resources/views/orchid/dashboard/
│   ├── welcome.blade.php                  # Dashboard welcome card
│   ├── metrics.blade.php                  # Metrics card component
│   └── quick-links.blade.php              # Quick links section
├── routes/
│   └── platform.php                       # Orchid routes
└── entrypoint.sh                          # Updated to install Orchid
```

---

## 🚀 Getting Started

### 1. **Rebuild the Docker Container**

Since we updated the `entrypoint.sh` to install Orchid, you need to rebuild:

```bash
# Stop containers
docker-compose down

# Rebuild and start
docker-compose up --build -d
```

### 2. **Watch the Logs**

Monitor the installation:

```bash
docker-compose logs -f laravel
```

You should see:
- `>> Installing Orchid Platform...`
- `>> Running migrations...` (Orchid tables created)
- `>> Running seeders...` (Orchid permissions created)

### 3. **Access the Admin Panel**

#### Local Development:
- URL: `http://admin.local.test/admin`
- Or: `http://localhost:8000/admin`

#### Production:
- URL: `https://admin.[company.com]/admin`

### 4. **Login**

Use your admin user credentials (from `DEFAULT_USER_EMAIL` and `DEFAULT_USER_PASSWORD` in `.env`).

**Default admin user**:
- Email: Value from `DEFAULT_USER_EMAIL`
- Password: Value from `DEFAULT_USER_PASSWORD`

---

## 🔧 Configuration

### Environment Variables

Add to your `.env` file:

```env
# Orchid Configuration
ORCHID_PREFIX=admin              # URL prefix (default: admin)
ORCHID_DOMAIN=                   # Optional subdomain (leave empty)
```

### Changing the Admin Path

If you want to use a different URL (e.g., `/dashboard` instead of `/admin`):

```env
ORCHID_PREFIX=dashboard
```

Then access at: `http://admin.local.test/dashboard`

---

## 🔐 Security & Authorization

### How Authorization Works

1. **Orchid Routes**: All Orchid routes are protected by the `OrchidAdminAccess` middleware
2. **Middleware Check**: Verifies user is authenticated AND has the `admin` role
3. **Permission-Based**: Individual screens check for specific permissions
4. **Spatie Integration**: Uses your existing Spatie roles/permissions

### Permission Structure

All admin users automatically get these permissions:

| Permission | Description |
|------------|-------------|
| `platform.systems.roles` | Access roles & permissions management |
| `platform.users.list` | View users list |
| `platform.users.edit` | Create and edit users |
| `platform.users.delete` | Delete users |
| `platform.settings.list` | View settings list |
| `platform.settings.edit` | Create and edit settings |
| `platform.settings.delete` | Delete settings |

### Adding Custom Permissions

Edit `backend/database/seeders/OrchidPermissionsSeeder.php` and add to the `$permissions` array.

---

## ✅ Verification Checklist

### Local Testing

- [ ] Container rebuilt and running: `docker-compose ps`
- [ ] Orchid installed: Check logs for "Installing Orchid Platform"
- [ ] Migrations ran: Check logs for Orchid table creation
- [ ] Seeders ran: Check logs for "Orchid permissions created"
- [ ] Admin panel accessible: `http://admin.local.test/admin`
- [ ] Can login with admin user
- [ ] Dashboard displays metrics correctly
- [ ] User management:
  - [ ] Can view user list
  - [ ] Can create new user
  - [ ] Can edit existing user
  - [ ] Can assign roles
  - [ ] Can upload avatar
  - [ ] Can delete user
- [ ] Settings management:
  - [ ] Can view settings list
  - [ ] Can create new setting
  - [ ] Can edit existing setting
  - [ ] Type validation works (try invalid JSON)
  - [ ] Can delete setting
- [ ] Roles & Permissions screen accessible
- [ ] Non-admin users are denied access (403)

### Existing API Routes Testing

**CRITICAL**: Verify that your existing Astro app still works:

- [ ] Astro app loads: `http://intranet.local.test`
- [ ] Sanctum auth works (login from Astro)
- [ ] API routes unchanged:
  - [ ] `GET /api/settings/public` - Public settings
  - [ ] `GET /api/me` - Current user
  - [ ] `GET /api/admin/users` - User list (admin)
  - [ ] `GET /api/admin/settings` - Settings (admin)
  - [ ] `POST /api/user/avatar` - Avatar upload
- [ ] CORS still working for cross-domain requests
- [ ] Cookies work between intranet/admin subdomains

### Production Deployment

- [ ] `.env` updated with production values
- [ ] `ADMIN_HOSTNAME` set correctly
- [ ] SSL/HTTPS configured for admin subdomain
- [ ] Sanctum stateful domains include both subdomains
- [ ] Session domain configured: `SESSION_DOMAIN=.company.com`
- [ ] CORS allows production frontend URL
- [ ] Admin user created and has `admin` role
- [ ] Database migrations ran
- [ ] Orchid assets published
- [ ] File uploads working (storage symlink created)

---

## 🔍 Troubleshooting

### Issue: "403 Access Denied" when accessing Orchid

**Solution**:
1. Check user has `admin` role:
   ```bash
   docker-compose exec laravel php artisan tinker
   >>> $user = User::where('email', 'your@email.com')->first();
   >>> $user->roles->pluck('name');  # Should include 'admin'
   ```

2. Assign admin role if missing:
   ```bash
   >>> $user->assignRole('admin');
   ```

### Issue: "Route [platform.dashboard] not defined"

**Solution**:
1. Check routes file exists: `backend/routes/platform.php`
2. Clear route cache:
   ```bash
   docker-compose exec laravel php artisan route:clear
   docker-compose exec laravel php artisan optimize:clear
   ```

### Issue: Orchid not installed

**Solution**:
1. Check if vendor directory exists and contains `orchid/platform`
2. Rebuild container: `docker-compose up --build -d`
3. Check logs: `docker-compose logs laravel`

### Issue: Avatar uploads not working

**Solution**:
1. Ensure storage symlink exists:
   ```bash
   docker-compose exec laravel php artisan storage:link
   ```

2. Check directory permissions:
   ```bash
   docker-compose exec laravel ls -la storage/app/public
   ```

### Issue: Settings changes don't reflect immediately

**Solution**:
Cache might be stale. Clear it:
```bash
docker-compose exec laravel php artisan cache:clear
```

### Issue: Existing API routes broken

**Solution**:
1. Check `routes/api_custom.php` still exists
2. Verify `routes/api.php` includes `api_custom.php`
3. Check Orchid middleware doesn't affect API routes (it shouldn't - API uses `/api/*`, Orchid uses `/admin/*`)

---

## 🎨 Customization

### Changing Dashboard Metrics

Edit `backend/app/Orchid/Screens/DashboardScreen.php`:

```php
public function query(): iterable
{
    return [
        'metrics' => [
            'users' => User::count(),
            'new_metric' => YourModel::count(),  // Add your metric
        ],
    ];
}
```

### Adding Menu Items

Edit `backend/app/Orchid/PlatformProvider.php`:

```php
protected function registerMenu(): void
{
    Menu::register([
        // Existing menus...

        Menu::make(__('My Custom Menu'))
            ->icon('bs.box')
            ->route('platform.custom.route')
            ->permission('platform.custom.access'),
    ]);
}
```

### Customizing Branding

Edit `backend/config/platform.php`:

```php
'template' => [
    'header' => 'My Company Admin',
    'footer' => 'Powered by My Company',
],
```

---

## 📚 Additional Resources

- [Orchid Official Docs](https://orchid.software/en/docs)
- [Orchid GitHub](https://github.com/orchidsoftware/platform)
- [Spatie Permissions Docs](https://spatie.be/docs/laravel-permission)
- [Laravel Sanctum Docs](https://laravel.com/docs/sanctum)

---

## 🔄 Removing Astro Admin Pages (Future)

Once you've confirmed Orchid works and you're ready to remove the Astro admin pages:

1. **Keep the API endpoints** (they're shared between Orchid and Astro)
2. **Remove Astro admin UI components**:
   - Delete Astro pages under `frontend/src/pages/admin/`
   - Remove admin routes from Astro router
   - Keep API client functions in Astro (reusable)

3. **No backend changes needed** - the API routes are used by both!

---

## 📝 Summary

**What you got**:
- ✅ Full admin panel at `/admin` route
- ✅ User management (CRUD + roles + avatar)
- ✅ Settings management (CRUD + types + caching)
- ✅ Role/permission management (Spatie integration)
- ✅ Admin-only access control
- ✅ Existing API routes preserved
- ✅ No breaking changes to Astro app
- ✅ Production-ready setup

**Next steps**:
1. Rebuild containers
2. Login to admin panel
3. Verify all features work
4. Test existing Astro app still works
5. Deploy to production when ready

---

## ❓ Questions or Issues?

If you encounter any problems:

1. Check this document's troubleshooting section
2. Review Docker logs: `docker-compose logs laravel`
3. Check Laravel logs: `docker-compose exec laravel tail -f storage/logs/laravel.log`
4. Verify database has Orchid tables: `docker-compose exec db mysql -uapp -psecret app -e "SHOW TABLES;"`

Happy admin panel building! 🎉

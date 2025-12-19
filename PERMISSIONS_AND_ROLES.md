# Permissions and Roles Guide

## Overview

This application uses **Spatie Laravel Permission** package for all authorization. There is **one permission system** with **two permission namespaces**:

1. **API Permissions** (`admin.*`) - Control access to API endpoints
2. **Orchid UI Permissions** (`platform.*`) - Control access to Orchid admin panel screens

Both permission sets are stored in the same Spatie database tables and managed through the same roles system.

---

## Permission Namespaces

### 1. API Permissions (`admin.*`)

Used by: API routes at `/api/admin/*` (called by Astro frontend)

| Permission | Description | Used By |
|------------|-------------|---------|
| `admin.view` | View admin-level data | General admin API access |
| `admin.manage-users` | Manage users via API | User management endpoints |

**Middleware:** `RoleMiddleware::class . ':admin'` (requires admin role)

**Example API Routes:**
```
GET  /api/admin/users              → Requires: admin role
GET  /api/admin/settings           → Requires: admin role
PUT  /api/admin/users/{user}       → Requires: admin role
```

### 2. Orchid UI Permissions (`platform.*`)

Used by: Orchid admin panel screens at `/admin/*`

| Permission | Description | Used By |
|------------|-------------|---------|
| `platform.systems.roles` | Access roles and permissions management | Roles & Permissions screen |
| `platform.users.list` | View users list | User list screen |
| `platform.users.edit` | Create and edit users | User create/edit screen |
| `platform.users.delete` | Delete users | User delete action |
| `platform.settings.list` | View settings list | Settings list screen |
| `platform.settings.edit` | Create and edit settings | Settings create/edit screen |
| `platform.settings.delete` | Delete settings | Settings delete action |

**Middleware:** `OrchidAdminAccess::class` (requires admin role) + individual screen permission checks

**Example Orchid Screens:**
```
/admin/dashboard                   → Requires: admin role (no specific permission)
/admin/users                       → Requires: platform.users.list
/admin/users/create                → Requires: platform.users.edit
/admin/settings                    → Requires: platform.settings.list
```

---

## Permission Mapping

This table shows the **conceptual mapping** between API and Orchid permissions:

| Feature Area | API Permission | Orchid Permission(s) | Notes |
|--------------|----------------|----------------------|-------|
| User Management | `admin.manage-users` | `platform.users.list`<br>`platform.users.edit`<br>`platform.users.delete` | Orchid has granular CRUD permissions |
| Settings Management | `admin.view` | `platform.settings.list`<br>`platform.settings.edit`<br>`platform.settings.delete` | Orchid has granular CRUD permissions |
| Roles & Permissions | *None* (admin-only) | `platform.systems.roles` | Only accessible via Orchid UI |

**Important:** These permissions are **independent** - having `admin.manage-users` does NOT automatically grant `platform.users.edit`.

---

## Roles

### Default Roles

#### 1. **Admin Role**
- **Name:** `admin`
- **Permissions:** ALL (both `admin.*` and `platform.*`)
- **Access:** Full API access + Full Orchid UI access
- **Created by:** `RolesAndPermissionsSeeder` + `OrchidPermissionsSeeder`

```php
$adminRole = Role::where('name', 'admin')->first();
$adminRole->permissions->pluck('name');
// Returns: [
//   'admin.view',
//   'admin.manage-users',
//   'platform.systems.roles',
//   'platform.users.list',
//   'platform.users.edit',
//   'platform.users.delete',
//   'platform.settings.list',
//   'platform.settings.edit',
//   'platform.settings.delete',
// ]
```

#### 2. **User Role**
- **Name:** `user`
- **Permissions:** NONE by default
- **Access:** No admin API access, No Orchid UI access
- **Created by:** `RolesAndPermissionsSeeder`

---

## Creating Custom Roles

### Example 1: API-Only Manager

User who can manage users via API but cannot access Orchid UI:

```php
use Spatie\Permission\Models\Role;

$apiManager = Role::create(['name' => 'api-manager']);
$apiManager->givePermissionTo([
    'admin.view',
    'admin.manage-users',
]);

// Assign to user
$user->assignRole('api-manager');
```

**Result:**
- ✅ Can call `/api/admin/users` (API access)
- ❌ Cannot access `/admin` (no Orchid UI access - blocked by `OrchidAdminAccess` middleware)

### Example 2: UI-Only Manager

User who can manage users via Orchid UI but cannot access API:

```php
$uiManager = Role::create(['name' => 'ui-manager']);
$uiManager->givePermissionTo([
    'platform.users.list',
    'platform.users.edit',
    'platform.users.delete',
]);

$user->assignRole('ui-manager');
```

**Result:**
- ✅ Can access `/admin/users` (Orchid UI access)
- ❌ Cannot call `/api/admin/users` (no API access - lacks admin role)

**Note:** To access Orchid at all, the user must have the `admin` role (enforced by `OrchidAdminAccess` middleware). So this example would need modification - see Example 4.

### Example 3: Read-Only Admin (Both API and UI)

User who can view but not modify data in both API and UI:

```php
$readOnly = Role::create(['name' => 'read-only-admin']);
$readOnly->givePermissionTo([
    'admin.view',
    'platform.users.list',
    'platform.settings.list',
]);

$user->syncRoles(['admin', 'read-only-admin']);
```

**Result:**
- ✅ Can access Orchid UI (has admin role)
- ✅ Can view users/settings in UI
- ❌ Cannot edit/delete users/settings in UI
- ✅ Can call read-only API endpoints

### Example 4: Settings Manager (UI Only)

User who can only manage settings via Orchid:

```php
$settingsManager = Role::create(['name' => 'settings-manager']);
$settingsManager->givePermissionTo([
    'platform.settings.list',
    'platform.settings.edit',
    'platform.settings.delete',
]);

// Must also have admin role to access Orchid
$user->syncRoles(['admin', 'settings-manager']);
```

**Result:**
- ✅ Can access `/admin/settings`
- ❌ Cannot access `/admin/users` (no permission)
- ❌ Cannot access roles management (no permission)

---

## Understanding the Admin Role Requirement

### Orchid Access Requirement

The `OrchidAdminAccess` middleware checks for the `admin` role:

```php
// app/Http/Middleware/OrchidAdminAccess.php
if (!auth()->user()->hasRole('admin')) {
    abort(403, 'Access denied. Admin role required.');
}
```

**This means:**
- To access ANY Orchid page (`/admin/*`), user **must** have the `admin` role
- Individual permissions (`platform.*`) provide granular control **within** Orchid
- You cannot have "UI-only" access without the `admin` role

### Design Decision

This is intentional for security:
1. Admin panel access is gated by role (admin = trusted)
2. Within admin panel, permissions control what you can do
3. API access is independent (checked by API middleware)

### Alternative: Remove Admin Role Check

If you want non-admin users to access Orchid, modify `OrchidAdminAccess.php`:

```php
// Option A: Check for any platform.* permission instead
if (!auth()->user()->hasAnyPermission([
    'platform.users.list',
    'platform.settings.list',
    'platform.systems.roles',
])) {
    abort(403, 'Access denied.');
}

// Option B: Create a new role like 'orchid-user'
if (!auth()->user()->hasAnyRole(['admin', 'orchid-user'])) {
    abort(403, 'Access denied.');
}
```

---

## Permission Assignment Patterns

### Pattern 1: Single Comprehensive Admin Role (Current Default)

```php
// Admin gets everything
$admin->syncPermissions(Permission::all());
```

**Pros:** Simple, no confusion
**Cons:** All-or-nothing access

### Pattern 2: Multiple Specialized Admin Roles

```php
// User admin
$userAdmin = Role::create(['name' => 'user-admin']);
$userAdmin->givePermissionTo([
    'admin.manage-users',
    'platform.users.list',
    'platform.users.edit',
    'platform.users.delete',
]);

// Settings admin
$settingsAdmin = Role::create(['name' => 'settings-admin']);
$settingsAdmin->givePermissionTo([
    'admin.view',
    'platform.settings.list',
    'platform.settings.edit',
    'platform.settings.delete',
]);

// Super admin gets both
$user->syncRoles(['admin', 'user-admin', 'settings-admin']);
```

**Pros:** Granular control, can assign specific responsibilities
**Cons:** More complex to manage

### Pattern 3: Direct Permission Assignment (Not Recommended)

```php
// Bypass roles entirely
$user->givePermissionTo('platform.users.list');
```

**Pros:** Maximum flexibility
**Cons:** Hard to manage at scale, no grouping logic

---

## Adding New Permissions

### When to Add Permissions

**Add to both namespaces** when:
- Feature is accessible via both API and UI
- Example: New "Products" management feature

**Add to API namespace only** when:
- Feature is API-only (no UI)
- Example: Webhook configuration endpoint

**Add to Orchid namespace only** when:
- Feature is UI-only (no API)
- Example: Dashboard analytics screen

### Step-by-Step Process

#### 1. Add to Seeder

**For API permissions:**

Edit `database/seeders/RolesAndPermissionsSeeder.php`:

```php
$permissions = [
    'admin.view' => 'View admin panel',
    'admin.manage-users' => 'Manage users',
    'admin.manage-products' => 'Manage products', // NEW
];
```

**For Orchid permissions:**

Edit `database/seeders/OrchidPermissionsSeeder.php`:

```php
$permissions = [
    // ... existing permissions

    // Products permissions
    'platform.products.list' => 'View products list',      // NEW
    'platform.products.edit' => 'Create and edit products', // NEW
    'platform.products.delete' => 'Delete products',        // NEW
];
```

#### 2. Run Seeders

```bash
docker-compose exec laravel php artisan db:seed --class=RolesAndPermissionsSeeder
docker-compose exec laravel php artisan db:seed --class=OrchidPermissionsSeeder
```

#### 3. Use in Code

**API Controller:**

```php
// app/Http/Controllers/Admin/ProductController.php
public function __construct()
{
    $this->middleware(['auth:sanctum', 'role:admin']);
}
```

**Orchid Screen:**

```php
// app/Orchid/Screens/Product/ProductListScreen.php
public function permission(): ?iterable
{
    return ['platform.products.list'];
}
```

**Orchid Layout:**

```php
// app/Orchid/Layouts/Product/ProductListLayout.php
Link::make(__('Edit'))
    ->icon('bs.pencil')
    ->route('platform.products.edit', $product)
    ->canSee(auth()->user()->hasPermissionTo('platform.products.edit'));
```

---

## Checking Permissions in Code

### In Controllers

```php
// Check single permission
if (auth()->user()->hasPermissionTo('admin.manage-users')) {
    // ...
}

// Check multiple permissions (any)
if (auth()->user()->hasAnyPermission(['admin.view', 'admin.manage-users'])) {
    // ...
}

// Check multiple permissions (all)
if (auth()->user()->hasAllPermissions(['admin.view', 'admin.manage-users'])) {
    // ...
}

// Check role
if (auth()->user()->hasRole('admin')) {
    // ...
}
```

### In Blade Views

```blade
@can('admin.manage-users')
    <button>Edit User</button>
@endcan

@role('admin')
    <a href="/admin">Admin Panel</a>
@endrole
```

### In Orchid Screens

```php
// Screen-level permission
public function permission(): ?iterable
{
    return ['platform.users.list'];
}

// Button-level permission
Button::make('Delete')
    ->method('remove')
    ->canSee(auth()->user()->hasPermissionTo('platform.users.delete'));
```

### In API Routes

```php
// Require admin role
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/users', [AdminUserController::class, 'index']);
});

// Require specific permission
Route::middleware(['auth:sanctum', 'permission:admin.manage-users'])->group(function () {
    Route::put('/admin/users/{user}', [AdminUserController::class, 'update']);
});
```

---

## Database Structure

All permissions are stored in Spatie's tables:

```sql
-- Permissions table
SELECT * FROM permissions;
+----+---------------------------+------------+
| id | name                      | guard_name |
+----+---------------------------+------------+
|  1 | admin.view                | web        |
|  2 | admin.manage-users        | web        |
|  3 | platform.systems.roles    | web        |
|  4 | platform.users.list       | web        |
|  5 | platform.users.edit       | web        |
|  6 | platform.users.delete     | web        |
|  7 | platform.settings.list    | web        |
|  8 | platform.settings.edit    | web        |
|  9 | platform.settings.delete  | web        |
+----+---------------------------+------------+

-- Roles table
SELECT * FROM roles;
+----+-------+------------+
| id | name  | guard_name |
+----+-------+------------+
|  1 | admin | web        |
|  2 | user  | web        |
+----+-------+------------+

-- Role has permissions (pivot)
SELECT * FROM role_has_permissions;
+---------------+--------+
| permission_id | role_id|
+---------------+--------+
|             1 |      1 | -- admin has admin.view
|             2 |      1 | -- admin has admin.manage-users
|             3 |      1 | -- admin has platform.systems.roles
|             4 |      1 | -- admin has platform.users.list
|             5 |      1 | -- admin has platform.users.edit
|             6 |      1 | -- admin has platform.users.delete
|             7 |      1 | -- admin has platform.settings.list
|             8 |      1 | -- admin has platform.settings.edit
|             9 |      1 | -- admin has platform.settings.delete
+---------------+--------+
```

---

## Troubleshooting

### User Can't Access Orchid

**Check:**
1. Does user have `admin` role?
   ```bash
   docker-compose exec laravel php artisan tinker
   >>> $user = User::find(1);
   >>> $user->roles->pluck('name');
   # Should include 'admin'
   ```

2. Assign admin role if missing:
   ```bash
   >>> $user->assignRole('admin');
   ```

### User Can Access Orchid But Can't See Screens

**Check:**
1. Does user have the required permission?
   ```bash
   >>> $user->permissions->pluck('name');
   ```

2. Check screen's `permission()` method:
   ```php
   public function permission(): ?iterable
   {
       return ['platform.users.list']; // User needs this
   }
   ```

3. Grant permission:
   ```bash
   >>> $user->givePermissionTo('platform.users.list');
   ```

### Permission Doesn't Exist

**Create it:**
```bash
docker-compose exec laravel php artisan tinker
>>> \Spatie\Permission\Models\Permission::create(['name' => 'platform.new.permission']);
```

Or run the appropriate seeder.

---

## Best Practices

### 1. Use Roles Over Direct Permissions

❌ **Bad:**
```php
$user->givePermissionTo('platform.users.list');
$user->givePermissionTo('platform.users.edit');
$user->givePermissionTo('platform.users.delete');
```

✅ **Good:**
```php
$userManager = Role::firstOrCreate(['name' => 'user-manager']);
$userManager->syncPermissions([
    'platform.users.list',
    'platform.users.edit',
    'platform.users.delete',
]);
$user->assignRole('user-manager');
```

### 2. Keep Permission Names Consistent

✅ **Good naming:**
```
platform.{resource}.{action}
platform.users.list
platform.users.edit
platform.settings.delete
```

❌ **Bad naming:**
```
users-list
edit_settings
DELETE_PRODUCTS
```

### 3. Document Permission Changes

When adding permissions, update this document with:
- Permission name and description
- Which role(s) should have it by default
- Mapping to API permissions (if applicable)

### 4. Seed Permissions, Not Roles

Store permission definitions in seeders, but create custom roles via UI or tinker.

**Seeder** (repeatable):
```php
Permission::firstOrCreate(['name' => 'platform.users.list']);
```

**UI/Tinker** (one-time):
```php
$customRole = Role::create(['name' => 'custom-manager']);
$customRole->givePermissionTo('platform.users.list');
```

### 5. Test Permission Checks

When adding new screens/endpoints, test:
- Admin user can access (positive test)
- Non-admin user cannot access (negative test)
- User with specific permission can access (granular test)

---

## Migration Guide: Consolidating Permissions

If you decide to consolidate to a single namespace in the future, here's how:

### Option: Use Only `admin.*` Permissions

1. Update all Orchid screens to use API permissions:
   ```php
   // Before
   public function permission(): ?iterable {
       return ['platform.users.list'];
   }

   // After
   public function permission(): ?iterable {
       return ['admin.manage-users'];
   }
   ```

2. Remove Orchid permissions from seeder

3. Update middleware checks in layouts

4. Run migration:
   ```bash
   docker-compose exec laravel php artisan db:seed --class=RolesAndPermissionsSeeder
   ```

---

## Summary

**Current Architecture:**
- ✅ One permission system (Spatie)
- ✅ Two namespaces (`admin.*` for API, `platform.*` for Orchid UI)
- ✅ Admin role gets all permissions automatically
- ✅ Granular control available for custom roles
- ✅ Independent access control for API vs UI

**Key Takeaways:**
1. Admin users don't need to worry about permissions (they have all)
2. Custom roles need permissions from both namespaces if accessing both API and UI
3. Permissions are conceptually mapped but technically independent
4. Adding new features requires considering both namespaces

**When in Doubt:**
- Give admin role → gets everything
- Need custom role → define which namespace(s) they need
- Adding feature → decide if API, UI, or both need permissions

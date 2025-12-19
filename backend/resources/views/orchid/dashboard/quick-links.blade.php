<div class="bg-white rounded shadow-sm p-4 mt-4">
    <h4 class="mb-3">Quick Links</h4>
    <div class="row">
        <div class="col-md-3">
            <a href="{{ route('platform.users.list') }}" class="btn btn-outline-primary w-100 mb-2">
                <i class="bs.people me-2"></i>
                Manage Users
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('platform.settings.list') }}" class="btn btn-outline-info w-100 mb-2">
                <i class="bs.gear me-2"></i>
                Manage Settings
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('platform.systems.roles') }}" class="btn btn-outline-success w-100 mb-2">
                <i class="bs.shield-lock me-2"></i>
                Roles & Permissions
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ config('app.frontend_url') }}" target="_blank" class="btn btn-outline-secondary w-100 mb-2">
                <i class="bs.box-arrow-up-right me-2"></i>
                View Frontend
            </a>
        </div>
    </div>
</div>

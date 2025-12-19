<div class="bg-white rounded shadow-sm p-4 mb-4">
    <h2 class="mb-3">
        <i class="icon-cup text-primary"></i> Welcome, {{ auth()->user()->name }}!
    </h2>
    <p class="text-muted">
        You're managing the admin panel for {{ config('app.name', 'Laravel') }}.
        Use the navigation menu to manage users, settings, and permissions.
    </p>

    @if($metrics['recent_user'])
    <div class="mt-3">
        <small class="text-muted">
            <i class="icon-user"></i>
            Latest registered user: <strong>{{ $metrics['recent_user']->name }}</strong>
            ({{ $metrics['recent_user']->created_at->diffForHumans() }})
        </small>
    </div>
    @endif
</div>

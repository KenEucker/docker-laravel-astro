#!/bin/bash
# Test script to debug settings API

API_URL="${1:-http://localhost:8000}"

echo "=== Testing Public Settings API ==="
echo ""

echo "1. Fetching public settings:"
curl -s "${API_URL}/api/settings/public" | jq '.' || curl -s "${API_URL}/api/settings/public"

echo ""
echo ""
echo "2. Fetching all settings (admin only - will fail without auth):"
curl -s "${API_URL}/api/admin/settings" | jq '.' || curl -s "${API_URL}/api/admin/settings"

echo ""
echo ""
echo "=== Instructions ==="
echo "If APP_NAME is not in the public settings response:"
echo "1. Go to /admin/settings"
echo "2. Click 'Edit' on the APP_NAME row"
echo "3. Make sure 'Public (non-admins can read)' is CHECKED"
echo "4. Click Save"
echo ""
echo "OR run the seeder:"
echo "  docker exec -it <laravel-container> php artisan db:seed --class=SettingsSeeder"

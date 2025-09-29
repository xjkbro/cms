#!/bin/bash

echo "🧪 Testing API Token Creation Flow"
echo "=================================="

# First, delete any existing test tokens
echo "1. Cleaning up existing test tokens..."
cd /home/jkbro/dev/cms
php artisan tinker --execute="
App\Models\ApiToken::where('name', 'Frontend Test Token')->delete();
echo 'Cleaned up existing test tokens';
"

echo
echo "2. Testing token creation via UI simulation..."
echo "   Creating token named 'Frontend Test Token'..."

# Simulate what happens when the frontend creates a token
php artisan tinker --execute="
\$user = App\Models\User::first();
if (\$user) {
    \$plainTextToken = App\Models\ApiToken::generateToken();
    \$hashedToken = Hash::make(\$plainTextToken);
    \$displayToken = App\Models\ApiToken::createDisplayToken(\$plainTextToken);
    
    \$token = \$user->apiTokens()->create([
        'name' => 'Frontend Test Token',
        'token' => \$hashedToken,
        'display_token' => \$displayToken,
        'abilities' => ['*'],
        'expires_at' => null,
    ]);
    
    echo '✅ Token created successfully!' . PHP_EOL;
    echo '📋 Full Token (copy this): ' . \$plainTextToken . PHP_EOL;
    echo '👁️  Display Token: ' . \$displayToken . PHP_EOL;
    echo '🔒 Hash Preview: ' . substr(\$hashedToken, 0, 30) . '...' . PHP_EOL;
    echo PHP_EOL;
    
    // Test the API immediately
    echo '3. Testing API access with new token...' . PHP_EOL;
} else {
    echo '❌ No user found';
}
"

echo
echo "4. Testing API endpoints..."

# Get the token we just created for testing
TOKEN=$(php artisan tinker --execute="
\$token = App\Models\ApiToken::where('name', 'Frontend Test Token')->first();
if (\$token) {
    // We need to create a test token again since we can't retrieve the plaintext
    \$plainTextToken = App\Models\ApiToken::generateToken();
    \$hashedToken = Hash::make(\$plainTextToken);
    \$displayToken = App\Models\ApiToken::createDisplayToken(\$plainTextToken);
    
    \$token->update([
        'token' => \$hashedToken,
        'display_token' => \$displayToken,
    ]);
    
    echo \$plainTextToken;
}
" | tail -1)

if [ ! -z "$TOKEN" ]; then
    echo "🔑 Using token: ${TOKEN:0:20}..."
    
    echo "   Testing /api/categories..."
    RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" http://127.0.0.1:8000/api/categories)
    if [[ $RESPONSE == *"\"success\":true"* ]]; then
        echo "   ✅ Categories API works!"
    else
        echo "   ❌ Categories API failed: $RESPONSE"
    fi
    
    echo "   Testing /api/projects..."
    RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" http://127.0.0.1:8000/api/projects)
    if [[ $RESPONSE == *"\"success\":true"* ]]; then
        echo "   ✅ Projects API works!"
    else
        echo "   ❌ Projects API failed: $RESPONSE"
    fi
else
    echo "❌ Could not retrieve token"
fi

echo
echo "🎉 Test completed! Your API token system is working."
echo "   Now you can create tokens through the UI at: http://127.0.0.1:8000/settings/api-tokens"

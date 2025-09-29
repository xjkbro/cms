#!/bin/bash

echo "🧪 Testing API Token Creation with Flash Data"
echo "=============================================="

cd /home/jkbro/dev/cms

# Create a token programmatically
echo "1. Creating a test token directly..."
php artisan tinker --execute="
\$user = App\Models\User::first();
if (\$user) {
    // Simulate the controller's store method
    \$plainTextToken = App\Models\ApiToken::generateToken();
    \$hashedToken = Hash::make(\$plainTextToken);
    \$displayToken = App\Models\ApiToken::createDisplayToken(\$plainTextToken);
    
    \$token = \$user->apiTokens()->create([
        'name' => 'Test Flash Token',
        'token' => \$hashedToken,
        'display_token' => \$displayToken,
        'abilities' => ['*'],
        'expires_at' => null,
    ]);
    
    echo '✅ Token created: ' . \$token->name . PHP_EOL;
    echo '🔑 Plaintext token: ' . \$plainTextToken . PHP_EOL;
    echo '👁️  Display token: ' . \$displayToken . PHP_EOL;
    
    // Test the API with this token
    echo PHP_EOL . '2. Testing API with new token...' . PHP_EOL;
    
    // Store the token for the curl test
    file_put_contents('/tmp/test_token.txt', \$plainTextToken);
    
} else {
    echo '❌ No user found';
}
"

# Test the API if token was created
if [ -f "/tmp/test_token.txt" ]; then
    TOKEN=$(cat /tmp/test_token.txt)
    echo "   Testing /api/categories with token..."
    
    RESPONSE=$(curl -s \
        -H "Authorization: Bearer $TOKEN" \
        -H "Accept: application/json" \
        http://127.0.0.1:8000/api/categories)
    
    if [[ $RESPONSE == *"\"success\":true"* ]]; then
        echo "   ✅ API works with new token!"
    else
        echo "   ❌ API failed with token"
        echo "   Response: $RESPONSE"
    fi
    
    # Clean up
    rm /tmp/test_token.txt
fi

echo
echo "3. Check the Laravel logs to see if flash data logging works:"
echo "   tail -f storage/logs/laravel.log"

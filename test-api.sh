#!/bin/bash

# API Token Test Script
# This script tests the API token functionality

API_BASE_URL="http://127.0.0.1:8001/api"
TOKEN=""

echo "🧪 API Token Test Script"
echo "========================="

# Function to test API endpoint
test_endpoint() {
    local endpoint=$1
    local method=${2:-GET}
    local description=$3
    
    echo ""
    echo "Testing: $description"
    echo "Endpoint: $method $endpoint"
    
    if [ -z "$TOKEN" ]; then
        echo "❌ No token provided. Please set TOKEN variable."
        return 1
    fi
    
    local response
    if [ "$method" = "GET" ]; then
        response=$(curl -s -w "\n%{http_code}" \
            -H "Authorization: Bearer $TOKEN" \
            -H "Content-Type: application/json" \
            "$API_BASE_URL$endpoint")
    else
        echo "❌ Method $method not implemented in this test script"
        return 1
    fi
    
    local body=$(echo "$response" | head -n -1)
    local status_code=$(echo "$response" | tail -n 1)
    
    if [ "$status_code" -eq 200 ]; then
        echo "✅ Success (HTTP $status_code)"
        echo "$body" | jq . 2>/dev/null || echo "$body"
    else
        echo "❌ Failed (HTTP $status_code)"
        echo "$body"
    fi
}

# Instructions
echo ""
echo "📋 Instructions:"
echo "1. Create an API token in the CMS settings"
echo "2. Set the TOKEN variable: export TOKEN='your-token-here'"
echo "3. Run this script again to test the endpoints"
echo ""

if [ -z "$TOKEN" ]; then
    echo "⚠️  TOKEN not set. Please create a token and export it:"
    echo "   export TOKEN='your-api-token-here'"
    echo "   Then run: ./test-api.sh"
    exit 1
fi

# Test endpoints
test_endpoint "/projects" "GET" "Get all projects"
test_endpoint "/categories" "GET" "Get all categories"
test_endpoint "/posts" "GET" "Get all posts"

echo ""
echo "🏁 Test completed!"
echo ""
echo "💡 To test POST/PUT/DELETE endpoints, you can use:"
echo "   curl -X POST $API_BASE_URL/categories \\"
echo "     -H 'Authorization: Bearer \$TOKEN' \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{\"name\":\"Test Category\"}'"

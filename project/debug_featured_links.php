<?php
// Debug featured links URLs and check for errors
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FEATURED LINKS DEBUG ===\n\n";

$links = DB::table('featured_links')->get();

echo "Total featured links: " . $links->count() . "\n\n";

foreach ($links as $link) {
    echo "Name: " . $link->name . "\n";
    echo "URL: " . $link->link . "\n";
    echo "Photo: " . $link->photo . "\n";
    
    // Check if URL is valid
    if (filter_var($link->link, FILTER_VALIDATE_URL)) {
        echo "Status: Valid URL\n";
    } else {
        echo "Status: INVALID URL - This might be causing errors!\n";
    }
    
    // Check if it's a relative URL that should be converted
    if (strpos($link->link, 'http') !== 0 && strpos($link->link, '/') === 0) {
        echo "Note: This is a relative URL - might need to be converted to full URL\n";
    }
    
    echo "---\n";
}

echo "\n=== CHECKING ROUTES ===\n";

// Check if the URLs match any existing routes
$routes = [
    'https://agreeme.in/demo/ecomsingle/category/electric/television/lcd-tv',
    'https://agreeme.in/demo/ecomsingle/category/electric/refrigerator',
    'https://agreeme.in/demo/ecomsingle/category/camera-and-photo/dslr',
    'https://agreeme.in/demo/ecomsingle/category/smart-phone-and-table',
    'https://agreeme.in/demo/ecomsingle/category/books-and-office',
    'https://agreeme.in/demo/ecomsingle/category/health-and-beauty',
    'https://agreeme.in/demo/ecomsingle/category/toys-and-hobbies',
    'https://agreeme.in/demo/ecomsingle/category/Home-decoration-and-Appliance',
    'https://agreeme.in/demo/ecomsingle/category/sport-and-Outdoor',
    'https://agreeme.in/demo/ecomsingle/category/jewelry-and-watches'
];

echo "These URLs point to an external demo site (agreeme.in) which might not be accessible.\n";
echo "We should update them to point to your local categories.\n\n";

// Check what categories exist in the local database
echo "=== LOCAL CATEGORIES ===\n";
$categories = DB::table('categories')->where('status', 1)->get();

echo "Available categories: " . $categories->count() . "\n";
foreach ($categories as $cat) {
    echo "- " . $cat->name . " (slug: " . $cat->slug . ")\n";
}
?>
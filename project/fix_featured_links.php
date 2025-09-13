<?php
// Fix featured links to point to local categories
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FIXING FEATURED LINKS ===\n\n";

// Get the base URL for your local site
$baseUrl = 'http://localhost/Ecommerce';

// Mapping of featured link names to local category slugs
$linkMappings = [
    'LCD Televisions' => 'electric',
    'Refrigerator' => 'electric', 
    'DSLR Camera' => 'camera-and-photo',
    'Mobile Phones' => 'smart-phone-and-table',
    'Books & Office' => 'books-and-office',
    'Health & Beauty' => 'health-and-beauty',
    'Toys' => 'toys-and-hobbies',
    'Home decoration' => 'Home-decoration-and-Appliance',
    'sport & Outdoor' => 'sport-and-Outdoor',
    'jewelry & watches' => 'jewelry-and-watches'
];

// Update each featured link
foreach ($linkMappings as $name => $slug) {
    $newUrl = $baseUrl . '/category/' . $slug;
    
    $updated = DB::table('featured_links')
        ->where('name', $name)
        ->update(['link' => $newUrl]);
    
    if ($updated) {
        echo "✓ Updated '$name' -> $newUrl\n";
    } else {
        echo "✗ Failed to update '$name'\n";
    }
}

echo "\n=== VERIFICATION ===\n";

// Verify the updates
$links = DB::table('featured_links')->get();
foreach ($links as $link) {
    echo "Name: " . $link->name . "\n";
    echo "URL: " . $link->link . "\n";
    echo "---\n";
}

echo "\nAll featured links have been updated to point to local categories!\n";
echo "The links should now work properly when clicked.\n";
?>
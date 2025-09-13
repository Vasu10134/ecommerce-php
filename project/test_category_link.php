<?php
// Test a category link to see what happens
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Category;

echo "=== TESTING CATEGORY LINKS ===\n\n";

// Test the electric category
$category = Category::where('slug', 'electric')->first();

if ($category) {
    echo "Category found: " . $category->name . "\n";
    echo "Slug: " . $category->slug . "\n";
    echo "Status: " . $category->status . "\n";
    echo "URL should be: http://localhost/Ecommerce/category/electric\n";
    
    // Check if there are products in this category
    $products = \App\Models\Product::where('category_id', $category->id)->where('status', 1)->count();
    echo "Products in category: " . $products . "\n";
} else {
    echo "Category 'electric' not found!\n";
}

echo "\n=== ALL CATEGORIES ===\n";
$categories = Category::where('status', 1)->get();
foreach ($categories as $cat) {
    echo "- " . $cat->name . " (slug: " . $cat->slug . ")\n";
}
?>
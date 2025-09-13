<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeaturedLink;

class FeaturedLinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $featuredLinks = [
            [
                'name' => 'Amazon',
                'link' => 'https://amazon.com',
                'photo' => 'amazon.png'
            ],
            [
                'name' => 'eBay',
                'link' => 'https://ebay.com',
                'photo' => 'ebay.png'
            ],
            [
                'name' => 'Flipkart',
                'link' => 'https://flipkart.com',
                'photo' => 'flipkart.png'
            ],
            [
                'name' => 'Walmart',
                'link' => 'https://walmart.com',
                'photo' => 'walmart.png'
            ],
            [
                'name' => 'Target',
                'link' => 'https://target.com',
                'photo' => 'target.png'
            ],
            [
                'name' => 'Best Buy',
                'link' => 'https://bestbuy.com',
                'photo' => 'bestbuy.png'
            ]
        ];

        foreach ($featuredLinks as $link) {
            FeaturedLink::create($link);
        }
    }
}

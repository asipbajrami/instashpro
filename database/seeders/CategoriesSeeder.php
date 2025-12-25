<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Category::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $categories = [
            ['id' => 1, 'name' => 'Tech & Electronics', 'slug' => 'tech-electronics', 'parent_id' => null],

            // Computers & Accessories
            ['id' => 2, 'name' => 'Computers & Accessories', 'slug' => 'computers-accessories', 'parent_id' => 1],
            ['id' => 3, 'name' => 'Laptops & MacBooks', 'slug' => 'laptops-macbooks', 'parent_id' => 2],
            ['id' => 4, 'name' => 'Gaming Laptops', 'slug' => 'gaming-laptops', 'parent_id' => 3],
            ['id' => 5, 'name' => 'Professional Laptops', 'slug' => 'professional-laptops', 'parent_id' => 3],
            ['id' => 6, 'name' => 'MacBooks', 'slug' => 'macbooks', 'parent_id' => 3],

            // Desktop PCs
            ['id' => 8, 'name' => 'Desktop PCs', 'slug' => 'desktop-pcs', 'parent_id' => 2],
            ['id' => 9, 'name' => 'Gaming PCs', 'slug' => 'gaming-pcs', 'parent_id' => 8],
            ['id' => 10, 'name' => 'Professional PCs', 'slug' => 'professional-pcs', 'parent_id' => 8],
            ['id' => 11, 'name' => 'All-in-Ones', 'slug' => 'all-in-ones', 'parent_id' => 8],

            // Components
            ['id' => 12, 'name' => 'Components', 'slug' => 'components', 'parent_id' => 2],
            ['id' => 13, 'name' => 'Graphics Cards', 'slug' => 'graphics-cards', 'parent_id' => 12],
            ['id' => 14, 'name' => 'Processors', 'slug' => 'processors', 'parent_id' => 12],
            ['id' => 15, 'name' => 'Memory', 'slug' => 'memory', 'parent_id' => 12],
            ['id' => 16, 'name' => 'Storage', 'slug' => 'storage', 'parent_id' => 12],
            ['id' => 17, 'name' => 'Power Supplies', 'slug' => 'power-supplies', 'parent_id' => 12],
            ['id' => 18, 'name' => 'Cases', 'slug' => 'cases', 'parent_id' => 12],

            // Peripherals
            ['id' => 19, 'name' => 'Computer Peripherals', 'slug' => 'computer-peripherals', 'parent_id' => 2],
            ['id' => 20, 'name' => 'Monitors', 'slug' => 'monitors', 'parent_id' => 19],
            ['id' => 21, 'name' => 'Keyboards', 'slug' => 'keyboards', 'parent_id' => 19],
            ['id' => 22, 'name' => 'Mice', 'slug' => 'mice', 'parent_id' => 19],
            ['id' => 23, 'name' => 'Headsets', 'slug' => 'headsets', 'parent_id' => 19],

            // Mobile & Tablets
            ['id' => 24, 'name' => 'Mobile & Tablets', 'slug' => 'mobile-tablets', 'parent_id' => 1],
            ['id' => 25, 'name' => 'Mobile Phones', 'slug' => 'phone', 'parent_id' => 24],
            ['id' => 28, 'name' => 'Tablets', 'slug' => 'tablets', 'parent_id' => 24],
            ['id' => 31, 'name' => 'Accessories', 'slug' => 'mobile-accessories', 'parent_id' => 24],
            ['id' => 32, 'name' => 'Cases', 'slug' => 'mobile-cases', 'parent_id' => 31],
            ['id' => 33, 'name' => 'Chargers', 'slug' => 'mobile-chargers', 'parent_id' => 31],
            ['id' => 34, 'name' => 'Screen Protection', 'slug' => 'screen-protection', 'parent_id' => 31],
            ['id' => 35, 'name' => 'Wearables', 'slug' => 'wearables', 'parent_id' => 24],
            ['id' => 36, 'name' => 'Smartwatches', 'slug' => 'smartwatches', 'parent_id' => 35],
            ['id' => 37, 'name' => 'Fitness Trackers', 'slug' => 'fitness-trackers', 'parent_id' => 35],

            // Gaming
            ['id' => 38, 'name' => 'Gaming', 'slug' => 'gaming', 'parent_id' => 1],
            ['id' => 39, 'name' => 'Gaming Gear', 'slug' => 'gaming-gear', 'parent_id' => 38],
            ['id' => 40, 'name' => 'Gaming Chairs', 'slug' => 'gaming-chairs', 'parent_id' => 39],
            ['id' => 41, 'name' => 'Gaming Desks', 'slug' => 'gaming-desks', 'parent_id' => 39],
            ['id' => 42, 'name' => 'Gaming Peripherals', 'slug' => 'gaming-peripherals', 'parent_id' => 39],
            ['id' => 43, 'name' => 'Consoles', 'slug' => 'consoles', 'parent_id' => 38],
            ['id' => 44, 'name' => 'PlayStation', 'slug' => 'playstation', 'parent_id' => 43],
            ['id' => 45, 'name' => 'Xbox', 'slug' => 'xbox', 'parent_id' => 43],
            ['id' => 46, 'name' => 'Nintendo', 'slug' => 'nintendo', 'parent_id' => 43],
            ['id' => 47, 'name' => 'Games & Digital', 'slug' => 'games-digital', 'parent_id' => 38],

            // Audio & Visual
            ['id' => 48, 'name' => 'Audio & Visual', 'slug' => 'audio-visual', 'parent_id' => 1],
            ['id' => 49, 'name' => 'Headphones', 'slug' => 'headphones', 'parent_id' => 48],
            ['id' => 50, 'name' => 'Wireless Earbuds', 'slug' => 'wireless-earbuds', 'parent_id' => 49],
            ['id' => 51, 'name' => 'Over-ear', 'slug' => 'over-ear', 'parent_id' => 49],
            ['id' => 52, 'name' => 'Gaming Headsets', 'slug' => 'gaming-headsets', 'parent_id' => 49],
            ['id' => 53, 'name' => 'TVs & Home Theater', 'slug' => 'tvs-home-theater', 'parent_id' => 48],
            ['id' => 54, 'name' => 'TVs', 'slug' => 'tvs', 'parent_id' => 53],
            ['id' => 55, 'name' => 'Soundbars', 'slug' => 'soundbars', 'parent_id' => 53],
            ['id' => 56, 'name' => 'Media Players', 'slug' => 'media-players', 'parent_id' => 53],
            ['id' => 57, 'name' => 'Professional Audio', 'slug' => 'professional-audio', 'parent_id' => 48],
            ['id' => 58, 'name' => 'Microphones', 'slug' => 'microphones', 'parent_id' => 57],
            ['id' => 59, 'name' => 'Studio Equipment', 'slug' => 'studio-equipment', 'parent_id' => 57],

            // Smart Home
            ['id' => 60, 'name' => 'Smart Home', 'slug' => 'smart-home', 'parent_id' => 1],
            ['id' => 61, 'name' => 'Security', 'slug' => 'security', 'parent_id' => 60],
            ['id' => 62, 'name' => 'Cameras', 'slug' => 'cameras', 'parent_id' => 61],
            ['id' => 63, 'name' => 'Smart Locks', 'slug' => 'smart-locks', 'parent_id' => 61],
            ['id' => 64, 'name' => 'Alarms', 'slug' => 'alarms', 'parent_id' => 61],
            ['id' => 65, 'name' => 'Smart Devices', 'slug' => 'smart-devices', 'parent_id' => 60],
            ['id' => 66, 'name' => 'Smart Lighting', 'slug' => 'smart-lighting', 'parent_id' => 65],
            ['id' => 67, 'name' => 'Smart Speakers', 'slug' => 'smart-speakers', 'parent_id' => 65],
            ['id' => 68, 'name' => 'Thermostats', 'slug' => 'thermostats', 'parent_id' => 65],
            ['id' => 69, 'name' => 'Smart Appliances', 'slug' => 'smart-appliances', 'parent_id' => 60],
            ['id' => 70, 'name' => 'Robot Vacuums', 'slug' => 'robot-vacuums', 'parent_id' => 69],
            ['id' => 71, 'name' => 'Smart Kitchen', 'slug' => 'smart-kitchen', 'parent_id' => 69],
            ['id' => 72, 'name' => 'Climate Control', 'slug' => 'climate-control', 'parent_id' => 69],

            // Cars & Vehicles (Root Category)
            ['id' => 100, 'name' => 'Cars & Vehicles', 'slug' => 'cars-vehicles', 'parent_id' => null],

            // Cars
            ['id' => 101, 'name' => 'Cars', 'slug' => 'cars', 'parent_id' => 100],
            ['id' => 102, 'name' => 'Sedans', 'slug' => 'sedans', 'parent_id' => 101],
            ['id' => 103, 'name' => 'SUVs', 'slug' => 'suvs', 'parent_id' => 101],
            ['id' => 104, 'name' => 'Hatchbacks', 'slug' => 'hatchbacks', 'parent_id' => 101],
            ['id' => 105, 'name' => 'Coupes', 'slug' => 'coupes', 'parent_id' => 101],
            ['id' => 106, 'name' => 'Convertibles', 'slug' => 'convertibles', 'parent_id' => 101],
            ['id' => 107, 'name' => 'Wagons', 'slug' => 'wagons', 'parent_id' => 101],
            ['id' => 108, 'name' => 'Vans & Minivans', 'slug' => 'vans-minivans', 'parent_id' => 101],
            ['id' => 109, 'name' => 'Trucks & Pickups', 'slug' => 'trucks-pickups', 'parent_id' => 101],

            // Motorcycles
            ['id' => 110, 'name' => 'Motorcycles', 'slug' => 'motorcycles', 'parent_id' => 100],
            ['id' => 111, 'name' => 'Sport Bikes', 'slug' => 'sport-bikes', 'parent_id' => 110],
            ['id' => 112, 'name' => 'Cruisers', 'slug' => 'cruisers', 'parent_id' => 110],
            ['id' => 113, 'name' => 'Touring', 'slug' => 'touring-bikes', 'parent_id' => 110],
            ['id' => 114, 'name' => 'Off-Road', 'slug' => 'off-road-bikes', 'parent_id' => 110],
            ['id' => 115, 'name' => 'Scooters', 'slug' => 'scooters', 'parent_id' => 110],

            // Commercial Vehicles
            ['id' => 120, 'name' => 'Commercial Vehicles', 'slug' => 'commercial-vehicles', 'parent_id' => 100],
            ['id' => 121, 'name' => 'Buses', 'slug' => 'buses', 'parent_id' => 120],
            ['id' => 122, 'name' => 'Trucks', 'slug' => 'trucks', 'parent_id' => 120],
            ['id' => 123, 'name' => 'Trailers', 'slug' => 'trailers', 'parent_id' => 120],

            // Vehicle Parts & Accessories
            ['id' => 130, 'name' => 'Parts & Accessories', 'slug' => 'vehicle-parts', 'parent_id' => 100],
            ['id' => 131, 'name' => 'Tires & Wheels', 'slug' => 'tires-wheels', 'parent_id' => 130],
            ['id' => 132, 'name' => 'Engine Parts', 'slug' => 'engine-parts', 'parent_id' => 130],
            ['id' => 133, 'name' => 'Interior Accessories', 'slug' => 'interior-accessories', 'parent_id' => 130],
            ['id' => 134, 'name' => 'Exterior Accessories', 'slug' => 'exterior-accessories', 'parent_id' => 130],
            ['id' => 135, 'name' => 'Audio & Electronics', 'slug' => 'car-audio-electronics', 'parent_id' => 130],
        ];

        foreach ($categories as $category) {
            Category::create(array_merge($category, ['is_temp' => false]));
        }
    }
}

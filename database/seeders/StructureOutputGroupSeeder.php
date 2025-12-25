<?php

namespace Database\Seeders;

use App\Models\StructureOutputGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StructureOutputGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * These groups are used for Typesense CLIP-based hybrid search
     * to classify Instagram posts by product category
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        StructureOutputGroup::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $groups = [
            [
                'used_for' => 'tech',
                'description' => 'Electronics and technology products for sale. Includes smartphones like iPhone, Samsung Galaxy, Pixel. Laptops and computers from Apple MacBook, Dell, HP, Lenovo, Asus. Computer monitors and displays. Tablets like iPad. Gaming consoles PlayStation, Xbox, Nintendo Switch. Smartwatches Apple Watch, Samsung Galaxy Watch. Headphones and earbuds AirPods, Sony, Bose. Cameras DSLR, mirrorless, GoPro. Computer components GPUs, CPUs, RAM, SSDs. Tech accessories chargers, cases, cables. Electronics store inventory.',
            ],
            [
                'used_for' => 'car',
                'description' => 'Vehicles and automotive products for sale. Cars sedans, SUVs, coupes, hatchbacks, wagons, convertibles. Brands Toyota, Honda, BMW, Mercedes-Benz, Audi, Ford, Volkswagen, Hyundai, Kia. Motorcycles sport bikes, cruisers, scooters Harley-Davidson, Yamaha, Kawasaki, Honda. Trucks pickup trucks, vans, commercial vehicles. Electric vehicles Tesla, EVs, hybrid cars. Car dealership inventory. Used cars for sale. Vehicle showroom. Automotive marketplace.',
            ],
        ];

        foreach ($groups as $group) {
            StructureOutputGroup::create($group);
        }
    }
}

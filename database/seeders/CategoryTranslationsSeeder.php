<?php

namespace Database\Seeders;

use App\Models\CategoryTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        CategoryTranslation::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $translations = [
            // Tech & Electronics
            ['category_id' => 1, 'locale' => 'en', 'name' => 'Tech & Electronics'],
            ['category_id' => 1, 'locale' => 'sq', 'name' => 'Teknologji & Elektronikë'],

            // Computers & Accessories
            ['category_id' => 2, 'locale' => 'en', 'name' => 'Computers & Accessories'],
            ['category_id' => 2, 'locale' => 'sq', 'name' => 'Kompjuterë & Aksesorë'],
            ['category_id' => 3, 'locale' => 'en', 'name' => 'Laptops & MacBooks'],
            ['category_id' => 3, 'locale' => 'sq', 'name' => 'Laptopë & MacBook'],
            ['category_id' => 4, 'locale' => 'en', 'name' => 'Gaming Laptops'],
            ['category_id' => 4, 'locale' => 'sq', 'name' => 'Laptopë për Lojëra'],
            ['category_id' => 5, 'locale' => 'en', 'name' => 'Professional Laptops'],
            ['category_id' => 5, 'locale' => 'sq', 'name' => 'Laptopë Profesionalë'],
            ['category_id' => 6, 'locale' => 'en', 'name' => 'MacBooks'],
            ['category_id' => 6, 'locale' => 'sq', 'name' => 'MacBook'],

            // Desktop PCs
            ['category_id' => 8, 'locale' => 'en', 'name' => 'Desktop PCs'],
            ['category_id' => 8, 'locale' => 'sq', 'name' => 'Kompjuterë Desktop'],
            ['category_id' => 9, 'locale' => 'en', 'name' => 'Gaming PCs'],
            ['category_id' => 9, 'locale' => 'sq', 'name' => 'PC për Lojëra'],
            ['category_id' => 10, 'locale' => 'en', 'name' => 'Professional PCs'],
            ['category_id' => 10, 'locale' => 'sq', 'name' => 'PC Profesionalë'],
            ['category_id' => 11, 'locale' => 'en', 'name' => 'All-in-Ones'],
            ['category_id' => 11, 'locale' => 'sq', 'name' => 'All-in-One'],

            // Components
            ['category_id' => 12, 'locale' => 'en', 'name' => 'Components'],
            ['category_id' => 12, 'locale' => 'sq', 'name' => 'Komponentë'],
            ['category_id' => 13, 'locale' => 'en', 'name' => 'Graphics Cards'],
            ['category_id' => 13, 'locale' => 'sq', 'name' => 'Karta Grafike'],
            ['category_id' => 14, 'locale' => 'en', 'name' => 'Processors'],
            ['category_id' => 14, 'locale' => 'sq', 'name' => 'Procesorë'],
            ['category_id' => 15, 'locale' => 'en', 'name' => 'Memory'],
            ['category_id' => 15, 'locale' => 'sq', 'name' => 'Memorie RAM'],
            ['category_id' => 16, 'locale' => 'en', 'name' => 'Storage'],
            ['category_id' => 16, 'locale' => 'sq', 'name' => 'Ruajtje / Storage'],
            ['category_id' => 17, 'locale' => 'en', 'name' => 'Power Supplies'],
            ['category_id' => 17, 'locale' => 'sq', 'name' => 'Furnizues Energjie'],
            ['category_id' => 18, 'locale' => 'en', 'name' => 'Cases'],
            ['category_id' => 18, 'locale' => 'sq', 'name' => 'Kasa'],

            // Peripherals
            ['category_id' => 19, 'locale' => 'en', 'name' => 'Computer Peripherals'],
            ['category_id' => 19, 'locale' => 'sq', 'name' => 'Pajisje Periferike'],
            ['category_id' => 20, 'locale' => 'en', 'name' => 'Monitors'],
            ['category_id' => 20, 'locale' => 'sq', 'name' => 'Monitorë'],
            ['category_id' => 21, 'locale' => 'en', 'name' => 'Keyboards'],
            ['category_id' => 21, 'locale' => 'sq', 'name' => 'Tastiera'],
            ['category_id' => 22, 'locale' => 'en', 'name' => 'Mice'],
            ['category_id' => 22, 'locale' => 'sq', 'name' => 'Minj'],
            ['category_id' => 23, 'locale' => 'en', 'name' => 'Headsets'],
            ['category_id' => 23, 'locale' => 'sq', 'name' => 'Kufje'],

            // Mobile & Tablets
            ['category_id' => 24, 'locale' => 'en', 'name' => 'Mobile & Tablets'],
            ['category_id' => 24, 'locale' => 'sq', 'name' => 'Celularë & Tabletë'],
            ['category_id' => 25, 'locale' => 'en', 'name' => 'Mobile Phones'],
            ['category_id' => 25, 'locale' => 'sq', 'name' => 'Telefona Celularë'],
            ['category_id' => 28, 'locale' => 'en', 'name' => 'Tablets'],
            ['category_id' => 28, 'locale' => 'sq', 'name' => 'Tabletë'],
            ['category_id' => 31, 'locale' => 'en', 'name' => 'Accessories'],
            ['category_id' => 31, 'locale' => 'sq', 'name' => 'Aksesorë'],
            ['category_id' => 32, 'locale' => 'en', 'name' => 'Cases'],
            ['category_id' => 32, 'locale' => 'sq', 'name' => 'Mbulesa'],
            ['category_id' => 33, 'locale' => 'en', 'name' => 'Chargers'],
            ['category_id' => 33, 'locale' => 'sq', 'name' => 'Karikues'],
            ['category_id' => 34, 'locale' => 'en', 'name' => 'Screen Protection'],
            ['category_id' => 34, 'locale' => 'sq', 'name' => 'Mbrojtës Ekrani'],
            ['category_id' => 35, 'locale' => 'en', 'name' => 'Wearables'],
            ['category_id' => 35, 'locale' => 'sq', 'name' => 'Pajisje të Veshura'],
            ['category_id' => 36, 'locale' => 'en', 'name' => 'Smartwatches'],
            ['category_id' => 36, 'locale' => 'sq', 'name' => 'Orë Inteligjente'],
            ['category_id' => 37, 'locale' => 'en', 'name' => 'Fitness Trackers'],
            ['category_id' => 37, 'locale' => 'sq', 'name' => 'Gjurmues Fitnesi'],

            // Gaming
            ['category_id' => 38, 'locale' => 'en', 'name' => 'Gaming'],
            ['category_id' => 38, 'locale' => 'sq', 'name' => 'Lojëra'],
            ['category_id' => 39, 'locale' => 'en', 'name' => 'Gaming Gear'],
            ['category_id' => 39, 'locale' => 'sq', 'name' => 'Pajisje për Lojëra'],
            ['category_id' => 40, 'locale' => 'en', 'name' => 'Gaming Chairs'],
            ['category_id' => 40, 'locale' => 'sq', 'name' => 'Karrige Gaming'],
            ['category_id' => 41, 'locale' => 'en', 'name' => 'Gaming Desks'],
            ['category_id' => 41, 'locale' => 'sq', 'name' => 'Tavolina Gaming'],
            ['category_id' => 42, 'locale' => 'en', 'name' => 'Gaming Peripherals'],
            ['category_id' => 42, 'locale' => 'sq', 'name' => 'Periferikë Gaming'],
            ['category_id' => 43, 'locale' => 'en', 'name' => 'Consoles'],
            ['category_id' => 43, 'locale' => 'sq', 'name' => 'Konsola'],
            ['category_id' => 44, 'locale' => 'en', 'name' => 'PlayStation'],
            ['category_id' => 44, 'locale' => 'sq', 'name' => 'PlayStation'],
            ['category_id' => 45, 'locale' => 'en', 'name' => 'Xbox'],
            ['category_id' => 45, 'locale' => 'sq', 'name' => 'Xbox'],
            ['category_id' => 46, 'locale' => 'en', 'name' => 'Nintendo'],
            ['category_id' => 46, 'locale' => 'sq', 'name' => 'Nintendo'],
            ['category_id' => 47, 'locale' => 'en', 'name' => 'Games & Digital'],
            ['category_id' => 47, 'locale' => 'sq', 'name' => 'Lojëra & Dixhitale'],

            // Audio & Visual
            ['category_id' => 48, 'locale' => 'en', 'name' => 'Audio & Visual'],
            ['category_id' => 48, 'locale' => 'sq', 'name' => 'Audio & Vizuale'],
            ['category_id' => 49, 'locale' => 'en', 'name' => 'Headphones'],
            ['category_id' => 49, 'locale' => 'sq', 'name' => 'Kufje'],
            ['category_id' => 50, 'locale' => 'en', 'name' => 'Wireless Earbuds'],
            ['category_id' => 50, 'locale' => 'sq', 'name' => 'Kufje Wireless'],
            ['category_id' => 51, 'locale' => 'en', 'name' => 'Over-ear'],
            ['category_id' => 51, 'locale' => 'sq', 'name' => 'Mbi Vesh'],
            ['category_id' => 52, 'locale' => 'en', 'name' => 'Gaming Headsets'],
            ['category_id' => 52, 'locale' => 'sq', 'name' => 'Kufje Gaming'],
            ['category_id' => 53, 'locale' => 'en', 'name' => 'TVs & Home Theater'],
            ['category_id' => 53, 'locale' => 'sq', 'name' => 'TV & Kino Shtëpiake'],
            ['category_id' => 54, 'locale' => 'en', 'name' => 'TVs'],
            ['category_id' => 54, 'locale' => 'sq', 'name' => 'Televizorë'],
            ['category_id' => 55, 'locale' => 'en', 'name' => 'Soundbars'],
            ['category_id' => 55, 'locale' => 'sq', 'name' => 'Soundbar'],
            ['category_id' => 56, 'locale' => 'en', 'name' => 'Media Players'],
            ['category_id' => 56, 'locale' => 'sq', 'name' => 'Media Player'],
            ['category_id' => 57, 'locale' => 'en', 'name' => 'Professional Audio'],
            ['category_id' => 57, 'locale' => 'sq', 'name' => 'Audio Profesionale'],
            ['category_id' => 58, 'locale' => 'en', 'name' => 'Microphones'],
            ['category_id' => 58, 'locale' => 'sq', 'name' => 'Mikrofona'],
            ['category_id' => 59, 'locale' => 'en', 'name' => 'Studio Equipment'],
            ['category_id' => 59, 'locale' => 'sq', 'name' => 'Pajisje Studio'],

            // Smart Home
            ['category_id' => 60, 'locale' => 'en', 'name' => 'Smart Home'],
            ['category_id' => 60, 'locale' => 'sq', 'name' => 'Shtëpi Inteligjente'],
            ['category_id' => 61, 'locale' => 'en', 'name' => 'Security'],
            ['category_id' => 61, 'locale' => 'sq', 'name' => 'Siguri'],
            ['category_id' => 62, 'locale' => 'en', 'name' => 'Cameras'],
            ['category_id' => 62, 'locale' => 'sq', 'name' => 'Kamera'],
            ['category_id' => 63, 'locale' => 'en', 'name' => 'Smart Locks'],
            ['category_id' => 63, 'locale' => 'sq', 'name' => 'Brava Inteligjente'],
            ['category_id' => 64, 'locale' => 'en', 'name' => 'Alarms'],
            ['category_id' => 64, 'locale' => 'sq', 'name' => 'Alarme'],
            ['category_id' => 65, 'locale' => 'en', 'name' => 'Smart Devices'],
            ['category_id' => 65, 'locale' => 'sq', 'name' => 'Pajisje Inteligjente'],
            ['category_id' => 66, 'locale' => 'en', 'name' => 'Smart Lighting'],
            ['category_id' => 66, 'locale' => 'sq', 'name' => 'Ndriçim Inteligjent'],
            ['category_id' => 67, 'locale' => 'en', 'name' => 'Smart Speakers'],
            ['category_id' => 67, 'locale' => 'sq', 'name' => 'Altoparlantë Inteligjentë'],
            ['category_id' => 68, 'locale' => 'en', 'name' => 'Thermostats'],
            ['category_id' => 68, 'locale' => 'sq', 'name' => 'Termostatë'],
            ['category_id' => 69, 'locale' => 'en', 'name' => 'Smart Appliances'],
            ['category_id' => 69, 'locale' => 'sq', 'name' => 'Pajisje Shtëpiake Inteligjente'],
            ['category_id' => 70, 'locale' => 'en', 'name' => 'Robot Vacuums'],
            ['category_id' => 70, 'locale' => 'sq', 'name' => 'Fshesë Robot'],
            ['category_id' => 71, 'locale' => 'en', 'name' => 'Smart Kitchen'],
            ['category_id' => 71, 'locale' => 'sq', 'name' => 'Kuzhinë Inteligjente'],
            ['category_id' => 72, 'locale' => 'en', 'name' => 'Climate Control'],
            ['category_id' => 72, 'locale' => 'sq', 'name' => 'Kontroll i Klimës'],

            // Cars & Vehicles (Root Category)
            ['category_id' => 100, 'locale' => 'en', 'name' => 'Cars & Vehicles'],
            ['category_id' => 100, 'locale' => 'sq', 'name' => 'Makina & Automjete'],

            // Cars
            ['category_id' => 101, 'locale' => 'en', 'name' => 'Cars'],
            ['category_id' => 101, 'locale' => 'sq', 'name' => 'Makina'],
            ['category_id' => 102, 'locale' => 'en', 'name' => 'Sedans'],
            ['category_id' => 102, 'locale' => 'sq', 'name' => 'Sedan'],
            ['category_id' => 103, 'locale' => 'en', 'name' => 'SUVs'],
            ['category_id' => 103, 'locale' => 'sq', 'name' => 'SUV'],
            ['category_id' => 104, 'locale' => 'en', 'name' => 'Hatchbacks'],
            ['category_id' => 104, 'locale' => 'sq', 'name' => 'Hatchback'],
            ['category_id' => 105, 'locale' => 'en', 'name' => 'Coupes'],
            ['category_id' => 105, 'locale' => 'sq', 'name' => 'Coupe'],
            ['category_id' => 106, 'locale' => 'en', 'name' => 'Convertibles'],
            ['category_id' => 106, 'locale' => 'sq', 'name' => 'Kabriolet'],
            ['category_id' => 107, 'locale' => 'en', 'name' => 'Wagons'],
            ['category_id' => 107, 'locale' => 'sq', 'name' => 'Karroçeri'],
            ['category_id' => 108, 'locale' => 'en', 'name' => 'Vans & Minivans'],
            ['category_id' => 108, 'locale' => 'sq', 'name' => 'Furgon & Minivan'],
            ['category_id' => 109, 'locale' => 'en', 'name' => 'Trucks & Pickups'],
            ['category_id' => 109, 'locale' => 'sq', 'name' => 'Kamionçina & Pickup'],

            // Motorcycles
            ['category_id' => 110, 'locale' => 'en', 'name' => 'Motorcycles'],
            ['category_id' => 110, 'locale' => 'sq', 'name' => 'Motorë'],
            ['category_id' => 111, 'locale' => 'en', 'name' => 'Sport Bikes'],
            ['category_id' => 111, 'locale' => 'sq', 'name' => 'Motorë Sportive'],
            ['category_id' => 112, 'locale' => 'en', 'name' => 'Cruisers'],
            ['category_id' => 112, 'locale' => 'sq', 'name' => 'Cruiser'],
            ['category_id' => 113, 'locale' => 'en', 'name' => 'Touring'],
            ['category_id' => 113, 'locale' => 'sq', 'name' => 'Turing'],
            ['category_id' => 114, 'locale' => 'en', 'name' => 'Off-Road'],
            ['category_id' => 114, 'locale' => 'sq', 'name' => 'Off-Road'],
            ['category_id' => 115, 'locale' => 'en', 'name' => 'Scooters'],
            ['category_id' => 115, 'locale' => 'sq', 'name' => 'Skuterë'],

            // Commercial Vehicles
            ['category_id' => 120, 'locale' => 'en', 'name' => 'Commercial Vehicles'],
            ['category_id' => 120, 'locale' => 'sq', 'name' => 'Automjete Komerciale'],
            ['category_id' => 121, 'locale' => 'en', 'name' => 'Buses'],
            ['category_id' => 121, 'locale' => 'sq', 'name' => 'Autobusë'],
            ['category_id' => 122, 'locale' => 'en', 'name' => 'Trucks'],
            ['category_id' => 122, 'locale' => 'sq', 'name' => 'Kamionë'],
            ['category_id' => 123, 'locale' => 'en', 'name' => 'Trailers'],
            ['category_id' => 123, 'locale' => 'sq', 'name' => 'Rimorkio'],

            // Vehicle Parts & Accessories
            ['category_id' => 130, 'locale' => 'en', 'name' => 'Parts & Accessories'],
            ['category_id' => 130, 'locale' => 'sq', 'name' => 'Pjesë & Aksesorë'],
            ['category_id' => 131, 'locale' => 'en', 'name' => 'Tires & Wheels'],
            ['category_id' => 131, 'locale' => 'sq', 'name' => 'Goma & Rrota'],
            ['category_id' => 132, 'locale' => 'en', 'name' => 'Engine Parts'],
            ['category_id' => 132, 'locale' => 'sq', 'name' => 'Pjesë Motori'],
            ['category_id' => 133, 'locale' => 'en', 'name' => 'Interior Accessories'],
            ['category_id' => 133, 'locale' => 'sq', 'name' => 'Aksesorë të Brendshëm'],
            ['category_id' => 134, 'locale' => 'en', 'name' => 'Exterior Accessories'],
            ['category_id' => 134, 'locale' => 'sq', 'name' => 'Aksesorë të Jashtëm'],
            ['category_id' => 135, 'locale' => 'en', 'name' => 'Audio & Electronics'],
            ['category_id' => 135, 'locale' => 'sq', 'name' => 'Audio & Elektronikë'],
        ];

        foreach ($translations as $translation) {
            CategoryTranslation::create($translation);
        }
    }
}

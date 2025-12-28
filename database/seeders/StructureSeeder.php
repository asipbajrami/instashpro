<?php

namespace Database\Seeders;

use App\Models\ProductAttribute;
use App\Models\StructureOutput;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StructureSeeder extends Seeder
{
    private array $attributeMap = [];

    /**
     * Run the database seeds.
     *
     * Structure:
     * - parent_key = 'tech' or 'car' (root group for querying)
     * - used_for = specific product type (phone, computer, display, car, motorcycle, etc.)
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        StructureOutput::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Build attribute slug -> id map dynamically
        $this->attributeMap = ProductAttribute::pluck('id', 'slug')->toArray();

        // Seed global attributes for both groups (brand, condition - top-level JSON fields)
        $this->seedGlobalAttributes('tech');
        $this->seedGlobalAttributes('car');

        // Seed tech group attributes
        $this->seedPhoneAttributes();
        $this->seedComputerAttributes();
        $this->seedDisplayAttributes();

        // Seed car group attributes
        $this->seedCarAttributes();
        $this->seedMotorcycleAttributes();
    }

    /**
     * Global attributes that apply to all product types (top-level JSON fields)
     * These map to brand, condition which are hardcoded in the JSON schema
     * Uses parent_key as used_for to avoid unique constraint violation
     */
    private function seedGlobalAttributes(string $parentKey): void
    {
        $attributes = [
            ['key' => 'brand', 'description' => 'Product manufacturer or brand name', 'slug' => 'brand'],
            ['key' => 'model', 'description' => 'Product model name or variant', 'slug' => 'model'],
            ['key' => 'condition', 'description' => 'Product condition (new, used, refurbished)', 'slug' => 'condition'],
        ];

        // Use parent_key as used_for (e.g., 'tech', 'car') to satisfy unique constraint
        $this->createAttributes($attributes, $parentKey, $parentKey);
    }

    /**
     * Get product_attribute_id by slug (returns null if not found)
     */
    private function getAttributeId(string $slug): ?int
    {
        return $this->attributeMap[$slug] ?? null;
    }

    /**
     * Phone-specific attributes (parent: tech)
     */
    private function seedPhoneAttributes(): void
    {
        $attributes = [
            ['key' => 'color', 'description' => 'Phone color (Black, White, Silver, Titanium, etc.)', 'slug' => 'color'],
            ['key' => 'storage', 'description' => 'Storage capacity (64GB, 128GB, 256GB, 512GB, 1TB)', 'slug' => 'storage'],
            ['key' => 'ram', 'description' => 'RAM capacity (4GB, 6GB, 8GB, 12GB, 16GB)', 'slug' => 'ram'],
            ['key' => 'display_size', 'description' => 'Screen size (6.1", 6.7", 6.9")', 'slug' => 'display_size'],
            ['key' => 'battery', 'description' => 'Battery capacity (3000mAh, 4500mAh, 5000mAh)', 'slug' => 'battery'],
            ['key' => 'warranty', 'description' => 'Warranty (1 year, 2 years, AppleCare+)', 'slug' => 'warranty'],
            ['key' => 'gifts', 'description' => 'Included gifts or accessories with product', 'slug' => 'gifts'],
            ['key' => 'credit', 'description' => 'Credit purchase information (e.g., Jute credit 3999lek/month)', 'slug' => 'credit'],
        ];

        $this->createAttributes($attributes, 'phone', 'tech');
    }

    /**
     * Computer/Laptop-specific attributes (parent: tech)
     */
    private function seedComputerAttributes(): void
    {
        $attributes = [
            ['key' => 'color', 'description' => 'Device color (Space Gray, Silver, Midnight, etc.)', 'slug' => 'color'],
            ['key' => 'processor', 'description' => 'CPU (Apple M3, Intel i7-13700, AMD Ryzen 9)', 'slug' => 'processor'],
            ['key' => 'ram', 'description' => 'RAM (8GB, 16GB, 32GB, 64GB, 128GB)', 'slug' => 'ram'],
            ['key' => 'storage', 'description' => 'Storage (256GB, 512GB, 1TB, 2TB SSD)', 'slug' => 'storage'],
            ['key' => 'display_size', 'description' => 'Screen size (13", 14", 15", 16", 17")', 'slug' => 'display_size'],
            ['key' => 'graphics', 'description' => 'GPU (RTX 4090, AMD Radeon, Intel Iris Xe, Apple GPU)', 'slug' => 'graphics'],
            ['key' => 'battery', 'description' => 'Battery life (10 hours, 15 hours, 22 hours)', 'slug' => 'battery'],
            ['key' => 'warranty', 'description' => 'Warranty (1 year, 3 years, AppleCare+)', 'slug' => 'warranty'],
            ['key' => 'gifts', 'description' => 'Included gifts or accessories with product', 'slug' => 'gifts'],
            ['key' => 'credit', 'description' => 'Credit purchase information (e.g., Jute credit 3999lek/month)', 'slug' => 'credit'],
        ];

        $this->createAttributes($attributes, 'computer', 'tech');
    }

    /**
     * Display/Monitor-specific attributes (parent: tech)
     */
    private function seedDisplayAttributes(): void
    {
        $attributes = [
            ['key' => 'color', 'description' => 'Monitor color (Black, White, Silver)', 'slug' => 'color'],
            ['key' => 'display_size', 'description' => 'Screen size (24", 27", 32", 34", 49")', 'slug' => 'display_size'],
            ['key' => 'resolution', 'description' => 'Resolution (1080p FHD, 1440p QHD, 4K UHD, 5K, 8K)', 'slug' => 'resolution'],
            ['key' => 'refresh_rate', 'description' => 'Refresh rate (60Hz, 75Hz, 120Hz, 144Hz, 240Hz)', 'slug' => 'refresh_rate'],
            ['key' => 'panel_type', 'description' => 'Panel technology (IPS, VA, OLED, Mini-LED, Nano IPS)', 'slug' => 'panel_type'],
            ['key' => 'connectivity', 'description' => 'Inputs (HDMI 2.1, DisplayPort 1.4, USB-C, Thunderbolt)', 'slug' => 'connectivity'],
            ['key' => 'warranty', 'description' => 'Warranty (1 year, 3 years, 5 years)', 'slug' => 'warranty'],
            ['key' => 'gifts', 'description' => 'Included gifts or accessories with product', 'slug' => 'gifts'],
            ['key' => 'credit', 'description' => 'Credit purchase information (e.g., Jute credit 3999lek/month)', 'slug' => 'credit'],
        ];

        $this->createAttributes($attributes, 'display', 'tech');
    }

    /**
     * Car-specific attributes (parent: car)
     */
    private function seedCarAttributes(): void
    {
        $attributes = [
            ['key' => 'year', 'description' => 'Manufacturing year (2020, 2021, 2022, 2023, 2024)', 'slug' => 'year'],
            ['key' => 'mileage', 'description' => 'Odometer reading (50,000 km, 30,000 miles)', 'slug' => 'mileage'],
            ['key' => 'engine', 'description' => 'Engine (2.0L, 3.5L V6, 5.0L V8, Electric)', 'slug' => 'engine'],
            ['key' => 'transmission', 'description' => 'Transmission (Manual, Automatic, CVT, DCT)', 'slug' => 'transmission'],
            ['key' => 'fuel_type', 'description' => 'Fuel (Gasoline, Diesel, Electric, Hybrid, Plug-in Hybrid)', 'slug' => 'fuel_type'],
            ['key' => 'drivetrain', 'description' => 'Drivetrain (FWD, RWD, AWD, 4WD)', 'slug' => 'drivetrain'],
            ['key' => 'color', 'description' => 'Exterior color (Black, White, Silver, Red, Blue)', 'slug' => 'color'],
            ['key' => 'interior_color', 'description' => 'Interior (Black Leather, Beige Cloth, Brown)', 'slug' => 'interior_color'],
            ['key' => 'body_type', 'description' => 'Body style (Sedan, SUV, Coupe, Hatchback, Wagon)', 'slug' => 'body_type'],
            ['key' => 'seats', 'description' => 'Number of seats (2, 4, 5, 7, 8)', 'slug' => 'seats'],
            ['key' => 'doors', 'description' => 'Number of doors (2, 3, 4, 5)', 'slug' => 'doors'],
            ['key' => 'vin', 'description' => 'Vehicle Identification Number', 'slug' => 'vin'],
            ['key' => 'registration', 'description' => 'Registration status/plate info', 'slug' => 'registration'],
            ['key' => 'warranty', 'description' => 'Warranty (Factory, Extended, Certified)', 'slug' => 'warranty'],
        ];

        $this->createAttributes($attributes, 'car', 'car');
    }

    /**
     * Motorcycle-specific attributes (parent: car)
     */
    private function seedMotorcycleAttributes(): void
    {
        $attributes = [
            ['key' => 'year', 'description' => 'Manufacturing year (2020, 2021, 2022, 2023, 2024)', 'slug' => 'year'],
            ['key' => 'mileage', 'description' => 'Odometer reading (10,000 km, 5,000 miles)', 'slug' => 'mileage'],
            ['key' => 'engine', 'description' => 'Engine displacement (125cc, 650cc, 1000cc, 1200cc)', 'slug' => 'engine'],
            ['key' => 'engine_type', 'description' => 'Engine type (Single, Twin, Inline-4, V-Twin)', 'slug' => 'engine_type'],
            ['key' => 'transmission', 'description' => 'Transmission (5-speed, 6-speed, Quick Shifter)', 'slug' => 'transmission'],
            ['key' => 'fuel_type', 'description' => 'Fuel (Gasoline, Electric)', 'slug' => 'fuel_type'],
            ['key' => 'color', 'description' => 'Color (Black, Red, Blue, White, Orange)', 'slug' => 'color'],
            ['key' => 'bike_type', 'description' => 'Type (Sport, Cruiser, Touring, Adventure, Naked)', 'slug' => 'bike_type'],
            ['key' => 'abs', 'description' => 'ABS (Yes, No, Cornering ABS)', 'slug' => 'abs'],
            ['key' => 'seat_height', 'description' => 'Seat height (780mm, 820mm, 850mm)', 'slug' => 'seat_height'],
            ['key' => 'weight', 'description' => 'Curb weight (150kg, 200kg, 250kg)', 'slug' => 'weight'],
            ['key' => 'registration', 'description' => 'Registration status/plate info', 'slug' => 'registration'],
            ['key' => 'warranty', 'description' => 'Warranty (1 year, 2 years, Extended)', 'slug' => 'warranty'],
        ];

        $this->createAttributes($attributes, 'motorcycle', 'car');
    }

    /**
     * Create attributes with given used_for and parent_key
     * Looks up product_attribute_id dynamically by slug
     */
    private function createAttributes(array $attributes, string $usedFor, string $parentKey): void
    {
        foreach ($attributes as $attr) {
            $productAttributeId = isset($attr['slug']) ? $this->getAttributeId($attr['slug']) : null;

            StructureOutput::create([
                'key' => $attr['key'],
                'type' => 'string',
                'description' => $attr['description'],
                'used_for' => $usedFor,
                'parent_key' => $parentKey,
                'required' => true,
                'product_attribute_id' => $productAttributeId,
            ]);
        }
    }
}

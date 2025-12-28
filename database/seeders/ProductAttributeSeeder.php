<?php

namespace Database\Seeders;

use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductAttributeSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ProductAttributeValue::truncate();
        ProductAttribute::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Global Attributes (apply to ALL products)
        $globalAttributes = [
            ['name' => 'Brand', 'type' => 'select', 'slug' => 'brand', 'attribute_group' => 'global'],
            ['name' => 'Model', 'type' => 'select', 'slug' => 'model', 'attribute_group' => 'global'],
            ['name' => 'Color', 'type' => 'select', 'slug' => 'color', 'attribute_group' => 'global'],
            ['name' => 'Condition', 'type' => 'select', 'slug' => 'condition', 'attribute_group' => 'global'],
            ['name' => 'Warranty', 'type' => 'select', 'slug' => 'warranty', 'attribute_group' => 'global'],
        ];

        // Tech-specific Attributes (root category slug: 'tech-electronics')
        $techCommonAttributes = [
            ['name' => 'RAM', 'type' => 'select', 'slug' => 'ram', 'attribute_group' => 'tech-electronics'],
            ['name' => 'Storage', 'type' => 'select', 'slug' => 'storage', 'attribute_group' => 'tech-electronics'],
            ['name' => 'Display Size', 'type' => 'select', 'slug' => 'display_size', 'attribute_group' => 'tech-electronics'],
            ['name' => 'Battery', 'type' => 'select', 'slug' => 'battery', 'attribute_group' => 'tech-electronics'],
            ['name' => 'Processor', 'type' => 'select', 'slug' => 'processor', 'attribute_group' => 'tech-electronics'],
            ['name' => 'Graphics', 'type' => 'select', 'slug' => 'graphics', 'attribute_group' => 'tech-electronics'],
            ['name' => 'Gifts', 'type' => 'select', 'slug' => 'gifts', 'attribute_group' => 'tech-electronics'],
            ['name' => 'Credit', 'type' => 'text', 'slug' => 'credit', 'attribute_group' => 'tech-electronics'],
        ];

        // Monitors specific attributes (category slug: 'monitors')
        $monitorAttributes = [
            ['name' => 'Resolution', 'type' => 'select', 'slug' => 'resolution', 'attribute_group' => 'monitors'],
            ['name' => 'Refresh Rate', 'type' => 'select', 'slug' => 'refresh_rate', 'attribute_group' => 'monitors'],
            ['name' => 'Panel Type', 'type' => 'select', 'slug' => 'panel_type', 'attribute_group' => 'monitors'],
        ];

        // Headphones specific attributes (category slug: 'headphones')
        $headphoneAttributes = [
            ['name' => 'Connectivity', 'type' => 'select', 'slug' => 'connectivity', 'attribute_group' => 'headphones'],
        ];

        // Merge all tech attributes
        $techAttributes = array_merge(
            $techCommonAttributes,
            $monitorAttributes,
            $headphoneAttributes
        );

        // Vehicle-specific Attributes (root category slug: 'cars-vehicles')
        $vehicleCommonAttributes = [
            ['name' => 'Year', 'type' => 'select', 'slug' => 'year', 'attribute_group' => 'cars-vehicles'],
            ['name' => 'Mileage', 'type' => 'text', 'slug' => 'mileage', 'attribute_group' => 'cars-vehicles'],
            ['name' => 'Transmission', 'type' => 'select', 'slug' => 'transmission', 'attribute_group' => 'cars-vehicles'],
            ['name' => 'Fuel Type', 'type' => 'select', 'slug' => 'fuel_type', 'attribute_group' => 'cars-vehicles'],
            ['name' => 'VIN', 'type' => 'text', 'slug' => 'vin', 'attribute_group' => 'cars-vehicles'],
            ['name' => 'Registration', 'type' => 'text', 'slug' => 'registration', 'attribute_group' => 'cars-vehicles'],
        ];

        // Car-specific Attributes (category slug: 'cars')
        $carAttributes = [
            ['name' => 'Engine', 'type' => 'select', 'slug' => 'engine', 'attribute_group' => 'cars'],
            ['name' => 'Drivetrain', 'type' => 'select', 'slug' => 'drivetrain', 'attribute_group' => 'cars'],
            ['name' => 'Interior Color', 'type' => 'select', 'slug' => 'interior_color', 'attribute_group' => 'cars'],
            ['name' => 'Body Type', 'type' => 'select', 'slug' => 'body_type', 'attribute_group' => 'cars'],
            ['name' => 'Seats', 'type' => 'select', 'slug' => 'seats', 'attribute_group' => 'cars'],
            ['name' => 'Doors', 'type' => 'select', 'slug' => 'doors', 'attribute_group' => 'cars'],
        ];

        // Motorcycle-specific Attributes (category slug: 'motorcycles')
        $motorcycleAttributes = [
            ['name' => 'Engine Type', 'type' => 'select', 'slug' => 'engine_type', 'attribute_group' => 'motorcycles'],
            ['name' => 'Bike Type', 'type' => 'select', 'slug' => 'bike_type', 'attribute_group' => 'motorcycles'],
            ['name' => 'ABS', 'type' => 'select', 'slug' => 'abs', 'attribute_group' => 'motorcycles'],
            ['name' => 'Seat Height', 'type' => 'select', 'slug' => 'seat_height', 'attribute_group' => 'motorcycles'],
            ['name' => 'Weight', 'type' => 'text', 'slug' => 'weight', 'attribute_group' => 'motorcycles'],
        ];

        $allAttributes = array_merge($globalAttributes, $techAttributes, $vehicleCommonAttributes, $carAttributes, $motorcycleAttributes);

        foreach ($allAttributes as $attribute) {
            ProductAttribute::create($attribute);
        }

        // Condition Values (permanent)
        $conditionAttribute = ProductAttribute::where('name', 'Condition')->first();
        $conditions = ['New', 'Used', 'Refurbished'];
        foreach ($conditions as $condition) {
            ProductAttributeValue::create([
                'value' => $condition,
                'type_value' => 'select',
                'ai_value' => strtolower($condition),
                'product_attribute_id' => $conditionAttribute->id,
                'is_temp' => false,
                'score' => 10
            ]);
        }

        // Note: Attribute values can be managed via the admin dashboard
        // The seedCarAttributeValues() and seedMotorcycleAttributeValues()
        // methods below can be called optionally to pre-populate values
    }

    /**
     * Optionally seed pre-defined values for car attributes
     * Call this method manually if you want to pre-populate car attribute values
     */
    public function seedCarAttributeValues(): void
    {
        // Year values (last 10 years)
        $yearAttribute = ProductAttribute::where('slug', 'year')->first();
        if ($yearAttribute) {
            $currentYear = (int) date('Y');
            for ($year = $currentYear; $year >= $currentYear - 10; $year--) {
                ProductAttributeValue::create([
                    'value' => (string) $year,
                    'type_value' => 'select',
                    'ai_value' => (string) $year,
                    'product_attribute_id' => $yearAttribute->id,
                    'is_temp' => false,
                    'score' => 10
                ]);
            }
        }

        // Transmission values
        $transmissionAttribute = ProductAttribute::where('slug', 'transmission')->first();
        if ($transmissionAttribute) {
            $transmissions = ['Manual', 'Automatic', 'CVT', 'DCT', 'Semi-Automatic'];
            foreach ($transmissions as $transmission) {
                ProductAttributeValue::create([
                    'value' => $transmission,
                    'type_value' => 'select',
                    'ai_value' => strtolower(str_replace('-', '_', $transmission)),
                    'product_attribute_id' => $transmissionAttribute->id,
                    'is_temp' => false,
                    'score' => 10
                ]);
            }
        }

        // Fuel Type values
        $fuelTypeAttribute = ProductAttribute::where('slug', 'fuel_type')->first();
        if ($fuelTypeAttribute) {
            $fuelTypes = ['Gasoline', 'Diesel', 'Electric', 'Hybrid', 'Plug-in Hybrid', 'LPG', 'CNG'];
            foreach ($fuelTypes as $fuelType) {
                ProductAttributeValue::create([
                    'value' => $fuelType,
                    'type_value' => 'select',
                    'ai_value' => strtolower(str_replace(['-', ' '], '_', $fuelType)),
                    'product_attribute_id' => $fuelTypeAttribute->id,
                    'is_temp' => false,
                    'score' => 10
                ]);
            }
        }

        // Drivetrain values
        $drivetrainAttribute = ProductAttribute::where('slug', 'drivetrain')->first();
        if ($drivetrainAttribute) {
            $drivetrains = ['FWD', 'RWD', 'AWD', '4WD'];
            foreach ($drivetrains as $drivetrain) {
                ProductAttributeValue::create([
                    'value' => $drivetrain,
                    'type_value' => 'select',
                    'ai_value' => strtolower($drivetrain),
                    'product_attribute_id' => $drivetrainAttribute->id,
                    'is_temp' => false,
                    'score' => 10
                ]);
            }
        }

        // Body Type values
        $bodyTypeAttribute = ProductAttribute::where('slug', 'body_type')->first();
        if ($bodyTypeAttribute) {
            $bodyTypes = ['Sedan', 'SUV', 'Coupe', 'Hatchback', 'Wagon', 'Convertible', 'Pickup', 'Van', 'Minivan', 'Crossover'];
            foreach ($bodyTypes as $bodyType) {
                ProductAttributeValue::create([
                    'value' => $bodyType,
                    'type_value' => 'select',
                    'ai_value' => strtolower($bodyType),
                    'product_attribute_id' => $bodyTypeAttribute->id,
                    'is_temp' => false,
                    'score' => 10
                ]);
            }
        }

        // Seats values
        $seatsAttribute = ProductAttribute::where('slug', 'seats')->first();
        if ($seatsAttribute) {
            $seats = ['2', '4', '5', '6', '7', '8', '9+'];
            foreach ($seats as $seat) {
                ProductAttributeValue::create([
                    'value' => $seat,
                    'type_value' => 'select',
                    'ai_value' => $seat,
                    'product_attribute_id' => $seatsAttribute->id,
                    'is_temp' => false,
                    'score' => 10
                ]);
            }
        }

        // Doors values
        $doorsAttribute = ProductAttribute::where('slug', 'doors')->first();
        if ($doorsAttribute) {
            $doors = ['2', '3', '4', '5'];
            foreach ($doors as $door) {
                ProductAttributeValue::create([
                    'value' => $door,
                    'type_value' => 'select',
                    'ai_value' => $door,
                    'product_attribute_id' => $doorsAttribute->id,
                    'is_temp' => false,
                    'score' => 10
                ]);
            }
        }

        // Interior Color values
        $interiorColorAttribute = ProductAttribute::where('slug', 'interior_color')->first();
        if ($interiorColorAttribute) {
            $interiorColors = ['Black', 'Beige', 'Brown', 'Gray', 'White', 'Red', 'Tan', 'Cream'];
            foreach ($interiorColors as $color) {
                ProductAttributeValue::create([
                    'value' => $color,
                    'type_value' => 'select',
                    'ai_value' => strtolower($color),
                    'product_attribute_id' => $interiorColorAttribute->id,
                    'is_temp' => false,
                    'score' => 10
                ]);
            }
        }

        // Engine values (common sizes)
        $engineAttribute = ProductAttribute::where('slug', 'engine')->first();
        if ($engineAttribute) {
            $engines = ['1.0L', '1.2L', '1.4L', '1.5L', '1.6L', '1.8L', '2.0L', '2.2L', '2.4L', '2.5L', '3.0L', '3.5L', '4.0L', '5.0L', '6.0L', 'Electric'];
            foreach ($engines as $engine) {
                ProductAttributeValue::create([
                    'value' => $engine,
                    'type_value' => 'select',
                    'ai_value' => strtolower(str_replace('.', '_', $engine)),
                    'product_attribute_id' => $engineAttribute->id,
                    'is_temp' => false,
                    'score' => 10
                ]);
            }
        }
    }

    /**
     * Optionally seed pre-defined values for motorcycle attributes
     * Call this method manually if you want to pre-populate motorcycle attribute values
     */
    public function seedMotorcycleAttributeValues(): void
    {
        // Engine Type values
        $engineTypeAttribute = ProductAttribute::where('slug', 'engine_type')->first();
        if ($engineTypeAttribute) {
            $engineTypes = ['Single Cylinder', 'Parallel Twin', 'V-Twin', 'Inline-3', 'Inline-4', 'V4', 'Flat Twin', 'Electric'];
            foreach ($engineTypes as $engineType) {
                ProductAttributeValue::create([
                    'value' => $engineType,
                    'type_value' => 'select',
                    'ai_value' => strtolower(str_replace(['-', ' '], '_', $engineType)),
                    'product_attribute_id' => $engineTypeAttribute->id,
                    'is_temp' => false,
                    'score' => 10
                ]);
            }
        }

        // Bike Type values
        $bikeTypeAttribute = ProductAttribute::where('slug', 'bike_type')->first();
        if ($bikeTypeAttribute) {
            $bikeTypes = ['Sport', 'Cruiser', 'Touring', 'Adventure', 'Naked', 'Dual Sport', 'Scooter', 'Cafe Racer', 'Chopper', 'Dirt Bike'];
            foreach ($bikeTypes as $bikeType) {
                ProductAttributeValue::create([
                    'value' => $bikeType,
                    'type_value' => 'select',
                    'ai_value' => strtolower(str_replace(' ', '_', $bikeType)),
                    'product_attribute_id' => $bikeTypeAttribute->id,
                    'is_temp' => false,
                    'score' => 10
                ]);
            }
        }

        // ABS values
        $absAttribute = ProductAttribute::where('slug', 'abs')->first();
        if ($absAttribute) {
            $absOptions = ['Yes', 'No', 'Cornering ABS', 'Single Channel', 'Dual Channel'];
            foreach ($absOptions as $abs) {
                ProductAttributeValue::create([
                    'value' => $abs,
                    'type_value' => 'select',
                    'ai_value' => strtolower(str_replace(' ', '_', $abs)),
                    'product_attribute_id' => $absAttribute->id,
                    'is_temp' => false,
                    'score' => 10
                ]);
            }
        }

        // Seat Height values (common ranges in mm)
        $seatHeightAttribute = ProductAttribute::where('slug', 'seat_height')->first();
        if ($seatHeightAttribute) {
            $seatHeights = ['750mm', '780mm', '800mm', '820mm', '840mm', '850mm', '870mm', '900mm'];
            foreach ($seatHeights as $height) {
                ProductAttributeValue::create([
                    'value' => $height,
                    'type_value' => 'select',
                    'ai_value' => str_replace('mm', '', $height),
                    'product_attribute_id' => $seatHeightAttribute->id,
                    'is_temp' => false,
                    'score' => 10
                ]);
            }
        }
    }
}

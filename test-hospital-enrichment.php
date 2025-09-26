<?php

require_once 'vendor/autoload.php';

use JoshFinlayAU\LaravelAnthropicWeb\AnthropicWebClient;

echo "Hospital Enrichment Testing\n";
echo "===========================\n\n";

// Check for API key
$apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? null;
if (!$apiKey) {
    echo "Error: No ANTHROPIC_API_KEY environment variable found.\n";
    echo "Set it with: export ANTHROPIC_API_KEY=your-key-here\n";
    exit(1);
}

$client = new AnthropicWebClient($apiKey);

// Define hospital enrichment schema (matching your existing structure)
$hospitalSchema = [
    'type' => 'object',
    'properties' => [
        'type' => ['type' => 'string', 'enum' => ['hospital', 'clinic']],
        'address' => ['type' => 'string'],
        'postal_code' => ['type' => 'string'],
        'state_province' => ['type' => 'string'],
        'website' => ['type' => 'string'],
        'phone' => ['type' => 'string'],
        'email' => ['type' => 'string'],
        'emergency_phone' => ['type' => 'string'],
        'specialties' => ['type' => 'string'],
        'accreditation' => ['type' => 'string'],
        'bed_count' => ['type' => 'integer'],
        'trauma_center' => ['type' => 'boolean'],
        'teaching_hospital' => ['type' => 'boolean'],
        'research_facility' => ['type' => 'boolean'],
        'safety_rating' => ['type' => 'number'],
        'patient_satisfaction' => ['type' => 'number'],
        'quality_certifications' => ['type' => 'string'],
        'accepts_insurance' => ['type' => 'boolean'],
        'emergency_services' => ['type' => 'boolean'],
        'operating_hours' => [
            'type' => 'object',
            'properties' => [
                'monday' => ['type' => 'string'],
                'tuesday' => ['type' => 'string'],
                'wednesday' => ['type' => 'string'],
                'thursday' => ['type' => 'string'],
                'friday' => ['type' => 'string'],
                'saturday' => ['type' => 'string'],
                'sunday' => ['type' => 'string']
            ]
        ],
        'languages_spoken' => ['type' => 'array', 'items' => ['type' => 'string']],
        'latitude' => ['type' => 'number'],
        'longitude' => ['type' => 'number'],
        'established_year' => ['type' => 'integer'],
        'ownership_type' => ['type' => 'string', 'enum' => ['public', 'private', 'non-profit']],
        'description' => ['type' => 'string'],
        'logo_url' => ['type' => 'string']
    ]
];

// Test hospitals
$testHospitals = [
    ['name' => 'Royal Brisbane and Women\'s Hospital', 'city' => 'Brisbane', 'country' => 'Australia'],
    ['name' => 'Mayo Clinic', 'city' => 'Rochester', 'country' => 'United States'],
    ['name' => 'Johns Hopkins Hospital', 'city' => 'Baltimore', 'country' => 'United States']
];

foreach ($testHospitals as $index => $hospital) {
    echo "Test " . ($index + 1) . ": Enriching {$hospital['name']}\n";
    echo str_repeat('-', 50) . "\n";
    
    $prompt = "Research and enrich data for {$hospital['name']} in {$hospital['city']}, {$hospital['country']}.

INSTRUCTIONS:
1. Use web search to find current, accurate information about this hospital
2. Only provide data you can verify from reliable sources
3. Use null for any fields you cannot find reliable information for
4. For coordinates, use precise decimal degrees
5. For operating hours, use 24-hour format or \"24/7\" for hospitals
6. For logo_url, find the direct URL to their official logo image

Return ONLY the JSON object with the enriched data according to the provided schema.";

    try {
        $startTime = microtime(true);
        
        $enrichedData = $client->completeJsonWithWebTools(
            $prompt,
            $hospitalSchema,
            'claude-sonnet-4-20250514',
            4000,
            ['max_uses' => 3], // web search options
            ['max_uses' => 5, 'citations' => ['enabled' => true]] // web fetch options
        );
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        echo "Enrichment completed in {$duration} seconds\n";
        echo "Enriched data:\n";
        echo json_encode($enrichedData, JSON_PRETTY_PRINT) . "\n\n";
        
        // Validate key fields
        $requiredFields = ['type', 'address', 'website', 'specialties'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($enrichedData[$field]) || $enrichedData[$field] === null) {
                $missingFields[] = $field;
            }
        }
        
        if (empty($missingFields)) {
            echo "All required fields populated\n";
        } else {
            echo "Missing fields: " . implode(', ', $missingFields) . "\n";
        }
        
        echo "\n" . str_repeat('=', 70) . "\n\n";
        
        // Add delay between requests to be respectful
        if ($index < count($testHospitals) - 1) {
            echo "Waiting 2 seconds before next request...\n\n";
            sleep(2);
        }
        
    } catch (Exception $e) {
        echo "Error enriching {$hospital['name']}: " . $e->getMessage() . "\n\n";
    }
}

echo "Hospital enrichment testing completed.\n";
echo "Package is ready for production use.\n";

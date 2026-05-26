<?php

namespace App\Services;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;
use Throwable;

// Import the Laravel AI agent helper
use function Laravel\Ai\agent;

class AiRecommendationService
{
    public function generateRecommendation(string $tier, array $data): string
    {
        error_log("AiRecommendationService@generateRecommendation - Start - Tier: {$tier}");
        $promptText = $this->buildPrompt($tier, $data);
        error_log("AiRecommendationService@generateRecommendation - Prompt built successfully.");
        

        try {
            // Using the laravel/ai SDK's agent helper with Structured Output
            $response = agent(
                schema: fn(JsonSchema $schema) => [
                    'recommendation' => $schema->string()
                        ->description('The fully formatted progress update to the parent based on the tier rules.')
                        ->required(),
                ]
            )->prompt(
                    $promptText,
                    provider: 'gemini', // Explicitly specify the Gemini provider
                    model: 'gemini-2.5-flash-lite' // Explicitly specify the Gemini model
                );
            // The SDK automatically parses the JSON response into array access
            $result = $response['recommendation'] ?? "We are currently analyzing data. Check back soon";
            error_log("AiRecommendationService@generateRecommendation - Success - Recommendation returned.");
            return $result;

        } catch (Throwable $e) {
            error_log("AiRecommendationService@generateRecommendation - Error - " . $e->getMessage());
            // Fallback gracefully in case of provider rate limits or downtime
            return "We are currently analyzing data. Check back soon! | Error: " . $e->getMessage();
        }
    }

    private function buildPrompt(string $tier, array $data): string
    {
        error_log("AiRecommendationService@buildPrompt - Start");
        // 1. Read prompt from file
        $template = File::get(resource_path('ai_prompt/master_prompt.txt'));

        // 2. Prepare the current date
        $currentDate = Carbon::now('Africa/Addis_Ababa')->format('l, F j, Y');

        $childName = $data['child_name'] ?? 'the child';
        
        // 3. Inject variables into the template
        $prompt = str_replace(
            ['{tier}', '{current_date}', '{child_name}', '{daily_summary}', '{weekly_summary}', '{snapshot}', '{events}'],
            [
                $tier,
                $currentDate,
                $childName,
                json_encode($data['daily'] ?? []),
                json_encode($data['weekly'] ?? []),
                json_encode($data['snapshot'] ?? []),
                json_encode($data['events'] ?? [])
            ],
            $template
        );
        error_log("AiRecommendationService@buildPrompt - Success");
        return $prompt;
    }
}

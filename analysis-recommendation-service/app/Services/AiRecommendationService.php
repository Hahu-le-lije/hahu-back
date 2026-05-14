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
        $promptText = $this->buildPrompt($tier, $data);
        error_log("Generated prompt for AI:\n" . $promptText);
        

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
            return $response['recommendation'] ?? "We are currently analyzing data. Check back soon!";

        } catch (Throwable $e) {
            // Fallback gracefully in case of provider rate limits or downtime
            return "We are currently analyzing data. Check back soon! | Error: " . $e->getMessage();
        }
    }

    private function buildPrompt(string $tier, array $data): string
    {
        // 1. Read prompt from file
        $template = File::get(resource_path('ai_prompt/master_prompt.txt'));

        // 2. Prepare the current date
        $currentDate = Carbon::now('Africa/Addis_Ababa')->format('l, F j, Y');

        $childName = $data['child_name'] ?? 'the child';
        // error_log("Building prompt for child: {$childName}, tier: {$tier}, date: {$currentDate}");
        // error_log("Daily Summary: " . print_r($data['daily'] ?? 'N/A', true));
        // error_log("Weekly Summary: " . print_r($data['weekly'] ?? 'N/A', true));
        // error_log("Snapshot: " . print_r($data['snapshot'] ?? 'N/A', true));
        // error_log("Events: " . print_r($data['events'] ?? 'N/A', true));
        // 3. Inject variables into the template
        return str_replace(
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
    }
}




/*


namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Throwable;


#[Provider('gemini')]           // <--- Pins this agent strictly to the Gemini SDK
#[Model('gemini-2.5-flash')]

class AiRecommendationService implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are an expert educational AI assistant analyzing child learning metrics and writing progress updates for parents.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'recommendation' => $schema->string()
                ->description('The fully formatted progress update to the parent based on the tier rules.')
                ->required(),
        ];
    }
    public function generateRecommendation(string $tier, array $data): string
    {
        try {
            $response = (new \App\Ai\Agents\AiRecommendationService)->prompt($this->buildPrompt($tier, $data));

            return $response['recommendation'] ?? "We are currently analyzing data. Check back soon!";
        } catch (Throwable $e) {
            return "We are currently analyzing data. Check back soon!";
        }
    }
    private function buildPrompt(string $tier, array $data): string
    {
        // 1. Read prompt from file
        $template = File::get(resource_path('ai_prompt/master_prompt.txt'));

        // 2. Prepare the current date
        $currentDate = Carbon::now('Africa/Addis_Ababa')->format('l, F j, Y');

        $childName = $data['child_name'] ?? 'the child';

        // 3. Inject variables into the template
        return str_replace(
            ['{tier}', '{current_date}', '{child_name}', '{daily_summary}', '{weekly_summary}', '{snapshot}', '{events}'],
            [$tier, $currentDate, $childName, $data['daily'], $data['weekly'], $data['snapshot'], $data['events']],
            $template
        );
    }
}




---



namespace App\Services;

use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Throwable;
use function Laravel\Ai\agent;
use Illuminate\Contracts\JsonSchema\JsonSchema;


class AiRecommendationService
{
    public function generateRecommendation(string $tier, array $data): string
    {
        $promptText = $this->buildPrompt($tier, $data);

        try {
            // Using the laravel/ai SDK's agent helper with Structured Output
            $response = agent(
                instructions: 'You are an expert educational AI assistant analyzing child learning metrics and writing progress updates for parents.',
                schema: fn(JsonSchema $schema) => [
                    'recommendation' => $schema->string()
                        ->description('The fully formatted progress update to the parent based on the tier rules.')
                        ->required(),
                ]
            )->prompt($promptText);

            // The SDK automatically parses the JSON response into array access
            return $response['recommendation'] ?? "We are currently analyzing data. Check back soon!";

        } catch (Throwable $e) {
            // Fallback gracefully in case of provider rate limits or downtime
            return "We are currently analyzing data. Check back soon!";
        }
    }

    private function buildPrompt(string $tier, array $data): string
    {
        // 1. Read prompt from file
        $template = File::get(resource_path('ai_prompt/master_prompt.txt'));

        // 2. Prepare the current date
        $currentDate = Carbon::now('Africa/Addis_Ababa')->format('l, F j, Y');

        $childName = $data['child_name'] ?? 'the child';

        // 3. Inject variables into the template
        return str_replace(
            ['{tier}', '{current_date}', '{child_name}', '{daily_summary}', '{weekly_summary}', '{snapshot}', '{events}'],
            [$tier, $currentDate, $childName, $data['daily'], $data['weekly'], $data['snapshot'], $data['events']],
            $template
        );
    }
}


---


namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class AiRecommendationService
{
    public function generateRecommendation(string $childName, string $tier, array $data): string
    {
        $prompt = $this->buildPrompt($childName, $tier, $data);

        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->post('https://api.openai.com/v1/chat/completions',[
                'model' => 'gpt-4o',
                'messages' => [['role' => 'system', 'content' => $prompt]],
                'temperature' => 0.7,
            ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }

        return "We are currently analyzing data for {$childName}. Check back soon!";
    }

    private function buildPrompt(string $childName, string $tier, array $data): string
    {
        // 1. Read prompt from file
        $template = File::get(resource_path('ai_prompt/master_prompt.txt'));

        // 2. Prepare the current date
        $currentDate = Carbon::now('Africa/Addis_Ababa')->format('l, F j, Y');

        // 3. Inject variables into the template
        return str_replace(['{tier}', '{current_date}', '{child_name}', '{daily_summary}', '{weekly_summary}', '{snapshot}', '{events}'],
            [$tier, $currentDate, $childName, $data['daily'], $data['weekly'], $data['snapshot'], $data['events']],
            $template
        );
    }
} 

*/
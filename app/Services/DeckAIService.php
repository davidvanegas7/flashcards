<?php

namespace App\Services;

use OpenAI\OpenAI;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class DeckAIService
{
    protected $client;
    
    public function __construct()
    {
        // $this->client = \OpenAI::client(config('services.openai.api_key'));

        $baseUrl = 'https://openrouter.ai/api/v1'; // Endpoint de OpenRouter
        $apiKey = config('services.openrouterai.api_key');
        
        $this->client = \OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($baseUrl)
            ->make();
    }

    public function generateCards($title, $description, $category, $numCards = 5, $mode)
    {
        try {
            $prompt = $this->buildPrompt($title, $description, $category, $numCards, $mode);
            
            $response = $this->client->chat()->create([
                // 'model' => 'gpt-4o-mini',
                'model' => 'google/gemini-2.5-pro-exp-03-25:free',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant that creates flashcards for studying. Return the cards in JSON format. Create the results based on the same language of the topic, description and category.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
            ]);

            return $this->parseResponse($response);

        } catch (\Exception $e) {
            Log::error('Error generating AI cards: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function buildPrompt($title, $description, $category, $numCards, $mode)
    {
        if ($mode == 'multiple') {
            $json = "'cards': [
                {
                    'question': 'Question text here',
                    'option1': 'Option answer 1 text here',
                    'option2': 'Option answer 2 text here',
                    'option3': 'Option answer 3 text here',
                    'option4': 'Option answer 4 text here',
                    'explanation': 'Explanation about the correct answer text here',
                    'answer': 'Position between 1 and 4 indicating the correct answer between the options'
                }
            ]";
        } else {
            $json = "'cards': [
                {
                    'question': 'Question text here',
                    'answer': 'Answer text here'
                }
            ]";
        }

        return "Create {$numCards} flashcards for studying {$title}. 
        Description: {$description}
        Category: {$category}

        **Important**: Ensure that each flashcard is different from previous ones. Avoid repeating similar questions or answers, and try to approach the topic from different angles.
        
        Please return the cards in this JSON format:
        {
            {$json}
        }";
    }

    protected function parseResponse($response)
    {
        $content = $response->choices[0]->message->content;
        
        $content = preg_replace('/[\x00-\x1F\x7F]/u', '', $content);
        $content = str_replace('```json', '', $content);
        $content = str_replace('```', '', $content);

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return $data['cards'];
        } catch (\JsonException $e) {
            Log::error('Error parsing AI response: ' . $content);
            throw new \Exception('Invalid response format from AI');
        }
    }
}

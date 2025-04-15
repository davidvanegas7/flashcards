<?php

namespace App\Services;

use OpenAI\OpenAI;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class CardAIService
{
    protected $client;
    
    public function __construct()
    {

        $baseUrl = 'https://openrouter.ai/api/v1'; // Endpoint de OpenRouter
        $apiKey = config('services.openrouterai.api_key');
        
        $this->client = \OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($baseUrl)
            ->make();
    }

    public function generateResponse($title, $description, $category, $question)
    {
        try {
            $prompt = $this->buildPrompt($title, $description, $category, $question);
            
            $response = $this->client->chat()->create([
                'model' => 'google/gemini-2.5-pro-exp-03-25:free',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant that response questions. Return the cards in JSON format. Create the results based on the same language of the topic, description and category.'
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

    protected function buildPrompt($title, $description, $category, $question)
    {
        return "Response the question based on the next values:
                Title: {$title}. 
                Description: {$description}
                Category: {$category}
                Question: {$question}
                
                Please return the answer in this JSON format:
                {
                    'cards': [
                        {
                            'question': '{$question}',
                            'answer': 'Answer text here'
                        }
                    ]
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

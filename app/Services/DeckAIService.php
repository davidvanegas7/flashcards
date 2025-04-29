<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class DeckAIService
{
    protected $client;
    protected $apiKey;
    
    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->client = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/',
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 300, // 5 minutos de timeout
            'connect_timeout' => 30, // 30 segundos para la conexión inicial
        ]);
    }

    public function generateCards($title, $description, $category, $numCards = 5, $mode)
    {
        try {
            $prompt = $this->buildPrompt($title, $description, $category, $numCards, $mode);
            
            $model = 'gemini-2.0-flash-001';
            $response = $this->client->post("models/{$model}:generateContent?key={$this->apiKey}", [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 60000,
                    ]
                ]
            ]);

            $responseData = json_decode($response->getBody(), true);
            return $this->parseResponse($responseData);

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

        **Important**: Ensure that each flashcard is different from previous ones. Avoid repeating similar questions or answers, and try to approach the topic from different angles. Avoid using single quotes in the questions or answers.
        
        Please return the cards in this JSON format:
        {
            {$json}
        }";
    }

    protected function parseResponse($response)
    {
        $content = $response['candidates'][0]['content']['parts'][0]['text'];
        
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

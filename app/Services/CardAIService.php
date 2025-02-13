<?php

namespace App\Services;

use OpenAI\Client;
use Illuminate\Support\Facades\Log;

class CardAIService
{
    protected $client;
    
    public function __construct()
    {
        $this->client = \OpenAI::client(config('services.openai.api_key'));
    }

    public function generateCards($title, $description, $category, $numCards = 5)
    {
        try {
            $prompt = $this->buildPrompt($title, $description, $category, $numCards);
            
            $response = $this->client->chat()->create([
                'model' => 'gpt-4o-mini',
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

    protected function buildPrompt($title, $description, $category, $numCards)
    {
        return "Create {$numCards} flashcards for studying {$title}. 
                Description: {$description}
                Category: {$category}

                **Important**: Ensure that each flashcard is different from previous ones. Avoid repeating similar questions or answers, and try to approach the topic from different angles.
                
                Please return the cards in this JSON format:
                {
                    'cards': [
                        {
                            'question': 'Question text here',
                            'answer': 'Answer text here'
                        }
                    ]
                }";
    }

    protected function parseResponse($response)
    {
        $content = $response->choices[0]->message->content;
        
        $content = preg_replace('/[\x00-\x1F\x7F]/u', '', $content);
        
        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return $data['cards'];
        } catch (\JsonException $e) {
            Log::error('Error parsing AI response: ' . $content);
            throw new \Exception('Invalid response format from AI');
        }
    }
}

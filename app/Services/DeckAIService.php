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

    public function generateCards($title, $description, $category, $numCards = 5, $mode='multiple')
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

        // Limpiar el contenido
        $content = preg_replace('/[\x00-\x1F\x7F]/u', '', $content);
        $content = str_replace('```json', '', $content);
        $content = str_replace('```', '', $content);
        $content = trim($content);

        try {
            $data = $this->processPartialJson($content);

            if (!isset($data['cards']) || !is_array($data['cards'])) {
                Log::error('Formato de respuesta inválido: no se encontró la clave "cards" o no es un array');
                return [];
            }
            return $data['cards'];
        } catch (\JsonException $e) {
            Log::error('Error parsing AI response: ' . $e->getMessage());
            Log::error('Contenido recibido: ' . $content);
            return [];
        }
        
    }

    public function processPartialJson($content) 
    {
        $validCards = [];
        $invalidCards = [];
        
        // Expresión regular mejorada para identificar estructuras de tarjetas
        // Esta versión es más tolerante con espacios en blanco y permite comillas escapadas dentro de strings
        $pattern = '/{                             
        \s*["\']question["\']\s*:\s*["\'] ( (?:[^"\'\\\\]|\\\\.)* ) ["\']\s*,
        \s*["\']option1["\']\s*:\s*["\'] ( (?:[^"\'\\\\]|\\\\.)* ) ["\']\s*,
        \s*["\']option2["\']\s*:\s*["\'] ( (?:[^"\'\\\\]|\\\\.)* ) ["\']\s*,
        \s*["\']option3["\']\s*:\s*["\'] ( (?:[^"\'\\\\]|\\\\.)* ) ["\']\s*,
        \s*["\']option4["\']\s*:\s*["\'] ( (?:[^"\'\\\\]|\\\\.)* ) ["\']\s*,
        \s*["\']explanation["\']\s*:\s*["\'] ( (?:[^"\'\\\\]|\\\\.)* ) ["\']\s*,
        \s*["\']answer["\']\s*:\s* ( \d+ ) \s*
        }/xs';  
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $index => $match) {
                try {
                    // Reconstruir la tarjeta manualmente para evitar problemas con JSON mal formado
                    $card = [
                        'question' => stripcslashes($match[1]),
                        'option1' => stripcslashes($match[2]),
                        'option2' => stripcslashes($match[3]),
                        'option3' => stripcslashes($match[4]),
                        'option4' => stripcslashes($match[5]),
                        'explanation' => stripcslashes($match[6]),
                        'answer' => (int)$match[7]
                    ];
                    
                    // Validaciones adicionales
                    if ($card['answer'] < 1 || $card['answer'] > 4) {
                        throw new \Exception("Respuesta fuera de rango: {$card['answer']}");
                    }
                    
                    if (empty($card['question']) || empty($card['option1'])) {
                        throw new \Exception("Campos obligatorios vacíos");
                    }
                    
                    $validCards[] = $card;
                    Log::info("Tarjeta #{$index} procesada correctamente");
                } catch (\Exception $e) {
                    $invalidCards[] = [
                        'raw' => $match[0],
                        'error' => $e->getMessage()
                    ];
                    Log::error("Error al procesar tarjeta #{$index}: " . $e->getMessage());
                }
            }
        }
        
        // Registrar estadísticas del procesamiento
        $totalFound = count($validCards) + count($invalidCards);
        Log::info("Procesamiento completado: {$totalFound} tarjetas encontradas, " . 
                count($validCards) . " válidas, " . 
                count($invalidCards) . " inválidas");
        
        return [
            'cards' => $validCards,
            'stats' => [
                'total_found' => $totalFound,
                'valid' => count($validCards),
                'invalid' => count($invalidCards)
            ],
            'invalid_cards' => $invalidCards
        ];
    }
}

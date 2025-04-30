<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\Deck;
use App\Models\Podcast;
use App\Models\ExpandedCard;
use App\Models\Category;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ProcessDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $document;
    public $tries = 3;
    public $timeout = 3600; // 1 hora
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if (!$this->document->is_cleaned) {
                $content = $this->cleanDocumentText($this->document->content);

                $this->document->update([
                    'content' => $content,
                    'is_cleaned' => true
                ]);
            }

            if (!$this->document->is_processed) {
                $this->generateMultipleChoiceQuestions($this->document);
                $this->generatePodcast($this->document);
                $this->document->update(['is_processed' => true]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing document: ' . $e->getMessage());
            throw $e;
        }
    }

    private function cleanDocumentText($text)
    {
        $apiKey = config('services.gemini.api_key');
        $client = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/',
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 600, // 10 minutos
            'connect_timeout' => 60,
        ]);

        $prompt = "Limpia y optimiza el siguiente texto para que sea más legible y adecuado para sirva de transcript para un podcast y lectura. 
        Los textos son transcritos de PDF, words o documentos externos, nosotros extraemos el texto del documento, por favor solo limpia aquello que lo haga confuso y deja el contenido lo mas original posible.

        Texto a limpiar:
        {$text}
        
        Devuelve solo el texto limpio y optimizado para la lectura, sin comentarios adicionales.";

        $model = 'gemini-2.5-flash-preview-04-17';
        try {
            $response = $client->post("models/{$model}:generateContent?key={$apiKey}", [
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
                        'temperature' => 0.3,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 500000,
                    ]
                ]
            ]);

            $responseData = json_decode($response->getBody(), true);
            return $responseData['candidates'][0]['content']['parts'][0]['text'];
        } catch (\Exception $e) {
            Log::error('Error al limpiar el texto con Gemini: ' . $e->getMessage());
            return $text;
        }
    }

    private function generateMultipleChoiceQuestions($document)
    {
        try {
            $apiKey = config('services.gemini.api_key');
            $client = new Client([
                'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/',
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 1200, // 20 minutos
                'connect_timeout' => 60,
            ]);
            
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

            $prompt = "Genera la mayor cantidad de preguntas de selección múltiple basadas en el siguiente texto. Cada pregunta debe tener 4 opciones y solo una respuesta correcta.  \n\n . Texto: \n {$document->content} \n Fin del texto.  \n\n
            **Important**: Ensure that each flashcard is different from previous ones. Avoid repeating similar questions or answers, and try to approach the topic from different angles. Avoid using single quotes in the questions or answers.
            
            Please return the cards in this JSON format:
            {
                {$json}
            }";

            $model = 'gemini-2.5-flash-preview-04-17';
            $response = $client->post("models/{$model}:generateContent?key={$apiKey}", [
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
                        'maxOutputTokens' => 500000,
                    ]
                ]
            ]);       

            $responseData = json_decode($response->getBody(), true);
            $questions = $this->parseResponse($responseData);
            
            // Crear un nuevo deck con las preguntas generadas
            $category = Category::firstOrCreate([
                'name' => strtolower($document->title), 
                'slug' => str_replace(' ', '-', strtolower($document->title)),
            ]);
    
            $deck = Deck::create([
                'name' => $document->title . ' - Preguntas',
                'user_id' => $document->user_id,
                'description' => $document->title . ' - Preguntas',
                'is_multiple_selection' => true,
                'document_id' => $document->id,
                'category_id' => $category->id,
            ]);
            
            foreach ($questions as $question) {
                $card = ExpandedCard::create([
                    'deck_id' => $deck->id,
                    'user_id' => $document->user_id,
                    'question' => $question['question'],
                    'option1' => $question['option1'],
                    'option2' => $question['option2'],
                    'option3' => $question['option3'],
                    'option4' => $question['option4'],
                    'answer' => $question['answer'],
                    'explanation' => $question['explanation'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al generar preguntas con Gemini: ' . $e->getMessage());
        }
    }

    private function generatePodcast($document)
    {
        $podcast = Podcast::create([
            'document_id' => $document->id,
            'title' => $document->title . ' - Podcast',
            'transcript' => $document->content,
        ]);

        // El audio se generará en el frontend usando la API de síntesis de voz del navegador
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

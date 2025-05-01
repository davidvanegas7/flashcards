<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Podcast;
use App\Models\Deck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use Google\Service\Drive;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessDocument;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;

class DocumentController extends Controller
{
    private $blobClient;

    public function __construct()
    {
        $connectionString = config('filesystems.disks.azure.connection_string');
        $this->blobClient = BlobRestProxy::createBlobService($connectionString);
    }

    public function index()
    {
        $documents = Document::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('documents.index', compact('documents'));
    }

    public function create()
    {
        // Verificar si hay un documento en procesamiento
        if (session('processing')) {
            $document = Document::find(session('document_id'));
            
            if ($document && $document->is_processed) {
                session()->forget(['processing', 'document_id', 'processing_document_id']);
                return redirect()->route('documents.show', $document)
                    ->with('success', 'Documento procesado exitosamente');
            }
        }

        return view('documents.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,txt|max:10240', // 10MB
            'title' => 'required|string|max:255',
        ]);

        $file = $request->file('document');
        $fileName = uniqid() . '_' . $file->getClientOriginalName();
        $containerName = config('filesystems.disks.azure.container');

        try {
            // Subir el archivo a Azure Blob Storage
            $content = fopen($file->getPathname(), 'r');
            $options = new CreateBlockBlobOptions();
            $options->setContentType($file->getMimeType());
            
            $this->blobClient->createBlockBlob($containerName, $fileName, $content, $options);
            
            $document = Document::where('title', $request->title)->where('user_id', auth()->id())->first();
            if (!$document) {
                $document = Document::create([
                    'user_id' => auth()->id(),
                    'title' => $request->title,
                    'file_path' => $fileName,
                    'file_type' => $file->getClientOriginalExtension(),
                    'is_cleaned' => false,
                    'is_processed' => false,
                    'content' => $this->extractTextFromDocument($file),
                ]);
            }

            // Despachar el job para procesar el documento en segundo plano
            ProcessDocument::dispatch($document);

            // Guardar el ID del documento en la sesión
            session()->put('processing_document_id', $document->id);
            session()->put('processing', true);

            return redirect()->route('documents.create')
                ->with('processing', true)
                ->with('document_id', $document->id);
        } catch (ServiceException $e) {
            return back()->with('error', 'Error al subir el archivo: ' . $e->getMessage());
        }
    }

    public function checkStatus(Document $document)
    {
        return response()->json([
            'is_processed' => $document->is_processed,
            'is_cleaned' => $document->is_cleaned
        ]);
    }

    public function show(Document $document)
    {
        // $this->authorize('view', $document);
        $decks = $document->decks;
        $podcast = $document->podcast;
        
        return view('documents.show', compact('document', 'decks', 'podcast'));
    }

    private function extractTextFromDocument($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        switch ($extension) {
            case 'txt':
                return file_get_contents($file->getPathname());
                
            case 'pdf':
                return $this->extractTextFromPdf($file);
                
            case 'doc':
            case 'docx':
                return $this->extractTextFromWord($file);
                
            default:
                throw new \Exception('Formato de archivo no soportado');
        }
    }

    private function extractTextFromPdf($file)
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($file->getPathname());
        return $pdf->getText();
    }

    private function extractTextFromWord($file)
    {
        $phpWord = IOFactory::load($file->getPathname());
        $text = '';
        
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                }
            }
        }
        
        return $text;
    }

    private function cleanDocumentText($text)
    {
        $apiKey = config('services.gemini.api_key');
        $client = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/',
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 300, // 5 minutos de timeout
            'connect_timeout' => 30,
        ]);

        $prompt = "Por favor, limpia y optimiza el siguiente texto para que sea más legible y adecuado para la generación de podcasts y lecturas. 
        Realiza las siguientes acciones:
        1. Elimina números de página, encabezados y pies de página
        2. Elimina referencias bibliográficas y citas
        3. Elimina notas al pie y comentarios
        4. Elimina caracteres especiales y símbolos innecesarios
        5. Mantén solo el contenido principal del texto
        6. Asegúrate de que el texto fluya naturalmente
        7. Preserva la estructura de párrafos y la jerarquía del contenido
        8. Elimina cualquier contenido redundante o repetitivo
        
        Texto a limpiar:
        {$text}
        
        Por favor, devuelve solo el texto limpio y optimizado, sin comentarios adicionales.";

        $model = 'gemini-2.0-flash-001';
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
            \Log::error('Error al limpiar el texto con Gemini: ' . $e->getMessage());
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
                'timeout' => 1800, // 30 minutos de timeout
                'connect_timeout' => 30, // 30 segundos para la conexión inicial
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

            $model = 'gemini-2.0-flash-001';
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
            $deck = Deck::create([
                'user_id' => auth()->id(),
                'title' => $document->title . ' - Preguntas',
                'document_id' => $document->id,
            ]);
            
            foreach ($questions as $question) {
                $deck->cards()->create([
                    'question' => $question['pregunta'],
                    'answer' => $question['respuesta_correcta'],
                    'options' => json_encode($question['opciones']),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error al generar preguntas con Gemini: ' . $e->getMessage());
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

// TODO 1: Generar un prompt para limpiar el contenido extraido del documento
// TODO 2: Generar un prompt para generar preguntas de selección múltiple
// TODO 3: Generar los audios a partir de los textos de las preguntas y respuestas
// TODO 4: Generar el podcast con los audios y el texto del transcript
// TODO 5: Generar un prompt para generar un resumen del contenido del documento
// TODO 6: Generar un prompt para generar un mapa mental del contenido del documento
// TODO 7: Generar un prompt para generar un diagrama de flujo del contenido del documento
// TODO 8: Generar un prompt para generar un diagrama de Venn del contenido del documento
// TODO 9: Generar un prompt para generar un diagrama de árbol del contenido del documento
// TODO 10: Generar un prompt para generar un diagrama de Gantt del contenido del documento


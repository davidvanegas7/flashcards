<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Deck;
use App\Services\CardAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CardController extends Controller
{
    protected $cardAIService;
    
    public function __construct(CardAIService $cardAIService)
    {
        $this->cardAIService = $cardAIService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index($deckId)
    {
        $cards = Card::where('deck_id', $deckId)->get();
        return view('cards.index', compact('cards'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $deck = Deck::findOrFail($request->deck);
        return view('cards.create', compact('deck'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'deck_id' => 'required|exists:decks,id',
        ]);

        $deck = Deck::findOrFail($request->deck_id);
        if ($deck->user_id !== auth()->id()) {
            abort(403);
        }

        $request->merge(['user_id' => auth()->id()]);
        Card::create($request->all());

        return redirect()->route('decks.show', $deck)->with('success', __('Card created successfully'));
    }

    /**
     * Generate new questions using AI.
     */
    public function generateCardsUsingAI(Request $request)
    {
        ini_set('max_execution_time', 300);

        try{
            $deck = Deck::findOrFail($request->deck_id);
            if ($deck->user_id !== auth()->id()) {
                abort(403);
            }

            $cards = $this->cardAIService->generateCards(
                $deck->name,
                $deck->description,
                $deck->category->name,
                30
            );

            foreach ($cards as $c) {
                $card = Card::create([
                    'deck_id' => $deck->id,
                    'user_id' => auth()->id(),
                    'question' => $c['question'],
                    'answer' => $c['answer'],
                ]);
            }

            return response()->json([
                'success'=> true,
                'message' => __('Cards generated successfully'),
                'cards' => $cards,
                'count' => count($cards)
            ], 200);
        } catch (\Exception $e) {
            Log::error(__('Error generating cards') . ': ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Error generating cards'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Card $card)
    {
        return view('cards.show', compact('card'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Deck $deck, Card $card)
    {
        return view('cards.edit', compact('deck', 'card'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Deck $deck, Card $card)
    {
        $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string|max:255',
            'deck_id' => 'required|exists:decks,id',
        ]);
        $card->update($request->all());
        return redirect()->route('decks.show', $request->deck_id)->with('success', 'Card updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Card $card)
    {
        $card->delete();
        return redirect()->route('cards.index', $card->deck_id)->with('success', 'Card deleted successfully');
    }
}

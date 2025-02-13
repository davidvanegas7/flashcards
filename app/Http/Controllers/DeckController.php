<?php

namespace App\Http\Controllers;

use App\Models\Deck;
use App\Models\Category;
use Illuminate\Http\Request;
use Auth;

class DeckController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $decks = Auth::user()->decks()
            ->withCount('cards')
            ->latest()
            ->get();

        return view('decks.index', compact('decks'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::pluck('name')->toArray();

        return view('decks.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:255',
        ]);

        $category = Category::firstOrCreate([
            'name' => strtolower($validated['category']), 
            'slug' => str_replace(' ', '-', strtolower($validated['category'])),
        ]);

        $deck = Auth::user()->decks()->create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category_id' => $category->id,
        ]);

        return redirect()->route('decks')->with('success', 'Deck created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Deck $deck)
    {
        $deck->load(['cards', 'category']);
    
        // Cargar contadores
        $deck->cards_count = $deck->cards->count();
        $deck->mastered_cards_count = $deck->cards->where('mastered', true)->count();
        $deck->review_cards_count = $deck->cards->where('mastered', false)->count();
    
        return view('decks.show', compact('deck'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Deck $deck)
    {
        $categories = Category::pluck('name')->toArray();

        return view('decks.edit', compact('deck', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Deck $deck)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:255',
        ]);

        $category = Category::firstOrCreate([
            'name' => strtolower($validated['category']), 
            'slug' => str_replace(' ', '-', strtolower($validated['category'])),
        ]);

        $deck->name = $validated['name'];
        $deck->description = $validated['description'];
        $deck->category_id = $category->id;
        $deck->save();

        return redirect()->route('decks')->with('success', 'Deck updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Deck $deck)
    {
        $deck->delete();
        return redirect()->route('decks.index')->with('success', 'Deck deleted successfully');
    }
}

<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Deck -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 sm:gap-6">
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2 sm:gap-4">
                                <h1 class="text-2xl font-bold text-gray-900">{{ $deck->name }}</h1>
                                <span class="px-2.5 py-0.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                                    {{ $deck->category->name }}
                                </span>
                                @if($deck->is_public)
                                    <span class="px-2.5 py-0.5 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        {{ __('Public') }}
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                        {{ __('Private') }}
                                    </span>
                                @endif
                                @if($deck->is_multiple_selection)
                                    <span class="inline-flex text-center items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-black">
                                        Multiple
                                    </span>
                                @endif
                            </div>
                            <p class="mt-2 text-gray-600">{{ $deck->description }}</p>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-2 sm:min-w-fit">
                            @if($deck->user_id === auth()->id())
                                <x-secondary-button 
                                    class="w-full sm:w-auto justify-center" 
                                    onclick="window.location='{{ route('decks.edit', $deck) }}'">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    {{ __('Edit')}}
                                </x-secondary-button>
                            @endif
                        </div>
                    </div>
                    <!-- Statistics -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Total Cards') }}</dt>
                            <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $deck->cards_count }}</dd>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Learned') }}</dt>
                            <dd class="mt-1 text-3xl font-semibold text-green-600">{{ $deck->mastered_cards_count }}</dd>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <dt class="text-sm font-medium text-gray-500">{{ __('To Review') }}</dt>
                            <dd class="mt-1 text-3xl font-semibold text-yellow-600">{{ $deck->review_cards_count }}</dd>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Last Practice') }}</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">
                                {{ $deck->last_studied_at ? $deck->last_studied_at->diffForHumans() : 'Nunca' }}
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <!--  PodCards -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 sm:gap-6 mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">PodCards</h2>
                        @if($deck->user_id === auth()->id())
                            <div class="flex flex-col sm:flex-row w-full sm:w-auto gap-2">
                                @if($deck->is_multiple_selection)
                                <x-primary-button 
                                    class="w-full sm:w-auto justify-center"
                                    onclick="window.location='{{ route('play.expanded-cards', ['deck' => $deck->id]) }}'">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                    {{ __('Play')}}
                                </x-primary-button>
                                @else
                                <x-primary-button 
                                    class="w-full sm:w-auto justify-center"
                                    onclick="window.location='{{ route('cards.create', ['deck' => $deck->id]) }}'">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    {{ __('Add Card')}}
                                </x-primary-button>
                                @endif
                            </div>
                        @endif
                    </div>

                    @foreach($deck->expandedCards as $card)
                    <div class="border rounded-lg mb-4 hover:shadow-md transition-shadow duration-200">
                        <div class="p-4" x-data="{ showAnswer: false }">
                            <div class="flex flex-col">
                                <!-- Cabecera con pregunta y botones -->
                                <div class="flex flex-col sm:flex-row justify-between items-start mb-4">
                                    <p class="text-lg font-medium text-gray-900 mb-3 sm:mb-0">{{ $card->question }}</p>
                                    <div class="flex space-x-2 w-full sm:w-auto">
                                        
                                    </div>
                                </div>

                                <!-- Respuesta -->
                                <div x-show="showAnswer"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                                    x-transition:enter-end="opacity-100 transform translate-y-0"
                                    class="text-gray-600 whitespace-pre-line w-full">
                                    {{ $card->answer }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @forelse($deck->cards as $card)
                    <div class="border rounded-lg mb-4 hover:shadow-md transition-shadow duration-200">
                        <div class="p-4" x-data="{ showAnswer: false }">
                            <div class="flex flex-col">
                                <!-- Cabecera con pregunta y botones -->
                                <div class="flex flex-col sm:flex-row justify-between items-start mb-4">
                                    <p class="text-lg font-medium text-gray-900 mb-3 sm:mb-0">{{ $card->question }}</p>
                                    <div class="flex space-x-2 w-full sm:w-auto">
                                        <button @click="showAnswer = !showAnswer"
                                            class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-medium font-semibold text-xs text-gray-700 capitalize tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                            <span x-text="showAnswer ? '{{ __('Hide') }}' : '{{ __('Show') }}'"></span>
                                        </button>
                                        @if($deck->user_id === auth()->id())
                                            <x-secondary-button 
                                                onclick="window.location='{{ route('cards.edit', ['deck' => $deck->id, 'card' => $card->id]) }}'">
                                                {{ __('Edit') }}
                                            </x-secondary-button>
                                        @endif
                                    </div>
                                </div>

                                <!-- Respuesta -->
                                <div x-show="showAnswer"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                                    x-transition:enter-end="opacity-100 transform translate-y-0"
                                    class="text-gray-600 whitespace-pre-line w-full">
                                    {{ $card->answer }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                        @if($deck->expandedCards->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('There are no cards yet') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Start by adding some cards to this deck.') }}</p>
                            @if($deck->user_id === auth()->id())
                                <div class="mt-6">
                                    <x-primary-button onclick="window.location='{{ route('cards.create', ['deck' => $deck->id]) }}'">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        {{ __('Add First Card')}}
                                    </x-primary-button>
                                </div>
                            @endif
                        </div>
                        @endif
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div id="loadingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl flex items-center">
            <svg class="animate-spin h-6 w-6 text-indigo-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-700">{{ __('Generating cards with AI...') }}</span>
        </div>
    </div>
</x-app-layout>
<script>
function generateCardsWithAI(deckId, mode) {
    const loadingModal = document.getElementById('loadingModal');
    loadingModal.classList.remove('hidden');
    loadingModal.classList.add('flex');

    fetch('{{ route("cards.generateAI") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            deck_id: deckId,
            mode: mode
        })
    })
    .then(response => response.json())
    .then(data => {
        loadingModal.classList.remove('flex');
        loadingModal.classList.add('hidden');
        console.log(data);
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error generating cards');
        }
    })
    .catch(error => {
        loadingModal.classList.remove('flex');
        loadingModal.classList.add('hidden');
        
        console.error('Error:', error);
        alert('Error generating cards');
    });
}
</script>
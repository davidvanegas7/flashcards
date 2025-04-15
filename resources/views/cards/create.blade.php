<x-app-layout>
    <div class="py-12" x-data="{ 
            question: '{{ old('question', '') }}', 
            answer: '{{ old('answer', '') }}',
            showAnswer: false 
        }"
        @ai-response.window="answer = $event.detail.response"
        >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800">{{ __('Add Card')}}</h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ __('Adding new card to deck:') }} <span class="font-medium">{{ $deck->name }}</span>
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Form Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form method="POST" action="{{ route('cards.store') }}">
                            @csrf
                            <input type="hidden" name="deck_id" value="{{ $deck->id }}">

                            <!-- Question -->
                            <div class="mb-6">
                                <x-input-label for="question" value="{{ __('Question') }}" />
                                <div class="mt-1">
                                    <textarea
                                        id="question"
                                        name="question"
                                        rows="3"
                                        x-model="question"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="{{ __('Example: What is the capital of France?') }}">
                                        required
                                    >{{ old('question') }}</textarea>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ __('Write the question or concept you want to learn')}}
                                </p>
                                <x-input-error :messages="$errors->get('question')" class="mt-2" />
                            </div>

                            <!-- Answer -->
                            <div class="mb-6">
                                <x-input-label for="answer" value="{{ __('Answer') }}" />
                                <div class="mt-1">
                                    <textarea
                                        id="answer"
                                        name="answer"
                                        rows="3"
                                        x-model="answer"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="{{ __('Example: Paris') }}"
                                        required
                                    >{{ old('answer') }}</textarea>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ __('Write the answer or explanation')}}
                                </p>
                                <x-input-error :messages="$errors->get('answer')" class="mt-2" />
                            </div>

                            <div class="flex items-center justify-end gap-4">
                                <x-secondary-button type="button" onclick="window.location='{{ route('decks.show', $deck) }}'">
                                    {{ __('Cancel') }}
                                </x-secondary-button>
                                <x-secondary-button type="button" x-on:click="generateResponseWithAI({{ $deck->id }}, question)">
                                    {{ __('AI Answer') }}
                                </x-secondary-button>
                                <x-primary-button>
                                    {{ __('Save Card') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Preview -->
                <div class="lg:sticky lg:top-4 space-y-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Preview') }}</h3>
                            
                            <div class="border rounded-lg p-6">
                                <!-- Question -->
                                <div class="text-center">
                                    <p class="text-lg font-medium text-gray-900" x-text="question || '{{ __('Question') }}'"></p>
                                </div>

                                <div class="mt-6 text-center">
                                    <button type="button"
                                            @click="showAnswer = !showAnswer"
                                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <template x-if="!showAnswer">
                                            <span>{{ __('Show Answer') }}</span>
                                        </template>
                                        <template x-if="showAnswer">
                                            <span>{{ __('Hide Answer') }}</span>
                                        </template>
                                    </button>
                                </div>

                                <!-- Answer -->
                                <div x-show="showAnswer"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     class="mt-6">
                                        <p class="text-lg text-gray-900 whitespace-pre-line" x-text="answer || '{{ __('Answer') }}'"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END Preview -->
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
            <span class="text-gray-700">{{ __('Generating response with AI...') }}</span>
        </div>
    </div>
</x-app-layout>

<script>
function generateResponseWithAI(deckId, question) {
    console.log('Generating response with AI...');
    const loadingModal = document.getElementById('loadingModal');
    loadingModal.classList.remove('hidden');
    loadingModal.classList.add('flex');

    fetch('{{ route("cards.generateResponseAI") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            deck_id: deckId,
            question: question
        })
    })
    .then(response => response.json())
    .then(data => {
        loadingModal.classList.remove('flex');
        loadingModal.classList.add('hidden');
        console.log(data);
        if (data.success) {
            window.dispatchEvent(new CustomEvent('ai-response', { 
                detail: { response: data.response }
            }));
        } else {
            alert('Error generating response');
        }
    })
    .catch(error => {
        loadingModal.classList.remove('flex');
        loadingModal.classList.add('hidden');
        
        console.error('Error:', error);
        alert('Error generating response - catch');
    });
}
</script>
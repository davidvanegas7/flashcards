<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h1 class="text-2xl font-bold mb-6">{{ $deck->name }} - Quiz</h1>
                    
                    <div x-data="{
                        currentCard: 0,
                        totalCards: {{ count($cards) }},
                        selectedOption: null,
                        answered: false,
                        correctAnswers: 0,
                        cards: {{ Illuminate\Support\Js::from($cards) }},
                        
                        checkAnswer() {
                            this.answered = true;
                            if (this.selectedOption === this.cards[this.currentCard].answer) {
                                this.correctAnswers++;
                            }
                        },
                        
                        nextCard() {
                            this.answered = false;
                            this.selectedOption = null;
                            this.currentCard++;
                        },
                        
                        restart() {
                            this.currentCard = 0;
                            this.selectedOption = null;
                            this.answered = false;
                            this.correctAnswers = 0;
                        }
                    }">
                        <!-- Progreso -->
                        <div class="mb-6">
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" 
                                     x-bind:style="'width: ' + ((currentCard + 1) / totalCards * 100) + '%'"></div>
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                <span x-text="currentCard + 1"></span> de <span x-text="totalCards"></span> preguntas
                            </div>
                        </div>
                        
                        <!-- Área de la Pregunta -->
                        <div x-show="currentCard < totalCards" class="mb-6">
                            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                                <h2 class="text-xl font-semibold mb-4" x-text="cards[currentCard].question"></h2>
                                
                                <div class="space-y-3">
                                    <template x-for="(option, index) in [cards[currentCard].option1, cards[currentCard].option2, cards[currentCard].option3, cards[currentCard].option4]" :key="index">
                                        <button 
                                            style="padding: 10px; margin: 10px;"
                                            x-bind:class="{
                                                'border-2 p-3 rounded-lg w-full text-left flex justify-between items-center': true,
                                                'bg-white hover:bg-gray-50 border-gray-200': !answered && selectedOption !== index + 1,
                                                'bg-blue-100 border-blue-300': !answered && selectedOption === index + 1,
                                                'bg-red-100 border-red-300': answered && selectedOption === index + 1 && selectedOption !== cards[currentCard].answer,
                                                'bg-green-100 border-green-300': answered && cards[currentCard].answer === index + 1
                                            }"
                                            x-bind:disabled="answered"
                                            @click="selectedOption = index + 1"
                                        >
                                            <span x-text="option"></span>
                                            <span x-show="answered && cards[currentCard].answer === index + 1" class="text-green-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                            <span x-show="answered && selectedOption === index + 1 && selectedOption !== cards[currentCard].answer" class="text-red-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            
                            <!-- Explicación después de responder -->
                            <div x-show="answered" class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg mb-6">
                                <h3 class="font-medium text-yellow-800 mb-2">Explicación:</h3>
                                <p x-text="cards[currentCard].explanation" class="text-yellow-700"></p>
                            </div>
                            
                            <div class="flex justify-between">
                                <button x-show="!answered" 
                                        x-bind:disabled="selectedOption === null"
                                        @click="checkAnswer()"
                                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                                    Comprobar respuesta
                                </button>
                                
                                <button x-show="answered" 
                                        @click="nextCard()"
                                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 flex items-center">
                                    Siguiente
                                    <svg class="w-4 h-4 ml-2" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Resultados finales -->
                        <div x-show="currentCard >= totalCards" class="text-center py-8">
                            <div class="mb-6">
                                <svg class="mx-auto h-24 w-24 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            
                            <h2 class="text-3xl font-bold mb-2">¡Quiz completado!</h2>
                            <p class="text-xl mb-4">Has respondido correctamente <span class="font-bold" x-text="correctAnswers"></span> de <span x-text="totalCards"></span> preguntas.</p>
                            <p class="text-2xl font-bold mb-6">
                                Puntuación: <span x-text="Math.round((correctAnswers / totalCards) * 100) + '%'"></span>
                            </p>
                            
                            <div class="mt-8 flex justify-center space-x-4">
                                <button @click="restart()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Reintentar Quiz
                                </button>
                                <a href="{{ route('decks.show', $deck) }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                                    Volver al mazo
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
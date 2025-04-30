<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $document->title }} 
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Información de depuración -->
                    <div class="mb-6 bg-gray-100 p-4 rounded">
                        <h3 class="text-lg font-medium mb-2">Estado del Documento</h3>
                        <p><strong>ID:</strong> {{ $document->id }}</p>
                        <p><strong>Tipo de archivo:</strong> {{ strtoupper($document->file_type) }}</p>
                        <p><strong>Estado:</strong> 
                            @if($document->is_processed)
                                <span class="text-green-600">Procesado</span>
                            @else
                                <span class="text-yellow-600">En proceso</span>
                            @endif
                        </p>
                        <p><strong>Fecha de creación:</strong> {{ $document->created_at }}</p>
                    </div>

                    @if($document->is_processed)
                        <div class="mb-6">
                            <h3 class="text-lg font-medium mb-2">Decks Generados</h3>
                            @if($decks && $decks->count() > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($decks as $deck)
                                        <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                            <h4 class="font-medium">{{ $deck->title }}</h4>
                                            <p class="text-sm text-gray-600">{{ $deck->cards->count() + $deck->expandedCards->count() }} tarjetas</p>
                                            <a href="{{ route('decks.show', $deck) }}" class="text-blue-600 hover:text-blue-800 text-sm">Ver deck</a>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="bg-gray-50 p-4 rounded">
                                    <p class="text-gray-600">No se han generado decks para este documento.</p>
                                </div>
                            @endif
                        </div>

                        @if($podcast)
                            <div class="mb-6">
                                <h3 class="text-lg font-medium mb-2">Podcast Generado</h3>
                                <div class="border rounded-lg p-4">
                                    <h4 class="font-medium">{{ $podcast->title }}</h4>
                                    
                                    <div class="mt-4">
                                        <div class="flex items-center space-x-4">
                                            <button onclick="toggleSpeech()" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                                </svg>
                                                <span id="playButtonText">Leer Transcript</span>
                                            </button>

                                            <button onclick="stopSpeech()" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                                                </svg>
                                                Detener
                                            </button>
                                            
                                            <div class="flex items-center space-x-2">
                                                <span id="currentTime">00:00</span>
                                                <span>/</span>
                                                <span id="totalTime">00:00</span>
                                            </div>
                                        </div>

                                        <!-- Barra de progreso -->
                                        <div class="mt-4">
                                            <div class="w-full bg-gray-200 rounded-full h-2.5 cursor-pointer relative" onclick="handleProgressBarClick(event)">
                                                <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                                                <div id="progressTooltip" class="absolute -top-8 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 pointer-events-none transition-opacity duration-200">
                                                    Fragmento <span id="tooltipFragment">0</span>
                                                </div>
                                            </div>
                                            <div class="mt-1 text-sm text-gray-600">
                                                <span id="currentFragment">0</span> de <span id="totalFragments">0</span> fragmentos
                                            </div>
                                        </div>

                                        <!-- Controles de velocidad -->
                                        <div class="mt-4 flex items-center space-x-4">
                                            <label class="text-sm text-gray-600">Velocidad:</label>
                                            <select id="rateSelect" onchange="changeRate()" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                                <option value="0.5">0.5x</option>
                                                <option value="0.75">0.75x</option>
                                                <option value="1" selected>1x</option>
                                                <option value="1.25">1.25x</option>
                                                <option value="1.5">1.5x</option>
                                                <option value="2">2x</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                let currentUtterance = null;
                                let isPlaying = false;
                                let startTime = null;
                                let totalDuration = 0;
                                let currentRate = 1.0;
                                let fragments = [];
                                let currentFragmentIndex = 0;

                                function splitIntoFragments(text, maxLength = 200) {
                                    // Dividir el texto en oraciones
                                    const sentences = text.match(/[^.!?]+[.!?]+/g) || [text];
                                    let fragments = [];
                                    let currentFragment = '';

                                    sentences.forEach(sentence => {
                                        if ((currentFragment + sentence).length > maxLength) {
                                            if (currentFragment) {
                                                fragments.push(currentFragment.trim());
                                            }
                                            currentFragment = sentence;
                                        } else {
                                            currentFragment += sentence;
                                        }
                                    });

                                    if (currentFragment) {
                                        fragments.push(currentFragment.trim());
                                    }

                                    return fragments;
                                }

                                function stopSpeech() {
                                    speechSynthesis.cancel();
                                    isPlaying = false;
                                    currentUtterance = null;
                                    startTime = null;
                                    currentFragmentIndex = 0;
                                    document.getElementById('playButtonText').textContent = 'Leer Transcript';
                                    document.getElementById('currentTime').textContent = '00:00';
                                    document.getElementById('progressBar').style.width = '0%';
                                    document.getElementById('currentFragment').textContent = '0';
                                }

                                function formatTime(seconds) {
                                    const minutes = Math.floor(seconds / 60);
                                    const remainingSeconds = Math.floor(seconds % 60);
                                    return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
                                }

                                function updateTime() {
                                    if (isPlaying && startTime) {
                                        const currentSeconds = Math.floor((Date.now() - startTime) / 1000);
                                        document.getElementById('currentTime').textContent = formatTime(currentSeconds);
                                        document.getElementById('totalTime').textContent = formatTime(totalDuration / currentRate);
                                    }
                                }

                                function changeRate() {
                                    const rate = parseFloat(document.getElementById('rateSelect').value);
                                    currentRate = rate;
                                    if (currentUtterance) {
                                        currentUtterance.rate = rate;
                                        if (isPlaying) {
                                            speechSynthesis.cancel();
                                            speakNextFragment();
                                        }
                                    }
                                }

                                function speakNextFragment() {
                                    if (currentFragmentIndex >= fragments.length) {
                                        stopSpeech();
                                        return;
                                    }

                                    currentUtterance = new SpeechSynthesisUtterance(fragments[currentFragmentIndex]);
                                    
                                    // Configurar la voz en español si está disponible
                                    const voices = speechSynthesis.getVoices();
                                    const spanishVoice = voices.find(voice => voice.lang.includes('es-US'));
                                    if (spanishVoice) {
                                        currentUtterance.voice = spanishVoice;
                                    }
                                    
                                    currentUtterance.rate = currentRate;
                                    currentUtterance.pitch = 1.0;

                                    currentUtterance.onend = () => {
                                        currentFragmentIndex++;
                                        document.getElementById('currentFragment').textContent = currentFragmentIndex;
                                        document.getElementById('progressBar').style.width = `${(currentFragmentIndex / fragments.length) * 100}%`;
                                        
                                        if (isPlaying) {
                                            speakNextFragment();
                                        }
                                    };

                                    speechSynthesis.speak(currentUtterance);
                                }

                                function handleProgressBarClick(event) {
                                    const progressBar = event.currentTarget;
                                    const rect = progressBar.getBoundingClientRect();
                                    const clickPosition = event.clientX - rect.left;
                                    const percentage = clickPosition / rect.width;
                                    const newFragmentIndex = Math.floor(percentage * fragments.length);
                                    
                                    if (newFragmentIndex !== currentFragmentIndex) {
                                        currentFragmentIndex = newFragmentIndex;
                                        document.getElementById('currentFragment').textContent = currentFragmentIndex;
                                        document.getElementById('progressBar').style.width = `${(currentFragmentIndex / fragments.length) * 100}%`;
                                        
                                        if (isPlaying) {
                                            speechSynthesis.cancel();
                                            speakNextFragment();
                                        }
                                    }
                                }

                                function showProgressTooltip(event) {
                                    const progressBar = event.currentTarget;
                                    const tooltip = document.getElementById('progressTooltip');
                                    const rect = progressBar.getBoundingClientRect();
                                    const mousePosition = event.clientX - rect.left;
                                    const percentage = mousePosition / rect.width;
                                    const fragmentIndex = Math.floor(percentage * fragments.length);
                                    
                                    document.getElementById('tooltipFragment').textContent = fragmentIndex;
                                    tooltip.style.left = `${mousePosition}px`;
                                    tooltip.style.opacity = '1';
                                }

                                function hideProgressTooltip() {
                                    const tooltip = document.getElementById('progressTooltip');
                                    tooltip.style.opacity = '0';
                                }

                                function toggleSpeech() {
                                    if (isPlaying) {
                                        speechSynthesis.pause();
                                        isPlaying = false;
                                        document.getElementById('playButtonText').textContent = 'Reanudar';
                                    } else {
                                        if (currentUtterance) {
                                            speechSynthesis.resume();
                                        } else {
                                            const transcript = @json($podcast->transcript);
                                            fragments = splitIntoFragments(transcript);
                                            document.getElementById('totalFragments').textContent = fragments.length;
                                            
                                            // Calcular la duración estimada (aproximadamente 1 palabra por segundo)
                                            const wordCount = transcript.split(/\s+/).length;
                                            totalDuration = wordCount;
                                            
                                            startTime = Date.now();
                                            isPlaying = true;
                                            document.getElementById('playButtonText').textContent = 'Pausar';
                                            speakNextFragment();
                                        }
                                    }
                                }

                                // Actualizar el tiempo cada segundo
                                setInterval(updateTime, 1000);

                                // Cargar las voces disponibles cuando estén listas
                                if (speechSynthesis.onvoiceschanged !== undefined) {
                                    speechSynthesis.onvoiceschanged = function() {
                                        // Las voces están cargadas
                                    };
                                }

                                // Agregar event listeners para el tooltip
                                document.addEventListener('DOMContentLoaded', function() {
                                    const progressBar = document.querySelector('.cursor-pointer');
                                    progressBar.addEventListener('mousemove', showProgressTooltip);
                                    progressBar.addEventListener('mouseleave', hideProgressTooltip);
                                });
                            </script>
                        @endif
                    @else
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        El documento está siendo procesado. Por favor, actualiza la página en unos momentos.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 
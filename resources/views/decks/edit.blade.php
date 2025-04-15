<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-visible shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">{{ __('Edit Deck') }}</h2>
                        <p class="mt-1 text-sm text-gray-600">{{ __('Update your deck information.') }}</p>
                    </div>

                    <form method="POST" action="{{ route('decks.update', $deck) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Deck Name -->
                        <div>
                            <x-input-label for="name" value="{{ __('Deck Name') }}" />
                            <x-text-input id="name"
                                name="name"
                                type="text"
                                class="mt-1 block w-full"
                                :value="old('name', $deck->name)"
                                required
                                autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Description -->
                        <div>
                            <x-input-label for="description" value="{{ __('Description') }}" />
                            <textarea id="description"
                                name="description"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                placeholder="{{ __('Describe the content of your deck') }}">{{ old('description', $deck->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <!-- Category -->
                        <div x-data="{
                            open: false,
                            search: '{{ old('category', $deck->category->name) }}',
                            selected: true,
                            categories: {{ json_encode($categories) }},
                            filteredCategories() {
                                if (this.search === '') return this.categories;
                                return this.categories.filter(category =>
                                    category.toLowerCase().includes(this.search.toLowerCase())
                                );
                            }
                        }" @click.away="open = false" class="relative">
                            <x-input-label for="category" value="{{ __('Category') }}" />
                            <div class="relative mt-1">
                                <input
                                    type="text"
                                    id="category"
                                    name="category"
                                    x-model="search"
                                    @focus="open = true"
                                    @input="selected = false"
                                    class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    placeholder="{{ __('Search or create a category') }}"
                                    autocomplete="off"
                                >
                                    <!-- Dropdown -->
                                <div x-show="open" 
                                     x-cloak
                                     class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200">
                                    <ul class="max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                        <!-- Existing categories -->
                                        <template x-for="category in filteredCategories()" :key="category">
                                            <li @click="search = category; selected = true; open = false"
                                                class="text-gray-900 cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-50"
                                                :class="{ 'bg-indigo-50': search === category }">
                                                <span x-text="category" class="block truncate"></span>
                                                <span x-show="search === category" 
                                                      class="absolute inset-y-0 right-0 flex items-center pr-4 text-indigo-600">
                                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            </li>
                                        </template>
                                        
                                        <!-- New Category -->
                                        <li x-show="!selected && search && !filteredCategories().includes(search)"
                                            @click="selected = true; open = false"
                                            class="text-gray-900 cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-50">
                                            <div class="flex items-center">
                                                <span class="text-indigo-600 font-medium">{{ __('Create Category') }}</span>
                                                <span class="ml-1" x-text="search"></span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('category')" class="mt-2" />
                        </div>

                        <!-- Multiple option -->
                        <div>
                            <x-input-label for="multiple_options" value="{{ __('Multiple options') }}" />
                            <!-- Switch/Toggle Button -->
                            <label for="multiple_options" class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="multiple_options" name="multiple_options" value="1" class="sr-only peer" {{ old('multiple_options', $deck->is_multiple_selection) ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <x-secondary-button type="button" onclick="window.location='{{ route('decks.show', $deck) }}'">
                                {{ __('Cancel') }}
                            </x-secondary-button>
                            <x-primary-button>
                                {{ __('Update Deck') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

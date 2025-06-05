<div
    x-data="{
        open: @entangle('isEditModalVisible'),
        activeTab: 'basic'
    }"
    x-show="open"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="fixed inset-0 flex items-center justify-center z-50 overflow-scroll"
    x-cloak
>
    <div class="relative content-wrapper rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-screen overflow-y-auto">
        <div class="p-6">
            <form wire:submit.prevent="updateCostCalculation">
                {{-- Tabs Navigation --}}
                <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                    <ul class="flex flex-wrap -mb-px">
                        <li class="mr-2">
                            <button type="button"
                                    @click="activeTab = 'basic'"
                                    :class="{'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400': activeTab === 'basic'}"
                                    class="inline-block py-2 px-4 text-sm font-medium focus:outline-none">
                                Basic Information
                            </button>
                        </li>
                        <li class="mr-2">
                            <button type="button"
                                    @click="activeTab = 'transport'"
                                    :class="{'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400': activeTab === 'transport'}"
                                    class="inline-block py-2 px-4 text-sm font-medium focus:outline-none">
                                Transport & Delivery
                            </button>
                        </li>
                        <li class="mr-2">
                            <button type="button"
                                    @click="activeTab = 'pricing'"
                                    :class="{'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400': activeTab === 'pricing'}"
                                    class="inline-block py-2 px-4 text-sm font-medium focus:outline-none">
                                Pricing & Costs
                            </button>
                        </li>
                        <li>
                            <button type="button"
                                    @click="activeTab = 'additional'"
                                    :class="{'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400': activeTab === 'additional'}"
                                    class="inline-block py-2 px-4 text-sm font-medium focus:outline-none">
                                Additional Info
                            </button>
                        </li>
                    </ul>
                </div>

                {{-- Tab Content: Basic Information --}}
                <div x-show="activeTab === 'basic'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Product --}}
                        <div>
                            <label for="product_id" class="block text-sm font-medium mb-1">Product</label>
                            <select id="product_id" wire:model="formData.product_id" wire:change="filterGrade"
                                    class="w-full rounded-md form-input">
                                <option value="">Select Product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                            @error('formData.product_id') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Grade --}}
                        <div>
                            <label for="grade_id" class="block text-sm font-medium mb-1">Grade</label>
                            <select id="grade_id" wire:model="formData.grade_id" class="w-full rounded-md form-input">
                                <option value="">Select Grade</option>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                @endforeach
                            </select>
                            @error('formData.grade_id') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Supplier --}}
                        <div>
                            <label for="supplier_id" class="block text-sm font-medium mb-1">Supplier</label>
                            <select id="supplier_id" wire:model="formData.supplier_id"
                                    class="w-full rounded-md form-input">
                                <option value="">Select Supplier</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            @error('formData.supplier_id') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Packaging --}}
                        <div>
                            <label for="packaging_id" class="block text-sm font-medium mb-1">Packaging</label>
                            <select id="packaging_id" wire:model="formData.packaging_id"
                                    class="w-full rounded-md form-input">
                                <option value="">Select Packaging</option>
                                @foreach ($packaging as $pkg)
                                    <option value="{{ $pkg->id }}">{{ $pkg->name }}</option>
                                @endforeach
                            </select>
                            @error('formData.packaging_id') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Tender No --}}
                        <div>
                            <label for="tender_no" class="block text-sm font-medium mb-1">Tender No</label>
                            <input type="text" id="tender_no" wire:model="formData.tender_no"
                                   class="w-full rounded-md form-input">
                            @error('formData.tender_no') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Status --}}
                        <div>
                            <label for="status" class="block text-sm font-medium mb-1">Status</label>
                            <select id="status" wire:model="formData.status" class="w-full rounded-md form-input">
                                <option value="">Select Status</option>
                                @foreach ($statusOptions as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>
                            @error('formData.status') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Date --}}
                        <div>
                            <label for="date" class="block text-sm font-medium mb-1">Date</label>
                            <input type="date" id="date" wire:model="formData.date"
                                   class="w-full rounded-md form-input">
                            @error('formData.date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Validity --}}
                        <div>
                            <label for="validity" class="block text-sm font-medium mb-1">Validity</label>
                            <input type="date" id="validity" wire:model="formData.validity"
                                   class="w-full rounded-md form-input">
                            @error('formData.validity') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Quantity --}}
                        <div>
                            <label for="quantity" class="block text-sm font-medium mb-1">Quantity (MT)</label>
                            <input type="number" id="quantity" wire:model="formData.quantity" step="0.01"
                                   class="w-full rounded-md form-input">
                            @error('formData.quantity') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Incoterm --}}
                        <div>
                            <label for="term" class="block text-sm font-medium mb-1">Incoterm</label>
                            <select id="term" wire:model="formData.term" class="w-full rounded-md form-input">
                                <option value="">Select Incoterm</option>
                                @foreach ($incotermsOptions as $term)
                                    <option value="{{ $term }}">{{ $term }}</option>
                                @endforeach
                            </select>
                            @error('formData.term') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- Tab Content: Transport & Delivery --}}
                <div x-show="activeTab === 'transport'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Transport Type --}}
                        <div>
                            <label for="transport_type" class="block text-sm font-medium mb-1">Transport Type</label>
                            <select id="transport_type" wire:model="formData.transport_type"
                                    class="w-full rounded-md form-input">
                                <option value="">Select Transport Type</option>
                                @foreach($transportTypeOptions as $groupLabel => $options)
                                    <optgroup label="{{ $groupLabel }}">
                                        @foreach($options as $optionValue)
                                            <option value="{{ $optionValue }}">{{ $optionValue }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('formData.transport_type') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Transport Cost --}}
                        <div>
                            <label for="transport_cost" class="block text-sm font-medium mb-1">Transport Cost
                                (USD)</label>
                            <input type="number" id="transport_cost" wire:model.live="formData.transport_cost" wire:change="calculateTotalCost"
                                   step="0.01" class="w-full rounded-md form-input">
                            @error('formData.transport_cost') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Container Type --}}
                        <div>
                            <label for="container_type" class="block text-sm font-medium mb-1">Container Type</label>
                            <select id="container_type" wire:model="formData.container_type"
                                    class="w-full rounded-md form-input">
                                <option value="">Select Container Type</option>
                                @foreach($containerTypeOptions as $group => $types)
                                    <optgroup label="{{ $group }}">
                                        @foreach($types as $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('formData.container_type') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- THC Cost --}}
                        <div>
                            <label for="thc_cost" class="block text-sm font-medium mb-1">THC Cost (USD)</label>
                            <input type="number" id="thc_cost" wire:model.live="formData.thc_cost" step="0.01" wire:change="calculateTotalCost"
                                   class="w-full rounded-md form-input">
                            @error('formData.thc_cost') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Stuffing Cost --}}
                        <div>
                            <label for="stuffing_cost" class="block text-sm font-medium mb-1">Stuffing Cost
                                (USD)</label>
                            <input type="number" id="stuffing_cost" wire:model.live="formData.stuffing_cost" step="0.01" wire:change="calculateTotalCost"
                                   class="w-full rounded-md form-input">
                            @error('formData.stuffing_cost') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Ocean Freight --}}
                        <div>
                            <label for="ocean_freight" class="block text-sm font-medium mb-1">Ocean Freight
                                (USD)</label>
                            <input type="number" id="ocean_freight" wire:model.live="formData.ocean_freight" step="0.01" wire:change="calculateTotalCost"
                                   class="w-full rounded-md form-input">
                            @error('formData.ocean_freight') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- Tab Content: Pricing & Costs --}}
                <div x-show="activeTab === 'pricing'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Win Price --}}
                        <div>
                            <label for="win_price_usd" class="block text-sm font-medium mb-1">Win Price (USD)</label>
                            <input type="number" id="win_price_usd" wire:model.live="formData.win_price_usd" step="0.01"
                                   class="w-full rounded-md form-input">
                            @error('formData.win_price_usd') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Persol Price --}}
                        <div>
                            <label for="persol_price_usd" class="block text-sm font-medium mb-1">Persol Price
                                (USD)</label>
                            <input type="number" id="persol_price_usd" wire:model.live="formData.persol_price_usd"
                                   step="0.01" class="w-full rounded-md form-input">
                            @error('formData.persol_price_usd') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Price Difference --}}
                        <div>
                            <label for="price_difference" class="block text-sm font-medium mb-1">Price Difference
                                (USD)</label>
                            <input type="number" id="price_difference" wire:model="formData.price_difference"
                                   step="0.01" class="w-full rounded-md form-input" readonly>
                            @error('formData.price_difference') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- CFR China --}}
                        <div>
                            <label for="cfr_china" class="block text-sm font-medium mb-1">CFR China (USD)</label>
                            <input type="number" id="cfr_china" wire:model="formData.cfr_china" step="0.01"
                                   class="w-full rounded-md form-input">
                            @error('formData.cfr_china') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Total Cost --}}
                        <div>
                            <label for="total_cost" class="block text-sm font-medium mb-1">Total Cost (USD)</label>
                            <input type="number" id="total_cost" wire:model="formData.total_cost" step="0.01"
                                   class="w-full rounded-md form-input" readonly>
                            @error('formData.total_cost') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Exchange Rate --}}
                        <div>
                            <label for="exchange_rate" class="block text-sm font-medium mb-1">Exchange Rate</label>
                            <input type="number" id="exchange_rate" wire:model.live="formData.exchange_rate" wire:change="calculateTotalCost"
                                   step="0.0001" class="w-full rounded-md form-input">
                            @error('formData.exchange_rate') <span
                                class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Additional Costs Section --}}
                    <div class="mt-6 space-y-4">
                        <h4 class="font-semibold mb-2">Additional Costs</h4>
                        @foreach ($formData['additional_costs'] ?? [] as $index => $additionalCost)
                            <div class="flex space-x-4 items-center">
                                <div class="flex-grow">
                                    <label for="additional_cost_name_{{ $index }}"
                                           class="block text-sm font-medium mb-1">Cost Name</label>
                                    <input type="text" id="additional_cost_name_{{ $index }}"
                                           wire:model="formData.additional_costs.{{ $index }}.name"
                                           class="w-full rounded-md form-input">
                                    @error('formData.additional_costs.' . $index . '.name') <span
                                        class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div class="flex-grow">
                                    <label for="additional_cost_cost_{{ $index }}"
                                           class="block text-sm font-medium mb-1">Cost (USD)</label>
                                    <input type="number" id="additional_cost_cost_{{ $index }}" wire:change="calculateTotalCost"
                                           wire:model.live="formData.additional_costs.{{ $index }}.cost" step="0.01"
                                           class="w-full rounded-md form-input">
                                    @error('formData.additional_costs.' . $index . '.cost') <span
                                        class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <button type="button" wire:click="removeAdditionalCost({{ $index }})"
                                        class="btn-delete mt-5 self-start">
                                    <span class="material-icons-outlined">delete</span>
                                </button>
                            </div>
                        @endforeach

                        <button type="button" wire:click="addAdditionalCost"
                                class="btn-secondary inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            <span class="material-icons-outlined -ml-1 mr-2 text-base">add</span>
                            Add Additional Cost
                        </button>
                    </div>
                </div>

                {{-- Tab Content: Additional Info --}}
                <div x-show="activeTab === 'additional'" class="space-y-4">
                    {{-- Note Section --}}
                    <div>
                        <label for="note" class="block text-sm font-medium mb-1">Note</label>
                        <textarea id="note" wire:model="formData.note" rows="6"
                                  class="w-full rounded-md form-textarea"></textarea>
                        @error('formData.note') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Form Actions (remains outside the tabs) --}}
                <div class="p-4 mt-6 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                    <button type="submit" class="btn-save inline-flex items-center px-4 py-2">
                        <span class="material-icons-outlined text-sm align-middle mr-1">save</span>
                        Update
                    </button>

                    <button type="button" class="btn-secondary inline-flex items-center px-4 py-2"
                            wire:click="closeEditModal">
                        <span class="material-icons-outlined text-sm align-middle mr-1">close</span>
                        Close
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>


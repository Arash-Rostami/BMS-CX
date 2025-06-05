<div class="flex items-center gap-2 mb-5">
    <span class="insight text-2xl material-icons-outlined">priority_high</span>
    <span class="text-xl font-semibold">
        Only <i class="cursor-help" title="Cargo">Product</i> and<i class="cursor-help" title="Refinery">Supplier</i> fields are required. Others are optional.
    </span>
</div>

@if ($errors->any())
    <div class="error-box">
        <strong class="font-bold">Oops!</strong>
        <span class="block sm:inline">There were some problems with your input.</span>
        <ul class="mt-2 list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form wire:submit.prevent="saveCostCalculation" class="form-grid">
    <div>
        <label for="product_id" class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">shopping_cart</span>
            Product</label>
        <select id="product_id" wire:model="formData.product_id" class="form-input"
                wire:change="productSelected($event.target.value)">
            <option value="">Select Product</option>
            @foreach($formData['products'] as $product)
                <option
                    value="{{ $product->id }}">{{ $product->name  ?? $product->id }}</option>
            @endforeach
        </select>
        @error('formData.product_id') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="grade_id"
               class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">straighten</span>
            Grade</label>
        <select id="grade_id" wire:model="formData.grade_id" class="form-input">
            <option value="">Select ...</option>
            @foreach($formData['grades'] as $grade)
                <option
                    value="{{ $grade->id }}">{{ $grade->name  ?? $grade->id }}</option>
            @endforeach
        </select>
        @error('formData.grade_id') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="supplier_id" class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">business</span>
            Supplier</label>
        <select id="supplier_id" wire:model="formData.supplier_id" class="form-input">
            <option value="">Select ...</option>
            @foreach($formData['suppliers'] as $supplier)
                <option
                    value="{{ $supplier->id }}">{{ $supplier->name ?? $supplier->id }}</option>
            @endforeach
        </select>
        @error('formData.supplier_id') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="packaging_id" class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">inventory_2</span>
            Packaging</label>
        <select id="packaging_id" wire:model="formData.packaging_id" class="form-input">
            <option value="">Select ...</option>
            @foreach($formData['packaging'] as $package)
                <option
                    value="{{ $package->id }}">{{ $package->name ??  $packaging->id }}</option>
            @endforeach
        </select>
        @error('formData.packaging_id') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="term"
               class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">assignment</span>
            Term</label>
        <select type="text" id="term" wire:model="formData.term" class="form-input">
            <option value="">Select ...</option>
            @foreach($formData['incotermsOptions'] as $incoterm)
                <option value="{{ $incoterm }}">
                    {{ $incoterm }}
                </option>
            @endforeach
        </select>
        @error('formData.term') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="quantity" class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">view_module</span>
            Quantity
            (MT)</label>
        <input type="number" step="0.01" id="quantity" wire:model="formData.quantity"
               class="form-input">
        @error('formData.quantity') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="date"
               class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">event</span>
            Date</label>
        <input type="date" id="date" wire:model="formData.date" class="form-input">
        @error('formData.date') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="validity" class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">calendar_today</span>
            Validity</label>
        <input type="date" id="validity" wire:model="formData.validity" class="form-input">
        @error('formData.validity') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="transport_type" class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">local_shipping</span>
            Transport
            Type</label>
        <select id="transport_type" wire:model="formData.transport_type" class="form-input">
            <option value=""> Select ...</option>
            @foreach($formData['transportTypeOptions'] as $groupLabel => $options)
                <optgroup label="{{ $groupLabel }}">
                    @foreach($options as $optionValue)
                        <option value="{{ $optionValue }}">{{ $optionValue }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error('formData.transport_type') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="container_type" class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">all_inbox</span>
            Container Type</label>
        <select id="container_type" wire:model="formData.container_type" class="form-input">
            <option value="">Select ...</option>
            @foreach($formData['containerTypeOptions'] as $group => $types)
                <optgroup label="{{ $group }}">
                    @foreach($types as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error('formData.container_type') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="status"
               class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">check_circle_outline</span>
            Status</label>
        <select id="status" wire:model="formData.status" class="form-input">
            <option value=""> Select ...</option>
            @foreach($formData['statusOptions'] as  $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </select>
        @error('formData.status') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="tender_no" class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">credit_card</span>
            Tender No</label>
        <input type="text" id="tender_no" wire:model="formData.tender_no" class="form-input">
        @error('formData.tender_no') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="win_price_usd" class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">monetization_on</span>
            Win Price (USD)</label>
        <input type="number" step="0.01" id="win_price_usd" wire:model="formData.win_price_usd"
               class="form-input">
        @error('formData.win_price_usd') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="persol_price_usd"
               class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">attach_money</span>
            Persol Price (USD)</label>
        <input type="number" step="0.01" id="persol_price_usd" wire:model="formData.persol_price_usd"
               class="form-input">
        @error('formData.persol_price_usd') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="price_difference"
               class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">compare_arrows</span>
            Price Difference (USD)</label>
        <input type="number" step="0.01" id="price_difference" wire:model="formData.price_difference"
               class="form-input">
        @error('formData.price_difference') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="cfr_china" class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">location_on</span>
            CFR China</label>
        <input type="number" step="0.01" id="cfr_china" wire:model="formData.cfr_china"
               class="form-input">
        @error('formData.cfr_china') <span class="error-message">{{ $message }}</span> @enderror
    </div>


    {{-- Cost-related columns --}}

    <div>
        <label for="transport_cost" class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">commute</span>
            Transport Cost</label>
        <input type="number" step="0.01" id="transport_cost" wire:model="formData.transport_cost"
               wire:change="calculateTotalCost"
               class="form-input">
        @error('formData.transport_cost') <span class="error-message">{{ $message }}</span> @enderror
    </div>


    <div>
        <label for="thc_cost" class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">anchor</span>
            THC Cost</label>
        <input type="number" step="0.01" id="thc_cost" wire:model="formData.thc_cost"
               wire:change="calculateTotalCost"
               class="form-input">
        @error('formData.thc_cost') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="stuffing_cost" class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">inventory</span>
            Stuffing Cost</label>
        <input type="number" step="0.01" id="stuffing_cost" wire:model="formData.stuffing_cost"
               wire:change="calculateTotalCost"
               class="form-input">
        @error('formData.stuffing_cost') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="ocean_freight" class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">directions_boat</span>
            Ocean Freight Cost</label>
        <input type="number" step="0.01" id="ocean_freight" wire:model="formData.ocean_freight"
               wire:change="calculateTotalCost"
               class="form-input">
        @error('formData.ocean_freight') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="exchange_rate" class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">currency_exchange</span>
            Exchange Rate Cost</label>
        <input type="number" step="0.000001" id="exchange_rate" wire:model="formData.exchange_rate"
               wire:change="calculateTotalCost"
               class="form-input">
        @error('formData.exchange_rate') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    {{-- JSON Columns --}}
    <div class="form-span-2">
        <label class="block text-sm font-medium label-text mb-2">
            <span class="material-icons-outlined text-sm text-gray-500">control_point_duplicate</span>
            Additional Costs</label>
        @foreach ($formData['additional_costs'] as $index => $costItem)
            {{-- Display each cost item in a flex container --}}
            <div class="flex items-start space-x-2 mb-2">
                {{--  Cost Name --}}
                <div class="flex-grow">
                    <input type="text" wire:model="formData.additional_costs.{{ $index }}.name"
                           placeholder="Cost Name"
                           class="form-input w-full @error('formData.additional_costs.'.$index.'.name') border-red-500 @enderror">
                    @error('formData.additional_costs.'.$index.'.name') <span
                        class="error-message text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
                </div>
                {{--  Cost Amount --}}
                <div class="flex-grow">
                    <input type="number" step="0.01"
                           wire:model="formData.additional_costs.{{ $index }}.cost"
                           placeholder="Cost Amount" wire:change="calculateTotalCost"
                           class="form-input w-full @error('formData.additional_costs.'.$index.'.cost') border-red-500 @enderror">
                    @error('formData.additional_costs.'.$index.'.cost') <span
                        class="error-message text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
                </div>
                {{-- Button to remove this cost item --}}
                <button type="button" wire:click="removeAdditionalCost({{ $index }})"
                        class="p-3 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-600 focus:outline-none"
                        title="Remove Cost">
                    {{-- Assuming you have Material Icons setup --}}
                    <span class="material-icons-outlined">remove_circle_outline</span>
                </button>
            </div>

        @endforeach
        {{-- Button to add a new cost item --}}
        <button type="button" wire:click="addAdditionalCost" title="Add"
                class="mt-2 px-4 py-2 text-white rounded btn bg-blue-500 focus:outline-none mx-auto">
            <span class="material-icons-outlined mr-1">add_circle_outline</span>
        </button>
        @error('formData.additional_costs') <span
            class="error-message text-red-600 dark:text-red-400 mt-2">{{ $message }}</span> @enderror
    </div>

    <div title="read only; auto-calculated total amount">
        <label for="total_cost" class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">attach_money</span>
            Total Costs</label>
        <input type="number" step="0.01" id="total_cost"
               placeholder="Auto-calculated total amount" value="{{ $formData['total_cost'] }}"
               disabled readonly class="form-input cursor-not-allowed">
        @error('formData.total_cost') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div class="form-span-2">
        <label for="note"
               class="block text-sm font-medium label-text">
            <span class="material-icons-outlined text-sm text-gray-500">note_alt</span>
            Note</label>
        <textarea id="note" wire:model="formData.note" rows="3" class="form-textarea"
                  placeholder="Enter additional details, special instructions, or relevant information ..."></textarea>
        @error('formData.note') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div class="form-span-2 submit-button-container flex justify-end mt-6" title="Save">
        <button type="submit" class="submit-button flex items-center gap-2 ">
            <span class="material-icons-outlined">save</span>
        </button>
    </div>
</form>

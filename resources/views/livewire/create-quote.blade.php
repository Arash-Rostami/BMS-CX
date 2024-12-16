<div>
    @php
        $isSubmitted = session('quoteToken')['quote_id'] != null;
    @endphp
    @if (session()->has("message-{$attachmentId}"))
        <div class="alert alert-success">
            <div
                class="fixed top-0 left-0 w-full h-screen flex items-center justify-center z-50 bg-gray-200">
                <div class="max-w-md bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-4">
                        <h1 class="text-2xl font-bold tracking-wide text-left text-gray-600">
                            Thank you!
                        </h1>
                        <h3 class="ext-gray-600">
                            Your quote was submitted successfully. We will get back to you shortly.
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="min-h-screen p-6 bg-gray-100 flex items-center justify-center">
            <div class="container max-w-screen-lg mx-auto">
                <div>
                    @if(!$isSubmitted)
                        <h2 class="font-semibold text-xl text-gray-600">
                            Thank you, {{ $providerName }}, for giving us your time.
                        </h2>
                        <p class="text-gray-500 mb-6">
                            Feel free to include any external links or attach PDF/IMAGE files as needed.
                        </p>
                        {{--Questionnaire Box--}}
                        <div class="rounded shadow-lg p-4 px-4 md:p-8 mb-6 bg-gray-300 hover:bg-blue-200">
                            <div class="grid gap-4 gap-y-2 text-sm grid-cols-1 lg:grid-cols-3">
                                <div class="text-gray-600 mb-4">
                                    <div class="md:col-span-2">
                                        <p class="font-medium text-lg">Quote Details</p>
                                        <ol class="text-small" id="form-instructions">
                                            <li>Please fill out all the necessary fields.</li>
                                            <li>For POL, POD, and containers, you may offer other choices.</li>
                                        </ol>
                                    </div>
                                </div>
                                {{--Questionnaire--}}
                                <div class="lg:col-span-2">
                                    <form wire:submit.prevent="submit">
                                        <div class="grid gap-4 gap-y-2 text-sm grid-cols-1 md:grid-cols-5">
                                            {{--POL--}}
                                            <div class="md:col-span-2">
                                                <label for="origin-port" class="form-label">Port of Loading
                                                    *</label>
                                                <select wire:model="originPort" id="origin-port"
                                                        class="form-select h-10 border mt-1 rounded px-4 w-full bg-gray-50"
                                                        required>
                                                    <option value="">Select an option</option>
                                                    @foreach ($iranianPorts as $port)
                                                        <option value="{{ $port }}"
                                                                @if($port == $originPort) selected @endif>
                                                            {{ $port }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('originPort') <span
                                                    class="text-red-600">* {{ $message }}</span> @enderror
                                            </div>
                                            {{--POD--}}
                                            <div class="md:col-span-2">
                                                <label for="destination-port"
                                                       class="form-label">Port of Discharge *</label>
                                                <select wire:model="destinationPort" id="destination-port"
                                                        class="form-select form-select h-10 border mt-1 rounded px-4 w-full bg-gray-50"
                                                        required>
                                                    <option value="">Select an option</option>
                                                    @foreach ($chinesePorts as  $port)
                                                        <option
                                                            value="{{ $port }}"
                                                            @if($port == $destinationPort) selected @endif>
                                                            {{ $port }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('destinationPort') <span
                                                    class="text-red-600">* {{ $message }}</span> @enderror
                                            </div>
                                            {{--Commodity--}}
                                            <div class="md:col-span-2">
                                                <label for="commodity" class="form-label ">Commodity *</label>
                                                <select wire:model="commodity" type="text" id="commodity" disabled
                                                        required
                                                        class="form-control h-10 border mt-1 rounded px-4 w-full bg-gray-50">
                                                    <option value="">Select an option</option>
                                                    @foreach ($productOptions as  $key => $option)
                                                        <option
                                                            value="{{ $key }}"
                                                            @if($key == $commodity) selected @endif>
                                                            {{ $option }} | Quant: {{ $quantity }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('commodity') <span
                                                    class="text-red-600">* {{ $message }}</span> @enderror
                                            </div>
                                            {{--Packaging--}}
                                            <div class="md:col-span-2">
                                                <label for="packing" class="form-label">Packaging *</label>
                                                <select wire:model="packing" id="packing" disabled
                                                        class="form-select form-select h-10 border mt-1 rounded px-4 w-full bg-gray-50">
                                                    <option value="">Select an option</option>
                                                    @foreach ($packagingOptions as $key => $option)
                                                        <option
                                                            value="{{ $key }}"
                                                            @if($key == $packing) selected @endif>
                                                            {{ $option }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('packing') <span
                                                    class="text-red-600">* {{ $message }}</span> @enderror
                                            </div>
                                            {{--Container type--}}
                                            <div class="md:col-span-2">
                                                <label for="container-type" class="form-label">Container Type</label>
                                                <select
                                                    wire:model="containerType"
                                                    id="container-type"
                                                    class="form-control h-10 border mt-1 rounded px-4 bg-gray-50 w-full"
                                                    required>
                                                    <option value="">Select a container type</option>
                                                    @foreach ($containerTypeOptions as $key => $option)
                                                        <option value="{{ $key }}"
                                                                @if($key == $containerType) selected @endif>
                                                            {{ $option }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('containerType')
                                                <span class="text-red-600">* {{ $message }}</span>
                                                @enderror
                                            </div>
                                            {{--Container No--}}
                                            <div class="md:col-span-2">
                                                <label for="container-number" class="form-label">Container No.</label>
                                                <input
                                                    wire:model="containerNumber"
                                                    type="text"
                                                    id="container-number"
                                                    class="form-control h-10 border mt-1 rounded px-4 bg-gray-50 w-full"
                                                    placeholder="Enter number (optional)">
                                                @error('containerNumber') <span
                                                    class="text-red-600">* {{ $message }}</span> @enderror
                                            </div>

                                            {{--Divider--}}
                                            <div class="md:col-span-5 border-t-2 border-dotted mt-6 p-2"></div>
                                            {{--Free Time--}}
                                            <div class="md:col-span-2">
                                                <label for="free-time"
                                                       class="form-label">Free Time @POL</label>
                                                <input wire:model="freeTime" type="number" id="free-time"
                                                       class="form-control form-select h-10 border mt-1 rounded px-4 w-full bg-gray-50">
                                                @error('freeTime') <span
                                                    class="text-red-600">* {{ $message }}</span> @enderror
                                            </div>
                                            {{--Free Time POD--}}
                                            <div class="md:col-span-2">
                                                <label for="free-time"
                                                       class="form-label">Free Time @POD</label>
                                                <input wire:model="freeTimePOD" type="number" id="free-time-pod"
                                                       class="form-control form-select h-10 border mt-1 rounded px-4 w-full bg-gray-50">
                                                @error('freeTimePOD') <span
                                                    class="text-red-600">* {{ $message }}</span> @enderror
                                            </div>
                                            {{--Offered Rate--}}
                                            <div class="md:col-span-2">
                                                <label for="offered-rate">Offered Rate</label>
                                                <input wire:model="offeredRate" type="text" id="offered-rate"
                                                       class="form-control h-10 border mt-1 rounded px-4 w-full bg-gray-50">
                                                @error('offeredRate') <span
                                                    class="text-red-600">* {{ $message }}</span> @enderror
                                            </div>
                                            {{--Local Charges--}}
                                            <div class="md:col-span-2">
                                                <label for="switch-bl">Local Charges</label>
                                                <input wire:model="localCharges" type="text" id="local-charges"
                                                       class="form-control h-10 border mt-1 rounded px-4 w-full bg-gray-50">
                                                @error('localCharges') <span
                                                    class="text-red-600">* {{ $message }}</span> @enderror
                                            </div>
                                            {{--Switch BL--}}
                                            <div class="md:col-span-2">
                                                <label for="switch-bl">Switch BL Fees</label>
                                                <input wire:model="switchBL" type="text" id="switch-bl"
                                                       class="form-control h-10 border mt-1 rounded px-4 w-full bg-gray-50">
                                                @error('switchBL') <span
                                                    class="text-red-600">* {{ $message }}</span> @enderror
                                            </div>
                                            {{--IMCO--}}
                                            <div class="md:col-span-2">
                                                <label for="imco">IMCO</label>
                                                <input wire:model="imco" type="text" id="imco" placeholder="optional"
                                                       class="form-control h-10 border mt-1 rounded px-4 w-full bg-gray-50">
                                                @error('imco') <span
                                                    class="text-red-600">* {{ $message }}</span> @enderror
                                            </div>
                                            {{--Validity--}}
                                            <div class="md:col-span-2">
                                                <label for="validity">Validity *</label>
                                                <input wire:model="validity" type="date" id="validity" required
                                                       class="form-control h-10 border mt-1 rounded px-4 w-full bg-gray-50">
                                                @error('validity') <span
                                                    class="text-red-600">* {{ $message }}</span> @enderror
                                            </div>
                                            {{--Attachment--}}
                                            <div class="md:col-span-2">
                                                <label for="attachment">Attachment (Max: 10MB)</label>
                                                <input wire:model="attachment" type="file" id="attachment"
                                                       accept="image/*,.pdf"
                                                       class="form-control h-10 border mt-1 rounded px-4 w-full bg-gray-50">
                                                <div wire:loading wire:target="attachment">🔁 Uploading...</div>

                                                @error('attachment') <span
                                                    class="text-red-600">* {{ $message }}</span> @enderror
                                            </div>
                                            {{--Extra--}}
                                            <div class="md:col-span-2">
                                                <label for="extra" >Extra/Link</label>
                                                <textarea
                                                    wire:model="extra"
                                                    id="extra"
                                                    placeholder="Paste any link or comment you want"
                                                    class="h-14 border mt-1 rounded px-4 bg-gray-50 w-full"
                                                ></textarea>
                                                @error('extra') <span class="text-red-600">* {{ $message }}</span> @enderror
                                            </div>
                                            {{--Submit Button--}}
                                            <div class="md:col-span-2 text-center flex justify-center items-center relative md:top-2">
                                                @unless($isSubmitted)
                                                        <button
                                                            class="bg-blue-500 hover:bg-blue-700 md:w-full
                                                            text-white font-bold py-2 px-4 rounded">
                                                            Submit
                                                        </button>
                                                @endunless
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <h2 class="font-light text-xl text-gray-600 mx-auto items-center justify-center">
                            Thank you for giving us your quote. <br>
                            We will certainly get back to you for our decision or the final outcome.
                        </h2>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>


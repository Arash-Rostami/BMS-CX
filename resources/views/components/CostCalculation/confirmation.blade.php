<div
    x-data="{
    showDeleteModal: false,
    itemToDelete: null,
    open(id){ this.itemToDelete = id; this.showDeleteModal = true },
    confirm(){ $dispatch('confirmDeleteCostCalculation',{id:this.itemToDelete}); this.close() },
    close(){ this.showDeleteModal = false; this.itemToDelete = null }
  }"
    @open-delete-confirmation.window="open($event.detail.id)"
>
    <div
        x-show="showDeleteModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 flex items-center justify-center z-50 overflow-auto"
        style="display: none;"
    >
        <div class="relative content-wrapper rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-screen overflow-y-auto bg-white dark:bg-gray-800">

            <!-- Body -->
            <div class="p-6">
                <div class="flex items-start space-x-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
                        <span class="material-icons-outlined text-red-600 dark:text-red-300">warning</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                            Delete Cost Calculation
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Are you sure you want to delete this cost calculation? This action cannot be undone.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                <button
                    @click="confirm()"
                    class="inline-flex justify-center rounded-md btn-delete px-4 py-2 text-white font-medium shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                >
                    Delete
                </button>
                <button
                    @click="close()"
                    class="inline-flex justify-center rounded-md dark:border-gray-500 px-4 py-2 shadow-lg
                    font-medium hover:text-gray-500  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

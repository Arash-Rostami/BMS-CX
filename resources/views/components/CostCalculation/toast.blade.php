<div id="toast-notification"
     x-data="{ show: false, message: '', type: '' }"
     x-init="
         $watch('show', value => {
             if (value && type === 'success') {
                 setTimeout(() => show = false, 3000);
             }
         });
         window.addEventListener('notify-success', e => {
             message = e.detail.message; type = 'success'; show = true;
         });
         window.addEventListener('notify-error', e => {
             message = e.detail.message; type = 'error'; show = true;
         });
     "
     x-show="show"
     :class="{
         'bg-green-500': type === 'success',
         'bg-red-500': type === 'error',
         'flex items-center justify-between': true // Use flexbox for better alignment
     }"
     class="p-4 rounded shadow text-white z-[9999] min-w-[300px] text-center transition-all duration-300 ease-in-out"
     style="display: none;"
>
    <div class="flex items-center">
        <i class="material-icons-outlined align-middle mr-2"
           x-text="type === 'success' ? 'check_circle' : (type === 'error' ? 'error' : '')">
        </i>
        <span x-text="message"></span>
    </div>

    <button x-show="type === 'error'"
            @click="show = false"
            class="ml-4 text-lg font-bold leading-none hover:text-black focus:outline-none"
            type="button"
            aria-label="Close"
    >&times;
    </button>
</div>

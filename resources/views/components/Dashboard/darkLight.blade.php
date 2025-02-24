<button @click="darkMode = !darkMode" class="flex items-center space-x-2 px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 dark:bg-gray-700 dark:hover:bg-gray-600 focus:outline-none transition-all duration-300">
    <i :class="darkMode ?  'fas fa-sun mr-2' :'fas fa-moon mr-2'"></i>
    <span x-text="darkMode ? 'Light Mode' : 'Dark Mode'"></span>
</button>

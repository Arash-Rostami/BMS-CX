<?php

namespace App\Services;

use Illuminate\Support\Arr;

class WelcomeMessage
{
    private array $messages = [
        'early_morning' => [
            "Rise and shine, early bird! 🐦☀️",
            "Good morning! Ready to seize the day? 🌅💪",
            "Top of the morning to you! Let's get started. 🌞🚀",
            "Wakey wakey! A new day awaits. 🌄✨",
            "Hello! Hope your morning is as bright as you are. 😊🌟",
            "Good daybreak! Let's make today amazing. 🌞🌼",
            "Greetings! May your morning be productive and pleasant. 📝🌻",
            "Morning! Here's to a day full of opportunities. 🌅🌈",
            "Hey there! Let's kickstart this beautiful morning. ☕🌺",
            "Salutations! Wishing you a splendid early start. 🌞🌿",
            "A fresh start to a wonderful day! ☕📖",
            "The early bird catches the worm! Have a great morning. 🐛🌞",
            "Hello, sunshine! Time to shine! ✨🌻",
            "May your coffee be strong and your day be productive! ☕💪",
            "A beautiful morning to make memories! 🌅📸",
            "Good morning! Let's make today count. 💯🌞",
            "Start your day with a smile! 😊🌅",
            "Wishing you a morning filled with joy and inspiration. 💖✨",
            "Hello! Hope your day is filled with positive vibes. ☀️😊",
            "A new day, a new beginning! Let's do this. 💪🚀",
        ],
        'late_morning' => [
            "Hope your day is off to a great start! 🌞👍",
            "Good mid-morning! Keep up the great work. 💼🌟",
            "Hello! Hope your morning is going smoothly. 😊🕊️",
            "Wishing you a productive late morning! 📈☕",
            "Hi there! Hope your morning has been wonderful. 🌸🌞",
            "Greetings! Keep shining this morning. ✨🌼",
            "Good day! May your morning be filled with success. 🏆🌿",
            "Hello! Keep up the fantastic morning energy. 💪🌅",
            "Hey! Hope your morning is as lovely as you are. 😊🌷",
            "Salutations! Wishing you continued success this morning. 🌟📊",
            "Almost lunchtime! Keep pushing! ⏰💪",
            "Nearing midday, keep up the momentum! 🚀📈",
            "A little more effort and you'll conquer the morning! 🏆🌟",
            "Hello! Hope the morning is treating you well. 😊☕",
            "Just a few more hours until lunch! You've got this. 💯👍",
            "Keep your spirits high, the afternoon is almost here! ☀️😊",
            "Hello! Hope you've had a productive morning so far. 📝🌟",
            "Wishing you continued success as the morning progresses. 📈🌿",
            "Hi there! Hope your morning is filled with positive energy. ✨🌸",
            "Greetings! Keep shining as the morning draws to a close. 🌟🌼",
        ],
        'afternoon' => [
            "Hello, afternoon delight! 🌤️😊",
            "Good afternoon! Hope your day is going well. ☀️👍",
            "Hi there! Enjoy your afternoon. 🍵🌺",
            "Greetings! Wishing you a pleasant afternoon. 🌼📚",
            "Hey! Hope your afternoon is productive and positive. 💼🌟",
            "Good day! May your afternoon be filled with joy. 😊🌈",
            "Hello! Keep up the great work this afternoon. 💪📈",
            "Salutations! Wishing you a wonderful afternoon. 🌷☕",
            "Hi! Hope your afternoon is as bright as your smile. 😃🌞",
            "Good afternoon! Here's to a successful rest of the day. 🏆🌿",
            "Time for a midday boost! How's your afternoon going? ☕😊",
            "Hello! Hope you're having a refreshing afternoon. 🍃☀️",
            "Enjoy the sunshine and the rest of your day! ☀️😎",
            "Greetings! May your afternoon be filled with inspiration. 🌟🎨",
            "Hey! Hope you're making the most of your afternoon. 👍💼",
            "Good afternoon! Here's to a productive and enjoyable time. 📈😊",
            "Hello! Keep that positive energy flowing this afternoon. ✨💪",
            "Salutations! Wishing you a relaxing and fulfilling afternoon. 😌🌸",
            "Hi! Hope you're having a fantastic afternoon so far. 🎉☀️",
            "Good afternoon! Here's to a great second half of the day. 🏆🌿",
        ],
        'evening' => [
            "Welcome, evening star! ⭐🌙",
            "Good evening! Hope you had a great day. 🌆😊",
            "Hello! Enjoy your evening. 🍷🌇",
            "Greetings! Wishing you a peaceful evening. 🌙🕊️",
            "Hey there! Relax and unwind this evening. 🛋️✨",
            "Good evening! May your night be restful. 🌃😴",
            "Hi! Hope your evening is enjoyable. 😊🍂",
            "Salutations! Wishing you a lovely evening. 🌇🌸",
            "Hello! Take some time to relax this evening. 🧘‍♂️🌜",
            "Good evening! Here's to a calm and pleasant night. 🌙🌿",
            "Time to wind down and reflect on the day. 🌃🧘‍♀️",
            "Hello! Hope you have a relaxing evening ahead. 😌🌙",
            "Enjoy the peacefulness of the evening. 🌌🕊️",
            "Greetings! May your evening be filled with tranquility. ✨🕯️",
            "Hey there! Put your feet up and enjoy the evening. 🛋️🌙",
            "Good evening! Here's to a restful and rejuvenating night. 😴⭐",
            "Hi! Hope you have a wonderful and enjoyable evening. 😊🌃",
            "Salutations! Wishing you a calm and serene evening. 🌿🌙",
            "Hello! Take a moment to appreciate the beauty of the evening. 🌆✨",
            "Good evening! Here's to a peaceful and pleasant night. 🌙🌸",
        ],
        'night' => [
            "Good to see you, night owl! 🌙🦉",
            "Hello! Hope you're having a restful night. 😴✨",
            "Greetings! Wishing you a peaceful night. 🌌🕯️",
            "Hey there! Sleep well and sweet dreams. 🌠💤",
            "Good night! May your rest be rejuvenating. 🌜🌟",
            "Hi! Hope your night is calm and serene. 🌃🌿",
            "Salutations! Wishing you a restful slumber. 😴🌙",
            "Good night! Take care and sleep tight. 🛌✨",
            "Hello! May your night be filled with tranquility. 🌌🕊️",
            "Good to see you! Wishing you a soothing night. 🌙🌸",
            "Time for some well-deserved rest. 😴🛌",
            "Hello, night owl! Hope you're having a peaceful night. 🦉🌙",
            "Enjoy the quiet and stillness of the night. 🌃✨",
            "Greetings! May your dreams be sweet and your sleep be sound. 😴🌌",
            "Hey there! Time to recharge for a new day. 💤🌙",
            "Good night! Here's to a restful and restorative sleep. 🛌🌟",
            "Hi! Hope you have a calm and tranquil night. 🌃🌿",
            "Salutations! Wishing you a peaceful and undisturbed slumber. 🌙🕊️",
            "Hello! May your night be filled with pleasant dreams. 😴🌸",
            "Good night! Here's to a deep and refreshing sleep. 🛌🌙",
        ],
        'default' => [
            "Greetings! Let’s have an awesome time together. 🎉😊",
            "Hello! Welcome aboard. 🚢🌟",
            "Hi there! Glad to have you here. 🤗🌼",
            "Welcome! Let's make it a great experience. 🌟👍",
            "Hey! Happy to see you. 😊🎈",
            "Salutations! Let's get started. 🚀✨",
            "Good to see you! Let's have fun. 🎉😃",
            "Hello! Excited to have you with us. 🤩🌺",
            "Hi! Let's make today amazing. 🌞💪",
            "Welcome! Looking forward to our time together. 🌟🤝"
        ]
    ];

    public function generate(int $currentHour = null): string
    {
        $currentHour ??= now()->hour;

        $period = match (true) {
            $currentHour >= 6 && $currentHour < 9 => 'early_morning',
            $currentHour >= 9 && $currentHour < 12 => 'late_morning',
            $currentHour >= 12 && $currentHour < 18 => 'afternoon',
            $currentHour >= 18 && $currentHour < 24 => 'evening',
            $currentHour >= 0 && $currentHour < 6 => 'night',
            default => 'default',
        };

        return Arr::random($this->messages[$period] ?? $this->messages['default']);
    }
}

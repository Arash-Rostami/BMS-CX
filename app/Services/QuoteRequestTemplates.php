<?php

namespace App\Services;

class QuoteRequestTemplates
{

    protected static array $openingLine = [
        0 => "I trust this email finds you in good health. I'm reaching out to inquire about certain details.",
        1 => " I hope this email finds you in excellent spirits. I'm contacting you to gather some information.",
        2 => "I hope this message finds you well. I am reaching out to request specific details.",
        3 => "I trust you're doing well. I'm writing to request some information.",
        4 => "I hope this email finds you very well. I am contacting you to obtain some information.",
        5 => "I hope you're doing well. I'm reaching out to request some information.",
        6 => "I trust this email finds you in good health. I'm writing to inquire about certain details.",
        7 => " I hope this message finds you well. I'm contacting you to gather some information.",
        8 => "I hope this email finds you well. I'm reaching out to request specific details.",
        9 => "I trust you're doing well. I am writing to request some information.",
        10 => "I hope this email finds you in great health. I am contacting you to seek some information."
    ];

    protected static array $questionLine = [
        0 => 'I would be delighted if you could assist us with our quote request for the following:',
        1 => 'I kindly request your assistance with our quote request for the following:',
        2 => 'It would be greatly appreciated if you could help us out with a quote request for the following:',
        3 => 'Would you be able to provide a quote for the following? We appreciate your time and assistance.',
        4 => 'To assist with our project, we would be grateful if you could offer a quote for the following:',
        5 => 'I have a request for a quote, and I would be happy if you could help us with the following:',
        6 => 'In need of a quote! Could you please assist us with the following?',
        7 => 'We are reaching out to request a quote for the following. Please let me know if you can help.',
        8 => 'Hoping to get a quote for the following. Your assistance is much appreciated!',
        9 => 'This request requires a quote. I would be thankful if you could consider the following:',
        10 => 'For our ongoing project, I need a quote. If possible, could you provide a quote for the following:',
    ];

    protected static array $invitationLine = [
        0 => "To simplify the process of collecting information, we've made a form on our website for your convenience. Kindly click the button below to fill out the form ",
        1 => "In order to make gathering information easier, we've created a form on our website where you can simply provide the necessary details. Please use the button below to access the form ",
        2 => "To streamline the process of getting information, we've made a form on our website for your convenience. Simply click the button below to access the form ",
        3 => "To facilitate the information gathering process, we've set up a form on our website where you can easily submit the required details. Please click the button below to access the form ",
        4 => "To simplify the process of gathering information, we've created a form on our website for your convenience. Please use the button below to access the form ",
        5 => "In order to make gathering information more efficient, we've made available a form on our website where you can easily submit the requested details. Kindly click the button below to access it ",
        6 => "To ease the process of collecting information, we've prepared a form on our website for your convenience. Please use the button below to fill out the form ",
        7 => "In order to make gathering information simpler, we've put together a form on our website where you can provide the necessary details. Please click the button below to access the form ",
        8 => "To facilitate the information gathering process, we've created a form on our website where you can easily submit the required details. Kindly click the button below to access the form ",
        9 => "To simplify the process of obtaining information, we've set up a form on our website for your convenience. Please use the button below to access the form ",
        10 => "In order to make gathering information more efficient, we've established a form on our website where you can effortlessly provide the necessary details. Please click the button below to access the form "
    ];

    protected static array $fallBackLine = [
        0 => "If you encounter any difficulties or have any inquiries, please feel free to contact me directly at",
        1 => "If you come across any problems or have any questions, please don't hesitate to reach out to me directly at ",
        2 => "In case you face any issues or have any questions, please don't hesitate to contact me directly at ",
        3 => "Should you have any difficulties or questions, please don't hesitate to reach out to me directly at ",
        4 => "If you need any assistance or have any questions, please feel free to contact me directly at ",
        5 => "If there are any issues or questions that arise, please don't hesitate to reach out to me directly at ",
        6 => "Should you face any problems or have any questions, please don't hesitate to contact me directly at ",
        7 => "If you face any challenges or have any questions, please feel free to reach out to me directly at ",
        8 => "In case you have any issues or questions, please don't hesitate to reach out to me at ",
        9 => "Should you meet any difficulties or have any questions, please don't hesitate to reach out to me at ",
        10 => "If you run into any problems or any questions, please don't hesitate to contact me at "
    ];

    protected static array $appreciationLine = [
        0 => "Your attention to this matter is greatly appreciated.",
        1 => "Thank you.",
        2 => "I appreciate your cooperation in addressing this matter.",
        3 => "Your attention to this matter is highly valued.",
        4 => "Thank you for your attention to this matter.",
        5 => "I would like to express my gratitude for your attention to this matter.",
        6 => "Thank you for taking the time to address this matter.",
        7 => "Your assistance with this matter is greatly appreciated.",
        8 => "I extend my thanks for your attention to this matter.",
        9 => "Thank you for your prompt attention.",
        10 => "Your efforts in addressing this matter are appreciated."
    ];


    protected static array $endingLine = [
        0 => "Best regards,",
        1 => "Warm regards,",
        2 => "Kind regards,",
        3 => "Sincerely,",
        4 => "Yours sincerely,",
        5 => "With gratitude,",
        6 => "With appreciation,",
        7 => "Respectfully,",
        8 => "Cordially,",
        9 => "Regards,",
    ];

    public static function getOpeningLine(): string
    {
        $index = array_rand(self::$openingLine);
        return self::$openingLine[$index];
    }

    public static function getInvitationLine(): string
    {
        $index = array_rand(self::$invitationLine);
        return self::$invitationLine[$index];
    }

    public static function getFallBackLine(): string
    {
        $index = array_rand(self::$fallBackLine);
        return self::$fallBackLine[$index];
    }

    public static function getAppreciationLine(): string
    {
        $index = array_rand(self::$appreciationLine);
        return self::$appreciationLine[$index];
    }

    public static function getEndingLine(): string
    {
        $index = array_rand(self::$endingLine);
        return self::$endingLine[$index];
    }

    public static function getQuestionLine(): string
    {
        $index = array_rand(self::$questionLine);
        return self::$questionLine[$index];
    }
}

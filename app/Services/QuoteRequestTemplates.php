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
        10 => "I hope this email finds you in great health. I am contacting you to seek some information.",
        11 => "I wanted to reach out to you with a quick request for assistance.",
        12 => "I’m writing to you today to ask for your help with some specific details.",
        13 => "I hope this note comes to you at a good time. I have a request I’d like to discuss.",
        14 => "I am reaching out to gather some information that you might be able to provide.",
        15 => "I wanted to connect with you regarding some information that’s needed.",
        16 => "I hope your day is going smoothly. I’m reaching out with a specific request.",
        17 => "I’d appreciate a moment of your time to assist with a small inquiry.",
        18 => "I’m contacting you to follow up on some information we’re hoping you can provide.",
        19 => "I hope everything is going well on your end. I’d like to ask for your input.",
        20 => "I’d like to take this opportunity to request your assistance with a matter."
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
        11 => "Could you please review the details below and provide us with a quote at your earliest convenience?",
        12 => "We’d greatly value your quote based on the following information.",
        13 => "Please take a moment to review the following and let us know if you can prepare a quote.",
        14 => "Would you mind sharing a quote with us for the details outlined below?",
        15 => "We’re requesting a quote for the specifics mentioned below and would appreciate your response.",
        16 => "When you have a moment, could you kindly provide a quote for the following details?",
        17 => "We’re hoping you can help us with a quote for the requirements listed below:",
        18 => "Could you take a look at the following information and assist us by preparing a quote?",
        19 => "Please let us know if you’re able to provide a quote for the specifics outlined below:",
        20 => "Your assistance in preparing a quote for the following would be very helpful."
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
        10 => "In order to make gathering information more efficient, we've established a form on our website where you can effortlessly provide the necessary details. Please click the button below to access the form ",
        11 => "We’ve provided a quick and easy form on our website to collect the required details. Please click below to access it ",
        12 => "To save time, we’ve prepared an online form where you can submit the necessary information. Access it using the button below ",
        13 => "You can conveniently provide the details we need by using the online form linked below ",
        14 => "For your convenience, an online form is available where you can enter the requested information. Click the button below to proceed ",
        15 => "We’ve created an easy-to-use form for gathering details. Simply click the button below to access it and provide your input ",
        16 => "To ensure a smooth process, please use the online form linked below to submit the information we’re requesting ",
        17 => "Submitting the required details is simple. Just click the button below to access our secure online form ",
        18 => "You can provide the necessary details by filling out the form on our website. Please use the button below to get started ",
        19 => "To make things easier for you, we’ve set up a straightforward form. Click below to provide the requested details ",
        20 => "We’ve streamlined the information collection process with a web form. Use the button below to quickly submit your details "
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
        10 => "If you run into any problems or any questions, please don't hesitate to contact me at ",
        11 => "If you need further clarification or encounter any trouble, don’t hesitate to contact me directly at ",
        12 => "Feel free to reach out to me directly if you have any doubts or face any challenges at ",
        13 => "Should you require any additional information or help, I’m available to assist at ",
        14 => "In case of any confusion or problem, you can always contact me directly at ",
        15 => "If there’s anything you need help with, please reach out to me without hesitation at ",
        16 => "Don’t hesitate to get in touch with me directly if you have any concerns or questions at ",
        17 => "If you have any uncertainties or require assistance, please contact me directly at ",
        18 => "Should you find anything unclear or need support, I’m just a message away at ",
        19 => "For any queries or additional assistance, feel free to get in touch with me directly at ",
        20 => "If anything is unclear or you run into difficulties, you can reach me directly at "
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
        10 => "Your efforts in addressing this matter are appreciated.",
        11 => "I am truly grateful for your time and effort on this matter.",
        12 => "Many thanks for your kind support and collaboration.",
        13 => "I deeply value the assistance you’ve provided.",
        14 => "Your support is sincerely appreciated.",
        15 => "I will be very much thankful for your quick and complete response.",
        16 => "Please accept my thanks for your valuable help.",
        17 => "I really admire your attention and support in addressing this matter.",
        18 => "Your input and effort mean a great deal to us.",
        19 => "We are really thankful for your assistance and cooperation.",
        20 => "I want to express my real gratitude for your response and help."
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

    public static array $emailBody = [
        'Concise' =>
            "
        <p>I am writing to request a quote for [brief description of what you need]. Please provide us with pricing and lead times. Details are below:</p>
        <p>[Space for details: Specifications, quantities, deadlines, etc.]</p>
        <p>Your prompt reply will be appreciated.</p>",
        'Detailed' =>
            "
        <p>Our company, Persol, is seeking a quote for [Project/Service]. We require [specific requirements, e.g., quantity, materials, etc.]. Please find the detailed specifications below:</p>
        <p>[Space for details: Detailed specifications, delivery requirements, etc.]</p>
        <p>We look forward to receiving your quote.</p>",
        'Formal' =>
            "
        <p>This letter serves as a formal request for a quote for the following: [Project/Service]. The detailed specifications are outlined below:</p>
        <p>[Space for details: Detailed specifications, terms and conditions, etc.]</p>
        <p>Please submit your quote by [Date].</p>",
        'Urgent' =>
            "
        <p>We, Persol Company, urgently require a quote for [Project/Service]. We need to make a decision by [Date], so a prompt response would be greatly appreciated. The details are as follows:</p>
        <p>[Space for details: Key specifications and deadlines]</p>
        <p>Your cooperation as well as input is appreciated.</p>",
        'Simple' =>
            "
        <p>Please provide a quote for the following:</p>
        <p>[Space for details]</p>
        <p>I will be more than happy to receive your quote and soon begin working with you.</p>",
        'With Introduction' =>
            "
        <p>I am contacting you regarding [brief context, e.g., a project we are undertaking]. I require a quote for the following:</p>
        <p>[Space for details]</p>
        <p>I look forward to your response.</p>",
        'Specific Requirements' =>
            "
        <p>I am requesting a quote for [Product/Service] with the following specific requirement: [Very specific requirement, e.g., custom size, specific material,etc.].</p>
        <p>[Space for details]</p>
        <p>Please let me know if you can fulfill this request.</p>",
        'Multiple Items' =>
            "
        <p>I require quotes for the following items:</p>
        <p>[Space for details: List of items with quantities and specifications]</p>
        <p>Please provide a breakdown of pricing for each item.</p>",
        'Following Up on Previous Contact' =>
            "
        <p>I am following up on our previous communication regarding a quote for [Project/Service]. I would appreciate it if you could provide us with an update on the status of our request.</p>
        <p>[Space for details - optional: Briefly reiterate key details]</p>
        <p>I am looking forward to collaborating with you/your company.</p>",
        'Request for Quotation (RFQ)' =>
            "
        <p>Please find attached my Request for Quotation (RFQ) document for [Project/Service]. Please submit your quotation by [Date].</p>
        <p>[Space for details - Optional: Brief summary, instructions for submission]</p>
        <p>I look forward to receiving your quotation.</p>",
        'Formal with Timeline' =>
            "
        <p>We, Persol Company, kindly request a quote for [Project/Service] and ask that you include a clear timeline for delivery. Please consider the specifications below:</p>
        <p>[Space for details: Timeline, materials, required completion date]</p>
        <p>Your prompt response is very much needed and greatly appreciated.</p>",
        'Technical Specifications' =>
            "
        <p>I am seeking a quote for [Project/Service] with highly technical specifications. Please review the requirements below and provide your best possible quote:</p>
        <p>[Space for details: Technical metrics, quality standards, certifications]</p>
        <p>Your detailed evaluation is appreciated.</p>",
        'Comparative Request' =>
            "
        <p>I am exploring options and kindly request a quote for [Project/Service]. I would be thankful if you could highlight any additional services you offer too:</p>
        <p>[Space for details: Competitive pricing, unique services]</p>
        <p>I am eagerly waiting for your quote.</p>",
        'Budget-Focused' =>
            "
        <p>We, Persol Company, are working within a set budget and request a quote for [Project/Service] that reflects competitive pricing without compromising quality:</p>
        <p>[Space for details: Budget constraints, cost breakdown]</p>
        <p>We look forward to reviewing your offer.</p>",
        'Comprehensive Breakdown' =>
            "
        <p>Please provide a comprehensive quote for [Project/Service], including itemized costs, optional services, and any applicable discounts:</p>
        <p>[Space for details: Line items, support packages]</p>
        <p>Your thoroughness will help us evaluate the best fit.</p>",
        'Partnership Inquiry' =>
            "
        <p>I am  considering a short/mid/long-term partnership and request a quote for [Project/Service] that reflects pricing for both immediate needs and ongoing collaboration:</p>
        <p>[Space for details: Recurring orders, volume discounts]</p>
        <p>I look forward to potentially working together.</p>",
        'Seasonal Offer' =>
            "
        <p>I am preparing for seasonal demand and request a quote for [Project/Service] that meets our increased requirements during [Season/Period]:</p>
        <p>[Space for details: Seasonal volumes, timelines]</p>
        <p>Your timely response will assist in our planning.</p>",
        'After Initial Estimate' =>
            "
        <p>I am writing to ask for your initial estimate. I would like to request a formal quote that confirms pricing and delivery details for [Project/Service]:</p>
        <p>[Space for details: Revised scopes, updates from previous communication]</p>
        <p>Your confirmation will help me and my company finalize our arrangements.</p>",
        'High-Value Project' =>
            "
        <p>I am seeking a quote for a high-value [Project/Service]. Please ensure that your quote reflects premium quality, extended warranties, and supportive terms:</p>
        <p>[Space for details: High-end requirements, quality assurance]</p>
        <p>I appreciate your attention to detail and prompt response.</p>",
        'Long-Term Supply' =>
            "
        <p>I require a quote for a short/mid/long-term supply arrangement for [Product/Service]. Please detail bulk pricing, scheduled deliveries, and any loyalty incentives:</p>
        <p>[Space for details: Contract duration, supply intervals]</p>
        <p>I look forward to establishing a mutually stable and beneficial relationship.</p>",
        'Consultative Approach' =>
            "
        <p>We, at Persol Company, require a quote for [Project/Service], considering various options and potential offers. We value your expertise in guiding us towards the best approach.</p>
        <p>[Space for details: Performance expectations, desired outcomes]</p>
        <p>We welcome your professional recommendations.</p>",
        'Preliminary Inquiry' =>
            "
        <p>I am conducting preliminary research and would appreciate a quotation for [Product/Service]. This information will assist in our initial planning phase.</p>
        <p>[Space for details: Estimated quantities, potential future needs]</p>
        <p>Your input is valuable to our process.</p>",
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

    public static function getEmailBody(): array
    {
        return self::$emailBody;
    }
}

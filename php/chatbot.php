<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add CORS headers for localhost
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

// Get the JSON data from the request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Log for debugging
error_log("Received data: " . print_r($data, true));

if (!isset($data['message']) || empty(trim($data['message']))) {
    echo json_encode(['success' => false, 'error' => 'Message is required']);
    exit;
}

$userMessage = trim($data['message']);

// Updated Context about John Vincent Pangilinan
$context = "You are an AI assistant for John Vincent Pangilinan's portfolio website. 

About John Vincent Pangilinan:
- Junior Software Engineer & Junior Web Developer from the Philippines
- Graduating BSIT student with hands-on OJT and freelance experience
- Gmail: your.email@gmail.com
- Telegram: @yourusername
- Location: Philippines
- GitHub: github.com/yourusername

Technical Skills:
- Programming Languages: PHP, JavaScript
- Web Technologies: HTML5, CSS3, Bootstrap
- Database: MySQL (CRUD operations, SQL queries)
- Tools: Git/GitHub, VS Code, XAMPP, Linux Commands
- Development Skills: Authentication, Responsive Design, MVC Concepts, Payment Integration (GCash), Data Analytics

Major Projects:

1. JAR Garments E-Commerce Website (Freelance Project) ⭐
   - Full-featured e-commerce platform for custom garments business
   - Customers browse and select clothing/garment designs
   - GCash payment integration for secure online transactions
   - Complete shopping cart and checkout system
   - Monthly and yearly sales analytics dashboard for business insights
   - Order management system for business owner
   - Customer account management and order tracking
   - Built with: PHP, MySQL, Bootstrap, JavaScript, GCash Payment API
   - Real-world freelance client project for JAR Garments

2. Library Management System (OJT Project)
   - Web-based system with PHP & MySQL
   - Student registration with Gmail-based login authentication
   - Book browsing and reservation system by genre and course
   - Admin dashboard for managing books, users, and transactions
   - RFID-based attendance tracking for library monitoring
   - Data analytics and reporting for library management
   - Developed during On-the-Job Training

3. Programming Language Learning Web Application (Capstone)
   - Interactive educational platform for programming beginners
   - Text-to-Speech functionality for accessibility
   - Built-in quizzes and assessment system
   - Live code editor environment for practice
   - Beginner-friendly UI/UX design
   - Focused on making programming accessible to everyone

Experience:
- Freelance Web Developer: Developed e-commerce solutions with payment gateway integration
- Web Developer Intern (OJT): Developed functional web systems, applied backend logic and validation, participated in testing and debugging

Certifications & Achievements:
- NC II Holder – EPAS (Electronic Products Assembly and Servicing)
- Dean's Lister – Academic Year 2023–2024

Soft Skills:
- Analytical & Logical Thinking
- Problem-Solving
- Fast Learner with attention to detail
- Team Collaboration
- Communication
- Time Management
- Adaptability

Contact:
- Gmail: your.email@gmail.com
- Telegram: @yourusername
- GitHub: github.com/yourusername

Answer questions about John Vincent's skills, projects, education, and experience professionally and conversationally. 
Highlight his freelance experience with e-commerce and payment integration.
Emphasize his practical experience with real clients and OJT.
Keep responses concise (2-3 sentences) unless asked for details.
Be encouraging and professional.

User question: " . $userMessage;

try {
    // Check if Gemini API key is set
    if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE') {
        throw new Exception('Gemini API key not configured');
    }

    // Prepare the request to Gemini AI
    $requestData = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $context]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 1024,
        ],
        'safetySettings' => [
            [
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ]
        ]
    ];

    // Make the API request to Gemini
    $ch = curl_init(GEMINI_API_URL . '?key=' . GEMINI_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception('Curl error: ' . $curlError);
    }

    if ($httpCode !== 200) {
        error_log('Gemini API Error. Status: ' . $httpCode . ', Response: ' . $response);
        throw new Exception('Gemini API request failed');
    }

    $responseData = json_decode($response, true);
    
    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        $aiResponse = $responseData['candidates'][0]['content']['parts'][0]['text'];
        
        // Store conversation in database (optional)
        storeConversation($userMessage, $aiResponse);
        
        echo json_encode([
            'success' => true,
            'response' => $aiResponse
        ]);
    } else {
        throw new Exception('Invalid response from Gemini AI');
    }

} catch (Exception $e) {
    error_log('Chatbot Error: ' . $e->getMessage());
    
    // Fallback response
    $fallbackResponse = getFallbackResponse($userMessage);
    echo json_encode([
        'success' => true,
        'response' => $fallbackResponse
    ]);
}

// Function to store conversation
function storeConversation($userMessage, $aiResponse) {
    try {
        $conn = getDBConnection();
        if ($conn) {
            $stmt = $conn->prepare("INSERT INTO chat_logs (user_message, ai_response, created_at) VALUES (?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("ss", $userMessage, $aiResponse);
                $stmt->execute();
                $stmt->close();
            }
            $conn->close();
        }
    } catch (Exception $e) {
        error_log('Database error: ' . $e->getMessage());
    }
}

// Fallback responses
function getFallbackResponse($message) {
    $message = strtolower($message);
    
    if (strpos($message, 'nc2') !== false || strpos($message, 'ncii') !== false || strpos($message, 'certification') !== false || strpos($message, 'epas') !== false) {
        return "Yes! John Vincent holds NC II certification in EPAS (Electronic Products Assembly and Servicing). He's also a Dean's Lister for Academic Year 2023-2024. His technical background combines electronics knowledge with software development skills!";
    }
    
    if (strpos($message, 'skill') !== false || strpos($message, 'technology') !== false || strpos($message, 'technical') !== false) {
        return "John Vincent specializes in PHP, JavaScript, HTML5, CSS3, and MySQL. He's experienced in payment integration (GCash), data analytics, CRUD operations, authentication, and responsive design. He works with Git, XAMPP, VS Code, and has knowledge of MVC concepts and Linux commands.";
    }
    
    if (strpos($message, 'project') !== false || strpos($message, 'work') !== false || strpos($message, 'portfolio') !== false) {
        return "John Vincent has developed: 1) JAR Garments E-Commerce (freelance) - a full e-commerce platform with GCash payment and sales analytics, 2) Library Management System (OJT) - with RFID tracking and Gmail authentication, and 3) Programming Learning Platform (capstone) - with text-to-speech and code editor. Check out the portfolio for more details!";
    }
    
    if (strpos($message, 'freelance') !== false || strpos($message, 'jar') !== false || strpos($message, 'garment') !== false) {
        return "John Vincent developed JAR Garments E-Commerce as a freelance project. It's a full-featured platform where customers select garment designs and pay via GCash. It includes a shopping cart, order management, and monthly/yearly sales analytics dashboard for the business owner. Great example of real-world client work!";
    }
    
    if (strpos($message, 'ecommerce') !== false || strpos($message, 'e-commerce') !== false || strpos($message, 'shop') !== false) {
        return "Yes! John Vincent built a complete e-commerce platform for JAR Garments as a freelance project. It features product browsing, shopping cart, GCash payment integration, order tracking, and comprehensive sales analytics. He has hands-on experience with payment gateways and e-commerce workflows.";
    }
    
    if (strpos($message, 'payment') !== false || strpos($message, 'gcash') !== false) {
        return "Absolutely! John Vincent has experience integrating GCash payment API in his JAR Garments E-Commerce project. He implemented secure payment processing, transaction management, and order confirmation systems. He understands payment gateway integration and e-commerce security.";
    }
    
    if (strpos($message, 'analytics') !== false || strpos($message, 'data') !== false || strpos($message, 'sales') !== false) {
        return "John Vincent has implemented data analytics features in multiple projects. In JAR Garments E-Commerce, he built monthly and yearly sales analytics dashboards. In his Library Management System, he created analytics for book transactions and attendance tracking. He knows how to work with data visualization and reporting.";
    }
    
    if (strpos($message, 'contact') !== false || strpos($message, 'hire') !== false || strpos($message, 'email') !== false || strpos($message, 'reach') !== false || strpos($message, 'telegram') !== false) {
        return "You can reach John Vincent via Gmail at your.email@gmail.com or Telegram @yourusername. He's based in the Philippines and available for freelance projects and full-time opportunities. Feel free to discuss your project needs!";
    }
    
    if (strpos($message, 'experience') !== false || strpos($message, 'ojt') !== false || strpos($message, 'work') !== false) {
        return "John Vincent has freelance experience developing e-commerce solutions with payment integration, and completed OJT as a Web Developer Intern where he built functional web systems using PHP and MySQL. He's a graduating BSIT student with hands-on real-world development experience.";
    }
    
    if (strpos($message, 'education') !== false || strpos($message, 'school') !== false || strpos($message, 'study') !== false) {
        return "John Vincent is a graduating Bachelor of Science in Information Technology (BSIT) student. He's a Dean's Lister for Academic Year 2023-2024 and holds an NC II certification in EPAS. He combines academic excellence with practical development experience!";
    }
    
    if (strpos($message, 'library') !== false || strpos($message, 'rfid') !== false) {
        return "John Vincent developed a comprehensive Library Management System during his OJT. It features Gmail-based authentication, book browsing and reservation, RFID-based attendance tracking, admin dashboard, and data analytics. A great example of his full-stack development capabilities!";
    }
    
    if (strpos($message, 'capstone') !== false || strpos($message, 'learning') !== false || strpos($message, 'programming') !== false) {
        return "His capstone project is a Programming Language Learning Platform designed for beginners. It features interactive lessons, text-to-speech for accessibility, built-in quizzes, and a live code editor. The project demonstrates his commitment to making technology accessible to everyone!";
    }
    
    if (strpos($message, 'hello') !== false || strpos($message, 'hi') !== false || strpos($message, 'hey') !== false) {
        return "Hello! 👋 I'm John Vincent's AI assistant. Feel free to ask about his skills, freelance projects (like the JAR Garments e-commerce platform), OJT experience, education, or how to get in touch with him via Telegram or Gmail!";
    }
    
    if (strpos($message, 'available') !== false || strpos($message, 'free') !== false) {
        return "Yes! John Vincent is available for both freelance projects and full-time opportunities. He has proven experience with e-commerce development, payment integration, and full-stack web applications. Contact him via Gmail (your.email@gmail.com) or Telegram (@yourusername) to discuss your project!";
    }
    
    return "Thanks for your interest in John Vincent! He's a talented Junior Software Engineer with freelance e-commerce experience and NC II EPAS certification. For specific questions about his skills, projects, or availability, feel free to ask or contact him directly via Gmail (your.email@gmail.com) or Telegram (@yourusername)!";
}
?>
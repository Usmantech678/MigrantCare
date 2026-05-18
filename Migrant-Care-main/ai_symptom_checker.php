<?php
// Ensure the user is logged in
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('symptomChecker'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Custom scrollbar for a cleaner look */
        #chat-messages::-webkit-scrollbar {
            width: 6px;
        }
        #chat-messages::-webkit-scrollbar-track {
            background: #f1f1ff;
        }
        #chat-messages::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        #chat-messages::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-2xl mx-4 h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl">
        <header class="bg-blue-600 text-white p-4 rounded-t-2xl shadow-md z-10">
            <div class="flex items-center justify-between">
                 <a href="index.php?page=worker_dashboard" class="text-white hover:underline">&larr; <?php echo t('backToDashboard'); ?></a>
                
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white mr-3"><path d="M12 8V4H8"/><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M12 12h4"/><path d="M12 16h4"/><path d="M8 12h.01"/><path d="M8 16h.01"/></svg>
                    <div>
                        <h1 class="text-xl font-bold"><?php echo t('symptomChecker'); ?></h1>
                        <p class="text-sm text-blue-100">Your Health Assistant</p>
                    </div>
                </div>

                <div>
                    <form action="index.php" method="POST" class="m-0">
                        <input type="hidden" name="action" value="set_language">
                        <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                        
                        <label for="language-select" class="sr-only"><?php echo t('selectLanguage'); ?></label>
                        <select id="language-select" name="language" onchange="this.form.submit()" class="bg-blue-700 text-white text-sm rounded-md border-0 p-2 focus:ring-2 focus:ring-white cursor-pointer">
                            <?php
                            global $translations; // Get the translations array from functions.php
                            $currentLang = $_SESSION['language'] ?? 'en'; // Get current language
                            
                            // Loop through all available languages and create an <option> for each
                            foreach ($translations as $code => $props) {
                                $selected = ($code == $currentLang) ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($code) . "\" {$selected}>" . htmlspecialchars($props['language']) . "</option>";
                            }
                            ?>
                        </select>
                    </form>
                </div>
                </div>
        </header>

        <main id="chat-messages" class="flex-1 p-6 overflow-y-auto">
            </main>

        <footer class="p-4 bg-white border-t border-gray-200 rounded-b-2xl">
            <form id="chat-form" class="flex items-center space-x-3">
                <input type="text" id="user-input" placeholder="<?php echo t('typeSymptomsHere'); ?>" autocomplete="off"
                    class="flex-1 w-full px-4 py-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 transition-shadow">
                <button type="submit"
                    class="bg-blue-600 text-white rounded-full p-3 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-transform duration-200 ease-in-out transform hover:scale-110">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.428A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                    </svg>
                </button>
            </form>
        </footer>
    </div>

    <script>
        const chatMessages = document.getElementById('chat-messages');
        const chatForm = document.getElementById('chat-form');
        const userInput = document.getElementById('user-input');

        // Function to add a message to the chat window
        function addMessage(sender, message) {
            const messageElement = document.createElement('div');
            messageElement.classList.add('flex', 'mb-4', 'items-end');
            
            // Sanitize message to prevent HTML injection and format newlines
            const sanitizedMessage = message.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\n/g, '<br>');


            if (sender === 'user') {
                messageElement.classList.add('justify-end');
                messageElement.innerHTML = `
                    <p class="mx-2 py-3 px-4 bg-blue-500 text-white rounded-2xl max-w-md">${sanitizedMessage}</p>
                    <div class="w-9 h-9 rounded-full flex items-center justify-center bg-blue-500 text-white font-bold text-sm flex-shrink-0">You</div>
                `;
            } else { // AI message
                messageElement.classList.add('justify-start');
                messageElement.innerHTML = `
                    <div class="w-9 h-9 rounded-full flex items-center justify-center bg-gray-500 text-white text-sm flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M12 12h4"/><path d="M12 16h4"/><path d="M8 12h.01"/><path d="M8 16h.01"/></svg>
                    </div>
                    <p class="mx-2 py-3 px-4 bg-gray-200 text-gray-800 rounded-2xl max-w-md">${sanitizedMessage}</p>
                `;
            }
            chatMessages.appendChild(messageElement);
            chatMessages.scrollTop = chatMessages.scrollHeight; // Auto-scroll to bottom
        }

        // Function to show typing indicator
        function showTypingIndicator() {
            const typingElement = document.createElement('div');
            typingElement.id = 'typing-indicator';
            typingElement.classList.add('flex', 'mb-4', 'items-end', 'justify-start');
            typingElement.innerHTML = `
                <div class="w-9 h-9 rounded-full flex items-center justify-center bg-gray-500 text-white text-sm flex-shrink-0">
                     <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M12 12h4"/><path d="M12 16h4"/><path d="M8 12h.01"/><path d="M8 16h.01"/></svg>
                </div>
                <div class="mx-2 py-3 px-4 bg-gray-200 text-gray-800 rounded-2xl max-w-xs">
                    <div class="flex items-center space-x-1">
                        <span class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: -0.3s;"></span>
                        <span class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: -0.15s;"></span>
                        <span class="w-2 h-2 bg-gray-500 rounded-full animate-bounce"></span>
                    </div>
                </div>
            `;
            chatMessages.appendChild(typingElement);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function removeTypingIndicator() {
            const typingIndicator = document.getElementById('typing-indicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }

        // --- CORE FUNCTION TO TALK TO YOUR PHP BACKEND ---
        async function getAIResponse(prompt) {
            showTypingIndicator();
            try {
                // This sends the user's message to your ajax.php file
                const response = await fetch('ajax.php?action=ai_chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ message: prompt }),
                });

                if (!response.ok) {
                    throw new Error(`Server error: ${response.statusText}`);
                }

                const data = await response.json();
                removeTypingIndicator();
                
                if (data.reply) {
                    addMessage('ai', data.reply);
                } else {
                    addMessage('ai', "I'm sorry, I couldn't process that. Please try again.");
                }

            } catch (error) {
                console.error("Error fetching AI response:", error);
                removeTypingIndicator();
                addMessage('ai', "I'm sorry, I'm having trouble connecting right now. Please try again later.");
            }
        }

        // Handle form submission
        chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const message = userInput.value.trim();
            if (message) {
                addMessage('user', message);
                getAIResponse(message);
                userInput.value = '';
            }
        });

        // Add initial greeting from AI
        window.addEventListener('load', () => {
             addMessage('ai', "<?php echo t('aiWelcomeMessage'); ?>");
        });
    </script>
</body>
</html>
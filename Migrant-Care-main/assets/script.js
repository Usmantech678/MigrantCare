// /migrantcare/assets/script.js

document.addEventListener('DOMContentLoaded', function() {
    
    // Geolocation for Worker Setup
    const getLocationBtn = document.getElementById('getLocationBtn');
    const locationInput = document.getElementById('currentLocation');
    if (getLocationBtn && locationInput) {
        getLocationBtn.addEventListener('click', () => {
            locationInput.value = 'Fetching...';
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(position => {
                    setTimeout(() => {
                        locationInput.value = 'Salem, Tamil Nadu';
                    }, 1000);
                }, () => {
                    locationInput.value = 'Permission denied.';
                });
            } else {
                locationInput.value = 'Geolocation not supported.';
            }
        });
    }

   
    const chatContainer = document.getElementById('chatContainer');
    if (chatContainer) {
        const chatMessages = document.getElementById('chatMessages');
        const chatInput = document.getElementById('chatInput');
        const sendChatBtn = document.getElementById('sendChatBtn');

        const scrollToBottom = () => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        };

        // This function now creates a more complex structure with avatars
        const addMessage = (text, sender) => {
            const row = document.createElement('div');
            row.className = `message-row ${sender}`; // 'user' or 'ai'

            const avatar = document.createElement('div');
            avatar.className = 'avatar';
            avatar.textContent = sender === 'user' ? 'You' : '🤖';

            const messageDiv = document.createElement('div');
            messageDiv.className = sender === 'user' ? 'user-message' : 'ai-message';
            messageDiv.textContent = text;

            row.appendChild(avatar);
            row.appendChild(messageDiv);
            chatMessages.appendChild(row);
            scrollToBottom();
        };

        const handleSendMessage = async () => {
            const userInput = chatInput.value.trim();
            if (userInput === '') return;

            addMessage(userInput, 'user');
            chatInput.value = '';

            // Add a "typing..." indicator for better UX
            const typingIndicator = document.createElement('div');
            typingIndicator.className = 'message-row ai';
            typingIndicator.innerHTML = `<div class="avatar">🤖</div><div class="ai-message typing-indicator"><span></span><span></span><span></span></div>`;
            chatMessages.appendChild(typingIndicator);
            scrollToBottom();

            try {
                const response = await fetch('ajax.php?action=ai_chat', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: userInput }),
                });

                if (!response.ok) throw new Error('Network response was not ok');

                const data = await response.json();
                
                // Remove typing indicator and add AI's response
                setTimeout(() => {
                    chatMessages.removeChild(typingIndicator);
                    addMessage(data.reply, 'ai');
                }, 800); 

            } catch (error) {
                console.error('Fetch error:', error);
                chatMessages.removeChild(typingIndicator);
                addMessage('Sorry, something went wrong. Please try again.', 'ai');
            }
        };

        sendChatBtn.addEventListener('click', handleSendMessage);
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleSendMessage();
            }
        });
    }

    // Handle multi-step registration form
    const regForm = document.getElementById('registerForm');
    if (regForm) {
        const steps = regForm.querySelectorAll('.form-step');
        let currentStep = 0;
        function showStep(stepIndex) {
            steps.forEach((step, index) => {
                step.style.display = index === stepIndex ? 'block' : 'none';
            });
        }
        regForm.addEventListener('click', function(e) {
            if (e.target.matches('[data-next]')) {
                e.preventDefault();
                if (currentStep < steps.length - 1) {
                    currentStep++;
                    showStep(currentStep);
                }
            }
        });
        showStep(0);
    }
    
    // ## NEW: Clinical Template Autocomplete Logic ##
    const templateSelector = document.getElementById('templateSelector');
    if (templateSelector) {
        // Find the form fields
        const symptomsInput = document.getElementById('symptomsInput');
        const diagnosisInput = document.getElementById('diagnosisInput');
        const prescriptionInput = document.getElementById('prescriptionInput');
        const notesInput = document.getElementById('notesInput');

        // Listen for changes on the dropdown
        templateSelector.addEventListener('change', function() {
            const selectedTemplateName = this.value;

            // Check if the selected value is a valid template
            if (selectedTemplateName && diseaseTemplates[selectedTemplateName]) {
                const template = diseaseTemplates[selectedTemplateName];
                
                // Populate the fields. The fields remain editable.
                symptomsInput.value = template.symptoms;
                diagnosisInput.value = template.diagnosis;
                prescriptionInput.value = template.prescription;
                notesInput.value = template.notes;
            }
        });
    }
});
document.addEventListener('DOMContentLoaded', function() {
    
    // ... (any other existing JavaScript code can stay here) ...

    // AI Chatbot Interactive Logic
    const chatContainer = document.getElementById('chatContainer');
    if (chatContainer) {
        const chatMessages = document.getElementById('chatMessages');
        const chatInput = document.getElementById('chatInput');
        const sendChatBtn = document.getElementById('sendChatBtn');

        const scrollToBottom = () => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        };

        const addMessage = (text, sender) => {
            const row = document.createElement('div');
            row.className = `message-row ${sender}`;
            const avatar = document.createElement('div');
            avatar.className = 'avatar';
            avatar.textContent = sender === 'user' ? 'You' : '🤖';
            const messageDiv = document.createElement('div');
            messageDiv.className = sender === 'user' ? 'user-message' : 'ai-message';
            messageDiv.textContent = text;
            row.appendChild(avatar);
            row.appendChild(messageDiv);
            chatMessages.appendChild(row);
            scrollToBottom();
        };

        const handleSendMessage = async () => {
            const userInput = chatInput.value.trim();
            if (userInput === '') return;
            addMessage(userInput, 'user');
            chatInput.value = '';
            try {
                const response = await fetch('ajax.php?action=ai_chat', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: userInput }),
                });
                const data = await response.json();
                setTimeout(() => { addMessage(data.reply, 'ai'); }, 500);
            } catch (error) {
                addMessage('Sorry, something went wrong. Please try again.', 'ai');
            }
        };

        sendChatBtn.addEventListener('click', handleSendMessage);
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                handleSendMessage();
            }
        });
    }
});
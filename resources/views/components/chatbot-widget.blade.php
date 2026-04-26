{{-- Chatbot Widget --}}

<button id="chat-toggle" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000; padding: 15px; border-radius: 50%; background-color: #4F46E5; color: white; border: none; cursor: pointer;">
    💬 Chat
</button>

<div id="chat-window" style="display: none; position: fixed; bottom: 80px; right: 20px; width: 350px; height: 450px; background: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1000; display: flex; flex-direction: column; overflow: hidden;">

    <div style="background: #4F46E5; color: white; padding: 15px; font-weight: bold;">
        Journal Assistant
    </div>

    <div id="chat-messages" style="flex: 1; padding: 15px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; background: #f9fafb;">
        <div style="align-self: flex-start; background: #e5e7eb; padding: 10px; border-radius: 8px; max-width: 80%;">
            Hello! I'm your journal assistant. What would you like to reflect on today?
        </div>
    </div>

    <div style="padding: 10px; border-top: 1px solid #e5e7eb; display: flex;">
        <input type="hidden" id="current-chat-id" value="">
        <input type="text" id="chat-input" placeholder="Ask about your entries..." style="flex: 1; padding: 10px; border: 1px solid #d1d5db; border-radius: 5px;">
        <button id="chat-send" style="margin-left: 5px; padding: 10px 15px; background: #4F46E5; color: white; border: none; border-radius: 5px; cursor: pointer;">Send</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('chat-toggle');
    const chatWindow = document.getElementById('chat-window');
    const chatMessages = document.getElementById('chat-messages');
    const chatInput = document.getElementById('chat-input');
    const sendBtn = document.getElementById('chat-send');
    const chatIdInput = document.getElementById('current-chat-id');

    // Toggles window visibility
    chatWindow.style.display = 'none'; // Ensure hidden on load
    toggleBtn.addEventListener('click', () => {
        chatWindow.style.display = chatWindow.style.display === 'none' ? 'flex' : 'none';
    });

    // Handles sending messages
    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    function sendMessage() {
        const message = chatInput.value.trim();
        if (!message) return;

        // Appends User Message to UI
        appendMessage(message, 'user');
        chatInput.value = '';

        // Shows a loading state
        const loadingId = appendMessage('...', 'model');

        // Sends AJAX request to Laravel Controller
        fetch('/chatbot/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}' // Required for Laravel security
            },
            body: JSON.stringify({
                message: message,
                chat_id: chatIdInput.value
            })
        })
        .then(async response => {
            if (!response.ok) {
                const text = await response.text();
                throw new Error(text);
            }
            return response.json();
        })
        .then(data => {
            // Update the hidden input with the active chat ID
            chatIdInput.value = data.chat_id;

            // Remove loading indicator and append real response
            document.getElementById(loadingId).remove();
            appendMessage(data.reply, 'model');
        })
        // .catch(error => {
        //     document.getElementById(loadingId).remove();
        //     appendMessage('Error: Could not connect to server.', 'model');
        // });
        .catch(error => {
            console.error(error);
            appendMessage('Server Error: ' + error.message, 'model');
        });
    }

    function appendMessage(text, role) {
        const msgDiv = document.createElement('div');
        const isUser = role === 'user';

        // Simple inline styling to differentiate user vs AI
        msgDiv.style.alignSelf = isUser ? 'flex-end' : 'flex-start';
        msgDiv.style.background = isUser ? '#4F46E5' : '#e5e7eb';
        msgDiv.style.color = isUser ? 'white' : 'black';
        msgDiv.style.padding = '10px';
        msgDiv.style.borderRadius = '8px';
        msgDiv.style.maxWidth = '80%';
        msgDiv.innerText = text;

        const uniqueId = 'msg-' + Date.now();
        msgDiv.id = uniqueId;

        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight; // Auto-scroll to bottom

        return uniqueId;
    }
});
</script>

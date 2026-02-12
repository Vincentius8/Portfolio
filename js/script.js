// Initialize AOS
AOS.init({
    duration: 1000,
    once: true,
    offset: 100
});

// Navbar Scroll Effect
window.addEventListener('scroll', function() {
    const navbar = document.getElementById('mainNav');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Smooth Scroll for Navigation Links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
            
            // Close mobile menu if open
            const navbarCollapse = document.querySelector('.navbar-collapse');
            if (navbarCollapse.classList.contains('show')) {
                navbarCollapse.classList.remove('show');
            }
        }
    });
});

// Contact Form Submission
const contactForm = document.getElementById('contactForm');
if (contactForm) {
    contactForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        submitBtn.textContent = 'Sending...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch('php/contact.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('✅ Message sent successfully!');
                this.reset();
            } else {
                alert('❌ Error sending message. Please try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('❌ Error sending message. Please try again.');
        } finally {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    });
}

// ===== CHATBOT FUNCTIONALITY - TELEGRAM STYLE =====

const chatbotToggle = document.getElementById('chatbot-toggle');
const chatbotWindow = document.getElementById('chatbot-window');
const chatbotClose = document.getElementById('chatbot-close');
const chatbotInput = document.getElementById('chatbot-input-field');
const chatbotSend = document.getElementById('chatbot-send');
const chatbotMessages = document.getElementById('chatbot-messages');
const chatbotTyping = document.getElementById('chatbot-typing');

let isTyping = false;

// Toggle chatbot window with animation
chatbotToggle.addEventListener('click', () => {
    const isActive = chatbotWindow.classList.contains('active');
    
    if (isActive) {
        chatbotWindow.style.animation = 'slideDownChat 0.2s ease-out forwards';
        setTimeout(() => {
            chatbotWindow.classList.remove('active');
            chatbotWindow.style.animation = '';
        }, 200);
    } else {
        chatbotWindow.classList.add('active');
        chatbotInput.focus();
        // Hide badge when opened
        const badge = document.querySelector('.chat-badge');
        if (badge) {
            badge.style.display = 'none';
        }
    }
});

chatbotClose.addEventListener('click', () => {
    chatbotWindow.style.animation = 'slideDownChat 0.2s ease-out forwards';
    setTimeout(() => {
        chatbotWindow.classList.remove('active');
        chatbotWindow.style.animation = '';
    }, 200);
});

// Get base URL dynamically
function getBaseUrl() {
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        return window.location.origin + '/Portfolio/';
    }
    return window.location.origin + '/';
}

// Send message function
async function sendMessage() {
    const message = chatbotInput.value.trim();
    
    if (message === '' || isTyping) return;
    
    // Add user message to chat
    addMessage(message, 'user');
    chatbotInput.value = '';
    
    // Show typing indicator
    isTyping = true;
    chatbotTyping.style.display = 'block';
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    
    try {
        const baseUrl = getBaseUrl();
        const url = baseUrl + 'php/chatbot.php';
        
        console.log('📤 Sending:', message);
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: message })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('📥 Response:', data);
        
        // Simulate typing delay for realistic effect
        await new Promise(resolve => setTimeout(resolve, 800));
        
        // Hide typing indicator
        chatbotTyping.style.display = 'none';
        isTyping = false;
        
        if (data.success) {
            addMessage(data.response, 'bot');
        } else {
            addMessage(data.error || 'Sorry, I encountered an error. Please try again.', 'bot');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        chatbotTyping.style.display = 'none';
        isTyping = false;
        addMessage('⚠️ Connection error. Make sure you\'re accessing via http://localhost/Portfolio/', 'bot');
    }
}

// Add message to chat - Telegram Style with animations
function addMessage(text, sender) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${sender}-message`;
    
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    
    if (sender === 'bot') {
        messageDiv.innerHTML = `
            <div class="message-avatar">
                <img src="images/profile.jpg" alt="JV" onerror="this.src='https://ui-avatars.com/api/?name=JV&background=0088cc&color=fff'">
            </div>
            <div class="message-bubble-wrapper">
                <div class="message-content">
                    <p>${escapeHtml(text)}</p>
                </div>
                <small class="message-time">${timeString}</small>
            </div>
        `;
    } else {
        messageDiv.innerHTML = `
            <div class="message-bubble-wrapper">
                <div class="message-content">
                    <p>${escapeHtml(text)}</p>
                </div>
                <small class="message-time">${timeString}</small>
            </div>
        `;
    }
    
    chatbotMessages.appendChild(messageDiv);
    
    // Smooth scroll to bottom
    setTimeout(() => {
        chatbotMessages.scrollTo({
            top: chatbotMessages.scrollHeight,
            behavior: 'smooth'
        });
    }, 50);
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Event listeners for sending messages
chatbotSend.addEventListener('click', sendMessage);

chatbotInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

// Show send button only when there's text
chatbotInput.addEventListener('input', () => {
    if (chatbotInput.value.trim() !== '') {
        chatbotSend.style.opacity = '1';
        chatbotSend.style.transform = 'scale(1)';
    } else {
        chatbotSend.style.opacity = '0.6';
    }
});

// Close chatbot when clicking outside
document.addEventListener('click', (e) => {
    if (!chatbotWindow.contains(e.target) && !chatbotToggle.contains(e.target)) {
        if (chatbotWindow.classList.contains('active')) {
            chatbotWindow.style.animation = 'slideDownChat 0.2s ease-out forwards';
            setTimeout(() => {
                chatbotWindow.classList.remove('active');
                chatbotWindow.style.animation = '';
            }, 200);
        }
    }
});

// Add active class to nav links on scroll
window.addEventListener('scroll', () => {
    let current = '';
    const sections = document.querySelectorAll('section');
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        
        if (pageYOffset >= sectionTop - 200) {
            current = section.getAttribute('id');
        }
    });
    
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === `#${current}`) {
            link.classList.add('active');
        }
    });
});

// Preload profile image
window.addEventListener('load', () => {
    const profileImg = new Image();
    profileImg.src = 'images/profile.jpg';
});
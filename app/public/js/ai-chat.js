// AI Chat functionality
let chatMessages = [];

$(document).ready(function() {
    initializeAIChat();
});

function initializeAIChat() {
    // Character counter
    $('#aiQuestion').on('input', function() {
        const length = $(this).val().length;
        $('#charCount').text(length);

        if (length > 450) {
            $('#charCount').addClass('text-warning');
        } else {
            $('#charCount').removeClass('text-warning');
        }
    });

    // Chat form submission
    $('#aiChatForm').on('submit', function(e) {
        e.preventDefault();
        const question = $('#aiQuestion').val().trim();
        if (question) {
            sendAIMessage(question);
        }
    });

    // Quick question buttons
    $('.quick-question').on('click', function() {
        const question = $(this).data('question');
        sendAIMessage(question);
    });

    // Clear chat
    $('#clearChatBtn').on('click', function() {
        if (confirm('Hapus semua percakapan?')) {
            clearChat();
        }
    });

    // Keyboard shortcuts
    $('#aiQuestion').on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.which === 13) {
            e.preventDefault();
            $('#aiChatForm').submit();
        }
    });

    // Auto-resize textarea
    $('#aiQuestion').on('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
}

function sendAIMessage(question) {
    if (!question.trim()) return;

    console.log('Sending AI message:', question);

    // Add user message to chat
    addMessage('user', question);

    // Clear input
    $('#aiQuestion').val('').trigger('input');

    // Show typing indicator
    showTypingIndicator();

    // Disable send button
    $('#sendBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

    // Get metric ID from page
    const metricId = $('[data-metric-id]').data('metric-id') || $('#metricId').val() || businessMetricId;
    const csrfToken = $('meta[name="csrf-token"]').attr('content') || $('[name="_token"]').val();

    // Send request to AI
    $.ajax({
        url: `/dashboard/metrics/${metricId}/ai-chat`,
        method: 'POST',
        data: {
            question: question,
            _token: csrfToken
        },
        success: function(response) {
            console.log('AI Response received:', response);
            hideTypingIndicator();

            if (response.success) {
                addMessage('ai', response.response);
                updateAIStatus('online');
            } else {
                console.error('AI returned error:', response.error);
                addMessage('ai', '❌ Maaf, terjadi kesalahan: ' + (response.error || 'Tidak dapat memproses pertanyaan Anda.'));
                updateAIStatus('error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });

            hideTypingIndicator();
            updateAIStatus('error');

            let errorMessage = 'Maaf, terjadi kesalahan saat menghubungi AI assistant.';

            // Try to parse error response
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.error) {
                    errorMessage = '❌ ' + response.error;
                }
            } catch (e) {
                // Use default error handling based on status code
                if (xhr.status === 0) {
                    errorMessage = '❌ Tidak dapat terhubung ke server. Periksa koneksi internet Anda.';
                } else if (xhr.status === 404) {
                    errorMessage = '❌ Endpoint AI tidak ditemukan. Route mungkin tidak terdaftar.';
                } else if (xhr.status === 429) {
                    errorMessage = '❌ Terlalu banyak permintaan. Silakan coba lagi dalam beberapa saat.';
                } else if (xhr.status === 500) {
                    errorMessage = '❌ Terjadi kesalahan server. Silakan coba lagi nanti atau periksa log Laravel.';
                } else if (xhr.status === 403) {
                    errorMessage = '❌ API key tidak valid atau akses ditolak. Periksa GEMINI_API_KEY di .env';
                } else if (xhr.status === 400) {
                    errorMessage = '❌ Permintaan tidak valid. Pastikan pertanyaan Anda sudah benar.';
                } else {
                    errorMessage = `❌ Error ${xhr.status}: ${xhr.statusText}`;
                }
            }

            addMessage('ai', errorMessage);

            // Show debug info in console
            console.log('Debug Info:');
            console.log('- Metric ID:', metricId);
            console.log('- Route URL:', `/dashboard/metrics/${metricId}/ai-chat`);
            console.log('- CSRF Token:', csrfToken);
        },
        complete: function() {
            // Re-enable send button
            $('#sendBtn').prop('disabled', false).html('<i class="fas fa-paper-plane"></i>');
        }
    });
}

function addMessage(type, content) {
    const timestamp = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    const avatar = type === 'ai' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';
    const name = type === 'ai' ? 'AI Assistant' : 'Anda';

    const messageHtml = `
        <div class="message ${type}-message">
            <div class="message-avatar">
                ${avatar}
            </div>
            <div class="message-content">
                <div class="message-header">
                    <strong>${name}</strong>
                    <small class="text-muted">${timestamp}</small>
                </div>
                <div class="message-text">${formatAIResponse(content)}</div>
            </div>
        </div>
    `;

    $('#chatMessages').append(messageHtml);
    scrollToBottom();

    // Store message
    chatMessages.push({
        type: type,
        content: content,
        timestamp: timestamp
    });
}

function formatAIResponse(text) {
    if (!text) return '';

    // Escape HTML first to prevent XSS
    const escapeHtml = (unsafe) => {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    };

    text = escapeHtml(text);

    // Convert markdown headers (must be done before other formatting)
    text = text.replace(/^### (.*?)$/gm, '<h5 class="mt-3 mb-2 fw-bold text-white">$1</h5>');
    text = text.replace(/^## (.*?)$/gm, '<h4 class="mt-3 mb-2 fw-bold text-white">$1</h4>');
    text = text.replace(/^# (.*?)$/gm, '<h3 class="mt-3 mb-2 fw-bold text-white">$1</h3>');

    // Convert bold text (**text**)
    text = text.replace(/\*\*([^\*]+)\*\*/g, '<strong class="text-warning">$1</strong>');

    // Convert italic text (*text*)
    text = text.replace(/\*([^\*]+)\*/g, '<em class="text-info">$1</em>');

    // Convert code blocks (```code```)
    text = text.replace(/```([\s\S]*?)```/g, '<pre class="bg-dark p-2 rounded mt-2 mb-2" style="color: #00ff00;"><code>$1</code></pre>');

    // Convert inline code (`code`)
    text = text.replace(/`([^`]+)`/g, '<code class="bg-dark px-2 py-1 rounded text-success">$1</code>');

    // Convert numbered lists (1. 2. 3.)
    text = text.replace(/^(\d+)\.\s+(.*)$/gm, function(match, num, content) {
        return '<div class="ms-3 mb-2"><span class="badge bg-primary me-2">' + num + '</span><span>' + content + '</span></div>';
    });

    // Convert bullet points (- or *)
    text = text.replace(/^[\-\*]\s+(.*)$/gm, '<div class="ms-3 mb-2"><i class="fas fa-chevron-right text-success me-2"></i>$1</div>');

    // Convert links [text](url)
    text = text.replace(/\[([^\]]+)\]\(([^\)]+)\)/g, '<a href="$2" target="_blank" class="text-primary text-decoration-underline">$1 <i class="fas fa-external-link-alt fa-xs"></i></a>');

    // Convert emoji icons to FontAwesome
    text = text.replace(/📈/g, '<i class="fas fa-chart-line text-success"></i>');
    text = text.replace(/📊/g, '<i class="fas fa-chart-bar text-info"></i>');
    text = text.replace(/💡/g, '<i class="fas fa-lightbulb text-warning"></i>');
    text = text.replace(/⚠️/g, '<i class="fas fa-exclamation-triangle text-warning"></i>');
    text = text.replace(/✅/g, '<i class="fas fa-check-circle text-success"></i>');
    text = text.replace(/❌/g, '<i class="fas fa-times-circle text-danger"></i>');
    text = text.replace(/🎯/g, '<i class="fas fa-bullseye text-primary"></i>');
    text = text.replace(/🔥/g, '<i class="fas fa-fire text-danger"></i>');
    text = text.replace(/⭐/g, '<i class="fas fa-star text-warning"></i>');
    text = text.replace(/📉/g, '<i class="fas fa-chart-line-down text-danger"></i>');

    // Convert line breaks (double for paragraphs, single for breaks)
    text = text.replace(/\n\n+/g, '</p><p class="mb-2">');
    text = text.replace(/\n/g, '<br>');

    // Wrap in paragraph if not already wrapped
    if (!text.startsWith('<h') && !text.startsWith('<div') && !text.startsWith('<p')) {
        text = '<p class="mb-2">' + text + '</p>';
    }

    return text;
}

function showTypingIndicator() {
    const typingHtml = `
        <div class="message ai-message" id="typingIndicator">
            <div class="message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="message-content">
                <div class="typing-indicator">
                    <span class="text-white">AI sedang mengetik</span>
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#chatMessages').append(typingHtml);
    scrollToBottom();
}

function hideTypingIndicator() {
    $('#typingIndicator').remove();
}

function scrollToBottom() {
    const chatContainer = $('#chatMessages');
    if (chatContainer.length) {
        chatContainer.scrollTop(chatContainer[0].scrollHeight);
    }
}

function clearChat() {
    // Keep only the initial AI message
    $('#chatMessages').find('.message').not(':first').remove();
    chatMessages = [];
    if (typeof toastr !== 'undefined') {
        toastr.success('Percakapan telah dihapus');
    }
}

// Update AI status based on connection
function updateAIStatus(status) {
    const statusElement = $('#aiStatus');
    if (statusElement.length) {
        if (status === 'online') {
            statusElement.removeClass('bg-danger bg-warning').addClass('bg-success')
                       .html('<i class="fas fa-circle me-1"></i>Online');
        } else if (status === 'error') {
            statusElement.removeClass('bg-success bg-warning').addClass('bg-danger')
                       .html('<i class="fas fa-exclamation-circle me-1"></i>Error');
        } else {
            statusElement.removeClass('bg-success bg-danger').addClass('bg-warning')
                       .html('<i class="fas fa-clock me-1"></i>Connecting');
        }
    }
}

// Export chat function (optional)
function exportChat() {
    const metricName = $('[data-metric-name]').data('metric-name') || 'Unknown Metric';
    const businessName = $('[data-business-name]').data('business-name') || 'Business';

    const chatData = {
        metric: metricName,
        business: businessName,
        timestamp: new Date().toISOString(),
        messages: chatMessages
    };

    const dataStr = JSON.stringify(chatData, null, 2);
    const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);

    const exportFileDefaultName = `ai_chat_${Date.now()}.json`;

    const linkElement = document.createElement('a');
    linkElement.setAttribute('href', dataUri);
    linkElement.setAttribute('download', exportFileDefaultName);
    linkElement.click();
}

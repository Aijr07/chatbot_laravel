<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Image Bot</title>
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; background-color: #f4f4f9; margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .chat-container { width: 100%; max-width: 600px; height: 80vh; background-color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 12px; display: flex; flex-direction: column; overflow: hidden; }
        #chat-box { flex-grow: 1; padding: 20px; overflow-y: auto; border-bottom: 1px solid #ddd; }
        .message { margin-bottom: 15px; display: flex; flex-direction: column; }
        .message .content { max-width: 80%; padding: 10px 15px; border-radius: 18px; line-height: 1.5; word-wrap: break-word; }
        .message.user { align-items: flex-end; }
        .message.user .content { background-color: #007bff; color: white; }
        .message.bot { align-items: flex-start; }
        .message.bot .content { background-color: #e9e9eb; color: #333; }
        .message.bot img { max-width: 100%; border-radius: 12px; margin-top: 10px; }
        .input-area { padding: 15px; display: flex; align-items: center; border-top: 1px solid #eee; }
        #chat-input { flex-grow: 1; border: 1px solid #ccc; border-radius: 20px; padding: 10px 15px; font-size: 16px; outline: none; }
        #chat-input:focus { border-color: #007bff; }
        .upload-btn { background: none; border: none; font-size: 24px; cursor: pointer; padding: 0 10px; color: #555; }
        #image-upload { display: none; }
    </style>
</head>
<body>
    <div class="chat-container">
        <div id="chat-box">
            <div class="message bot">
                <div class="content">Hai! Ketik deskripsi untuk membuat gambar, atau unggah gambar (🖼️) lalu ketik instruksi untuk mengeditnya.</div>
            </div>
        </div>
        <div class="input-area">
            <input type="file" id="image-upload" accept="image/*">
            <button class="upload-btn" onclick="document.getElementById('image-upload').click();" title="Unggah gambar untuk diedit">🖼️</button>
            <input type="text" id="chat-input" placeholder="Ketik pesanmu...">
        </div>
    </div>

    <script>
    const chatBox = document.getElementById('chat-box');
    const chatInput = document.getElementById('chat-input');
    const imageUpload = document.getElementById('image-upload');
    const uploadBtn = document.querySelector('.upload-btn'); // Ambil elemen tombol
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // --- Fungsi untuk menambahkan pesan ke antarmuka ---
    function addMessage(sender, text, imageUrl = null) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}`;
        const contentDiv = document.createElement('div');
        contentDiv.className = 'content';
        
        if (text) contentDiv.innerText = text;
        if (imageUrl) {
            const img = document.createElement('img');
            img.src = imageUrl;
            if (!text) contentDiv.style.padding = '5px';
            contentDiv.appendChild(img);
        }
        
        messageDiv.appendChild(contentDiv);
        chatBox.appendChild(messageDiv);
        chatBox.scrollTop = chatBox.scrollHeight;
        return messageDiv;
    }

    // --- Fungsi utama untuk mengirim pesan ---
    async function sendMessage(imageFile = null) {
        const prompt = chatInput.value.trim();
        if (!prompt) {
            addMessage('bot', 'Harap masukkan prompt teks.');
            return;
        };

        // Tampilkan prompt teks dan pratinjau gambar jika ada
        addMessage('user', prompt, imageFile ? URL.createObjectURL(imageFile) : null);
        chatInput.value = '';

        const endpoint = "{{ route('chatbot.message') }}";
        let bodyData;
        let headers = { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' };
        
        // Cek apakah ada file gambar yang dilampirkan
        if (imageFile) {
            // Jika ada, siapkan FormData untuk mengirim file dan teks (mode EDIT)
            bodyData = new FormData();
            bodyData.append('prompt', prompt);
            bodyData.append('image', imageFile);
        } else {
            // Jika tidak ada gambar, kirim sebagai JSON biasa (mode GENERATE)
            headers['Content-Type'] = 'application/json';
            bodyData = JSON.stringify({ prompt: prompt });
        }

        let processingMessage = null;
        try {
            processingMessage = addMessage('bot', 'Sedang memproses...');
            
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: headers,
                body: bodyData
            });

            if (processingMessage) processingMessage.remove();

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.text || `Server merespons dengan status: ${response.status}`);
            }
            
            addMessage('bot', data.text, data.image || null);

        } catch (error) {
            console.error('Error:', error);
            if (processingMessage) processingMessage.remove();
            addMessage('bot', `Oops, terjadi kesalahan: ${error.message}`);
        }
    }
    
    // --- Event Listeners ---

    // 1. Saat tombol upload diklik, buka dialog file
    uploadBtn.addEventListener('click', () => {
        const prompt = chatInput.value.trim();
        if (!prompt) {
            addMessage('bot', 'Harap ketik instruksi edit terlebih dahulu sebelum memilih gambar.');
            return;
        }
        imageUpload.click(); // Buka dialog pemilihan file
    });

    // 2. Saat file dipilih, langsung kirim pesan
    imageUpload.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (!file) return;

        // Panggil sendMessage dengan file yang dipilih
        sendMessage(file);
        
        imageUpload.value = ""; // Reset input file
    });

    // 3. Saat menekan Enter, kirim pesan tanpa gambar (mode generate)
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage(null); // Kirim tanpa file
        }
    });
</script>   
</body>
</html>
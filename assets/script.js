var wecPlayer; 
var isYoutube = false;
var currentMode = 'bilingual'; // Theo dõi chế độ hiện tại

document.addEventListener('DOMContentLoaded', function() {
    // --- UI MOVER (Giữ nguyên) ---
    const playerContainer = document.querySelector('.wec-video-container-full');
    const siteHeader = document.querySelector('header') || document.querySelector('.site-header');
    if (playerContainer) {
        if (siteHeader && siteHeader.parentNode) siteHeader.parentNode.insertBefore(playerContainer, siteHeader.nextSibling);
        else document.body.prepend(playerContainer);
        playerContainer.style.display = 'flex';
    }
    
    // --- VARIABLES ---
    const transcriptContainer = document.getElementById('wec-transcript-content');
    const mp4Player = document.getElementById('wec-main-player');
    const iframeElement = document.getElementById('wec-yt-iframe');
    const popup = document.getElementById('wec-dict-popup');
    const popupWord = document.getElementById('wec-dict-word');
    const popupBody = document.getElementById('wec-dict-body');
    const popupClose = document.getElementById('wec-dict-close');
    const modeBtns = document.querySelectorAll('.wec-mode-btn');

    if (!transcriptContainer) return;

    // --- TOOLBAR ---
    if (modeBtns) {
        modeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                modeBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const mode = this.dataset.mode;
                currentMode = mode; // Cập nhật biến toàn cục
                
                transcriptContainer.className = ''; 
                transcriptContainer.classList.add('mode-' + mode);
                
                // Nếu bật chế độ Dictation -> Reset lại dòng hiện tại để sinh Quiz
                if (mode === 'dictation') {
                    const activeLine = document.querySelector('.wec-transcript-line.active');
                    if (activeLine) generateQuiz(activeLine);
                } else {
                    // Nếu tắt Dictation -> Khôi phục lại text gốc
                    restoreTranscript();
                }
            });
        });
    }

    // --- DICTATION LOGIC (MỚI) ---
    function generateQuiz(lineElement) {
        // Tránh tạo quiz lại nếu đã tạo rồi
        if (lineElement.dataset.quizReady === 'true') return;

        const subEnDiv = lineElement.querySelector('.wec-sub-en');
        const originalHTML = subEnDiv.innerHTML; // Lưu lại HTML gốc để khôi phục
        lineElement.dataset.originalHtml = originalHTML;

        // Lấy tất cả các từ (span.wec-word)
        const words = subEnDiv.querySelectorAll('.wec-word');
        let newHTML = '';
        
        words.forEach((span, index) => {
            const word = span.innerText;
            // Logic ẩn từ: Ẩn ngẫu nhiên 50% số từ (hoặc từ dài hơn 2 ký tự)
            // Để demo dễ, ta ẩn các từ ở vị trí chẵn
            if (index % 2 !== 0 && word.length > 1) {
                newHTML += `<input type="text" class="wec-input-word" data-answer="${word}" placeholder="___"> `;
            } else {
                newHTML += `<span class="wec-word">${word}</span> `;
            }
        });

        // Thêm Feedback area
        newHTML += '<div class="wec-quiz-feedback"></div>';
        
        subEnDiv.innerHTML = newHTML;
        lineElement.dataset.quizReady = 'true';

        // Gắn sự kiện cho các ô input
        const inputs = subEnDiv.querySelectorAll('.wec-input-word');
        inputs.forEach((input, idx) => {
            // Khi gõ Enter -> Check
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    checkAnswer(this);
                    // Focus ô tiếp theo nếu đúng
                    if (this.classList.contains('correct') && inputs[idx+1]) {
                        inputs[idx+1].focus();
                    }
                }
            });
        });
        
        // Focus ô đầu tiên
        if(inputs.length > 0) inputs[0].focus();
    }

    function checkAnswer(input) {
        const val = input.value.trim().toLowerCase();
        const ans = input.dataset.answer.toLowerCase().replace(/[.,\/#!$%\^&\*;:{}=\-_`~()]/g,"");
        const cleanVal = val.replace(/[.,\/#!$%\^&\*;:{}=\-_`~()]/g,"");

        if (cleanVal === ans) {
            input.classList.add('correct');
            input.classList.remove('incorrect');
            input.disabled = true; // Khóa lại
            input.value = input.dataset.answer; // Hiện từ chuẩn (viết hoa đúng)
        } else {
            input.classList.add('incorrect');
            // Sau 0.5s bỏ class rung
            setTimeout(() => input.classList.remove('incorrect'), 500);
        }
        
        // Kiểm tra xem xong hết chưa -> Chuyển câu
        const line = input.closest('.wec-transcript-line');
        const totalInputs = line.querySelectorAll('.wec-input-word').length;
        const correctInputs = line.querySelectorAll('.wec-input-word.correct').length;
        
        const feedback = line.querySelector('.wec-quiz-feedback');
        if (totalInputs === correctInputs) {
            feedback.innerHTML = '<span class="success">✨ Chính xác! Chuyển câu...</span>';
            setTimeout(() => {
                // Logic chuyển câu tiếp theo (tìm line kế tiếp và click vào nó để trigger seek)
                const nextLine = line.nextElementSibling;
                if(nextLine) {
                    const start = parseFloat(nextLine.dataset.start);
                    seekVideo(start);
                }
            }, 1500);
        }
    }

    function restoreTranscript() {
        const lines = document.querySelectorAll('.wec-transcript-line');
        lines.forEach(line => {
            if (line.dataset.originalHtml) {
                line.querySelector('.wec-sub-en').innerHTML = line.dataset.originalHtml;
                line.dataset.quizReady = 'false';
            }
        });
    }

    // --- PLAYER CONTROLS & LOOP LOGIC ---
    function pauseVideo() {
        if (isYoutube && wecPlayer && typeof wecPlayer.pauseVideo === 'function') wecPlayer.pauseVideo();
        if (mp4Player) mp4Player.pause();
    }
    
    function seekVideo(time) {
        if (isYoutube && wecPlayer) {
            wecPlayer.seekTo(time, true); wecPlayer.playVideo();
        } else if (mp4Player) {
            mp4Player.currentTime = time; mp4Player.play();
        }
    }

    // --- HIGHLIGHT & LOOP (QUAN TRỌNG CHO QUIZ) ---
    function highlightLine(currentTime) {
        const lines = document.querySelectorAll('.wec-transcript-line');
        lines.forEach(line => {
            const start = parseFloat(line.dataset.start);
            const end = parseFloat(line.dataset.end);
            
            // LOGIC LOOP: Nếu đang ở chế độ Dictation
            if (currentMode === 'dictation' && line.classList.contains('active')) {
                // Nếu video chạy quá thời gian kết thúc của câu -> Tua lại đầu câu
                if (currentTime > end) {
                    seekVideo(start);
                    return;
                }
            }

            if (currentTime >= start && currentTime < end) {
                if (!line.classList.contains('active')) {
                    document.querySelectorAll('.wec-transcript-line.active').forEach(l => l.classList.remove('active'));
                    line.classList.add('active');
                    line.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Nếu là Dictation -> Tạo Quiz cho dòng mới active này
                    if (currentMode === 'dictation') generateQuiz(line);
                }
            }
        });
    }

    // --- INIT PLAYER EVENTS ---
    if (mp4Player) mp4Player.addEventListener('timeupdate', () => highlightLine(mp4Player.currentTime));
    if (iframeElement) {
        isYoutube = true;
        if (!window.YT) {
            var tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            var firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }
        window.onYouTubeIframeAPIReady = function() {
            wecPlayer = new YT.Player('wec-yt-iframe', {
                events: {
                    'onStateChange': function(event) {
                        if (event.data == YT.PlayerState.PLAYING) {
                            var timerID = setInterval(() => {
                                if(wecPlayer.getPlayerState() != YT.PlayerState.PLAYING) clearInterval(timerID);
                                highlightLine(wecPlayer.getCurrentTime());
                            }, 250);
                        }
                    }
                }
            });
        };
    }

    // --- OTHER CLICK EVENTS (Save, Popup...) ---
    // (Giữ nguyên logic cũ)
    document.addEventListener('click', function(e) {
        if (popup && popup.style.display === 'block') {
            if (!popup.contains(e.target) && !e.target.classList.contains('wec-word')) popup.style.display = 'none';
        }
        if (e.target && e.target.id === 'wec-btn-save') {
            const btn = e.target;
            btn.innerText = 'Đang lưu...'; btn.disabled = true;
            jQuery.ajax({
                url: wec_params.ajax_url, type: 'POST',
                data: { action: 'wec_save_word', word: btn.dataset.word, meaning: btn.dataset.meaning, video_id: wec_params.post_id || 0 },
                success: function(res) {
                    if(res.success) { btn.innerText = '✔ Đã lưu'; setTimeout(() => { btn.disabled = false; }, 2000); }
                    else { alert(res.data); btn.disabled = false; }
                }
            });
        }
    });

    transcriptContainer.addEventListener('click', function(e) {
        // Nếu đang ở Dictation mode thì không cho click từ để tra cứu (tránh xung đột input)
        if (currentMode === 'dictation' && e.target.tagName === 'INPUT') return;

        if (e.target.classList.contains('wec-word')) {
            if (currentMode === 'dictation') return; // Không tra từ khi đang làm bài tập
            e.stopPropagation(); pauseVideo();
            let word = e.target.innerText.replace(/[.,\/#!$%\^&\*;:{}=\-_`~()]/g,"");
            showPopup(word, e.clientX, e.clientY);
            return;
        }
        const line = e.target.closest('.wec-transcript-line');
        if (line) {
            const seekTime = parseFloat(line.dataset.start);
            seekVideo(seekTime);
        }
    });

    function showPopup(word, x, y) { /* Code cũ giữ nguyên */ 
        if(!popup) return;
        popupWord.innerText = word; popupBody.innerHTML = 'Loading...'; popup.style.display = 'block';
        const winWidth = window.innerWidth;
        let left = x; if (x + 320 > winWidth) left = winWidth - 320 - 20;
        popup.style.top = (y + 15) + 'px'; popup.style.left = left + 'px';
        jQuery.ajax({
            url: wec_params.ajax_url, type: 'GET', data: { action: 'wec_lookup_word', word: word },
            success: function(response) {
                if (response.success) {
                    popupBody.innerHTML = `Nghĩa: <b>${response.data.meaning}</b><br><button id="wec-btn-save" data-word="${word}" data-meaning="${response.data.meaning}" style="margin-top:5px;">+ Lưu</button>`;
                }
            }
        });
    }
    if (popupClose) popupClose.addEventListener('click', () => { popup.style.display = 'none'; });
});
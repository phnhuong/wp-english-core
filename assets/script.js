var wecPlayer; 
var isYoutube = false;
var currentMode = 'bilingual';

document.addEventListener('DOMContentLoaded', function() {
    // --- UI MOVER ---
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

    // --- LOGIC TRẮC NGHIỆM TỪ VỰNG (NÂNG CẤP: FEEDBACK & SUMMARY) ---
    const quizStartBtn = document.getElementById('wec-quiz-start-btn');
    if (quizStartBtn && typeof wecQuizData !== 'undefined') {
        let score = 0;
        let questionIndex = 0;
        let totalQuestions = 5; // Số câu hỏi mỗi lượt chơi (có thể tăng lên)
        let quizPool = [];

        quizStartBtn.addEventListener('click', startQuiz);

        function startQuiz() {
            score = 0;
            questionIndex = 0;
            // Trộn danh sách từ để tạo bộ câu hỏi ngẫu nhiên
            quizPool = [...wecQuizData].sort(() => Math.random() - 0.5).slice(0, totalQuestions);
            
            // Ẩn nút bắt đầu, hiện câu hỏi đầu tiên
            nextQuestion();
        }

        function nextQuestion() {
            // KIỂM TRA KẾT THÚC GAME
            if (questionIndex >= quizPool.length) {
                showSummary();
                return;
            }

            const question = quizPool[questionIndex];
            
            // Tạo 3 đáp án sai
            let answers = [question];
            while (answers.length < 4) {
                const randomItem = wecQuizData[Math.floor(Math.random() * wecQuizData.length)];
                // Đảm bảo đáp án sai không trùng đáp án đúng và không trùng nhau
                if (!answers.includes(randomItem) && randomItem.word !== question.word) {
                    answers.push(randomItem);
                }
            }
            answers.sort(() => Math.random() - 0.5);

            // Render
            const qDiv = document.getElementById('wec-quiz-question');
            const oDiv = document.getElementById('wec-quiz-options');
            const feedbackDiv = document.getElementById('wec-quiz-feedback') || document.createElement('div');
            
            // Reset giao diện
            qDiv.innerHTML = `Câu ${questionIndex + 1}/${quizPool.length}: <br><span style="color:#2563eb">${question.word}</span> nghĩa là?`;
            oDiv.innerHTML = '';
            feedbackDiv.innerHTML = ''; // Xóa feedback cũ

            answers.forEach(ans => {
                const btn = document.createElement('button');
                btn.className = 'wec-quiz-btn';
                btn.innerText = ans.meaning;
                btn.onclick = () => {
                    // --- LOGIC CHẤM ĐIỂM & FEEDBACK ---
                    const allBtns = oDiv.querySelectorAll('button');
                    allBtns.forEach(b => b.disabled = true); // Khóa tất cả nút

                    if (ans.word === question.word) {
                        btn.classList.add('correct');
                        score++;
                        document.getElementById('wec-quiz-score').innerText = 'Điểm: ' + (score * 10);
                        // Hiệu ứng âm thanh (nếu muốn) hoặc visual
                    } else {
                        btn.classList.add('wrong');
                        // Hiện đáp án đúng để học viên biết
                        allBtns.forEach(b => {
                            if (b.innerText === question.meaning) {
                                b.classList.add('correct');
                                b.style.opacity = '1'; // Làm nổi bật đáp án đúng
                            } else if (b !== btn) {
                                b.style.opacity = '0.5'; // Làm mờ các đáp án khác
                            }
                        });
                    }
                    
                    questionIndex++;
                    // Chờ 1.5s để người dùng xem kết quả rồi mới chuyển
                    setTimeout(nextQuestion, 1500);
                };
                oDiv.appendChild(btn);
            });
        }

        function showSummary() {
            const card = document.getElementById('wec-quiz-card');
            const percentage = Math.round((score / quizPool.length) * 100);
            let message = '';
            if (percentage >= 80) message = 'Xuất sắc! 🎉';
            else if (percentage >= 50) message = 'Khá tốt! 👍';
            else message = 'Cố gắng hơn nhé! 💪';

            card.innerHTML = `
                <h2 style="color:#333; margin-bottom:10px;">Kết thúc!</h2>
                <div style="font-size:48px; font-weight:bold; color:#2563eb; margin-bottom:10px;">${score}/${quizPool.length}</div>
                <p style="font-size:18px; color:#666; margin-bottom:30px;">${message}</p>
                <button id="wec-quiz-restart-btn" style="padding:12px 25px; background:#10b981; color:#fff; border:none; border-radius:5px; cursor:pointer; font-size:16px;">Chơi lại</button>
                <br><br>
                <a href="${location.pathname.replace('trac-nghiem-tu-vung', 'kho-tu-vung-cua-toi')}" style="color:#2563eb; text-decoration:none;">← Về kho từ vựng</a>
            `;

            document.getElementById('wec-quiz-restart-btn').addEventListener('click', function() {
                location.reload(); // Reload trang để reset game đơn giản nhất
            });
        }
    }

    if (!transcriptContainer) return; // --- DỪNG NẾU KHÔNG Ở TRANG BÀI HỌC ---

    // --- CÁC PHẦN DƯỚI GIỮ NGUYÊN (PLAYER, DICTATION...) ---
    if (modeBtns) {
        modeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                modeBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const mode = this.dataset.mode;
                currentMode = mode;
                transcriptContainer.className = ''; 
                transcriptContainer.classList.add('mode-' + mode);
                if (mode === 'dictation') {
                    const activeLine = document.querySelector('.wec-transcript-line.active');
                    if (activeLine) generateQuiz(activeLine);
                } else {
                    restoreTranscript();
                }
            });
        });
    }

    function generateQuiz(lineElement) {
        if (lineElement.dataset.quizReady === 'true') return;
        const subEnDiv = lineElement.querySelector('.wec-sub-en');
        const originalHTML = subEnDiv.innerHTML;
        lineElement.dataset.originalHtml = originalHTML;
        const words = subEnDiv.querySelectorAll('.wec-word');
        let newHTML = '';
        words.forEach((span, index) => {
            const word = span.innerText;
            if (index % 2 !== 0 && word.length > 1) {
                newHTML += `<input type="text" class="wec-input-word" data-answer="${word}" placeholder="___"> `;
            } else { newHTML += `<span class="wec-word">${word}</span> `; }
        });
        newHTML += '<div class="wec-quiz-feedback"></div>';
        subEnDiv.innerHTML = newHTML;
        lineElement.dataset.quizReady = 'true';
        const inputs = subEnDiv.querySelectorAll('.wec-input-word');
        inputs.forEach((input, idx) => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    checkAnswer(this);
                    if (this.classList.contains('correct') && inputs[idx+1]) inputs[idx+1].focus();
                }
            });
        });
        if(inputs.length > 0) inputs[0].focus();
    }

    function checkAnswer(input) {
        const val = input.value.trim().toLowerCase();
        const ans = input.dataset.answer.toLowerCase().replace(/[.,\/#!$%\^&\*;:{}=\-_`~()]/g,"");
        const cleanVal = val.replace(/[.,\/#!$%\^&\*;:{}=\-_`~()]/g,"");
        if (cleanVal === ans) {
            input.classList.add('correct'); input.classList.remove('incorrect');
            input.disabled = true; input.value = input.dataset.answer;
        } else {
            input.classList.add('incorrect'); setTimeout(() => input.classList.remove('incorrect'), 500);
        }
        const line = input.closest('.wec-transcript-line');
        const totalInputs = line.querySelectorAll('.wec-input-word').length;
        const correctInputs = line.querySelectorAll('.wec-input-word.correct').length;
        if (totalInputs === correctInputs) {
            line.querySelector('.wec-quiz-feedback').innerHTML = '<span class="success">✨ Chính xác!</span>';
            setTimeout(() => {
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

    function pauseVideo() {
        if (isYoutube && wecPlayer && typeof wecPlayer.pauseVideo === 'function') wecPlayer.pauseVideo();
        if (mp4Player) mp4Player.pause();
    }
    function seekVideo(time) {
        if (isYoutube && wecPlayer) { wecPlayer.seekTo(time, true); wecPlayer.playVideo(); }
        else if (mp4Player) { mp4Player.currentTime = time; mp4Player.play(); }
    }

    function highlightLine(currentTime) {
        const lines = document.querySelectorAll('.wec-transcript-line');
        lines.forEach(line => {
            const start = parseFloat(line.dataset.start);
            const end = parseFloat(line.dataset.end);
            if (currentMode === 'dictation' && line.classList.contains('active')) {
                if (currentTime > end) { seekVideo(start); return; }
            }
            if (currentTime >= start && currentTime < end) {
                if (!line.classList.contains('active')) {
                    document.querySelectorAll('.wec-transcript-line.active').forEach(l => l.classList.remove('active'));
                    line.classList.add('active');
                    line.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (currentMode === 'dictation') generateQuiz(line);
                }
            }
        });
    }

    if (mp4Player) mp4Player.addEventListener('timeupdate', () => highlightLine(mp4Player.currentTime));
    if (iframeElement) {
        isYoutube = true;
        if (!window.YT) {
            var tag = document.createElement('script'); tag.src = "https://www.youtube.com/iframe_api";
            var firstScriptTag = document.getElementsByTagName('script')[0]; firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
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
                    if(res.success) { btn.innerText = '✔ ' + (res.data === 'Từ này đã lưu rồi.' ? 'Đã có' : 'Đã lưu'); setTimeout(() => { btn.disabled = false; }, 2000); }
                    else { alert(res.data); btn.innerText = 'Lỗi'; btn.disabled = false; }
                },
                error: function(xhr) { console.error(xhr.responseText); alert('Lỗi kết nối Server'); btn.disabled = false; }
            });
        }
    });

    transcriptContainer.addEventListener('click', function(e) {
        if (currentMode === 'dictation' && e.target.tagName === 'INPUT') return;
        if (e.target.classList.contains('wec-word')) {
            if (currentMode === 'dictation') return;
            e.stopPropagation(); pauseVideo();
            let word = e.target.innerText.replace(/[.,\/#!$%\^&\*;:{}=\-_`~()]/g,"");
            showPopup(word, e.clientX, e.clientY);
            return;
        }
        const line = e.target.closest('.wec-transcript-line');
        if (line) { const seekTime = parseFloat(line.dataset.start); seekVideo(seekTime); }
    });

    function showPopup(word, x, y) {
        if(!popup) return;
        popupWord.innerText = word; popupBody.innerHTML = 'Loading...'; popup.style.display = 'block';
        const winWidth = window.innerWidth; let left = x;
        if (x + 320 > winWidth) left = winWidth - 320 - 20;
        popup.style.top = (y + 15) + 'px'; popup.style.left = left + 'px';
        jQuery.ajax({
            url: wec_params.ajax_url, type: 'GET', data: { action: 'wec_lookup_word', word: word },
            success: function(response) {
                if (response.success) {
                    popupBody.innerHTML = `Nghĩa: <b>${response.data.meaning}</b><br><button id="wec-btn-save" data-word="${word}" data-meaning="${response.data.meaning}" style="margin-top:5px; background:#2563eb; color:#fff; border:none; padding:5px; border-radius:3px;">+ Lưu</button>`;
                }
            }
        });
    }
    if (popupClose) popupClose.addEventListener('click', () => { popup.style.display = 'none'; });
});
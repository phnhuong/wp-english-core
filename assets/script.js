var wecPlayer; 
var isYoutube = false;

document.addEventListener('DOMContentLoaded', function() {
    const transcriptContainer = document.getElementById('wec-transcript-content');
    const mp4Player = document.getElementById('wec-main-player');
    const iframeElement = document.getElementById('wec-yt-iframe');
    
    // Elements
    const popup = document.getElementById('wec-dict-popup');
    const popupWord = document.getElementById('wec-dict-word');
    const popupBody = document.getElementById('wec-dict-body');
    const popupClose = document.getElementById('wec-dict-close');
    
    // Toolbar Buttons
    const modeBtns = document.querySelectorAll('.wec-mode-btn');

    if (!transcriptContainer) return;

    // --- 1. LOGIC CHUYỂN ĐỔI CHẾ ĐỘ HIỂN THỊ ---
    if (modeBtns) {
        modeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Xóa active cũ
                modeBtns.forEach(b => b.classList.remove('active'));
                // Active nút hiện tại
                this.classList.add('active');
                
                // Lấy mode (en, vi, bilingual, hidden)
                const mode = this.dataset.mode;
                
                // Cập nhật class cho container để CSS tự xử lý ẩn hiện
                transcriptContainer.className = ''; // Reset class
                transcriptContainer.classList.add('mode-' + mode);
            });
        });
    }

    // --- CÁC HÀM CŨ (PLAYER, CLICK, AJAX...) GIỮ NGUYÊN ---
    // (Tôi viết lại đầy đủ để bạn copy cho tiện)
    
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

    // Sự kiện Click Transcript
    transcriptContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('wec-word')) {
            e.stopPropagation();
            pauseVideo();
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

    // Popup Ajax
    function showPopup(word, x, y) {
        if(!popup) return;
        popupWord.innerText = word;
        popupBody.innerHTML = '<div style="color:#666;">Translation... <span class="spinner">↻</span></div>';
        
        popup.style.display = 'block';
        
        // Vị trí
        const winWidth = window.innerWidth;
        const popupWidth = 320;
        let left = x;
        if (x + popupWidth > winWidth) left = winWidth - popupWidth - 20;
        popup.style.top = (y + 15) + 'px';
        popup.style.left = left + 'px';

        // Gọi Ajax
        if (typeof wec_params !== 'undefined') {
            jQuery.ajax({
                url: wec_params.ajax_url,
                type: 'GET',
                data: { action: 'wec_lookup_word', word: word },
                success: function(response) {
                    if (response.success) {
                        popupBody.innerHTML = `
                            <div style="font-size: 16px; margin-bottom: 5px; color: #333;">
                                Nghĩa: <b>${response.data.meaning}</b>
                            </div>
                            <div style="margin-top: 10px; padding-top:10px; border-top:1px dashed #eee;">
                                <button style="background:#2563eb; color:#fff; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">+ Lưu từ</button>
                            </div>`;
                    } else { popupBody.innerHTML = 'Error.'; }
                }
            });
        }
    }

    if (popupClose) popupClose.addEventListener('click', () => { popup.style.display = 'none'; });
    document.addEventListener('click', (e) => {
        if (popup && popup.style.display === 'block') {
            if (!popup.contains(e.target) && !e.target.classList.contains('wec-word')) {
                popup.style.display = 'none';
            }
        }
    });

    // Highlight
    function highlightLine(currentTime) {
        const lines = document.querySelectorAll('.wec-transcript-line');
        lines.forEach(line => {
            const start = parseFloat(line.dataset.start);
            const end = parseFloat(line.dataset.end);
            if (currentTime >= start && currentTime < end) {
                if (!line.classList.contains('active')) {
                    document.querySelectorAll('.wec-transcript-line.active').forEach(l => l.classList.remove('active'));
                    line.classList.add('active');
                    line.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }

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
});
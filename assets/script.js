var wecPlayer; 
var isYoutube = false;

document.addEventListener('DOMContentLoaded', function() {
    const transcriptContainer = document.getElementById('wec-transcript-content');
    const mp4Player = document.getElementById('wec-main-player');
    const iframeElement = document.getElementById('wec-yt-iframe');
    
    const popup = document.getElementById('wec-dict-popup');
    const popupWord = document.getElementById('wec-dict-word');
    const popupBody = document.getElementById('wec-dict-body');
    const popupClose = document.getElementById('wec-dict-close');
    const modeBtns = document.querySelectorAll('.wec-mode-btn');

    if (!transcriptContainer) return;

    // --- TOOLBAR MODES ---
    if (modeBtns) {
        modeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                modeBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                transcriptContainer.className = ''; 
                transcriptContainer.classList.add('mode-' + this.dataset.mode);
            });
        });
    }

    // --- PLAYER CONTROLS ---
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

    // --- CLICK EVENTS ---
    document.addEventListener('click', function(e) {
        // 1. Click Close Popup
        if (popup && popup.style.display === 'block') {
            if (!popup.contains(e.target) && !e.target.classList.contains('wec-word')) {
                popup.style.display = 'none';
            }
        }

        // 2. Click Save Button (XỬ LÝ MỚI)
        if (e.target && e.target.id === 'wec-btn-save') {
            const btn = e.target;
            const word = btn.dataset.word;
            const meaning = btn.dataset.meaning;
            
            btn.innerText = 'Đang lưu...';
            btn.disabled = true;

            jQuery.ajax({
                url: wec_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'wec_save_word',
                    word: word,
                    meaning: meaning,
                    video_id: wec_params.post_id || 0
                },
                success: function(res) {
                    if(res.success) {
                        btn.innerText = '✔ ' + (res.data === 'Từ này đã lưu rồi.' ? 'Đã có' : 'Đã lưu');
                        btn.style.background = '#10b981';
                        setTimeout(() => { btn.disabled = false; }, 2000);
                    } else {
                        alert(res.data); // Hiện lỗi từ Server (VD: Chưa login, Lỗi DB)
                        btn.innerText = 'Lỗi';
                        btn.disabled = false;
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    alert('Lỗi kết nối: ' + error);
                    btn.innerText = 'Lỗi Mạng';
                    btn.disabled = false;
                }
            });
        }
    });

    // --- CLICK TRANSCRIPT ---
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

    function showPopup(word, x, y) {
        if(!popup) return;
        popupWord.innerText = word;
        popupBody.innerHTML = '<div style="color:#666;">Translating... <span class="spinner">↻</span></div>';
        
        popup.style.display = 'block';
        
        const winWidth = window.innerWidth;
        const popupWidth = 320;
        let left = x;
        if (x + popupWidth > winWidth) left = winWidth - popupWidth - 20;
        popup.style.top = (y + 15) + 'px';
        popup.style.left = left + 'px';

        if (typeof wec_params !== 'undefined') {
            jQuery.ajax({
                url: wec_params.ajax_url,
                type: 'GET',
                data: { action: 'wec_lookup_word', word: word },
                success: function(response) {
                    if (response.success) {
                        const meaning = response.data.meaning;
                        popupBody.innerHTML = `
                            <div style="font-size: 16px; margin-bottom: 5px; color: #333;">
                                Nghĩa: <b>${meaning}</b>
                            </div>
                            <div style="margin-top: 10px; padding-top:10px; border-top:1px dashed #eee;">
                                <button id="wec-btn-save" 
                                    data-word="${word}" 
                                    data-meaning="${meaning}"
                                    style="background:#2563eb; color:#fff; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">
                                    + Lưu từ
                                </button>
                            </div>`;
                    } else { popupBody.innerHTML = 'Error translation.'; }
                }
            });
        }
    }
    
    if (popupClose) popupClose.addEventListener('click', () => { popup.style.display = 'none'; });

    // --- HIGHLIGHT ---
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
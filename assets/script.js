var wecPlayer; 
var isYoutube = false;

document.addEventListener('DOMContentLoaded', function() {
    const transcriptContainer = document.getElementById('wec-transcript-content');
    const mp4Player = document.getElementById('wec-main-player');
    const iframeElement = document.getElementById('wec-yt-iframe');
    
    // Elements Popup
    const popup = document.getElementById('wec-dict-popup');
    const popupWord = document.getElementById('wec-dict-word');
    const popupBody = document.getElementById('wec-dict-body');
    const popupClose = document.getElementById('wec-dict-close');

    if (!transcriptContainer) return;

    // --- HÀM PLAYER ---
    function pauseVideo() {
        if (isYoutube && wecPlayer && typeof wecPlayer.pauseVideo === 'function') wecPlayer.pauseVideo();
        if (mp4Player) mp4Player.pause();
    }
    
    function playVideo() {
        if (isYoutube && wecPlayer) wecPlayer.playVideo();
        if (mp4Player) mp4Player.play();
    }

    function seekVideo(time) {
        if (isYoutube && wecPlayer) {
            wecPlayer.seekTo(time, true);
            wecPlayer.playVideo();
        } else if (mp4Player) {
            mp4Player.currentTime = time;
            mp4Player.play();
        }
    }

    // --- HÀM CLICK ---
    transcriptContainer.addEventListener('click', function(e) {
        // TRƯỜNG HỢP 1: Click vào TỪ (Tra từ điển)
        if (e.target.classList.contains('wec-word')) {
            e.stopPropagation(); // QUAN TRỌNG: Ngăn không cho tua video
            
            // Dừng video ngay
            pauseVideo();

            // Lấy từ vựng
            let word = e.target.innerText;
            // Xóa dấu câu thừa (ví dụ "Hello," -> "Hello")
            word = word.replace(/[.,\/#!$%\^&\*;:{}=\-_`~()]/g,"");

            // Hiện Popup
            showPopup(word, e.clientX, e.clientY);
            return;
        }

        // TRƯỜNG HỢP 2: Click vào DÒNG (Tua video)
        const line = e.target.closest('.wec-transcript-line');
        if (line) {
            const seekTime = parseFloat(line.dataset.start);
            seekVideo(seekTime);
        }
    });

    // --- HÀM POPUP ---
    function showPopup(word, x, y) {
        if(!popup) return;
        popupWord.innerText = word;
        popupBody.innerHTML = `Đang tra nghĩa từ: <b>${word}</b>...<br><i style="color:#666; font-size:13px;">(Tính năng API từ điển sẽ có ở Giai đoạn 2)</i>`;
        
        popup.style.display = 'block';
        
        // Tính toán vị trí Popup để không bị che
        // Lấy chiều rộng màn hình
        const winWidth = window.innerWidth;
        const popupWidth = 320;
        
        let left = x;
        if (x + popupWidth > winWidth) {
            left = winWidth - popupWidth - 20; // Dịch sang trái nếu sát lề phải
        }
        
        popup.style.top = (y + 15) + 'px';
        popup.style.left = left + 'px';
    }

    // Đóng Popup
    if (popupClose) {
        popupClose.addEventListener('click', () => { popup.style.display = 'none'; });
    }
    // Click ra ngoài thì đóng
    document.addEventListener('click', (e) => {
        if (popup && popup.style.display === 'block') {
            if (!popup.contains(e.target) && !e.target.classList.contains('wec-word')) {
                popup.style.display = 'none';
            }
        }
    });

    // --- LOGIC HIGHLIGHT VÀ YOUTUBE ---
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

    if (mp4Player) {
        mp4Player.addEventListener('timeupdate', () => highlightLine(mp4Player.currentTime));
    }

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
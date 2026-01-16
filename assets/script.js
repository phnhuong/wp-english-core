// Biến toàn cục cho Youtube Player
var wecPlayer; 
var isYoutube = false;

document.addEventListener('DOMContentLoaded', function() {
    const transcriptContainer = document.getElementById('wec-transcript-content');
    const mp4Player = document.getElementById('wec-main-player');
    const iframeElement = document.getElementById('wec-yt-iframe');

    // Nếu không có transcript thì không làm gì cả
    if (!transcriptContainer) return;

    // --- HÀM DÙNG CHUNG ---
    function highlightLine(currentTime) {
        // Tìm dòng thoại phù hợp với thời gian hiện tại
        // Logic tối ưu: Chỉ loop qua các dòng chưa active để đỡ lag nếu transcript dài
        const lines = document.querySelectorAll('.wec-transcript-line');
        
        let foundActive = false;

        lines.forEach(line => {
            const start = parseFloat(line.dataset.start);
            const end = parseFloat(line.dataset.end);

            if (currentTime >= start && currentTime < end) { // Dùng < end để tránh trùng
                if (!line.classList.contains('active')) {
                    // Xóa class active ở các dòng khác
                    document.querySelectorAll('.wec-transcript-line.active').forEach(l => l.classList.remove('active'));
                    
                    line.classList.add('active');
                    // Cuộn box xuống dòng đang đọc
                    line.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                foundActive = true;
            }
        });
        
        // Nếu không tìm thấy dòng nào (đoạn nhạc dạo), xóa active cũ
        if (!foundActive && document.querySelector('.wec-transcript-line.active')) {
             // Giữ lại dòng cuối cùng hoặc xóa tùy logic, ở đây ta giữ nguyên để người dùng dễ theo dõi
        }
    }

    // --- XỬ LÝ CLICK VÀO DÒNG THOẠI (TUA VIDEO) ---
    transcriptContainer.addEventListener('click', function(e) {
        const line = e.target.closest('.wec-transcript-line');
        if (line) {
            const seekTime = parseFloat(line.dataset.start);
            
            if (isYoutube && wecPlayer && typeof wecPlayer.seekTo === 'function') {
                wecPlayer.seekTo(seekTime, true);
                wecPlayer.playVideo();
            } else if (mp4Player) {
                mp4Player.currentTime = seekTime;
                mp4Player.play();
            }
        }
    });

    // --- LOGIC 1: NẾU LÀ FILE MP4 THƯỜNG ---
    if (mp4Player) {
        mp4Player.addEventListener('timeupdate', function() {
            highlightLine(mp4Player.currentTime);
        });
    }

    // --- LOGIC 2: NẾU LÀ YOUTUBE (IFRAME API) ---
    if (iframeElement) {
        isYoutube = true;
        
        // 1. Inject Youtube API Script vào trang web
        if (!window.YT) {
            var tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            var firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }

        // 2. Hàm callback khi Youtube API tải xong
        window.onYouTubeIframeAPIReady = function() {
            wecPlayer = new YT.Player('wec-yt-iframe', {
                events: {
                    'onReady': onPlayerReady,
                    'onStateChange': onPlayerStateChange
                }
            });
        };

        // 3. Khi Player sẵn sàng -> Tạo vòng lặp kiểm tra thời gian
        // Youtube không có sự kiện timeupdate liên tục như HTML5, ta phải dùng setInterval
        var timerID;
        function onPlayerReady(event) {
            // Player đã sẵn sàng
        }

        function onPlayerStateChange(event) {
            if (event.data == YT.PlayerState.PLAYING) {
                // Nếu đang chạy -> Bắt đầu loop lấy thời gian (0.5 giây/lần)
                timerID = setInterval(function() {
                    var currentTime = wecPlayer.getCurrentTime();
                    highlightLine(currentTime);
                }, 250); // 250ms check 1 lần cho mượt
            } else {
                // Nếu dừng -> Xóa loop đỡ tốn tài nguyên
                clearInterval(timerID);
            }
        }
    }
});
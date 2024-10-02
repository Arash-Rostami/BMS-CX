<div class="digital-clock">
    <div class="time-container">
        <span id="time"></span>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap');

    .digital-clock {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
        overflow: hidden;
    }

    .time-container {
        font-family: 'Orbitron', sans-serif;
        color: #fff;
        font-size: 8rem;
        text-shadow:
            0 0 10px rgba(255, 255, 255, 0.5),
            0 0 20px rgba(255, 255, 255, 0.4),
            0 0 30px rgba(255, 255, 255, 0.3),
            0 0 40px rgba(255, 255, 255, 0.2);
        position: relative;
    }

    .time-container::after {
        content: '';
        position: absolute;
        top: -20%;
        left: -20%;
        width: 140%;
        height: 140%;
        background: radial-gradient(circle at center, transparent, rgba(0, 0, 0, 0.2));
        transform: rotate(45deg);
        pointer-events: none;
    }
</style>

<script>
    function updateTime() {
        const timeElement = document.getElementById('time');
        const now = new Date();

        let hours = now.getHours();
        let minutes = now.getMinutes();
        let seconds = now.getSeconds();

        // Format time as HH:MM:SS
        hours = hours < 10 ? '0' + hours : hours;
        minutes = minutes < 10 ? '0' + minutes : minutes;
        seconds = seconds < 10 ? '0' + seconds : seconds;

        timeElement.textContent = `${hours}:${minutes}:${seconds}`;
    }


    updateTime();
    setInterval(updateTime, 1000);
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You've Been Tracked!</title>
    <style>
        body {
            background-color: black;
            color: green;
            font-family: "Courier New", Courier, monospace;
            font-size: 20px;
            text-align: center;
            margin-top: 20%;
        }
        .hacked-text {
            animation: blink 1s infinite;
        }
        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0; }
            100% { opacity: 1; }
        }
        .typewriter {
            overflow: hidden; 
            white-space: nowrap; 
            border-right: .15em solid green; 
            animation: typing 4s steps(30, end), blink-caret .75s step-end infinite;
        }
        @keyframes typing {
            from { width: 0; }
            to { width: 100%; }
        }
        @keyframes blink-caret {
            from, to { border-color: transparent; }
            50% { border-color: green; }
        }
    </style>
</head>
<body>
    <div class="typewriter">
        <p class="hacked-text">Is system working?</p>
    </div>
    <p class="hacked-text">All your files belong to us...</p>
    <p>System Failure in <span id="countdown">10</span> seconds...</p>

    <script>
        let countdown = document.getElementById('countdown');
        let seconds = 10;

        setInterval(() => {
            if (seconds > 0) {
                seconds--;
                countdown.textContent = seconds;
            } else {
                document.body.innerHTML = '<h1 style="color: red;">SYSTEM FAILURE</h1>';
            }
        }, 1000);
    </script>
</body>
</html>

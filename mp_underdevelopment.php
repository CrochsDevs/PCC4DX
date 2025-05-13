<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Philippine Carabao Center Milk Production System - Coming Soon">
    <title>PCC Milk Production System - Coming Soon</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --pcc-green: #003974;
            --pcc-gold: #FFD700;
            --cream: #FFF9F0;
            --text-dark: #1A2F1D;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--cream) 0%, #E8F5E9 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            overflow-x: hidden;
            line-height: 1.6;
        }

        .hero {
            background: linear-gradient(160deg, var(--pcc-green) 30%, #1E401F 100%);
            width: 100%;
            padding: 4rem 0;
            text-align: center;
            clip-path: polygon(0 0, 100% 0, 100% 90%, 0 100%);
            margin-bottom: 6rem;
            position: relative;
        }

        .pcc-logo {
            width: 200px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.25));
            transition: transform 0.3s ease;
        }

        .pcc-logo:hover {
            transform: scale(1.05);
        }

        .container {
            text-align: center;
            padding: 3rem 2.5rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 25px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.1);
            margin: -8rem auto 4rem;
            max-width: 800px;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(8px);
        }

        h1 {
            color: var(--pcc-green);
            font-size: 2.8rem;
            margin: 1.5rem 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            line-height: 1.2;
        }

        .construction-text {
            font-size: 1.2rem;
            color: var(--text-dark);
            margin: 1.5rem 0;
            padding: 0 1rem;
        }

        .carabao-container {
            position: relative;
            margin: 2rem 0;
        }

        .carabao-image {
            width: 280px;
            filter: drop-shadow(0 8px 20px rgba(0,0,0,0.15));
            animation: float 3.5s ease-in-out infinite;
        }

        .animation-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 2rem auto;
            max-width: 500px;
        }

        .emoji-item {
            font-size: 2.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            animation: pulse 2s infinite;
        }

        .progress-container {
            margin: 2.5rem 0;
            padding: 0 1rem;
        }

        .progress-label {
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-bottom: 0.8rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .progress-bar {
            width: 100%;
            height: 18px;
            background: #EDF2F7;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            width: 65%;
            height: 100%;
            background: linear-gradient(90deg, var(--pcc-green) 0%, var(--pcc-gold) 100%);
            border-radius: 12px;
            transition: width 1s ease;
            position: relative;
            overflow: hidden;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(255,255,255,0.4) 50%, 
                transparent 100%);
            animation: shine 2s infinite;
        }

        .back-button {
            padding: 1rem 2.5rem;
            background: linear-gradient(45deg, var(--pcc-green) 0%, #1E401F 100%);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            margin-top: 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            box-shadow: 0 6px 20px rgba(44,95,45,0.3);
        }

        .back-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(44,95,45,0.4);
            background: linear-gradient(45deg, #1E401F 0%, var(--pcc-green) 100%);
        }

        .wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            overflow: hidden;
            line-height: 0;
            z-index: 0;
        }

        .wave svg {
            position: relative;
            display: block;
            width: calc(100% + 1.3px);
            height: 120px;
        }

        .shape-fill {
            fill: var(--pcc-green);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        @media (max-width: 768px) {
            .container {
                margin: -6rem 1.5rem 3rem;
                padding: 2rem 1.5rem;
            }

            h1 {
                font-size: 2rem;
            }

            .carabao-image {
                width: 220px;
            }

            .construction-text {
                font-size: 1rem;
            }

            .back-button {
                padding: 0.8rem 2rem;
                font-size: 1rem;
            }

            .emoji-item {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .hero {
                padding: 3rem 0;
            }

            .pcc-logo {
                width: 160px;
            }

            .container {
                border-radius: 20px;
            }

            .progress-bar {
                height: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="hero">
        
    </div>

    <div class="container">
        <h1>PCC Milk Production</h1>
        
        <div class="carabao-container">
            <img src="images/logo.png" 
                 alt="Carabao Illustration" 
                 class="carabao-image">
        </div>

        <div class="animation-grid">
            <div class="emoji-item">🐃</div>
            <div class="emoji-item">🚧</div>
            <div class="emoji-item">🥛</div>
        </div>

    <p class="construction-text">
        PCC is developing a digital platform to enhance carabao milk production, 
        improve dairy quality, and support the growth of local dairy enterprises.
    </p>


            <div class="progress-container">
        <div class="progress-label">
            <span>Development Progress</span>
            <span>90%</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: 90%;"></div>
        </div>
    </div>


        <a href="services.php" class="back-button">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Back to Services
        </a>
    </div>

    <div class="wave">
        <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
        </svg>
    </div>
</body>
</html>
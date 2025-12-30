<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WRK - Work Smarter</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta name="description"
        content="WRK - The intelligent workspace for modern teams. Streamline your projects, meetings, and collaboration.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 50%, #16213e 100%) !important;
            min-height: 100vh;
            overflow-x: hidden;
            color: #ffffff;
        }

        .wrk-page {
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            position: relative;
        }

        .gradient-text {
            background: linear-gradient(135deg, #a78bfa 0%, #ec4899 50%, #f472b6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-glow {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.2) 0%, transparent 70%);
            top: -200px;
            left: 50%;
            transform: translateX(-50%);
            pointer-events: none;
        }

        .card-glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-glass:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(102, 126, 234, 0.4);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.25);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-link:hover {
            color: #fff;
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .fade-in {
            animation: fadeIn 1s ease-out forwards;
            opacity: 0;
        }

        .fade-in-delay-1 {
            animation-delay: 0.2s;
        }

        .fade-in-delay-2 {
            animation-delay: 0.4s;
        }

        .fade-in-delay-3 {
            animation-delay: 0.6s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pulse-dot {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .grid-bg {
            background-image:
                linear-gradient(rgba(102, 126, 234, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(102, 126, 234, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
        }

        /* Text colors */
        .text-white {
            color: #ffffff !important;
        }

        .text-white-70 {
            color: rgba(255, 255, 255, 0.7);
        }

        .text-white-60 {
            color: rgba(255, 255, 255, 0.6);
        }

        .text-white-50 {
            color: rgba(255, 255, 255, 0.5);
        }

        .text-white-30 {
            color: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>

<body>
    <div class="wrk-page grid-bg">
        <div class="hero-glow"></div>

        <!-- Navigation -->
        <nav style="padding: 24px 48px; position: relative; z-index: 10;">
            <div
                style="max-width: 1280px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div
                        style="width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                        <span style="color: #fff; font-weight: 700; font-size: 18px;">W</span>
                    </div>
                    <span style="color: #fff; font-weight: 600; font-size: 20px; letter-spacing: -0.5px;">WRK</span>
                </div>

                @if (Route::has('login'))
                    <div style="display: flex; align-items: center; gap: 16px;">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn-secondary">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="nav-link">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn-primary">Get Started</a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </nav>

        <!-- Hero Section -->
        <main
            style="display: flex; align-items: center; justify-content: center; padding: 80px 24px; min-height: calc(100vh - 180px);">
            <div style="max-width: 900px; margin: 0 auto; text-align: center;">
                <!-- Badge -->
                <div class="fade-in"
                    style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 9999px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 32px;">
                    <span class="pulse-dot"
                        style="width: 8px; height: 8px; border-radius: 50%; background: #4ade80;"></span>
                    <span class="text-white-70" style="font-size: 14px;">Now in beta</span>
                </div>

                <!-- Main Heading -->
                <h1 class="fade-in fade-in-delay-1"
                    style="font-size: clamp(48px, 8vw, 72px); font-weight: 700; line-height: 1.1; margin-bottom: 24px; color: #ffffff;">
                    Work smarter with
                    <span class="gradient-text" style="display: block; margin-top: 8px;">WRK</span>
                </h1>

                <!-- Subheading -->
                <p class="fade-in fade-in-delay-2 text-white-60"
                    style="font-size: 18px; max-width: 600px; margin: 0 auto 40px; line-height: 1.7;">
                    The intelligent workspace for modern teams. Manage projects, track grants,
                    organize meetings, and collaborate seamlessly—all in one place.
                </p>

                <!-- CTA Buttons -->
                <div class="fade-in fade-in-delay-3"
                    style="display: flex; flex-wrap: wrap; gap: 16px; justify-content: center;">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn-primary">
                            Go to Dashboard
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                    @else
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn-primary">
                                Start for free
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                        @endif
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="btn-secondary">Sign in</a>
                        @endif
                    @endauth
                </div>

                <!-- Feature Cards -->
                <div
                    style="margin-top: 80px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
                    <div class="card-glass floating"
                        style="border-radius: 16px; padding: 24px; text-align: left; animation-delay: 0s;">
                        <div
                            style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.1) 100%); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                            <svg width="24" height="24" fill="none" stroke="#60a5fa" viewBox="0 0 24 24"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                        <h3 style="color: #fff; font-weight: 600; font-size: 18px; margin-bottom: 8px;">Projects</h3>
                        <p class="text-white-50" style="font-size: 14px; line-height: 1.6;">Track milestones, manage
                            deliverables, and keep your team aligned.</p>
                    </div>

                    <div class="card-glass floating"
                        style="border-radius: 16px; padding: 24px; text-align: left; animation-delay: 0.5s;">
                        <div
                            style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, rgba(168, 85, 247, 0.2) 0%, rgba(168, 85, 247, 0.1) 100%); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                            <svg width="24" height="24" fill="none" stroke="#a78bfa" viewBox="0 0 24 24"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 style="color: #fff; font-weight: 600; font-size: 18px; margin-bottom: 8px;">Grants</h3>
                        <p class="text-white-50" style="font-size: 14px; line-height: 1.6;">Manage funders, track
                            requirements, and never miss a deadline.</p>
                    </div>

                    <div class="card-glass floating"
                        style="border-radius: 16px; padding: 24px; text-align: left; animation-delay: 1s;">
                        <div
                            style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, rgba(236, 72, 153, 0.2) 0%, rgba(236, 72, 153, 0.1) 100%); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                            <svg width="24" height="24" fill="none" stroke="#f472b6" viewBox="0 0 24 24"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 style="color: #fff; font-weight: 600; font-size: 18px; margin-bottom: 8px;">Meetings</h3>
                        <p class="text-white-50" style="font-size: 14px; line-height: 1.6;">Schedule, prepare, and
                            capture insights from every meeting.</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer style="padding: 32px 24px; text-align: center; position: relative; z-index: 10;">
            <p class="text-white-30" style="font-size: 14px;">
                © {{ date('Y') }} WRK. Built with care.
            </p>
        </footer>
    </div>
</body>

</html>
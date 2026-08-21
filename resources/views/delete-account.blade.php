<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Deactivate Account | {{ $setting?->title ?? config('app.name', 'ZeroBuy') }}</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tab Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ $setting?->favicon ?? asset('assets/favicon.png') }}">

    <style>
        :root {
            --bg-color: #090d16;
            --card-bg: rgba(17, 24, 39, 0.7);
            --card-border: rgba(255, 255, 255, 0.08);
            --primary: #ef4444; /* Alert Red */
            --primary-hover: #dc2626;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --input-bg: rgba(255, 255, 255, 0.03);
            --input-border: rgba(255, 255, 255, 0.1);
            --input-focus: #3b82f6; /* Blue Focus */
            --success: #10b981; /* Green */
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient background glow elements */
        .ambient-glow-1 {
            position: absolute;
            top: 10%;
            left: 10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(239, 68, 68, 0.15) 0%, rgba(0,0,0,0) 70%);
            border-radius: 50%;
            z-index: 1;
            filter: blur(40px);
            pointer-events: none;
        }

        .ambient-glow-2 {
            position: absolute;
            bottom: 10%;
            right: 10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, rgba(0,0,0,0) 70%);
            border-radius: 50%;
            z-index: 1;
            filter: blur(60px);
            pointer-events: none;
        }

        .wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 480px;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
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

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            margin-bottom: 16px;
            padding: 12px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }

        .logo-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .app-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        .alert-box {
            background: rgba(239, 68, 68, 0.06);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 28px;
            font-size: 13.5px;
            line-height: 1.5;
            color: #fca5a5;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .alert-box svg {
            flex-shrink: 0;
            margin-top: 2px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .input {
            width: 100%;
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 14px;
            color: var(--text-main);
            outline: none;
            transition: all 0.25s ease;
        }

        .input::placeholder {
            color: rgba(156, 163, 175, 0.4);
        }

        .input:focus {
            border-color: var(--input-focus);
            background-color: rgba(255, 255, 255, 0.05);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        }

        .input-error {
            border-color: rgba(239, 68, 68, 0.5) !important;
        }

        .input-error:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15) !important;
        }

        .error-message {
            color: #fca5a5;
            font-size: 12px;
            margin-top: 6px;
            font-weight: 500;
            display: block;
        }

        .textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* Checkbox customization */
        .checkbox-container {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
            margin: 24px 0;
            user-select: none;
        }

        .checkbox-container input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .checkmark {
            height: 20px;
            width: 20px;
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .checkbox-container:hover input ~ .checkmark {
            border-color: rgba(255, 255, 255, 0.25);
        }

        .checkbox-container input:checked ~ .checkmark {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .checkmark svg {
            display: none;
            fill: none;
            stroke: white;
            stroke-width: 3;
        }

        .checkbox-container input:checked ~ .checkmark svg {
            display: block;
        }

        .checkbox-label {
            font-size: 13px;
            line-height: 1.45;
            color: var(--text-muted);
        }

        .checkbox-label strong {
            color: var(--text-main);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, #f43f5e 100%);
            border: none;
            border-radius: 12px;
            padding: 15px 24px;
            color: white;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
            background: linear-gradient(135deg, var(--primary-hover) 0%, #e11d48 100%);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* Success State */
        .success-card {
            text-align: center;
            padding: 16px 8px;
        }

        .success-icon-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 50%;
            margin-bottom: 24px;
            color: var(--success);
            animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes scaleIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .success-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 12px;
        }

        .success-desc {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 12px 24px;
            color: var(--text-main);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>

    <!-- Ambient glowing backdrops -->
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

    <div class="wrapper">
        <div class="card">
            
            @if(session('success'))
                <!-- Success State -->
                <div class="success-card">
                    <div class="success-icon-container">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <h2 class="success-title">Account Deactivated</h2>
                    <p class="success-desc">
                        Your account has been successfully deactivated. Your personal data is preserved securely but your account profile is now offline and inactive. You have been signed out from all active app sessions.
                    </p>
                    <a href="{{ url('/') }}" class="btn-secondary">Go to Home</a>
                </div>
            @else
                <!-- Form State -->
                <div class="header">
                    <div class="logo-container">
                        @if($setting && $setting->logo)
                            <img src="{{ $setting->logo }}" alt="{{ $setting->title ?? 'Logo' }}">
                        @else
                            <!-- Fallback default logo SVG -->
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                            </svg>
                        @endif
                    </div>
                    <div class="app-name">{{ $setting?->title ?? config('app.name', 'ZeroBuy') }}</div>
                    <h1 class="title">Deactivate Account</h1>
                </div>

                <div class="alert-box">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <div>
                        <strong>Important:</strong> Deactivating your account will take your profile offline and log you out of all devices immediately. Your existing order records, transaction logs, and profile data will not be deleted but you will not be able to log in.
                    </div>
                </div>

                <form action="{{ route('account.delete.submit') }}" method="POST">
                    @csrf

                    <!-- Contact/Identifier field -->
                    <div class="form-group">
                        <label class="label" for="contact">Email Address or Phone Number</label>
                        <input 
                            type="text" 
                            id="contact" 
                            name="contact" 
                            class="input @error('contact') input-error @enderror" 
                            placeholder="Enter email or phone registered on app" 
                            value="{{ old('contact') }}" 
                            required
                            autocomplete="username"
                        >
                        @error('contact')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Password field -->
                    <div class="form-group">
                        <label class="label" for="password">Confirm Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="input @error('password') input-error @enderror" 
                            placeholder="Enter your security password" 
                            required
                            autocomplete="current-password"
                        >
                        @error('password')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Reason field -->
                    <div class="form-group">
                        <label class="label" for="reason">Reason for leaving (Optional)</label>
                        <textarea 
                            id="reason" 
                            name="reason" 
                            class="input textarea @error('reason') input-error @enderror" 
                            placeholder="Please tell us why you are deactivating your account"
                        >{{ old('reason') }}</textarea>
                        @error('reason')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Confirmation Checkbox -->
                    <label class="checkbox-container">
                        <input type="checkbox" name="confirm" value="1" required {{ old('confirm') ? 'checked' : '' }}>
                        <span class="checkmark">
                            <svg viewBox="0 0 24 24" width="12" height="12">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        <span class="checkbox-label">
                            I confirm that I understand this will <strong>deactivate my account profile</strong> and that my account data is not deleted from the server.
                        </span>
                    </label>
                    @error('confirm')
                        <span class="error-message" style="margin-top: -16px; margin-bottom: 16px;">{{ $message }}</span>
                    @enderror

                    <!-- Submit Button -->
                    <button type="submit" class="btn">Deactivate Account</button>
                </form>
            @endif

        </div>
    </div>

</body>
</html>

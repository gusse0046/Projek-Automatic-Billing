@extends('layouts.app')

@section('content')
<style>
    body {
        background: linear-gradient(135deg, #1b4332 0%, #2d5016 50%, #40916c 100%);
        min-height: 100vh;
        font-family: 'Inter', sans-serif;
    }
    
    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background-image: 
            radial-gradient(circle at 20% 80%, rgba(64, 145, 108, 0.1) 0%, transparent 50%),
            radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
    }
    
    .login-card {
        background: rgba(255, 255, 255, 0.95);
        border: none;
        border-radius: 20px;
        box-shadow: 
            0 20px 40px rgba(27, 67, 50, 0.3),
            0 0 0 1px rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(15px);
        overflow: hidden;
        position: relative;
        max-width: 450px;
        width: 100%;
    }
    
    .login-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(90deg, #40916c, #95d5b2, #40916c);
    }
    
    .card-header {
        background: linear-gradient(135deg, #1b4332, #2d5016, #40916c);
        color: white;
        border: none;
        text-align: center;
        padding: 2.5rem 2rem;
        position: relative;
    }
    
    .card-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(90deg, #95d5b2, #40916c);
        border-radius: 2px;
    }
    
    .logo-container {
        margin-bottom: 1.5rem;
    }
    
    .logo-container img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid rgba(255, 255, 255, 0.8);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        object-fit: cover;
        transition: transform 0.3s ease;
        background-color: white;
        padding: 8px;
    }
    
    .logo-container img:hover {
        transform: scale(1.05);
    }
    
    .card-title {
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        letter-spacing: -0.5px;
    }
    
    .card-subtitle {
        font-size: 0.9rem;
        opacity: 0.9;
        margin-top: 0.5rem;
        font-weight: 400;
    }
    
    .card-body {
        padding: 3rem 2.5rem;
        background: rgba(255, 255, 255, 0.98);
    }
    
    .form-label {
        color: #1b4332;
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }
    
    .form-control {
        border: 2px solid #95d5b2;
        border-radius: 15px;
        padding: 0.75rem 1.25rem;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.9);
        font-family: 'Inter', sans-serif;
    }
    
    .form-control:focus {
        border-color: #40916c;
        box-shadow: 0 0 0 0.2rem rgba(64, 145, 108, 0.25);
        background: white;
        transform: translateY(-1px);
    }
    
    .form-control.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    .btn-login {
        background: linear-gradient(135deg, #1b4332, #40916c);
        border: none;
        border-radius: 15px;
        padding: 0.875rem 2rem;
        font-size: 1.1rem;
        font-weight: 600;
        color: white;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        width: 100%;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .btn-login::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }
    
    .btn-login:hover::before {
        left: 100%;
    }
    
    .btn-login:hover {
        background: linear-gradient(135deg, #40916c, #95d5b2);
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(27, 67, 50, 0.4);
    }
    
    .btn-login:active {
        transform: translateY(-1px);
    }
    
    .invalid-feedback {
        font-size: 0.875rem;
        color: #dc3545;
        margin-top: 0.5rem;
        font-weight: 500;
    }
    
    .form-floating {
        position: relative;
    }
    
    .form-floating > .form-control {
        padding: 1rem 1.25rem 0.5rem;
    }
    
    .form-floating > label {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        padding: 1rem 1.25rem;
        pointer-events: none;
        border: 1px solid transparent;
        transform-origin: 0 0;
        transition: opacity .1s ease-in-out,transform .1s ease-in-out;
        color: #6c757d;
    }
    
    /* Animation untuk form */
    .login-card {
        animation: slideUp 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(50px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    /* Form elements animation */
    .form-floating {
        animation: fadeInUp 0.6s ease-out;
        animation-fill-mode: both;
    }
    
    .form-floating:nth-child(1) { animation-delay: 0.1s; }
    .form-floating:nth-child(2) { animation-delay: 0.2s; }
    .btn-login { 
        animation: fadeInUp 0.6s ease-out;
        animation-delay: 0.3s;
        animation-fill-mode: both;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Loading state */
    .btn-login.loading {
        pointer-events: none;
        opacity: 0.7;
    }
    
    .btn-login.loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        margin: auto;
        border: 2px solid transparent;
        border-top-color: white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
        .login-container {
            padding: 1rem;
        }
        
        .card-body {
            padding: 2rem 1.5rem;
        }
        
        .card-header {
            padding: 2rem 1.5rem;
        }
        
        .logo-container img {
            width: 100px;
            height: 100px;
        }
        
        .card-title {
            font-size: 1.75rem;
        }
        
        .login-card {
            margin: 1rem;
        }
    }
    
    @media (max-width: 480px) {
        .logo-container img {
            width: 80px;
            height: 80px;
        }
        
        .card-title {
            font-size: 1.5rem;
        }
        
        .form-control {
            padding: 0.625rem 1rem;
        }
        
        .btn-login {
            padding: 0.75rem 1.5rem;
        }
    }
</style>

<div class="login-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="login-card mx-auto">
                    <div class="card-header">
                        <div class="logo-container">
                            <img src="{{ asset('image/kmi-logo.png') }}" alt="KMI Finance Logo" class="img-fluid">
                        </div>
                        <h2 class="card-title">KAYU MEBEL INDONESIA</h2>
                        <p class="card-subtitle">Billing Management System</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('login') }}" id="loginForm">
                            @csrf
                            <div class="form-floating mb-4">
                                <input type="email"
    class="form-control {{ isset($errors) && $errors->has('email') ? 'is-invalid' : '' }}"
                                       id="email" name="email" value="{{ old('email') }}" required 
                                       placeholder="Enter your email address">
                                <label for="email">Email Address</label>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       id="password" name="password" required 
                                       placeholder="Enter your password">
                                <label for="password">Password</label>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <button type="submit" class="btn btn-login" id="loginBtn">
                               
                                Sign In
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    
    loginForm.addEventListener('submit', function() {
      
        loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';
    });
    
    // Auto-focus first input
    const emailInput = document.getElementById('email');
    if (emailInput && !emailInput.value) {
        emailInput.focus();
    }
});
</script>
@endsection
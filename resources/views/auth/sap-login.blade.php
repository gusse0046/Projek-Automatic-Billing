@extends('layouts.app')

@section('content')
<style>
    body {
        background: linear-gradient(135deg, #1b4332 0%, #2d5016 50%, #40916c 100%);
        min-height: 100vh;
        font-family: 'Inter', sans-serif;
    }
    
    .sap-login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background-image: 
            radial-gradient(circle at 20% 80%, rgba(64, 145, 108, 0.1) 0%, transparent 50%),
            radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
    }
    
    .sap-login-card {
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
    
    .sap-login-card::before {
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
    
    .card-title {
        font-size: 2rem;
        font-weight: 700;
        margin: 0 0 1rem 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        letter-spacing: -0.5px;
    }
    
    .user-type-badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 0.75rem 1.5rem;
        border-radius: 25px;
        font-size: 0.9rem;
        border: 1px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(10px);
    }
    
    .card-body {
        padding: 3rem 2.5rem;
        background: rgba(255, 255, 255, 0.98);
    }
    
    .alert-info-custom {
        background: linear-gradient(135deg, rgba(64, 145, 108, 0.1), rgba(149, 213, 178, 0.1));
        border: 1px solid rgba(64, 145, 108, 0.3);
        border-radius: 15px;
        padding: 1.25rem 1.5rem;
        margin-bottom: 2rem;
        color: #1b4332;
        font-weight: 500;
    }
    
    .alert-info-custom i {
        color: #40916c;
        margin-right: 0.75rem;
        font-size: 1.1rem;
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
    
    .invalid-feedback {
        font-size: 0.875rem;
        color: #dc3545;
        margin-top: 0.5rem;
        font-weight: 500;
    }
    
    .alert-danger {
        background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
        border: 1px solid rgba(220, 53, 69, 0.3);
        border-radius: 15px;
        color: #721c24;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        font-weight: 500;
    }
    
    .btn-sap-login {
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
        margin-bottom: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .btn-sap-login::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }
    
    .btn-sap-login:hover::before {
        left: 100%;
    }
    
    .btn-sap-login:hover {
        background: linear-gradient(135deg, #40916c, #95d5b2);
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(27, 67, 50, 0.4);
    }
    
    .btn-sap-login:active {
        transform: translateY(-1px);
    }
    
    .btn-back {
        background: rgba(149, 213, 178, 0.1);
        border: 2px solid #95d5b2;
        border-radius: 15px;
        padding: 0.875rem 2rem;
        font-size: 1rem;
        font-weight: 500;
        color: #1b4332;
        transition: all 0.3s ease;
        width: 100%;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }
    
    .btn-back:hover {
        background: #95d5b2;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(149, 213, 178, 0.4);
        text-decoration: none;
    }
    
    .btn-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    /* Loading state */
    .btn-sap-login.loading {
        pointer-events: none;
        opacity: 0.7;
    }
    
    .btn-sap-login.loading::after {
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
    
    /* Icon styling */
    .btn i {
        margin-right: 0.75rem;
        font-size: 1rem;
    }
    
    /* Animation untuk form */
    .sap-login-card {
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
    
    /* Form group animation */
    .mb-3 {
        animation: fadeInUp 0.6s ease-out;
        animation-fill-mode: both;
    }
    
    .mb-3:nth-child(2) { animation-delay: 0.1s; }
    .mb-3:nth-child(3) { animation-delay: 0.2s; }
    .mb-3:nth-child(4) { animation-delay: 0.3s; }
    .btn-container { 
        animation: fadeInUp 0.6s ease-out;
        animation-delay: 0.4s;
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
    
    /* Form floating labels */
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
    
    /* Responsive design */
    @media (max-width: 768px) {
        .sap-login-container {
            padding: 1rem;
        }
        
        .card-body {
            padding: 2rem 1.5rem;
        }
        
        .card-header {
            padding: 2rem 1.5rem;
        }
        
        .card-title {
            font-size: 1.75rem;
        }
        
        .sap-login-card {
            margin: 1rem;
        }
        
        .btn-sap-login, .btn-back {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
        }
    }
    
    @media (max-width: 480px) {
        .card-title {
            font-size: 1.5rem;
        }
        
        .form-control {
            padding: 0.625rem 1rem;
        }
        
        .user-type-badge {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }
        
        .alert-info-custom {
            padding: 1rem;
            font-size: 0.9rem;
        }
    }
</style>

<div class="sap-login-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="sap-login-card mx-auto">
                    <div class="card-header">
                        <h4 class="card-title">SAP Authentication</h4>
                        <div class="user-type-badge">
                            <small>User Type: 
                                <strong>
                                    @if($selectedUserType == 'admin-finance') Admin Finance
                                    @elseif($selectedUserType == 'admin-it') Admin IT
                                    @else Exim
                                    @endif
                                </strong>
                            </small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert-info-custom">
                            <i class="fas fa-shield-alt"></i>
                            Enter your SAP credentials to access the dashboard system.
                        </div>
                        
                        <form method="POST" action="{{ route('sap.login') }}" id="sapLoginForm">
                            @csrf
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control @error('sap_username') is-invalid @enderror" 
                                       id="sap_username" name="sap_username" value="{{ old('sap_username') }}" 
                                       required placeholder="SAP Username">
                                <label for="sap_username">SAP Username</label>
                                @error('sap_username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control @error('sap_password') is-invalid @enderror" 
                                       id="sap_password" name="sap_password" required 
                                       placeholder="SAP Password">
                                <label for="sap_password">SAP Password</label>
                                @error('sap_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            @error('msg')
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                            
                            <div class="btn-container">
                                <button type="submit" class="btn btn-sap-login" id="sapLoginBtn">
                                    </i> Connect to SAP
                                </button>
                                <a href="{{ route('user-type.select') }}" class="btn btn-back">
                                    </i> Back to User Selection
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sapLoginForm = document.getElementById('sapLoginForm');
    const sapLoginBtn = document.getElementById('sapLoginBtn');
    
    sapLoginForm.addEventListener('submit', function() {
        sapLoginBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Connecting...';
    });
    
    // Auto-focus first input
    const usernameInput = document.getElementById('sap_username');
    if (usernameInput && !usernameInput.value) {
        usernameInput.focus();
    }
});
</script>
@endsection
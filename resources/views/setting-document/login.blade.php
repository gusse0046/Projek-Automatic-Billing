@extends('layouts.app')

@section('content')
<style>
/* Forest Wood Theme Styles */
.forest-bg {
    background: linear-gradient(135deg, 
        #2F4F2F 0%,     /* Dark Slate Gray */
        #228B22 25%,    /* Forest Green */
        #556B2F 50%,    /* Dark Olive Green */
        #6B8E23 75%,    /* Olive Drab */
        #8FBC8F 100%    /* Dark Sea Green */
    );
    min-height: 100vh;
    position: relative;
    overflow: hidden;
}

.forest-bg::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
        radial-gradient(circle at 20% 80%, rgba(139, 69, 19, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(34, 139, 34, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(85, 107, 47, 0.2) 0%, transparent 50%);
    animation: forestMovement 20s ease-in-out infinite;
}

@keyframes forestMovement {
    0%, 100% { 
        transform: translateX(0) translateY(0); 
        opacity: 0.7;
    }
    50% { 
        transform: translateX(-10px) translateY(-5px); 
        opacity: 0.9;
    }
}

.wood-card {
    background: linear-gradient(145deg, 
        #D2B48C 0%,     /* Tan */
        #DEB887 20%,    /* Burlywood */
        #F5DEB3 40%,    /* Wheat */
        #DEB887 60%,    /* Burlywood */
        #D2B48C 80%,    /* Tan */
        #CD853F 100%    /* Peru */
    );
    border: 3px solid #8B4513;
    border-radius: 20px;
    box-shadow: 
        0 15px 35px rgba(139, 69, 19, 0.4),
        inset 0 2px 5px rgba(255, 255, 255, 0.3),
        inset 0 -2px 5px rgba(139, 69, 19, 0.2);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
    animation: cardFloat 6s ease-in-out infinite;
}

@keyframes cardFloat {
    0%, 100% { 
        transform: translateY(0px); 
    }
    50% { 
        transform: translateY(-5px); 
    }
}

.wood-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
        repeating-linear-gradient(
            90deg,
            transparent,
            transparent 2px,
            rgba(139, 69, 19, 0.1) 2px,
            rgba(139, 69, 19, 0.1) 4px
        );
    pointer-events: none;
}

.wood-header {
    background: linear-gradient(135deg, 
        #8B4513 0%,     /* Saddle Brown */
        #A0522D 50%,    /* Sienna */
        #8B4513 100%    /* Saddle Brown */
    );
    border-bottom: 2px solid #654321;
    position: relative;
    overflow: hidden;
}

.wood-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, 
        transparent, 
        rgba(255, 215, 0, 0.3), 
        transparent
    );
    animation: woodShine 3s ease-in-out infinite;
}

@keyframes woodShine {
    0% { 
        left: -100%; 
    }
    50% { 
        left: 100%; 
    }
    100% { 
        left: 100%; 
    }
}

.forest-form-control {
    background: linear-gradient(145deg, #F5F5DC, #FFFACD);
    border: 2px solid #8B4513;
    border-radius: 12px;
    padding: 12px 16px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: inset 2px 2px 5px rgba(139, 69, 19, 0.1);
}

.forest-form-control:focus {
    background: linear-gradient(145deg, #FFFACD, #F0F8FF);
    border-color: #228B22;
    box-shadow: 
        inset 2px 2px 5px rgba(139, 69, 19, 0.1),
        0 0 0 3px rgba(34, 139, 34, 0.2);
    transform: translateY(-1px);
}

.forest-label {
    color: #654321;
    font-weight: 600;
    text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.7);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
}

.forest-label::before {
    content: '🌿';
    margin-right: 8px;
    font-size: 14px;
}

.wood-btn-primary {
    background: linear-gradient(135deg, 
        #228B22 0%,     /* Forest Green */
        #32CD32 50%,    /* Lime Green */
        #228B22 100%    /* Forest Green */
    );
    border: 2px solid #006400;
    border-radius: 12px;
    padding: 12px 24px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(34, 139, 34, 0.3);
}

.wood-btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, 
        transparent, 
        rgba(255, 255, 255, 0.3), 
        transparent
    );
    transition: left 0.5s;
}

.wood-btn-primary:hover {
    background: linear-gradient(135deg, 
        #32CD32 0%,     /* Lime Green */
        #7CFC00 50%,    /* Lawn Green */
        #32CD32 100%    /* Lime Green */
    );
    border-color: #228B22;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(34, 139, 34, 0.4);
}

.wood-btn-primary:hover::before {
    left: 100%;
}

.wood-btn-secondary {
    background: linear-gradient(135deg, 
        #D2B48C 0%,     /* Tan */
        #F5DEB3 50%,    /* Wheat */
        #D2B48C 100%    /* Tan */
    );
    border: 2px solid #8B4513;
    border-radius: 12px;
    color: #654321;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(139, 69, 19, 0.2);
}

.wood-btn-secondary:hover {
    background: linear-gradient(135deg, 
        #F5DEB3 0%,     /* Wheat */
        #FFFACD 50%,    /* Lemon Chiffon */
        #F5DEB3 100%    /* Wheat */
    );
    color: #8B4513;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(139, 69, 19, 0.3);
}

.forest-alert {
    background: linear-gradient(135deg, 
        #87CEEB 0%,     /* Sky Blue */
        #98FB98 50%,    /* Pale Green */
        #87CEEB 100%    /* Sky Blue */
    );
    border: 2px solid #4682B4;
    border-radius: 12px;
    color: #2F4F2F;
    font-weight: 500;
    box-shadow: 0 4px 15px rgba(70, 130, 180, 0.2);
}

.forest-alert-danger {
    background: linear-gradient(135deg, 
        #FFB6C1 0%,     /* Light Pink */
        #FFA07A 50%,    /* Light Salmon */
        #FFB6C1 100%    /* Light Pink */
    );
    border: 2px solid #CD5C5C;
    color: #8B0000;
}

.forest-icon {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    color: transparent;
    text-shadow: 2px 2px 4px rgba(139, 69, 19, 0.3);
    animation: iconGlow 2s ease-in-out infinite alternate;
}

@keyframes iconGlow {
    0% { 
        filter: drop-shadow(0 0 5px rgba(255, 215, 0, 0.5)); 
    }
    100% { 
        filter: drop-shadow(0 0 15px rgba(255, 215, 0, 0.8)); 
    }
}

.forest-title {
    color: #F5DEB3;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    font-weight: 700;
    letter-spacing: 1px;
}

.nature-decoration {
    position: absolute;
    color: rgba(34, 139, 34, 0.3);
    font-size: 24px;
    animation: sway 4s ease-in-out infinite;
    pointer-events: none;
}

.nature-decoration:nth-child(1) {
    top: 10%;
    left: 15%;
    animation-delay: 0s;
}

.nature-decoration:nth-child(2) {
    top: 20%;
    right: 20%;
    animation-delay: 1s;
}

.nature-decoration:nth-child(3) {
    bottom: 30%;
    left: 10%;
    animation-delay: 2s;
}

.nature-decoration:nth-child(4) {
    bottom: 15%;
    right: 15%;
    animation-delay: 3s;
}

@keyframes sway {
    0%, 100% { 
        transform: rotate(-5deg) translateX(0); 
        opacity: 0.3;
    }
    50% { 
        transform: rotate(5deg) translateX(5px); 
        opacity: 0.6;
    }
}

.test-account-box {
    background: linear-gradient(135deg, 
        rgba(255, 215, 0, 0.1) 0%,
        rgba(255, 165, 0, 0.1) 100%
    );
    border: 1px solid rgba(255, 215, 0, 0.3);
    border-radius: 8px;
    padding: 8px 12px;
    color: #8B4513;
    font-weight: 500;
    text-align: center;
    backdrop-filter: blur(5px);
}

.input-group-text {
    background: linear-gradient(135deg, #8B4513, #A0522D);
    border: 2px solid #654321;
    color: #F5DEB3;
}

.is-invalid {
    border-color: #DC143C !important;
    box-shadow: 0 0 0 3px rgba(220, 20, 60, 0.2) !important;
}

.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #DC143C;
    background: rgba(220, 20, 60, 0.1);
    padding: 4px 8px;
    border-radius: 4px;
    border-left: 3px solid #DC143C;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .wood-card {
        margin: 10px;
        border-radius: 15px;
    }
    
    .nature-decoration {
        font-size: 18px;
    }
    
    .forest-bg {
        padding: 20px 0;
    }
}

@media (max-width: 576px) {
    .wood-card {
        margin: 5px;
        border-radius: 12px;
    }
    
    .card-body {
        padding: 1.5rem !important;
    }
    
    .nature-decoration {
        font-size: 16px;
    }
}
</style>

<div class="forest-bg">
    <!-- Nature Decorations -->
    <div class="nature-decoration">🌲</div>
    <div class="nature-decoration">🍃</div>
    <div class="nature-decoration">🌿</div>
    <div class="nature-decoration">🍂</div>

    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6 col-lg-5">
            <div class="card wood-card border-0">
                <div class="card-header wood-header text-white border-0">
                    <h4 class="mb-0 forest-title text-center">
                        <i class="fas fa-cog me-2 forest-icon"></i>Setting Document Login
                    </h4>
                </div>
                <div class="card-body p-4">
                    <div class="alert forest-alert border-0 mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-2" style="color: #4682B4;"></i>
                            <span>Masukkan kredensial Setting Document untuk mengakses dashboard.</span>
                        </div>
                    </div>
                    
                    <form method="POST" action="{{ route('setting-document.login.submit') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="username" class="form-label forest-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" class="form-control forest-form-control @error('username') is-invalid @enderror" 
                                       id="username" name="username" value="{{ old('username') }}" 
                                       placeholder="Masukkan username Anda" required>
                            </div>
                            @error('username')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label forest-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control forest-form-control @error('password') is-invalid @enderror" 
                                       id="password" name="password" 
                                       placeholder="Masukkan password Anda" required>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        @error('msg')
                            <div class="alert forest-alert-danger border-0 mb-4">
                                <i class="fas fa-exclamation-triangle me-2"></i>{{ $message }}
                            </div>
                        @enderror
                        
                        <div class="d-grid gap-3">
                            <button type="submit" class="btn wood-btn-primary border-0">
                                <i class="fas fa-sign-in-alt me-2"></i>Login Setting Document
                            </button>
                          <a href="javascript:history.back()" class="btn wood-btn-secondary border-0 text-decoration-none">
    <i class="fas fa-arrow-left me-2"></i>Back
</a>
                        </div>
                    </form>
                    
                    <div class="mt-4">
                        <div class="test-account-box">
                            <div class="d-flex align-items-center justify-content-center">
                             
                                <small class="mb-0">
                                    <strong>Usernam/Pass:</strong> admin_setting / setting123
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
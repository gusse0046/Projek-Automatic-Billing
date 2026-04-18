@extends('layouts.app')

@section('content')
<style>
    body {
        background: linear-gradient(135deg, #2d5016 0%, #4a7c59 50%, #6b8e23 100%);
        min-height: 100vh;
        font-family: 'Arial', sans-serif;
    }
    
    .user-type-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background-image: 
            radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.1) 0%, transparent 50%),
            radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
        padding: 2rem 0;
    }
    
    .main-card {
        background: rgba(255, 255, 255, 0.95);
        border: none;
        border-radius: 20px;
        box-shadow: 
            0 20px 40px rgba(0, 0, 0, 0.3),
            0 0 0 1px rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        overflow: hidden;
        position: relative;
    }
    
    .main-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(90deg, #228b22, #32cd32, #9acd32, #228b22);
    }
    
    .card-header {
        background: linear-gradient(135deg, #2d5016, #4a7c59);
        color: white;
        border: none;
        text-align: center;
        padding: 2rem;
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
        background: linear-gradient(90deg, #32cd32, #9acd32);
        border-radius: 2px;
    }
    
    .card-title {
        font-size: 1.8rem;
        font-weight: 600;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .card-body {
        padding: 2.5rem;
        background: rgba(255, 255, 255, 0.98);
    }
    
    .description-text {
        color: #2d5016;
        font-size: 1.1rem;
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .user-type-card {
        border: 2px solid #e8f5e8;
        border-radius: 15px;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.9);
        margin-bottom: 1rem;
    }
    
    .user-type-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #228b22, #32cd32);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    
    .user-type-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(34, 139, 34, 0.2);
        border-color: #32cd32;
    }
    
    .user-type-card:hover::before {
        transform: scaleX(1);
    }
    
    .user-type-card.selected {
        border-color: #32cd32;
        background: linear-gradient(135deg, rgba(50, 205, 50, 0.1), rgba(154, 205, 50, 0.1));
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(34, 139, 34, 0.3);
    }
    
    .user-type-card.selected::before {
        transform: scaleX(1);
    }
    
    .user-type-card .card-body {
        padding: 2rem 1.5rem;
        background: transparent;
    }
    
    .icon-container {
        margin-bottom: 1rem;
    }
    
    .icon-admin-finance {
        color: #228b22;
        font-size: 3rem;
    }
    
    .icon-setting-document {
        color: #4a7c59;
        font-size: 3rem;
    }
    
    .icon-exim {
        color: #6b8e23;
        font-size: 3rem;
    }
    
    .user-type-title {
        color: #2d5016;
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .user-type-description {
        color: #4a7c59;
        font-size: 0.95rem;
        margin-bottom: 1rem;
    }
    
    .form-check {
        margin-top: 1rem;
    }
    
    .form-check-input {
        transform: scale(1.2);
        margin-right: 0.5rem;
    }
    
    .form-check-input:checked {
        background-color: #32cd32;
        border-color: #32cd32;
    }
    
    .form-check-label {
        color: #2d5016;
        font-weight: 500;
    }
    
    .btn-continue {
        background: linear-gradient(135deg, #228b22, #32cd32);
        border: none;
        border-radius: 12px;
        padding: 0.75rem 2.5rem;
        font-size: 1.1rem;
        font-weight: 600;
        color: white;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        min-width: 250px;
    }
    
    .btn-continue::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }
    
    .btn-continue:hover::before {
        left: 100%;
    }
    
    .btn-continue:hover {
        background: linear-gradient(135deg, #32cd32, #228b22);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(34, 139, 34, 0.4);
    }
    
    .btn-continue:disabled {
        background: #6c757d;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .btn-continue:disabled::before {
        display: none;
    }
    
    /* Animasi untuk card */
    .main-card {
        animation: slideUp 0.6s ease-out;
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .user-type-card {
        animation: fadeInUp 0.6s ease-out;
        animation-fill-mode: both;
    }
    
    .user-type-card:nth-child(1) { animation-delay: 0.1s; }
    .user-type-card:nth-child(2) { animation-delay: 0.2s; }
    .user-type-card:nth-child(3) { animation-delay: 0.3s; }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
        .card-body {
            padding: 1.5rem;
        }
        
        .user-type-card .card-body {
            padding: 1.5rem 1rem;
        }
        
        .card-title {
            font-size: 1.5rem;
        }
        
        .btn-continue {
            min-width: 200px;
        }
    }
</style>

<div class="user-type-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="card main-card">
                    <div class="card-header">
                        <h4 class="card-title">Pilih Tipe User</h4>
                        <!-- Admin IT Setting Button -->
                        <a href="{{ route('setting-document.login') }}" 
                           class="btn btn-sm btn-outline-light position-absolute" 
                           style="top: 1rem; right: 1rem; border-radius: 8px; font-size: 0.85rem;"
                           title="Admin IT - Document Settings">
                            <i class="fas fa-cog me-1"></i>Settings
                        </a>
                    </div>
                    <div class="card-body">
                        <p class="description-text">Silakan pilih tipe user untuk melanjutkan ke sistem:</p>
                        
                        <form method="POST" action="{{ route('user-type.store') }}">
                            @csrf
                            <div class="row">
                                @foreach($userTypes as $userType)
                                @if($userType->slug != 'setting-document')
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100 user-type-card" data-user-type="{{ $userType->slug }}">
                                        <div class="card-body text-center">
                                            <div class="icon-container">
                                                @if($userType->slug == 'admin-finance')
                                                    <i class="fas fa-calculator icon-admin-finance"></i>
                                                @else
                                                    <i class="fas fa-ship icon-exim"></i>
                                                @endif
                                            </div>
                                            <h5 class="user-type-title">{{ $userType->name }}</h5>
                                            <p class="user-type-description">{{ $userType->description }}</p>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" 
                                                       name="user_type" value="{{ $userType->slug }}" 
                                                       id="userType{{ $userType->id }}">
                                                <label class="form-check-label" for="userType{{ $userType->id }}">
                                                    Pilih {{ $userType->name }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @endforeach
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-continue" id="continueBtn" disabled>
                                    Lanjutkan
                                </button>
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
    const radioButtons = document.querySelectorAll('input[name="user_type"]');
    const continueBtn = document.getElementById('continueBtn');
    const userTypeCards = document.querySelectorAll('.user-type-card');
    
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            continueBtn.disabled = false;
            
            // Remove selected class from all cards
            userTypeCards.forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to current card
            const selectedCard = document.querySelector(`[data-user-type="${this.value}"]`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }
            
            // Update button text based on selection
            if (this.value === 'setting-document') {
                continueBtn.textContent = 'Lanjutkan ke Setting Login';
            } else {
                continueBtn.textContent = 'Lanjutkan ke Login SAP';
            }
        });
    });
    
    // Click card to select radio
    userTypeCards.forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            continueBtn.disabled = false;
            
            // Remove selected class from all cards
            userTypeCards.forEach(c => {
                c.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            this.classList.add('selected');
            
            // Update button text
            continueBtn.textContent = 'Lanjutkan ke Login SAP';
        });
    });
});
</script>
@endsection
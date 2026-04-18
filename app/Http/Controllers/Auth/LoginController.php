<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // ✅ PENTING: Clear SEMUA session sebelum login baru
        $this->clearAllSessions($request);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Dapatkan user yang login
            $user = Auth::user();
            
            // FIXED: Set user type session berdasarkan EMAIL yang digunakan login
            $userType = $this->determineUserTypeFromEmail($user->email);
            session(['selected_user_type' => $userType]);
            
            Log::info('=== USER LOGIN SUCCESSFUL ===', [
                'user' => $user->name,
                'email' => $user->email,
                'user_type' => $userType,
                'timestamp' => now()->toDateTimeString()
            ]);
            
            // FIXED: Routing berdasarkan email login (bukan user_type di database)
            if ($userType === 'setting-document') {
                // admin_setting@example.com -> langsung ke setting dashboard
                return redirect()->route('setting-document.login');
            }
            
            // finance@example.com dan exim@example.com -> SAP login dulu
            return redirect()->route('sap.login.form');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ]);
    }

    /**
     * KUNCI PERBAIKAN: Tentukan user type berdasarkan EMAIL yang digunakan login
     */
    private function determineUserTypeFromEmail($email)
    {
        // Mapping email ke user type
        $emailToUserType = [
            'finance@example.com' => 'admin-finance',
            'exim@example.com' => 'exim',
            'logistic@example.com' => 'logistic', // ✅ TAMBAHAN untuk logistic
            'admin_setting@example.com' => 'setting-document',
            'test@example.com' => 'admin-finance' // default untuk test account
        ];

        // Return user type berdasarkan email, atau default ke admin-finance
        return $emailToUserType[$email] ?? 'admin-finance';
    }

    /**
     * ✅ METHOD BARU: Clear semua session yang berhubungan dengan login
     */
    private function clearAllSessions(Request $request)
    {
        $sessionKeysToRemove = [
            // Session dari login biasa
            'selected_user_type',
            'user_type',
            
            // Session dari SAP login
            'sap_username',
            'sap_authenticated',
            'sap_user',
            
            // Session dari setting document
            'setting_authenticated',
            'setting_user',
            'setting_username',
            'setting_role',
            
            // Session lainnya yang mungkin ada
            'last_activity',
            'login_time'
        ];
        
        foreach ($sessionKeysToRemove as $key) {
            $request->session()->forget($key);
        }
        
        Log::info('Previous sessions cleared before new login');
    }

    /**
     * ✅ LOGOUT METHOD - DIPERBAIKI LENGKAP
     */
    public function logout(Request $request)
    {
        // 1. Catat informasi logout untuk debugging
        $userName = Auth::user()->name ?? 'Unknown User';
        $userEmail = Auth::user()->email ?? 'Unknown Email';
        
        Log::info('=== USER LOGOUT STARTED ===', [
            'user' => $userName,
            'email' => $userEmail,
            'timestamp' => now()->toDateTimeString()
        ]);
        
        // 2. HAPUS SEMUA session keys yang berhubungan dengan login
        $sessionKeysToRemove = [
            // Session dari login biasa
            'selected_user_type',
            'user_type',
            
            // Session dari SAP login
            'sap_username',
            'sap_authenticated',
            'sap_user',
            
            // Session dari setting document
            'setting_authenticated',
            'setting_user',
            'setting_username',
            'setting_role',
            
            // Session lainnya yang mungkin ada
            'last_activity',
            'login_time'
        ];
        
        // Hapus satu per satu dengan logging
        foreach ($sessionKeysToRemove as $key) {
            if ($request->session()->has($key)) {
                $request->session()->forget($key);
                Log::info("Session key removed: {$key}");
            }
        }
        
        // 3. Logout dari Auth Laravel
        Auth::logout();
        
        // 4. Invalidate session (hapus file session dari server)
        $request->session()->invalidate();
        
        // 5. Regenerate CSRF token (security)
        $request->session()->regenerateToken();
        
        Log::info('=== USER LOGOUT COMPLETED ===', [
            'user' => $userName,
            'timestamp' => now()->toDateTimeString()
        ]);
        
        // 6. Redirect ke login page dengan pesan
        return redirect()->route('login')->with('status', 'Anda telah berhasil logout.');
    }
}
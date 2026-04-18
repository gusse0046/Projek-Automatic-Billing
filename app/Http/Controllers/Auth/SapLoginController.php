<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\UserSession;
use Illuminate\Support\Facades\Auth;

class SapLoginController extends Controller
{
    public function showLoginForm()
    {
        // Pastikan user sudah login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // AMBIL user type dari SESSION (sudah di-set di LoginController berdasarkan email)
        $selectedUserType = session('selected_user_type', 'admin-finance');
        
        // Jika user type adalah setting-document, redirect ke setting login
        if ($selectedUserType === 'setting-document') {
            return redirect()->route('setting-document.login');
        }

        return view('auth.sap-login', compact('selectedUserType'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'sap_username' => 'required',
            'sap_password' => 'required',
        ]);

        // Pastikan user sudah login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // ✅ PENTING: Clear SAP session lama sebelum login baru
        $this->clearSapSessions($request);

        $user = Auth::user();
        
        // AMBIL user type dari SESSION (yang sudah di-set berdasarkan email login)
        $userType = session('selected_user_type', 'admin-finance');

        try {
            $response = Http::timeout(30)->post('http://127.0.0.1:51/api/sap-login', [
                'username' => $request->sap_username,
                'password' => $request->sap_password,
            ]);

            if ($response->successful()) {
                // Simpan session SAP
                session([
                    'sap_username' => $request->sap_username,
                    'sap_authenticated' => true,
                    'selected_user_type' => $userType // maintain user type dari login awal
                ]);

                Log::info('=== SAP LOGIN SUCCESSFUL ===', [
                    'user' => $user->name,
                    'sap_username' => $request->sap_username,
                    'user_type' => $userType,
                    'timestamp' => now()->toDateTimeString()
                ]);

                // Simpan ke database
                try {
                    UserSession::create([
                        'user_id' => Auth::id(),
                        'user_type_slug' => $userType,
                        'sap_username' => $request->sap_username,
                        'sap_login_at' => now()
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to save user session to database: ' . $e->getMessage());
                }

                // REDIRECT ke dashboard sesuai user type dari SESSION
                return $this->redirectToDashboard($userType);
            }

            $errorMessage = $response->json('error') ?? 'Login ke SAP gagal';
            return back()->withErrors(['msg' => 'Login ke SAP gagal: ' . $errorMessage]);

        } catch (\Exception $e) {
            Log::error('SAP Login Error: ' . $e->getMessage());
            return back()->withErrors(['msg' => 'Koneksi ke SAP service gagal. Pastikan Python service berjalan.']);
        }
    }

    /**
     * ✅ METHOD BARU: Clear SAP session sebelum login baru
     */
    private function clearSapSessions(Request $request)
    {
        $sapSessionKeys = [
            'sap_username',
            'sap_authenticated',
            'sap_user',
            'sap_login_time'
        ];
        
        foreach ($sapSessionKeys as $key) {
            $request->session()->forget($key);
        }
        
        Log::info('Previous SAP sessions cleared before new SAP login');
    }

    /**
     * REDIRECT ke dashboard yang tepat berdasarkan user type
     */
    private function redirectToDashboard($userType)
    {
        Log::info('Redirecting to dashboard', [
            'user_type' => $userType,
            'target_route' => $this->getDashboardRouteName($userType)
        ]);

        switch ($userType) {
            case 'admin-finance':
                return redirect()->route('dashboard.admin-finance');
                
            case 'exim':
                return redirect()->route('dashboard.exim');
                
            case 'logistic':
                return redirect()->route('dashboard.logistic');
                
            case 'setting-document':
                return redirect()->route('setting-document.dashboard');
                
            default:
                // Default fallback ke admin-finance
                Log::warning('Unknown user type, defaulting to admin-finance', [
                    'user_type' => $userType
                ]);
                return redirect()->route('dashboard.admin-finance');
        }
    }

    /**
     * ✅ METHOD HELPER: Get dashboard route name
     */
    private function getDashboardRouteName($userType)
    {
        $routes = [
            'admin-finance' => 'dashboard.admin-finance',
            'exim' => 'dashboard.exim',
            'logistic' => 'dashboard.logistic',
            'setting-document' => 'setting-document.dashboard'
        ];

        return $routes[$userType] ?? 'dashboard.admin-finance';
    }
}
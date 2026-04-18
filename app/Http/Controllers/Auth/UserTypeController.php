<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserTypeController extends Controller
{
    public function index()
    {
        $userTypes = UserType::all();
        return view('auth.select-user-type', compact('userTypes'));
    }

    public function select(Request $request)
    {
        $request->validate([
            'user_type' => 'required|exists:user_types,slug'
        ]);

        // ✅ PENTING: Clear previous user type sebelum set yang baru
        $request->session()->forget(['selected_user_type', 'user_type']);

        // Set user type baru
        session(['selected_user_type' => $request->user_type]);
        
        Log::info('=== USER TYPE SELECTED ===', [
            'user_type' => $request->user_type,
            'timestamp' => now()->toDateTimeString()
        ]);
        
        // Jika pilih setting document, redirect ke setting document login
        if ($request->user_type === 'setting-document') {
            return redirect()->route('setting-document.login');
        }
        
        return redirect()->route('sap.login.form');
    }
}
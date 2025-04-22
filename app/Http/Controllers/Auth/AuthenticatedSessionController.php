<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Clients; // Import Client model
use App\Models\User; // Import User model

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        // Attempt to authenticate the user
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // After successful authentication, get the authenticated user
            $user = Auth::user();

            // Insert 50 items into tbl_clients
            // $this->insertClientItems($user->service_counter_id);

            // Regenerate the session
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        }

        // If authentication fails, return to the login page with errors
        return back()->withErrors(['email' => 'These credentials do not match our records.']);
    }

    /**
     * Insert 50 items into tbl_client once the user logs in.
     */
    private function insertClientItems($serviceCounterId)
    {
        // Check if the service counter ID is valid
        if (!$serviceCounterId) {
            return;
        }

        // Check if the client items already exist
        $existingClients = Clients::where('service_counter_id', $serviceCounterId)->count();
        if ($existingClients > 0) {
            return; // Items already exist, no need to insert again
        } {
            // Insert 50 items into tbl_client
            $clients = [];
            for ($i = 0; $i < 50; $i++) {
                $clients[] = [
                    'service_counter_id' => $serviceCounterId,
                    'full_name' => 'Client ' . ($i + 1),
                    'contact_number' => null,
                    'priority_level_id' => 1,
                    'created_at' => now(),
                ];
            }

            // Insert the 50 clients into tbl_client
            Clients::insert($clients);
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
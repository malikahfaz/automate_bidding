<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class PlatformAccountController extends Controller
{
    /**
     * Display platform accounts.
     */
    public function index()
    {
        $accounts = PlatformAccount::all();
        return view('admin.platform-accounts.index', compact('accounts'));
    }

    /**
     * Update platform account credentials securely.
     */
    public function update(Request $request, $id)
    {
        $account = PlatformAccount::findOrFail($id);

        $request->validate([
            'email' => 'required|email',
            'password' => 'nullable|string|min:4',
            'status' => 'required|in:active,expired,error,disabled'
        ]);

        $data = [
            'email' => $request->email,
            'status' => $request->status,
        ];

        // Update password if provided (encrypted)
        if ($request->filled('password')) {
            $data['encrypted_password'] = Crypt::encryptString($request->password);
        }

        $account->update($data);

        return back()->with('success', "Master account credentials for " . strtoupper($account->platform) . " updated successfully!");
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Core\Auth;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;

final class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        return $this->view('auth/login', ['title' => 'Sign in'], 'layouts/auth');
    }

    public function login(Request $request): Response
    {
        $data = $this->validate($request, ['email' => 'required|email', 'password' => 'required']);
        if (!Auth::attempt($data['email'], $data['password'])) {
            Session::flash('errors', ['email' => 'That email and password don\'t match an account.']);
            Session::flash('old', ['email' => $data['email']]);
            return $this->back();
        }
        $user = Auth::user();
        Session::flash('success', 'Welcome back, ' . explode(' ', $user['name'])[0] . '.');
        $home = $user['role'] === 'seller' ? '/seller' : '/dashboard';
        $intended = Session::pull('url.intended');
        return $this->redirect(is_string($intended) && $intended !== '/' && $intended !== '/login' ? $intended : $home);
    }

    public function showRegister(Request $request): Response
    {
        return $this->view('auth/register', ['title' => 'Create account', 'role' => config('app.seller_signup') && $request->query('role') === 'seller' ? 'seller' : 'buyer',
            'sellerSignup' => (bool) config('app.seller_signup')], 'layouts/auth');
    }

    public function register(Request $request): Response
    {
        $sellerSignup = (bool) config('app.seller_signup');
        // Single-store mode: every new account is a shopper, whatever the form sends.
        $role = $sellerSignup && $request->input('role') === 'seller' ? 'seller' : 'buyer';
        $rules = [
            'name'     => 'required|min:2|max:120',
            'email'    => 'required|email|max:190|unique:users,email',
            'phone'    => 'nullable|phone_ph',
            'password' => 'required|min:8|max:72|password|confirmed',
            'terms'    => 'required',
        ];
        if ($role === 'seller') {
            $rules['store_name'] = 'required|min:3|max:120|unique:users,store_name';
        }
        $data = $this->validate($request, $rules, ['terms' => 'Agreement to the terms', 'name' => 'Full name']);

        $id = User::register([
            'role' => $role, 'name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'],
            'phone' => $data['phone'] ?? null, 'store_name' => $data['store_name'] ?? null,
        ]);
        $newUser = User::find($id);
        Auth::login($newUser);
        \App\Services\NotificationService::welcome($newUser);
        Session::flash('success', $role === 'seller'
            ? 'Your store is live. Add products or import some from the catalog API.'
            : 'Account created. Add your delivery address to check out faster.');
        return $this->redirect($role === 'seller' ? '/seller' : '/dashboard');
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        Session::flash('success', 'You signed out.');
        return $this->redirect('/');
    }
}

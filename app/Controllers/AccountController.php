<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\NotificationService;
use App\Services\ShippingService;
use Core\Auth;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;

final class AccountController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = Auth::user();
        return $this->view('account/edit', [
            'title' => 'Account settings', 'user' => $user, 'regions' => ShippingService::regions(),
        ], $user['role'] === 'seller' ? 'layouts/seller' : 'layouts/main');
    }

    public function update(Request $request): Response
    {
        $user = Auth::user();
        $rules = [
            'name'         => 'required|min:2|max:120',
            'email'        => 'required|email|unique:users,email,' . $user['id'],
            'phone'        => 'nullable|phone_ph',
            'address_line' => 'nullable|max:255',
            'city'         => 'nullable|max:100',
            'province'     => 'nullable|max:100',
            'region'       => 'nullable|in:' . implode(',', array_keys(ShippingService::regions())),
            'postal_code'  => 'nullable|regex:/^\d{4}$/',
        ];
        if ($user['role'] === 'seller') {
            $rules['store_name'] = 'required|min:3|max:120|unique:users,store_name,' . $user['id'];
            $rules['store_description'] = 'nullable|max:500';
        }
        $data = $this->validate($request, $rules, ['address_line' => 'Street address']);
        $data['email'] = strtolower($data['email']);
        $emailChanged = $data['email'] !== strtolower($user['email']);
        if ($emailChanged && !password_verify((string) $request->input('email_password', ''), $user['password'])) {
            Session::flash('errors', ['email_password' => 'Enter your current password to change your email.']);
            Session::flash('old', $request->only(array_keys($rules)));
            return $this->back();
        }
        User::updateById((int) $user['id'], $data);
        if ($emailChanged) {
            Auth::refresh();
            NotificationService::emailChanged(Auth::user(), $user['email'], $request->ip());
        }
        Session::flash('success', $emailChanged ? 'Settings saved. We sent a notice to your old email address.' : 'Settings saved.');
        return $this->back();
    }

    public function password(Request $request): Response
    {
        $data = $this->validate($request, [
            'current_password' => 'required',
            'password'         => 'required|min:8|max:72|password|confirmed|different:current_password',
        ], ['password' => 'New password']);
        $user = Auth::user();
        if (!password_verify($data['current_password'], $user['password'])) {
            Session::flash('errors', ['current_password' => 'Current password is incorrect.']);
            return $this->back();
        }
        Auth::changePassword((int) $user['id'], $data['password'], true);
        NotificationService::passwordChanged($user, 'changed', $request->ip());
        Session::flash('success', 'Password changed. Other devices were signed out, and we emailed you a security notice.');
        return $this->back();
    }
}

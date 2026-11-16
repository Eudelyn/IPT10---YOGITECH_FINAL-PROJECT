<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\NotificationService;
use App\Services\PasswordResetService;
use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;

/**
 * Forgot password → emailed link → choose a new password → security notice email.
 */
final class PasswordResetController extends Controller
{
    public function showForgot(Request $request): Response
    {
        return $this->view('auth/forgot', [
            'title' => 'Forgot password',
            // Developer hint (your own computer + APP_DEBUG only): emails are saved, not sent.
            'devMail' => \Core\SchemaRepair::allowedFromWeb($request) && config('mail.driver') === 'log',
        ], 'layouts/auth');
    }

    public function sendLink(Request $request): Response
    {
        $data = $this->validate($request, ['email' => 'required|email|max:190']);
        $email = strtolower($data['email']);
        $user = User::firstWhere('email', $email);

        if ($user) {
            $token = PasswordResetService::createToken((int) $user['id'], $request->ip());
            if ($token !== null) {
                NotificationService::passwordResetLink($user, absolute_url('/reset-password/' . $token), PasswordResetService::EXPIRES_MINUTES);
            }
        }

        // Same answer whether or not the account exists, so this page can't be used
        // to find out who has a Yogitech account.
        Session::flash('success', 'If an account exists for ' . $email . ', we sent a link to reset the password. Check your inbox and spam folder. The link expires in ' . PasswordResetService::EXPIRES_MINUTES . ' minutes.');
        return $this->redirect('/forgot-password');
    }

    public function showReset(Request $request, string $token): Response
    {
        $reset = PasswordResetService::findValid($token);
        if (!$reset) {
            Session::flash('error', 'That reset link is invalid or has expired. Request a new one.');
            return $this->redirect('/forgot-password');
        }
        return $this->view('auth/reset', [
            'title' => 'Choose a new password', 'token' => $token, 'email' => $reset['email'],
        ], 'layouts/auth');
    }

    public function reset(Request $request): Response
    {
        $token = (string) $request->input('token', '');
        $reset = PasswordResetService::findValid($token);
        if (!$reset) {
            Session::flash('error', 'That reset link is invalid or has expired. Request a new one.');
            return $this->redirect('/forgot-password');
        }
        $data = $this->validate($request, [
            'password' => 'required|min:8|max:72|password|confirmed',
        ], ['password' => 'New password']);

        if (password_verify($data['password'], $reset['password'])) {
            Session::flash('errors', ['password' => 'Choose a password you haven\'t used for this account.']);
            return $this->back();
        }

        $done = Database::transaction(function () use ($reset, $data) {
            if (!PasswordResetService::consume((int) $reset['reset_id'])) {
                return false;
            }
            Auth::changePassword((int) $reset['id'], $data['password'], false); // signs out every session
            return true;
        });
        if (!$done) {
            Session::flash('error', 'That reset link was already used. Request a new one if you still need it.');
            return $this->redirect('/forgot-password');
        }

        NotificationService::passwordChanged($reset, 'reset', $request->ip());
        if (Auth::check()) {
            Auth::logout();
        }
        Session::flash('success', 'Password updated. Sign in with your new password.');
        Session::flash('old', ['email' => $reset['email']]);
        return $this->redirect('/login');
    }
}

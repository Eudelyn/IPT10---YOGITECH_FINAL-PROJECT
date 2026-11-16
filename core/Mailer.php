<?php
declare(strict_types=1);

namespace Core;

use RuntimeException;
use Throwable;

/**
 * Sends email through the configured driver: log, brevo (REST API) or smtp.
 * Never throws: returns ['status' => sent|failed|logged, 'error' => ?string].
 */
final class Mailer
{
    public static function send(string $toEmail, string $toName, string $subject, string $html, string $text = '', array $meta = []): array
    {
        $driver = (string) config('mail.driver', 'log');
        $text = $text !== '' ? $text : trim(html_entity_decode(strip_tags(preg_replace('#<br\s*/?>|</p>|</tr>#i', "\n", $html)), ENT_QUOTES, 'UTF-8'));
        $msg = compact('toEmail', 'toName', 'subject', 'html', 'text');

        try {
            switch ($driver) {
                case 'brevo':
                    self::viaBrevo($msg);
                    $result = ['status' => 'sent', 'error' => null];
                    break;
                case 'gmail':
                    self::viaGmail($msg);
                    $result = ['status' => 'sent', 'error' => null];
                    break;
                case 'mailtrap':
                    self::viaMailtrap($msg);
                    $result = ['status' => 'sent', 'error' => null];
                    break;
                case 'smtp':
                    self::viaSmtp($msg);
                    $result = ['status' => 'sent', 'error' => null];
                    break;
                default:
                    self::toFile($msg);
                    $result = ['status' => 'logged', 'error' => null];
            }
        } catch (Throwable $e) {
            Logger::error('Email failed', ['to' => $toEmail, 'driver' => $driver, 'error' => $e->getMessage()]);
            self::toFile($msg); // keep a copy so nothing is lost
            $result = ['status' => 'failed', 'error' => str_limit($e->getMessage(), 480)];
        }

        try {
            Database::insert('email_logs', [
                'user_id' => $meta['user_id'] ?? null, 'order_id' => $meta['order_id'] ?? null,
                'to_email' => $toEmail, 'subject' => $subject, 'type' => $meta['type'] ?? 'general',
                'driver' => $driver, 'status' => $result['status'], 'error' => $result['error'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            // email_logs table missing (old database) — run "php yogi migrate"
        }
        return $result;
    }

    /** Brevo: POST https://api.brevo.com/v3/smtp/email with header "api-key". */
    private static function viaBrevo(array $m): void
    {
        $key = (string) config('mail.brevo.api_key');
        if ($key === '') {
            throw new RuntimeException('BREVO_API_KEY is empty in .env');
        }
        $res = (new HttpClient(15, 1))->withHeaders(['api-key' => $key])->post((string) config('mail.brevo.url'), [
            'sender'      => ['name' => config('mail.from.name'), 'email' => config('mail.from.address')],
            'to'          => [['email' => $m['toEmail'], 'name' => $m['toName'] ?: $m['toEmail']]],
            'subject'     => $m['subject'],
            'htmlContent' => self::withLogo($m['html'], 'url'),
            'textContent' => $m['text'],
        ]);
        if (!$res['ok']) {
            throw new RuntimeException('Brevo API error: ' . ($res['json']['message'] ?? $res['error'] ?? 'HTTP ' . $res['status']));
        }
    }

    /**
     * Gmail API: POST https://gmail.googleapis.com/gmail/v1/users/me/messages/send
     * with {"raw": base64url(MIME message)} and a Bearer access token.
     * The access token (valid 1 hour) comes from the saved refresh token and is cached.
     */
    private static function viaGmail(array $m): void
    {
        $res = (new HttpClient(20, 1))->withHeaders(['Authorization' => 'Bearer ' . self::gmailAccessToken()])
            ->post((string) config('mail.gmail.send_url'), ['raw' => rtrim(strtr(base64_encode(self::mime($m)), '+/', '-_'), '=')]);
        if ($res['status'] === 401) {
            // Token was revoked early: forget it and try once more with a fresh one.
            Cache::forget('gmail_access_token');
            $res = (new HttpClient(20, 0))->withHeaders(['Authorization' => 'Bearer ' . self::gmailAccessToken()])
                ->post((string) config('mail.gmail.send_url'), ['raw' => rtrim(strtr(base64_encode(self::mime($m)), '+/', '-_'), '=')]);
        }
        if (!$res['ok']) {
            $error = $res['json']['error']['message'] ?? $res['error'] ?? 'HTTP ' . $res['status'];
            if ($res['status'] === 403) {
                $error .= ' (Check that the Gmail API is enabled in your Google Cloud project.)';
            }
            throw new RuntimeException('Gmail API error: ' . $error);
        }
    }

    /** Exchanges GMAIL_REFRESH_TOKEN for an access token, cached until a minute before it expires. */
    private static function gmailAccessToken(): string
    {
        $cached = Cache::get('gmail_access_token');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }
        $c = (array) config('mail.gmail');
        foreach (['client_id' => 'GMAIL_CLIENT_ID', 'client_secret' => 'GMAIL_CLIENT_SECRET', 'refresh_token' => 'GMAIL_REFRESH_TOKEN'] as $key => $envName) {
            if ((string) ($c[$key] ?? '') === '') {
                throw new RuntimeException($envName . ' is empty in .env' . ($key === 'refresh_token' ? '. Run: php yogi gmail:auth' : ''));
            }
        }
        $res = (new HttpClient(15, 1))->postForm((string) $c['token_url'], [
            'client_id' => $c['client_id'], 'client_secret' => $c['client_secret'],
            'refresh_token' => $c['refresh_token'], 'grant_type' => 'refresh_token',
        ]);
        $token = (string) ($res['json']['access_token'] ?? '');
        if (!$res['ok'] || $token === '') {
            $code = (string) ($res['json']['error'] ?? '');
            if ($code === 'invalid_grant') {
                throw new RuntimeException('Gmail sign-in expired or was revoked. Run "php yogi gmail:auth" again. '
                    . '(While your Google Cloud app is in "Testing", Google expires it every 7 days; publish the app to stop that.)');
            }
            if ($code === 'invalid_client') {
                throw new RuntimeException('Google rejected GMAIL_CLIENT_ID / GMAIL_CLIENT_SECRET. Copy them again from Google Cloud → Clients.');
            }
            throw new RuntimeException('Couldn\'t get a Gmail access token: ' . ($res['json']['error_description'] ?? $code ?: ($res['error'] ?? 'HTTP ' . $res['status'])));
        }
        Cache::put('gmail_access_token', $token, max(60, (int) ($res['json']['expires_in'] ?? 3600) - 60));
        return $token;
    }

    /**
     * Mailtrap Email Sandbox: POST https://sandbox.api.mailtrap.io/api/send/{inbox_id}
     * with "Authorization: Bearer <token>". Emails are caught in the Mailtrap inbox, never delivered.
     */
    private static function viaMailtrap(array $m): void
    {
        $token = (string) config('mail.mailtrap.token');
        $inbox = (string) config('mail.mailtrap.inbox_id');
        if ($token === '' || $inbox === '') {
            throw new RuntimeException('MAILTRAP_API_TOKEN and MAILTRAP_INBOX_ID must be set in .env');
        }
        if (!ctype_digit($inbox)) {
            throw new RuntimeException('MAILTRAP_INBOX_ID must be the number from your inbox URL (mailtrap.io/inboxes/<number>/messages)');
        }
        $res = (new HttpClient(15, 1))->withHeaders(['Authorization' => 'Bearer ' . $token])->post(
            rtrim((string) config('mail.mailtrap.url'), '/') . '/' . $inbox,
            [
                'from'     => ['email' => config('mail.from.address'), 'name' => config('mail.from.name')],
                'to'       => [['email' => $m['toEmail'], 'name' => $m['toName'] ?: $m['toEmail']]],
                'subject'  => $m['subject'],
                'html'     => $m['html'],
                'attachments' => self::logoPng() === null ? [] : [[
                    'content' => base64_encode(self::logoPng()), 'filename' => 'yogitech.png', 'type' => 'image/png',
                    'disposition' => 'inline', 'content_id' => 'yogi-logo',
                ]],
                'text'     => $m['text'],
                'category' => 'Yogitech',
            ]
        );
        if (!$res['ok']) {
            $errors = $res['json']['errors'] ?? null;
            $detail = is_array($errors) ? implode('; ', array_map('strval', $errors)) : ($res['error'] ?? 'HTTP ' . $res['status']);
            if ($res['status'] === 401) {
                $detail = 'Unauthorized: check MAILTRAP_API_TOKEN (create one under Settings → API Tokens with access to the sandbox).';
            }
            throw new RuntimeException('Mailtrap API error: ' . $detail);
        }
    }

    /** Minimal SMTP client (SSL on 465, STARTTLS on 587) with AUTH LOGIN. */
    private static function viaSmtp(array $m): void
    {
        $c = (array) config('mail.smtp');
        $verify = (bool) config('services.verify_ssl', true);
        $ctx = stream_context_create(['ssl' => ['verify_peer' => $verify, 'verify_peer_name' => $verify, 'allow_self_signed' => !$verify]]);
        $remote = ($c['encryption'] === 'ssl' ? 'ssl://' : 'tcp://') . $c['host'] . ':' . $c['port'];
        $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            throw new RuntimeException("Can't connect to {$c['host']}:{$c['port']} ($errstr)");
        }
        stream_set_timeout($fp, 15);
        try {
            self::expect($fp, [220]);
            self::cmd($fp, 'EHLO yogitech.local', [250]);
            if ($c['encryption'] === 'tls') {
                self::cmd($fp, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('STARTTLS failed');
                }
                self::cmd($fp, 'EHLO yogitech.local', [250]);
            }
            if ($c['username'] !== '') {
                self::cmd($fp, 'AUTH LOGIN', [334]);
                self::cmd($fp, base64_encode($c['username']), [334]);
                self::cmd($fp, base64_encode(str_replace(' ', '', $c['password'])), [235]);
            }
            $from = (string) config('mail.from.address');
            self::cmd($fp, "MAIL FROM:<$from>", [250]);
            self::cmd($fp, "RCPT TO:<{$m['toEmail']}>", [250, 251]);
            self::cmd($fp, 'DATA', [354]);
            $data = preg_replace('/^\./m', '..', self::mime($m)); // dot-stuffing
            fwrite($fp, $data . "\r\n.\r\n");
            self::expect($fp, [250]);
            self::cmd($fp, 'QUIT', [221]);
        } finally {
            fclose($fp);
        }
    }

    private static function mime(array $m): string
    {
        $enc = static fn(string $s) => '=?UTF-8?B?' . base64_encode($s) . '?=';
        $alt = 'yogi_alt_' . bin2hex(random_bytes(8));
        $rel = 'yogi_rel_' . bin2hex(random_bytes(8));
        $from = (string) config('mail.from.address');
        $logo = strpos($m['html'], 'cid:yogi-logo') !== false ? self::logoPng() : null;
        $html = $logo === null ? self::withLogo($m['html'], 'url') : $m['html'];
        $headers = [
            'Date: ' . date('r'),
            'From: ' . $enc((string) config('mail.from.name')) . " <$from>",
            'To: ' . ($m['toName'] ? $enc($m['toName']) . ' ' : '') . "<{$m['toEmail']}>",
            'Subject: ' . $enc($m['subject']),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . (substr(strrchr($from, '@'), 1) ?: 'yogitech.local') . '>',
            'MIME-Version: 1.0',
        ];
        $alternative = "--$alt\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($m['text'])) . "\r\n"
            . "--$alt\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html)) . "\r\n"
            . "--$alt--";
        if ($logo === null) {
            return implode("\r\n", $headers) . "\r\nContent-Type: multipart/alternative; boundary=\"$alt\"\r\n\r\n" . $alternative;
        }
        // multipart/related = the HTML plus the bunny logo it shows via cid:yogi-logo
        return implode("\r\n", $headers) . "\r\nContent-Type: multipart/related; boundary=\"$rel\"\r\n\r\n"
            . "--$rel\r\nContent-Type: multipart/alternative; boundary=\"$alt\"\r\n\r\n" . $alternative . "\r\n"
            . "--$rel\r\nContent-Type: image/png; name=\"yogitech.png\"\r\nContent-Transfer-Encoding: base64\r\n"
            . "Content-ID: <yogi-logo>\r\nContent-Disposition: inline; filename=\"yogitech.png\"\r\n\r\n"
            . chunk_split(base64_encode($logo)) . "\r\n"
            . "--$rel--";
    }

    /** The bunny logo PNG used in emails, or null if the file is missing. */
    private static function logoPng(): ?string
    {
        static $png = false;
        if ($png === false) {
            $file = BASE_PATH . '/public/assets/img/logo.png';
            $png = is_file($file) ? (string) file_get_contents($file) : null;
        }
        return $png;
    }

    /** Swaps cid:yogi-logo for a data URI (saved copies) or the public URL (drivers without inline images). */
    private static function withLogo(string $html, string $mode): string
    {
        if (strpos($html, 'cid:yogi-logo') === false) {
            return $html;
        }
        $src = $mode === 'data' && self::logoPng() !== null
            ? 'data:image/png;base64,' . base64_encode((string) self::logoPng())
            : absolute_url('/assets/img/logo.png');
        return str_replace('cid:yogi-logo', $src, $html);
    }

    /** @param resource $fp */
    private static function cmd($fp, string $line, array $codes): string
    {
        fwrite($fp, $line . "\r\n");
        return self::expect($fp, $codes);
    }

    /** @param resource $fp */
    private static function expect($fp, array $codes): string
    {
        $reply = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $reply .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($reply, 0, 3);
        if (!in_array($code, $codes, true)) {
            $hint = $code === 535 ? ' (Gmail needs an App Password, not your normal password)' : '';
            throw new RuntimeException('SMTP error: ' . trim($reply ?: 'no reply') . $hint);
        }
        return $reply;
    }

    private static function toFile(array $m): void
    {
        $dir = BASE_PATH . '/storage/mail';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $name = date('Ymd-His') . '-' . preg_replace('/[^a-z0-9]+/i', '-', $m['toEmail']) . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.html';
        @file_put_contents("$dir/$name", "<!-- To: {$m['toName']} <{$m['toEmail']}> | Subject: " . htmlspecialchars($m['subject']) . " -->\n" . self::withLogo($m['html'], 'data'));
    }
}

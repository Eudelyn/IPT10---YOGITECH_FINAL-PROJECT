<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Database;
use Core\Exceptions\HttpException;
use Core\Request;
use Core\Response;
use Core\SchemaRepair;
use Throwable;

/**
 * Developer mailbox (/dev/mail): every email the app made, and why sending failed.
 * With MAIL_DRIVER=log nothing is sent, so this is where reset links can be clicked.
 * Only on your own computer while APP_DEBUG=true.
 */
final class DevMailController extends Controller
{
    public function index(Request $request): Response
    {
        $this->guard($request);
        $files = glob(BASE_PATH . '/storage/mail/*.html') ?: [];
        rsort($files);
        $mails = [];
        foreach (array_slice($files, 0, 30) as $f) {
            $head = (string) fgets(fopen($f, 'r'));
            preg_match('/To: (.*?) \| Subject: (.*?) -->/', $head, $m);
            $mails[] = ['file' => basename($f), 'to' => html_entity_decode($m[1] ?? '?'), 'subject' => html_entity_decode($m[2] ?? '?'), 'at' => date('M j, g:i:s A', filemtime($f))];
        }
        try {
            $logs = Database::select('SELECT * FROM email_logs ORDER BY id DESC LIMIT 15');
        } catch (Throwable $e) {
            $logs = [];
        }
        return $this->view('dev/mail', [
            'title' => 'Developer mailbox', 'mails' => $mails, 'logs' => $logs,
            'driver' => (string) config('mail.driver'),
        ]);
    }

    public function show(Request $request, string $file): Response
    {
        $this->guard($request);
        $path = BASE_PATH . '/storage/mail/' . $file;
        if (!preg_match('/^[\w.-]+\.html$/', $file) || !is_file($path)) {
            throw new HttpException(404);
        }
        return Response::html((string) file_get_contents($path))
            ->withHeader('Content-Security-Policy', "default-src 'none'; img-src data: https: http:; style-src 'unsafe-inline'");
    }

    private function guard(Request $request): void
    {
        if (!SchemaRepair::allowedFromWeb($request)) {
            throw new HttpException(404);
        }
    }
}

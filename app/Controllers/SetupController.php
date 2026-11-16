<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Exceptions\HttpException;
use Core\Request;
use Core\Response;
use Core\SchemaRepair;
use Core\Session;
use Throwable;

/** "Repair database now" button on the error page. Local machine + APP_DEBUG=true only. */
final class SetupController extends Controller
{
    public function repair(Request $request): Response
    {
        if (!SchemaRepair::allowedFromWeb($request)) {
            throw new HttpException(404);
        }
        try {
            $log = SchemaRepair::run();
            Session::flash('success', 'Database repaired: ' . implode(' · ', $log));
        } catch (Throwable $e) {
            Session::flash('error', 'Repair failed: ' . $e->getMessage() . ' Import database/yogitech.sql again in phpMyAdmin.');
        }
        return $this->back();
    }
}

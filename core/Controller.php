<?php
declare(strict_types=1);

namespace Core;

use Core\Exceptions\HttpResponseException;

/**
 * Base controller with shortcuts for views, JSON, redirects and validation.
 */
abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/main'): Response
    {
        return Response::html(View::render($view, $data, $layout));
    }

    /** @param mixed $data */
    protected function json($data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $to): Response
    {
        return Response::redirect($to);
    }

    protected function back(): Response
    {
        return back();
    }

    /**
     * Validates input. On failure, flashes errors + old input and redirects back
     * (or returns a 422 JSON response for API/AJAX requests).
     */
    protected function validate(Request $request, array $rules, array $labels = []): array
    {
        $validator = new Validator($request->all(), $rules, $labels);
        if ($validator->fails()) {
            if ($request->wantsJson()) {
                throw new HttpResponseException(Response::json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422));
            }
            $old = $request->all();
            $old = array_diff_key($old, array_flip(SENSITIVE_FIELDS));
            Session::flash('errors', $validator->errors());
            Session::flash('old', $old);
            throw new HttpResponseException(back());
        }
        return $validator->validated();
    }
}

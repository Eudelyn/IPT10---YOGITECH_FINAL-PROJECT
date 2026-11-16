<?php
declare(strict_types=1);

namespace Core;

/**
 * Rule-based validator.
 *   new Validator($data, ['email' => 'required|email|unique:users,email', 'password' => 'required|min:8|confirmed'])
 * Rules: required, nullable, string, email, numeric, integer, min, max, between, in, confirmed,
 *        password, different:field, luhn, card_expiry,
 *        unique:table,column[,ignoreId], exists:table,column, url, regex, phone_ph, alpha_dash
 */
final class Validator
{
    private array $data;
    private array $rules;
    private array $labels;
    private array $errors = [];
    private array $validated = [];

    public function __construct(array $data, array $rules, array $labels = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->labels = $labels;
        $this->run();
    }

    public function fails(): bool { return $this->errors !== []; }
    public function passes(): bool { return $this->errors === []; }
    public function errors(): array { return $this->errors; }
    public function validated(): array { return $this->validated; }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->data[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            if (is_array($value) || is_object($value)) {
                $this->errors[$field] = $this->label($field) . ' is invalid.';
                continue;
            }
            $isEmpty = $value === null || $value === '' || $value === [];

            if ($isEmpty && !in_array('required', $rules, true)) {
                $this->validated[$field] = in_array('nullable', $rules, true) ? null : $value;
                continue;
            }

            foreach ($rules as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $message = $this->check($field, $name, $param, $value, $isEmpty);
                if ($message !== null) {
                    $this->errors[$field] = $message;
                    break; // first error per field is enough
                }
            }
            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
    }

    private function label(string $field): string
    {
        return $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    /** @param mixed $value */
    private function check(string $field, string $rule, ?string $param, $value, bool $isEmpty): ?string
    {
        $label = $this->label($field);
        $isNumeric = is_numeric($value);
        switch ($rule) {
            case 'required':
                return $isEmpty ? "$label is required." : null;
            case 'nullable':
            case 'string':
                return null;
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "Enter a valid email address.";
            case 'numeric':
                return $isNumeric ? null : "$label must be a number.";
            case 'integer':
                return filter_var($value, FILTER_VALIDATE_INT) !== false ? null : "$label must be a whole number.";
            case 'min':
                if ($isNumeric && $this->hasNumericRule($field)) {
                    return (float) $value >= (float) $param ? null : "$label must be at least $param.";
                }
                return mb_strlen((string) $value) >= (int) $param ? null : "$label must be at least $param characters.";
            case 'max':
                if ($isNumeric && $this->hasNumericRule($field)) {
                    return (float) $value <= (float) $param ? null : "$label must not be greater than $param.";
                }
                return mb_strlen((string) $value) <= (int) $param ? null : "$label must not exceed $param characters.";
            case 'between':
                [$lo, $hi] = explode(',', (string) $param);
                return ((float) $value >= (float) $lo && (float) $value <= (float) $hi) ? null : "$label must be between $lo and $hi.";
            case 'in':
                return in_array((string) $value, explode(',', (string) $param), true) ? null : "Choose a valid $label.";
            case 'confirmed':
                return ($value === ($this->data[$field . '_confirmation'] ?? null)) ? null : "$label confirmation doesn't match.";
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) ? null : "$label must be a valid URL.";
            case 'regex':
                return preg_match((string) $param, (string) $value) ? null : "$label format is invalid.";
            case 'alpha_dash':
                return preg_match('/^[A-Za-z0-9_-]+$/', (string) $value) ? null : "$label may only contain letters, numbers, dashes and underscores.";
            case 'password':
                // Same strength rule everywhere: sign up, reset and change password.
                return preg_match('/[A-Za-z]/', (string) $value) && preg_match('/\d/', (string) $value)
                    ? null : "$label must contain at least one letter and one number.";
            case 'different':
                return (string) $value !== (string) ($this->data[(string) $param] ?? '') ? null : "$label must be different from your current password.";
            case 'luhn':
                return self::luhn((string) $value) ? null : "$label is not a valid card number.";
            case 'card_expiry':
                return self::expiryInFuture((string) $value) ? null : "$label is in the past.";
            case 'phone_ph':
                $digits = preg_replace('/\D/', '', (string) $value);
                return preg_match('/^(639\d{9}|09\d{9})$/', (string) $digits) ? null : "Enter a valid PH mobile number (09XXXXXXXXX).";
            case 'unique':
                [$table, $column, $ignore] = array_pad(explode(',', (string) $param), 3, null);
                $sql = "SELECT COUNT(*) FROM `$table` WHERE `" . ($column ?: $field) . "` = ?";
                $params = [$value];
                if ($ignore) {
                    $sql .= ' AND id <> ?';
                    $params[] = (int) $ignore;
                }
                return (int) Database::scalar($sql, $params) === 0 ? null : "This $label is already taken.";
            case 'exists':
                [$table, $column] = array_pad(explode(',', (string) $param), 2, null);
                return (int) Database::scalar("SELECT COUNT(*) FROM `$table` WHERE `" . ($column ?: $field) . "` = ?", [$value]) > 0
                    ? null : "The selected $label is invalid.";
        }
        throw new \InvalidArgumentException("Unknown validation rule [$rule]");
    }

    /** Luhn checksum used by every real card number. */
    public static function luhn(string $number): bool
    {
        $digits = preg_replace('/\D/', '', $number);
        if (strlen($digits) < 13 || strlen($digits) > 19) {
            return false;
        }
        $sum = 0;
        $double = false;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $d = (int) $digits[$i];
            if ($double) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $sum += $d;
            $double = !$double;
        }
        return $sum % 10 === 0;
    }

    /** "MM/YY" that is this month or later. */
    public static function expiryInFuture(string $value, ?int $now = null): bool
    {
        if (!preg_match('#^(0[1-9]|1[0-2])/(\d{2})$#', $value, $m)) {
            return false;
        }
        $now = $now ?? time();
        $exp = (2000 + (int) $m[2]) * 12 + (int) $m[1];
        $cur = (int) date('Y', $now) * 12 + (int) date('n', $now);
        return $exp >= $cur;
    }

    private function hasNumericRule(string $field): bool
    {
        $rules = is_array($this->rules[$field]) ? $this->rules[$field] : explode('|', $this->rules[$field]);
        return in_array('numeric', $rules, true) || in_array('integer', $rules, true);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use App\Models\Campus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;


class RegisteredUserController extends Controller
{
    private int $verifyResendWindowSeconds = 300;
    public function create()
    {
        return view('auth.register');
    }

    private function throttleKeyForVerification(string $email): string
    {
        return 'verify-resend:'.mb_strtolower(trim($email));
    }

public function store(Request $request, FirebaseAuth $firebaseAuth)
{
    // Normalize email to lowercase early
    $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);

    // DNS/HIBP gates (HIBP only in prod)
    $online  = $this->isOnline();
    $useDns  = $online;
    $useHibp = $online && app()->isProduction();

    $pwRule = PasswordRule::min(8)->mixedCase()->numbers();
    if ($useHibp) {
        $pwRule = $pwRule->uncompromised(10000);
    }

    Log::info('[Register][Hybrid] Start', [
        'email_raw' => $request->input('email'),
        'online'    => $online,
        'useDns'    => $useDns,
        'useHibp'   => $useHibp,
        'ip'        => $request->ip(),
        'ua'        => substr((string) $request->userAgent(), 0, 255),
    ]);

    $rules = [
        'first_name'  => ['required','string','max:60'],
        'middle_name' => ['nullable','string','max:60'],
        'last_name'   => ['required','string','max:60'],
        'cp_no'       => ['required','string','regex:/^(09\d{9}|\+639\d{9})$/'],
        'email'       => ['required','string','email:rfc'.($useDns ? ',dns' : ''),'max:255','unique:users,email'],
        'password'    => ['required','confirmed', $pwRule],
        'terms'       => ['accepted'],
    ];

    $messages = [
        'terms.accepted'     => 'You must accept the Terms and Privacy Policy to continue.',
        'email.unique'       => 'That email is already registered. If you are a student, please contact the MIS for support.',
        'password.confirmed' => 'Passwords do not match.',
    ];

    $attributes = [
        'cp_no' => 'mobile number',
    ];

    $validator = Validator::make($request->all(), $rules, $messages, $attributes);

    if ($validator->fails()) {
        Log::warning('[Register][Hybrid] Validation failed', [
            'errors' => $validator->errors()->toArray(),
            'old'    => $request->except(['password','password_confirmation']),
        ]);
        $this->toast('Please correct the highlighted fields.', 'error', 6000);
        return back()->withErrors($validator)->withInput();
    }

    // Optional heads-up when offline (DNS/HIBP skipped)
    if (! $online) {
        Log::notice('[Register][Hybrid] Offline hint toast');
        $this->toast('You appear to be offline. Check your internet connection and try again.', 'warning', 6000);
    }

    $data     = $validator->validated();
    $email    = strtolower(trim($data['email']));
    $fullName = trim($data['first_name'].' '.($data['middle_name'] ? $data['middle_name'].' ' : '').$data['last_name']);

    // Role detection via PSU email
    $psuInfo  = $this->parsePsuEmail($email);
    $roleName = $psuInfo ? 'student' : 'guest';

    $firebaseUser = null;

    DB::beginTransaction();
    try {
        // Guard: email already exists in Firebase?
        try {
            Log::info('[Register][Hybrid] Firebase pre-check getUserByEmail');
            $existing = $firebaseAuth->getUserByEmail($email);

            if ($existing) {
                $isVerified = (bool) ($existing->emailVerified ?? false);
                Log::info('[Register][Hybrid] Firebase user found', [
                    'email' => $email,
                    'uid'   => $existing->uid,
                    'verified' => $isVerified,
                ]);

                // Try to find the local user row
                $local = User::where('email', $email)->first();

                if (! $isVerified) {
                    // Ensure there's a local row to send Laravel's verification email from.
                    if (! $local) {
                        Log::info('[Register][Hybrid] No local row; creating minimal local user for verification resend');

                        // Prefer Firebase displayName if present; else fall back to submitted parts.
                        $displayName = trim((string) ($existing->displayName ?? '')) ?: $fullName;

                        $local = User::create([
                            'firebase_uid' => $existing->uid,
                            'name'         => $displayName,
                            'first_name'   => $data['first_name'] ?? null,
                            'middle_name'  => $data['middle_name'] ?? null,
                            'last_name'    => $data['last_name'] ?? null,
                            'cp_no'        => $data['cp_no'] ?? null,
                            'email'        => $email,
                            'password'     => null,       // Firebase owns auth
                            'photo_path'   => null,
                            'info_status'  => false,
                        ]);

                        // Assign a sensible role based on email (student/guest) if none yet
                        if (method_exists($local, 'assignRole')) {
                            $local->assignRole($roleName);
                        }
                    }

                    // If the local user still hasn't verified, resend Laravel verification
                    if (method_exists($local, 'hasVerifiedEmail') && ! $local->hasVerifiedEmail()) {
                    // Rate-limit verification resends: 1 per 5 minutes
                    $key = $this->throttleKeyForVerification($email);
                    $decaySeconds = $this->verifyResendWindowSeconds;

                    if (RateLimiter::tooManyAttempts($key, 1)) {
                        $availableIn = RateLimiter::availableIn($key);
                        Log::info('[Register][Hybrid] Verification resend throttled', [
                            'email' => $email,
                                'available_in_sec' => $availableIn,
                            ]);

                            $mins = max(1, (int) ceil($availableIn / 60));
                            $this->toast("We’ve recently sent a verification link. Please check your inbox or try again in about {$mins} minute(s).", 'warning', 7000);

                            return back()
                                ->withErrors([
                                    'email' => "A verification link was sent recently. Please try again in about {$mins} minute(s).",
                                ])
                                ->withInput();
                        }

                        // Not throttled → record a hit and send
                        RateLimiter::hit($key, $decaySeconds);

                        try {
                            $local->sendEmailVerificationNotification();
                            Log::info('[Register][Hybrid] Resent Laravel verification email', ['user_id' => $local->id, 'email' => $email]);
                        } catch (\Throwable $mailEx) {
                            Log::warning('[Register][Hybrid] Failed to resend verification email', [
                                'email' => $email,
                                'err'   => $mailEx->getMessage(),
                            ]);
                        }
                    }

                    // Tell user we resent the link and stop here
                    $this->toast('This email is already registered but not yet verified. We’ve sent a new verification link — please check your inbox.', 'info', 7000);
                    return back()
                        ->withErrors(['email' => 'A new verification link has been sent to your email. Please verify to continue.'])
                        ->withInput();
                }

                // Already verified → keep your existing behavior
                Log::warning('[Register][Hybrid] Firebase user already exists and is verified', ['email' => $email]);
                $this->toast('This email is already registered and verified. Please sign in or reset your password.', 'warning', 6000);
                return back()
                    ->withErrors(['email' => 'This email is already registered and verified. Please sign in or reset your password.'])
                    ->withInput();
            }
        } catch (AuthException $e) {
            if ($this->isNetworkError($e)) {
                Log::warning('[Register][Hybrid] Network while checking existing', ['email' => $email, 'msg' => $e->getMessage()]);
                $this->toast('Cannot reach the authentication service. Check your internet connection and try again.', 'error', 6000);
                return back()
                    ->withErrors(['email' => 'You appear to be offline. Please try again when you have an internet connection.'])
                    ->withInput();
            }
            Log::info('[Register][Hybrid] Firebase pre-check says not found (OK).');
        }

        // 1) Create in Firebase Auth (credentials live there)
        Log::info('[Register][Hybrid] Firebase createUser start');
        $firebaseUser = $firebaseAuth->createUser([
            'email'       => $email,
            'password'    => $data['password'],
            'displayName' => $fullName,
        ]);
        Log::info('[Register][Hybrid] Firebase createUser ok', ['uid' => $firebaseUser->uid]);

        // 2) Create local user row (NO password; Firebase owns it). photo_path remains NULL for now.
        Log::info('[Register][Hybrid] Local DB insert start');
        $user = User::create([
            'firebase_uid' => $firebaseUser->uid,
            'name'         => $fullName,
            'first_name'   => $data['first_name'],
            'last_name'    => $data['last_name'],
            'middle_name'  => $data['middle_name'] ?? null,
            'cp_no'        => $data['cp_no'],
            'email'        => $email,
            'password'     => null,
            'photo_path'   => null,
            'info_status'  => false,
        ]);

        // If PSU, seed student fields
        if ($psuInfo) {
            $user->student_number     = $psuInfo['student_number'];
            $user->student_campus_id  = $psuInfo['campus_id']; 
            $user->save();
        }

        Log::info('[Register][Hybrid] Local DB insert ok', ['user_id' => $user->id]);

        // 3) Assign role
        if (method_exists($user, 'assignRole')) {
            Log::info('[Register][Hybrid] Assign role', ['role' => $roleName]);
            $user->assignRole($roleName);
        }

        DB::commit();
        Log::info('[Register][Hybrid] Commit ok');

        // 5) Toast + redirect
        $msg = $psuInfo
            ? "Welcome, PSU student ". ($psuInfo['student_number'] ?? '') . "! We sent a verification link to your email."
            : "We sent a verification link to your email. Please verify, then sign in.";
        Log::info('[Register][Hybrid] Success toast + redirect', ['email' => $email]);
        $this->toast($msg, 'success', 5000);
        $user->notify(new \App\Notifications\BrandedVerifyEmail());
        Log::info('[Register][Hybrid] Fired Registered event for verification');

        return redirect()->route('login');

    } catch (AuthException|FirebaseException $e) {
        DB::rollBack();

        if ($this->isNetworkError($e)) {
            Log::warning('[Register][Hybrid] Network error with Firebase', [
                'email' => $email,
                'msg'   => $e->getMessage(),
            ]);
            $this->toast('Cannot reach the authentication service. Please check your internet connection and try again.', 'error', 6000);
            return back()
                ->withErrors(['email' => 'You appear to be offline. Please try again when you have an internet connection.'])
                ->withInput();
        }

        $message    = $e->getMessage();
        $emailError = null;

        if (stripos($message, 'EMAIL_EXISTS') !== false || stripos($message, 'already exists') !== false) {
            $emailError = 'This email is already registered. Please sign in or reset your password.';
        } elseif (stripos($message, 'INVALID_EMAIL') !== false || stripos($message, 'invalid email') !== false) {
            $emailError = 'The email address is invalid.';
        } elseif (stripos($message, 'OPERATION_NOT_ALLOWED') !== false) {
            $emailError = 'Email/password sign-in is not enabled in Firebase.';
        }

        Log::warning('[Register][Hybrid] Firebase failed', ['email' => $email, 'map' => $emailError, 'msg' => $message]);
        $this->toast($emailError ?? 'Registration failed. Please try again in a moment.', 'error', 6000);

        return back()
            ->withErrors($emailError ? ['email' => $emailError] : ['error' => 'Registration failed. Please try again.'])
            ->withInput();

    } catch (\Throwable $e) {
        DB::rollBack();

        // Clean orphan in Firebase if DB failed after Firebase create
        if ($firebaseUser && isset($firebaseUser->uid)) {
            try {
                Log::warning('[Register][Hybrid] DB failed; deleting Firebase user', ['uid' => $firebaseUser->uid]);
                $firebaseAuth->deleteUser($firebaseUser->uid);
            } catch (\Throwable $ignored) {
                Log::warning('[Register][Hybrid] Failed to delete orphaned Firebase user', [
                    'uid' => $firebaseUser->uid,
                    'err' => $ignored->getMessage()
                ]);
            }
        }

        if ($this->isNetworkError($e)) {
            Log::warning('[Register][Hybrid] Generic network failure', ['email' => $email, 'msg' => $e->getMessage()]);
            $this->toast('Cannot complete registration while offline. Please try again with an internet connection.', 'error', 6000);

            return back()
                ->withErrors(['email' => 'You appear to be offline. Please try again when you have an internet connection.'])
                ->withInput();
        }

        Log::error('[Register][Hybrid] Failed', ['email' => $email, 'err' => $e->getMessage()]);
        $this->toast('Registration failed. Please try again in a moment.', 'error', 6000);

        return back()
            ->withErrors(['error' => config('app.debug') ? $e->getMessage() : 'Registration failed.'])
            ->withInput();
    }
}

private function toast(string $message, string $level = 'info', int $ms = 5000): void
{
    try {
        Log::debug('[Toast] '.$level, ['msg' => $message, 'autoClose' => $ms]);
        Alert::toast($message, $level)->autoClose($ms);
    } catch (\Throwable $e) {
        Log::warning('[Toast] Failed to queue', ['level' => $level, 'msg' => $message, 'err' => $e->getMessage()]);
    }
}

private function isNetworkError(\Throwable $e): bool
{
    $needleMsgs = [
        'Could not resolve host', 'Connection timed out', 'Failed to connect', 'cURL error', 'SSL',
    ];
    $cur = $e;
    while ($cur) {
        $msg = $cur->getMessage();
        foreach ($needleMsgs as $needle) {
            if (stripos($msg, $needle) !== false) {
                return true;
            }
        }
        if ($cur instanceof \GuzzleHttp\Exception\ConnectException) return true;
        $cur = $cur->getPrevious();
    }
    return false;
}

private function parsePsuEmail(string $email): ?array
{
    $email = trim($email);

    // Require PSU domain
    if (!Str::endsWith(Str::lower($email), '@psu.edu.ph')) {
        return null;
    }

    $local = Str::upper(Str::before($email, '@')); // e.g. "22UR0628"

    if (!preg_match('/^(?<year>\d{2})(?<campus>[A-Z]{2})(?<student_no>\d{4,})$/', $local, $m)) {
        return null;
    }

    $campusAbbrev = $m['campus'];

    // Look up campus by abbrev (case-insensitive) with a small cache
    $cacheKey = "campus.abbrev.$campusAbbrev";
    $campus = Cache::remember($cacheKey, 600, function () use ($campusAbbrev) {
        return Campus::query()
            ->whereRaw('UPPER(abbrev) = ?', [$campusAbbrev])
            ->where('is_active', true)
            ->first();
    });

    if (!$campus) {
        // Campus code is not recognized → treat as non-student (null)
        return null;
    }

    // Normalize student_number: YY + CC + NNNN (minimum 4 digits, keep full digits if >4)
    $studentDigits = $m['student_no'];
    if (strlen($studentDigits) < 4) {
        $studentDigits = str_pad($studentDigits, 4, '0', STR_PAD_LEFT);
    }

    $studentNumber = $m['year'] . $campusAbbrev . $studentDigits;

    return [
        'year'            => $m['year'],       // "22"
        'campus'          => $campusAbbrev,    // "UR"
        'campus_id'       => $campus->id,      // MySQL FK
        'student_number'  => $studentNumber,   // "22UR0628"
        'student_no_raw'  => $m['student_no'], // original digit tail (optional)
    ];
}

    private function isOnline(): bool
    {
        // Very fast, non-blocking-ish probe: if we can resolve google.com, assume online
        // Suppress warnings to avoid noisy logs on offline machines.
        return (bool) @dns_get_record('google.com', DNS_A);
    }
}

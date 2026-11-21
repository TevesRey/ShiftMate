<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SiginRequest;
use App\Models\User;
use App\Models\Employees;
use App\Models\Notifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle a request for registration based on users table migration.
     */
    public function register(RegisterRequest $request)
    {
        $fullName = trim($request->name);
        $role = 'employee'; // Public registration is employee-only; admin accounts are seeded by the owner.

        $user = User::create([
            'full_name' => $fullName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'status' => 'inactive',
            'registration_status' => 'pending',
        ]);

        // Every employee account gets a linked employee record so the account
        // appears in Employee Management and can submit schedule-related requests.
        $this->ensureEmployeeProfile($user);
        Employees::where('user_id', $user->id)->update(['status' => 'inactive']);

        $adminMessage = $user->full_name . ' (' . $user->email . ') submitted an employee registration on ' . $user->created_at->format('M j, Y g:i A') . '.';
        User::where('role', 'admin')->get()->each(function ($admin) use ($adminMessage) {
            Notifications::create([
                'user_id' => $admin->id,
                'title' => 'New employee registration',
                'message' => $adminMessage,
                'type' => 'employee_registration',
                'is_read' => false,
            ]);
        });

        // Registration is pending until an administrator approves it. Send a
        // signed verification link immediately; account approval and email
        // verification are separate requirements before an employee can log in.
        [$verificationEmailSent, $verificationEmailError] = $this->sendVerificationEmail($user);

        return response()->json([
            'message' => $verificationEmailSent
                ? 'Registration submitted. Check your email and verify your address, then wait for administrator approval.'
                : 'Registration submitted, but the verification email could not be sent. Please contact the administrator or try resending it later.',
            'approval_required' => true,
            'verification_email_sent' => $verificationEmailSent,
            'email_error' => $verificationEmailError,
            'user' => $user,
        ], 201);
    }


    /** Verify an employee email using a signed, time-limited link. */
    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        abort_unless(hash_equals(sha1($user->email), $hash), 403, 'This email verification link is invalid.');

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect()->to(rtrim((string) config('app.url'), '/') . '/?email_verified=1');
    }

    /** Resend a verification link without revealing whether an email exists. */
    public function resendVerification(Request $request)
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = strtolower(trim($validated['email']));
        $user = User::where('email', $email)->where('role', 'employee')->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            [$sent, $error] = $this->sendVerificationEmail($user);
            if (! $sent) {
                return response()->json([
                    'message' => 'The verification email could not be sent. Please try again later or contact the administrator.',
                    'email_sent' => false,
                    'email_error' => $error,
                ], 503);
            }
        }

        return response()->json([
            'message' => 'If this employee account needs verification, a new verification email has been sent.',
        ]);
    }

    /** Send a signed verification link and log delivery failures for diagnostics. */
    private function sendVerificationEmail(User $user): array
    {
        if ($user->hasVerifiedEmail()) {
            return [true, null];
        }

        try {
            $verificationUrl = URL::temporarySignedRoute(
                'api.email.verify',
                now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1($user->email)]
            );

            $body = "Hello {$user->full_name},\n\n"
                . "Please verify your email address to complete your ShiftMate registration.\n\n"
                . "Verify your email: {$verificationUrl}\n\n"
                . "This link expires in 60 minutes. If you did not create this account, you can ignore this email.\n\n"
                . "Thank you,\nShiftMate Administration";

            Mail::raw($body, function ($message) use ($user) {
                $message->to($user->email)->subject('Verify your ShiftMate email address');
            });

            return [true, null];
        } catch (\Throwable $exception) {
            Log::error('ShiftMate verification email failed.', [
                'user_id' => $user->id,
                'recipient' => $user->email,
                'exception' => $exception->getMessage(),
            ]);

            return [false, 'SMTP delivery failed. Check the application logs and mail environment settings.'];
        }
    }

    /** List employee registrations awaiting approval (administrator only). */
    public function pendingRegistrations(Request $request)
    {
        if (strtolower((string) $request->user()->role) !== 'admin') {
            return response()->json(['message' => 'Administrator access required.'], 403);
        }

        return response()->json(User::where('role', 'employee')
            ->where('registration_status', 'pending')
            ->orderBy('created_at')
            ->get(['id', 'full_name', 'email', 'created_at', 'registration_status']));
    }

    /** Approve or reject a pending employee registration (administrator only). */
    public function reviewRegistration(Request $request, int $id)
    {
        if (strtolower((string) $request->user()->role) !== 'admin') {
            return response()->json(['message' => 'Administrator access required.'], 403);
        }

        $validated = $request->validate(['decision' => ['required', 'in:approved,rejected']]);
        abort_if((int) $request->user()->id === (int) $id, 403, 'You cannot approve or reject your own account.');
        $user = User::where('role', 'employee')->findOrFail($id);
        if (($user->registration_status ?? 'approved') !== 'pending') {
            return response()->json(['message' => 'This registration has already been reviewed.'], 409);
        }

        $user->registration_status = $validated['decision'];
        $user->status = $validated['decision'] === 'approved' ? 'active' : 'inactive';
        $user->save();
        if ($validated['decision'] === 'approved') {
            $this->ensureEmployeeProfile($user);
            Employees::where('user_id', $user->id)->update(['status' => 'active']);

            // Send immediately so SMTP failures are reported to the admin
            // instead of being silently queued without a running worker.
            try {
                $loginUrl = rtrim((string) config('app.url'), '/') . '/';
                $body = "Hello {$user->full_name},\n\n"
                    . "Good news! Your ShiftMate employee account has been approved. "
                    . "You can now sign in using the email address and password you registered with.\n\n"
                    . "Sign in here: {$loginUrl}\n\n"
                    . "Thank you,\nShiftMate Administration";

                Mail::raw($body, function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Your ShiftMate account has been approved');
                });

                $emailSent = true;
                $emailError = null;
            } catch (\Throwable $exception) {
                $emailSent = false;
                $emailError = 'SMTP delivery failed. Check storage/logs/laravel.log.';

                Log::error('ShiftMate employee approval email failed.', [
                    'user_id' => $user->id,
                    'recipient' => $user->email,
                    'exception' => $exception->getMessage(),
                ]);
            }
        } else {
            Employees::where('user_id', $user->id)->update(['status' => 'inactive']);
            $emailSent = null;
            $emailError = null;
        }

        $message = 'Registration ' . $validated['decision'] . '.';
        if ($validated['decision'] === 'approved') {
            $message .= $emailSent
                ? ' Approval email sent to ' . $user->email . '.'
                : ' Account approved, but the approval email could not be sent. Check the Laravel log and Gmail SMTP settings.';
        }

        return response()->json([
            'message' => $message,
            'email_sent' => $emailSent,
            'email_error' => $emailError,
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Start password recovery.
     * In local development, show a one-time reset URL inside ShiftMate so the
     * flow can be tested without configuring Gmail/SMTP. Production continues
     * to use Laravel's normal email delivery and never exposes the token in API
     * responses.
     */
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = strtolower(trim($validated['email']));

        if (app()->environment('local')) {
            $user = Password::broker()->getUser(['email' => $email]);

            // Keep the response generic for unknown addresses.
            if (! $user) {
                return response()->json([
                    'message' => 'If an account exists for that email, recovery instructions are available in ShiftMate.',
                ]);
            }

            $token = Password::broker()->createToken($user);
            $resetUrl = url('/reset-password/' . urlencode($token) . '?email=' . urlencode($email));

            return response()->json([
                'message' => 'Your account was found. Use the secure reset link below to choose a new password.',
                'reset_url' => $resetUrl,
                'delivery' => 'in_app_local',
            ]);
        }

        $status = Password::sendResetLink(['email' => $email]);

        // Avoid disclosing whether an email is registered in production.
        if ($status === Password::RESET_LINK_SENT || $status === Password::INVALID_USER) {
            return response()->json([
                'message' => 'If an account exists for that email, a password reset link has been sent.',
            ]);
        }

        return response()->json(['message' => __($status)], 422);
    }

    /** Validate the broker token and replace the password securely. */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successfully. You can now sign in.']);
        }

        return response()->json(['message' => __($status)], 422);
    }

    /**
     * Handle a request for authentication.
     */
    public function login(SiginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (strtolower((string) $user->role) === 'employee' && ($user->registration_status ?? 'approved') !== 'approved') {
            return response()->json([
                'message' => ($user->registration_status ?? 'pending') === 'rejected'
                    ? 'Your registration was rejected. Please contact the administrator.'
                    : 'Your account is awaiting administrator approval.',
                'code' => 'REGISTRATION_NOT_APPROVED',
                'registration_status' => $user->registration_status ?? 'pending',
            ], 403);
        }

        if (strtolower((string) $user->role) === 'employee' && ! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email address before logging in. Check your inbox or request a new verification link.',
                'code' => 'EMAIL_NOT_VERIFIED',
                'email' => $user->email,
            ], 403);
        }

        // Role is detected from the account, so users do not need to choose a portal.
        // Keep the optional check for older clients that still send a portal value.
        $portal = $request->filled('portal') ? strtolower((string) $request->input('portal')) : null;
        $role = strtolower((string) $user->role);
        if ($portal !== null) {
            $portalMatchesRole = $portal === 'admin'
                ? in_array($role, ['admin', 'manager'], true)
                : ($portal === 'employee' && $role === 'employee');
            if (!$portalMatchesRole) {
                return response()->json([
                    'message' => 'This account does not match the selected portal.',
                    'code' => 'PORTAL_ROLE_MISMATCH',
                ], 403);
            }
        }

        // Successful authentication reactivates the account after a prior logout.
        $user->status = 'active';
        $user->save();
        if (strtolower((string) $user->role) === 'employee') {
            Employees::where('user_id', $user->id)->update(['status' => 'active']);
        }

        // Repair older employee accounts created before profile linking existed.
        $this->ensureEmployeeProfile($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    /**
     * Log the user out (Revoke token).
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->status = 'inactive';
            $user->save();
            if (strtolower((string) $user->role) === 'employee') {
                Employees::where('user_id', $user->id)->update(['status' => 'inactive']);
            }
        }
        $token = $user?->currentAccessToken();
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Keep the login account connected to the employee-management record.
     * Placeholder details are editable by an administrator later.
     */
    private function ensureEmployeeProfile(User $user): void
    {
        if (strtolower((string) $user->role) !== 'employee') {
            return;
        }

        if (Employees::where('user_id', $user->id)->exists()) {
            return;
        }

        $nextNumber = ((int) Employees::max('employee_number')) + 1;

        Employees::create([
            'user_id' => $user->id,
            'employee_number' => $nextNumber,
            'position' => 'Not set',
            'status' => 'active',
        ]);
    }

    /** Update the signed-in user's profile details. */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
        ]);

        $user = $request->user();
        $newEmail = strtolower(trim($validated['email']));
        $emailChanged = strtolower((string) $user->email) !== $newEmail;

        $user->full_name = trim($validated['full_name']);
        $user->email = $newEmail;
        if ($emailChanged && strtolower((string) $user->role) === 'employee') {
            // A changed email address must be verified again before employee login.
            $user->email_verified_at = null;
        }
        $user->save();

        $verificationEmailSent = null;
        if ($emailChanged && strtolower((string) $user->role) === 'employee') {
            [$verificationEmailSent] = $this->sendVerificationEmail($user);
        }

        return response()->json([
            'message' => $emailChanged && strtolower((string) $user->role) === 'employee'
                ? ($verificationEmailSent
                    ? 'Profile updated. Please verify your new email address before your next login.'
                    : 'Profile updated, but the verification email could not be sent. Please request a new verification link.' )
                : 'Profile updated successfully.',
            'verification_email_sent' => $verificationEmailSent,
            'user' => $user->fresh(),
        ]);
    }

    /** Store the signed-in user's profile picture. */
    public function uploadProfilePhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();

        // Local development uses Laravel's public disk. Production can set
        // FILESYSTEM_DISK=s3 so profile photos survive stateless Vercel instances.
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';

        try {
            $path = $request->file('photo')->storePublicly('profile-photos', $disk);
            if (! $path) {
                return response()->json(['message' => 'The profile photo could not be saved. Please try again.'], 500);
            }

            $photoUrl = Storage::disk($disk)->url($path);

            // Delete older relative paths when possible. Absolute URLs may point
            // to a remote object store and are intentionally not guessed/rewritten.
            $oldPath = (string) $user->profile_photo_path;
            if ($oldPath !== '' && ! preg_match('/^https?:\/\//i', $oldPath)) {
                Storage::disk($disk)->delete($oldPath);
            }

            $user->forceFill(['profile_photo_path' => $photoUrl])->save();
        } catch (\Throwable $exception) {
            Log::error('ShiftMate profile photo upload failed.', [
                'user_id' => $user->id,
                'disk' => $disk,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Profile photo storage is unavailable. Check the configured filesystem storage and try again.',
            ], 500);
        }

        return response()->json([
            'message' => 'Profile photo updated successfully.',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json($user);
    }
}

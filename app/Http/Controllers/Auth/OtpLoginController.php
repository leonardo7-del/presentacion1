<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OtpLoginController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! password_verify($credentials['password'], (string) $user->password_hash)) {
            $this->audit(
                'LOGIN_ATTEMPT_FAILED',
                $user?->id,
                $request,
                ['email' => $credentials['email']]
            );

            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son validas.',
            ]);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Carbon::now()->addMinutes(5);

        DB::table('otp_codes')->insert([
            'user_id' => $user->id,
            'code' => $otp,
            'expires_at' => $expiresAt,
            'used' => 0,
        ]);

        Mail::raw(
            "Tu codigo OTP es: {$otp}. Expira en 5 minutos.",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Codigo OTP de acceso');
            }
        );

        $request->session()->put('otp_pending_user_id', $user->id);
        $request->session()->put('otp_pending_email', $user->email);

        $this->audit('LOGIN_ATTEMPT_SUCCESS', $user->id, $request, [
            'email' => $user->email,
        ]);

        return redirect()->route('otp.form')
            ->with('status', 'Te enviamos un codigo OTP a tu correo.');
    }

    public function showOtpForm(Request $request): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $pendingUserId = $request->session()->get('otp_pending_user_id');

        if (! $pendingUserId) {
            return redirect()->route('login');
        }

        return Inertia::render('auth/otp', [
            'status' => $request->session()->get('status'),
            'email' => $request->session()->get('otp_pending_email'),
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $pendingUserId = $request->session()->get('otp_pending_user_id');

        if (! $pendingUserId) {
            return redirect()->route('login');
        }

        $otpRecord = DB::table('otp_codes')
            ->where('user_id', $pendingUserId)
            ->where('used', 0)
            ->orderByDesc('id')
            ->first();

        if (! $otpRecord) {
            $this->audit('OTP_VERIFICATION_FAILED', $pendingUserId, $request, [
                'reason' => 'no_otp_found',
            ]);

            throw ValidationException::withMessages([
                'code' => 'No hay un codigo OTP disponible.',
            ]);
        }

        $isExpired = Carbon::parse((string) $otpRecord->expires_at)->lte(Carbon::now());
        $isSameCode = hash_equals((string) $otpRecord->code, $data['code']);

        if (! $isSameCode || $isExpired) {
            $this->audit('OTP_VERIFICATION_FAILED', $pendingUserId, $request, [
                'reason' => $isExpired ? 'expired' : 'invalid_code',
            ]);

            throw ValidationException::withMessages([
                'code' => $isExpired
                    ? 'El codigo OTP expiro. Inicia sesion otra vez.'
                    : 'El codigo OTP no es valido.',
            ]);
        }

        DB::table('otp_codes')
            ->where('id', $otpRecord->id)
            ->update(['used' => 1]);

        Auth::loginUsingId((int) $pendingUserId);
        $request->session()->forget(['otp_pending_user_id', 'otp_pending_email']);
        $request->session()->regenerate();

        $this->audit('OTP_VERIFICATION_SUCCESS', (int) $pendingUserId, $request, [
            'otp_code_id' => $otpRecord->id,
        ]);

        return redirect()->intended(route('dashboard'));
    }

    private function audit(string $action, ?int $userId, Request $request, array $details = []): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => $request->ip(),
            'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }
}

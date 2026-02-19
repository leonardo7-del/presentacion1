<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_valid_credentials_generate_otp_and_redirect_to_otp_form()
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('otp.form'));
        $this->assertGuest();

        $this->assertDatabaseHas('otp_codes', [
            'user_id' => $user->id,
            'used' => 0,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'LOGIN_ATTEMPT_SUCCESS',
        ]);
    }

    public function test_invalid_credentials_fail_and_are_audited()
    {
        $user = User::factory()->create();

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'LOGIN_ATTEMPT_FAILED',
        ]);
    }

    public function test_users_can_authenticate_by_verifying_otp()
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $otp = DB::table('otp_codes')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        $response = $this->post(route('otp.verify'), [
            'code' => $otp->code,
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('otp_codes', [
            'id' => $otp->id,
            'used' => 1,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'OTP_VERIFICATION_SUCCESS',
        ]);
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    }

    public function test_authenticated_users_are_redirected_from_otp_form_to_dashboard()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('otp.form'));

        $response->assertRedirect(route('dashboard'));
    }
}

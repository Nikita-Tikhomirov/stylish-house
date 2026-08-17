<?php

namespace Tests\Feature;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class PrivacyConsentTest extends TestCase
{
    public function test_privacy_policy_is_publicly_available(): void
    {
        $this->get('/policy')
            ->assertMovedPermanently()
            ->assertRedirect('http://localhost/policy/');

        // Laravel's test URL helper trims trailing slashes, so send this
        // request through the HTTP kernel without normalizing the URI.
        $kernel = $this->app->make(HttpKernel::class);
        $request = Request::create('/policy/', 'GET');
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<h1', $response->getContent());
        $this->assertStringContainsString('Политика конфиденциальности', $response->getContent());
    }

    public function test_contact_form_rejects_missing_privacy_consent(): void
    {
        $this->postJson('/send-form', [
            'name' => 'Иван',
            'phone' => '+7 999 123-45-67',
            'message' => 'Перезвоните мне',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('privacy_consent');
    }

    public function test_contact_form_accepts_explicit_privacy_consent(): void
    {
        Mail::shouldReceive('send')
            ->once()
            ->with('emails.contact', Mockery::type('array'), Mockery::type('Closure'));

        $this->postJson('/send-form', [
            'name' => 'Иван',
            'phone' => '+7 999 123-45-67',
            'message' => 'Перезвоните мне',
            'privacy_consent' => '1',
        ])->assertOk()->assertJsonPath('success', true);

    }

    public function test_registration_rejects_missing_privacy_consent(): void
    {
        $this->post('/register', [
            'name' => 'Иван',
            'email' => '',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ])->assertSessionHasErrors('privacy_consent');
    }

    public function test_all_personal_data_forms_use_shared_consent_component(): void
    {
        $popups = file_get_contents(resource_path('views/components/front/popups.blade.php'));
        $question = file_get_contents(resource_path('views/components/front/section/how.blade.php'));
        $checkout = file_get_contents(resource_path('views/front/checkout.blade.php'));
        $register = file_get_contents(resource_path('views/auth/register.blade.php'));

        $this->assertSame(3, substr_count($popups, '<x-front.consent'));
        $this->assertSame(1, substr_count($question, '<x-front.consent'));
        $this->assertSame(1, substr_count($checkout, '<x-front.consent'));
        $this->assertSame(1, substr_count($register, '<x-front.consent'));
    }
}

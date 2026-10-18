<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Support\ForgeMailer;
use Illuminate\Support\Facades\Mail;
class StandaloneMailTest extends TestCase {
 public function test_mail_uses_configured_transport_once(): void {
  Mail::shouldReceive('raw')->once()->with('test body',\Mockery::type('Closure'));
  $this->assertTrue(ForgeMailer::sendRaw('recipient@example.invalid','Recipient','Subject','test body'));
 }
}

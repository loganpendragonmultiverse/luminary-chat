<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Models\Character;
use App\Services\CharacterRosterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
class BetaReadinessTest extends TestCase {
 use RefreshDatabase;
 public function test_standalone_public_pages_have_no_author_tracking(): void {
  foreach(['/','/login','/register','/how-it-works','/byok-guide','/privacy','/terms','/contact'] as $path)
   $this->get($path)->assertOk()->assertDontSee('googletagmanager')->assertDontSee('lab-feedback.php')->assertDontSee('contact-intake.php');
 }
 public function test_sitemap_uses_instance_url(): void {
  $this->get('/sitemap.xml')->assertOk()->assertSee(config('app.url'),false)->assertDontSee('loganpendragonforge.com');
 }
 public function test_fresh_roster_is_generic_and_separate_per_account(): void {
  Http::preventStrayRequests();
  $a=User::factory()->create();$b=User::factory()->create();
  $service=app(CharacterRosterService::class);
  $first=$service->forUser($a);$second=$service->forUser($b);
  $this->assertCount(3,$first);$this->assertCount(3,$second);
  $this->assertSame(['luminary','mira','aurora'],$first->pluck('template_key')->all());
  foreach($first as $c){
   $this->assertSame($a->id,$c->user_id);
   $this->assertNotEmpty($c->rooms);
   $this->assertStringNotContainsString('twin-flame',json_encode($c->toArray()));
   $this->assertStringContainsString('No prior relationship',$c->scenario);
  }
  $first[0]->update(['personality'=>'My custom character']);
  $this->assertSame('My custom character',$service->forUser($a)[0]->personality);
  $this->assertNotSame('My custom character',$service->forUser($b)[0]->personality);
  $this->actingAs($b)->put(route('characters.update',$first[0]),['name'=>'Stolen'])->assertNotFound();
 }
 public function test_account_deletion_removes_keys_and_character_state(): void {
  $user=User::factory()->create(['password'=>bcrypt('private-test-password'),'venice_api_key'=>'synthetic-only-key']);
  $character=app(CharacterRosterService::class)->forUser($user)->first();
  $chat=\App\Models\Chat::create(['user_id'=>$user->id,'character_id'=>$character->id,'title'=>'Delete me','model'=>$character->default_model,'temperature'=>1,'max_tokens'=>900,'top_p'=>1]);
  \Illuminate\Support\Facades\DB::table('chat_turn_logs')->insert(['chat_id'=>$chat->id,'event_type'=>'test','request_payload'=>json_encode(['content'=>'private synthetic content']),'created_at'=>now(),'updated_at'=>now()]);
  $this->actingAs($user)->delete(route('account.destroy'),['current_password'=>'private-test-password','confirmation'=>'DELETE']);
  $this->assertDatabaseMissing('users',['id'=>$user->id]);
  $this->assertDatabaseMissing('characters',['user_id'=>$user->id]);
  $this->assertDatabaseCount('chat_turn_logs',0);
 }
}

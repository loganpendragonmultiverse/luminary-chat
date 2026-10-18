<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CompanionBoundaryTest extends TestCase {
 use RefreshDatabase;
 public function test_guest_cannot_read_saved_personality(): void {
  $this->getJson('/companion-personality/mira')->assertUnauthorized();
 }
 public function test_lookup_is_scoped_to_current_user_and_excludes_private_soul(): void {
  $owner=User::factory()->create();$other=User::factory()->create();
  Character::create(['user_id'=>$owner->id,'name'=>'Private Mira','slug'=>'mira-'.$owner->id,'template_key'=>'mira','short_description'=>'Preview test','personality'=>'SECRET-PERSONALITY','scenario'=>'','first_message'=>'','default_model'=>'venice-uncensored-role-play','temperature'=>0.7,'max_tokens'=>4096,'top_p'=>1.0,'persona_documents'=>['Soul.md'=>'SECRET-SOUL']]);
  $this->actingAs($other)->getJson('/companion-personality/mira')->assertNotFound();
  $r=$this->actingAs($owner)->getJson('/companion-personality/mira');
  $r->assertOk()->assertJsonPath('name','Private Mira')->assertJsonPath('motionEnabled',false)->assertDontSee('SECRET-SOUL')->assertDontSee('SECRET-PERSONALITY');
  $this->actingAs($owner)->getJson('/companion-personality/unknown')->assertNotFound();
 }
}

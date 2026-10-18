<?php
namespace App\Support;
use Illuminate\Support\Facades\Mail;
class ForgeMailer {
 public static function sendRaw(string $to, ?string $toName, string $subject, string $body, string $fromName = 'Application'): bool {
  try { Mail::raw($body, function ($message) use ($to,$toName,$subject) { $message->to($to,$toName)->subject($subject); }); return true; }
  catch (\Throwable $e) { report($e); return false; }
 }
}

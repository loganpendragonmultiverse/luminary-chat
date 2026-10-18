<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\CharacterPersonaController;
use App\Http\Controllers\CharacterCardController;
use App\Http\Controllers\CharacterVersionController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatFolderController;
use App\Http\Controllers\ChatTransferController;
use App\Http\Controllers\ComparisonController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImageGenerationController;
use App\Http\Controllers\MemoryController;
use App\Http\Controllers\MemoryEntryController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ModelManagementController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SummaryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', function () {
    return response(
        "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /dashboard\nDisallow: /account\nDisallow: /account-settings\nDisallow: /settings\nDisallow: /chats\nDisallow: /chat-folders\nDisallow: /characters\nDisallow: /models\nDisallow: /messages\nDisallow: /memory\nDisallow: /rooms\nDisallow: /compare\nDisallow: /auth-status\nDisallow: /chat\nDisallow: /start-chat\nDisallow: /user-settings\n\nSitemap: ".rtrim(config('app.url'), '/')."/sitemap.xml\n",
        200,
        ['Content-Type' => 'text/plain']
    );
});

Route::get('/sitemap.xml', function () {
    $urls = [
        ['loc' => rtrim(config('app.url'), '/').'/', 'lastmod' => '2026-07-25', 'priority' => '1.0', 'changefreq' => 'weekly'],
        ['loc' => rtrim(config('app.url'), '/').'/how-it-works', 'lastmod' => '2026-07-23', 'priority' => '0.9', 'changefreq' => 'monthly'],
        ['loc' => rtrim(config('app.url'), '/').'/byok-guide', 'lastmod' => '2026-07-23', 'priority' => '0.9', 'changefreq' => 'monthly'],
        ['loc' => rtrim(config('app.url'), '/').'/memory-and-privacy', 'lastmod' => '2026-07-23', 'priority' => '0.8', 'changefreq' => 'monthly'],
        ['loc' => rtrim(config('app.url'), '/').'/faq', 'lastmod' => '2026-07-23', 'priority' => '0.7', 'changefreq' => 'monthly'],
        ['loc' => rtrim(config('app.url'), '/').'/roadmap-beta', 'lastmod' => '2026-07-23', 'priority' => '0.7', 'changefreq' => 'monthly'],
        ['loc' => rtrim(config('app.url'), '/').'/contact', 'lastmod' => '2026-07-12', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['loc' => rtrim(config('app.url'), '/').'/privacy', 'lastmod' => '2026-07-25', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ['loc' => rtrim(config('app.url'), '/').'/terms', 'lastmod' => '2026-07-25', 'priority' => '0.3', 'changefreq' => 'yearly'],
    ];

    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach ($urls as $url) {
        $xml .= "  <url>\n";
        $xml .= '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1) . "</loc>\n";
        $xml .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
        $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
        $xml .= "    <priority>{$url['priority']}</priority>\n";
        $xml .= "  </url>\n";
    }
    $xml .= "</urlset>\n";

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

Route::get('/auth-status', function (Request $request) {
    return response()
        ->json([
            'authenticated' => (bool) $request->user(),
            'isAdmin' => (bool) $request->user()?->is_admin,
            'csrfToken' => $request->user() ? csrf_token() : null,
        ])
        ->header('Cache-Control', 'private, no-store');
})->name('auth.status');

Route::get('/chat', function (Request $request) {
    return $request->user()
        ? redirect()->route('characters.index')
        : redirect()->guest(route('login'));
})->name('chat.entry');

Route::get('/start-chat', function (Request $request) {
    return $request->user()
        ? redirect()->route('characters.index')
        : redirect()->guest(route('login'));
})->name('chat.start');

Route::get('/user-settings', function (Request $request) {
    return $request->user()
        ? redirect()->route('settings.index')
        : redirect()->route('login');
})->name('settings.entry');

Route::view('/terms', 'pages.terms')->name('terms');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/how-it-works', 'pages.how-it-works')->name('how-it-works');
Route::view('/byok-guide', 'pages.byok-guide')->name('byok-guide');
Route::view('/memory-and-privacy', 'pages.memory-and-privacy')->name('memory-and-privacy');
Route::view('/faq', 'pages.faq')->name('faq');
Route::redirect('/roadmap', '/change-log', 302);
Route::redirect('/roadmap-beta', '/change-log', 302)->name('roadmap.beta');
Route::view('/change-log', 'pages.roadmap')->name('change-log');
Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

Route::get('/', function (Request $request) {
    if ($request->user()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('welcome');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'signup'])->name('register.store');
    Route::get('/forgot-password', [PasswordController::class, 'forgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('chats.index'))->name('dashboard');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/chats', [ChatController::class, 'index'])->name('chats.index');
    Route::get('/characters/{character}/chats', [ChatController::class, 'forCharacter'])->name('characters.chats');
    Route::post('/characters/{character}/chats', [ChatController::class, 'store'])->name('chats.store');
    Route::get('/chats/{chat}', [ChatController::class, 'show'])->name('chats.show');
    Route::put('/chats/{chat}', [ChatController::class, 'update'])->name('chats.update');
    Route::delete('/chats/{chat}', [ChatController::class, 'destroy'])->name('chats.destroy');
    Route::post('/chats/{chat}/archive', [ChatFolderController::class, 'archive'])->name('chats.archive');
    Route::post('/chats/{chat}/restore', [ChatFolderController::class, 'restore'])->name('chats.restore');
    Route::put('/chats/{chat}/folder', [ChatFolderController::class, 'assign'])->name('chats.folder');
    Route::post('/chat-folders', [ChatFolderController::class, 'store'])->name('chat-folders.store');
    Route::put('/chat-folders/{chatFolder}', [ChatFolderController::class, 'update'])->name('chat-folders.update');
    Route::delete('/chat-folders/{chatFolder}', [ChatFolderController::class, 'destroy'])->name('chat-folders.destroy');
    Route::post('/chat-folders/bulk', [ChatFolderController::class, 'bulkUpdate'])->name('chat-folders.bulk');
    Route::post('/chat-folders/{chatFolder}/favorite', [ChatFolderController::class, 'favorite'])->name('chat-folders.favorite');
    Route::get('/chats/{chat}/export', [ChatTransferController::class, 'export'])->name('chats.export');
    Route::get('/account/export', [ChatTransferController::class, 'exportAccount'])->name('account.export');
    Route::post('/chats/import', [ChatTransferController::class, 'import'])->name('chats.import');

    Route::post('/chats/{chat}/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/chats/{chat}/images', [ImageGenerationController::class, 'store'])->name('images.store');
    Route::post('/chats/{chat}/messages/regenerate', [MessageController::class, 'regenerate'])->name('messages.regenerate');
    Route::put('/messages/{message}', [MessageController::class, 'update'])->name('messages.update');
    Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

    Route::put('/chats/{chat}/summary', [SummaryController::class, 'update'])->name('summaries.update');
    Route::post('/chats/{chat}/memory', [MemoryEntryController::class, 'storeForChat'])->name('memory.chat.store');
    Route::post('/characters/{character}/memory', [MemoryEntryController::class, 'storeForCharacter'])->name('memory.character.store');
    Route::delete('/memory/{memoryEntry}', [MemoryEntryController::class, 'destroy'])->name('memory.destroy');
    Route::put('/memory/{memoryEntry}', [MemoryEntryController::class, 'update'])->name('memory.update');

    Route::get('/characters', [CharacterController::class, 'index'])->name('characters.index');
    Route::post('/characters/reset-all', [CharacterController::class, 'resetAll'])->name('characters.reset-all');
    Route::put('/characters/{character}/default-model', [CharacterController::class, 'updateDefaultModel'])->name('characters.default-model.update');
    Route::put('/characters/{character}/defaults', [CharacterController::class, 'updateDefaults'])->name('characters.defaults.update');
    Route::put('/characters/{character}/persona/{fileName}', [CharacterPersonaController::class, 'update'])->name('characters.persona.update');
    Route::post('/characters/{character}/persona/restore', [CharacterPersonaController::class, 'restore'])->name('characters.persona.restore');
    Route::post('/characters/{character}/rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::post('/characters/{character}/rooms/restore', [RoomController::class, 'restore'])->name('rooms.restore');
    Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
    Route::post('/characters', [CharacterController::class, 'store'])->name('characters.store');
    Route::post('/characters/import', [CharacterController::class, 'import'])->name('characters.import');
    Route::get('/characters/{character}/export', [CharacterController::class, 'export'])->name('characters.export');
    Route::get('/characters/{character}/card', [CharacterCardController::class, 'export'])->name('characters.card.export');
    Route::post('/characters/card/import', [CharacterCardController::class, 'import'])->name('characters.card.import');
    Route::post('/characters/{character}/duplicate', [CharacterController::class, 'duplicate'])->name('characters.duplicate');
    Route::put('/characters/{character}', [CharacterController::class, 'update'])->name('characters.update');
    Route::delete('/characters/{character}', [CharacterController::class, 'destroy'])->name('characters.destroy');
    Route::post('/characters/{character}/favorite', [CharacterController::class, 'favorite'])->name('characters.favorite');
    Route::get('/characters/{character}/versions/{version}', [CharacterVersionController::class, 'show'])->name('characters.versions.show');
    Route::post('/characters/{character}/versions/{version}/restore', [CharacterVersionController::class, 'restore'])->name('characters.versions.restore');

    Route::get('/account-settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/models', [ModelManagementController::class, 'index'])->name('models.index');
    Route::put('/models', [ModelManagementController::class, 'update'])->name('models.update');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::delete('/settings/memory', [SettingsController::class, 'clearMemory'])->name('settings.memory.clear');
    Route::delete('/account', [SettingsController::class, 'destroyAccount'])->name('account.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');

    Route::get('/memory', [MemoryController::class, 'index'])->name('memory.index');
    Route::post('/memory/bulk', [MemoryController::class, 'bulk'])->name('memory.bulk');
    Route::post('/memory/dedupe', [MemoryController::class, 'dedupe'])->name('memory.dedupe');

    Route::get('/compare', [ComparisonController::class, 'index'])->name('compare.index');
    Route::post('/compare', [ComparisonController::class, 'store'])->name('compare.store');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
});

// Companion review only exposes the authenticated user's own character identity.
Route::get('/companion-personality/{template}', function (Request $request, string $template) {
    abort_unless(in_array($template, ['luminary', 'mira', 'aurora'], true), 404);
    $character = \App\Models\Character::query()->where('user_id', $request->user()->id)->where('template_key', $template)->firstOrFail();
    return response()->json(['name' => $character->name, 'template' => $template, 'chatUrl' => route('characters.chats', $character), 'motionEnabled' => false])->header('Cache-Control', 'private, no-store');
})->middleware('auth');


// Companion Home beta: account-scoped conversation and bounded world actions.
Route::get("/companion-world/context", [\App\Http\Controllers\CompanionWorldController::class, "context"]);
Route::post("/companion-world/talk/{template}", [\App\Http\Controllers\CompanionWorldController::class, "talk"])->middleware(["auth", "throttle:10,1"]);

Route::post("/companion-world/session/{template}", [\App\Http\Controllers\CompanionWorldController::class, "session"])->middleware(["auth", "throttle:40,1"]);

// The world entry is protected by the same session login as Chat Mode.
Route::get('/companion/cottage-20261012', fn () => response()->view('companion.home')->header('Cache-Control', 'private, no-store'))->middleware('auth')->name('companion.home');
Route::get('/companion/cottage-20261012/index.html', fn () => redirect()->route('companion.home'))->middleware('auth');

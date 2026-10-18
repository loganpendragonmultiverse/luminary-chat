<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatTurnLog;
use App\Models\MemoryEntry;
use App\Services\ModelCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request, ModelCatalogService $catalog): View
    {
        $modelOptions = $catalog->forUser($request->user());

        return view('settings.index', [
            'profileUser' => $request->user(),
            'conversationCount' => Chat::where('user_id', $request->user()->id)->count(),
            'memoryCount' => MemoryEntry::where('user_id', $request->user()->id)->count(),
            'providerModelCounts' => collect($modelOptions)->countBy('provider'),
        ]);
    }

    public function updateProfile(Request $request, ModelCatalogService $catalog): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            'venice_api_key' => ['nullable', 'string', 'max:500'],
            'openrouter_api_key' => ['nullable', 'string', 'max:500'],
            'remove_venice_api_key' => ['nullable', 'boolean'],
            'remove_openrouter_api_key' => ['nullable', 'boolean'],
            'avatar_url' => ['nullable', 'url', 'max:500'],
            'memory_enabled' => ['nullable', 'boolean'],
            'memory_auto_extract_enabled' => ['nullable', 'boolean'],
            'context_depth' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'privacy_allow_diagnostics' => ['nullable', 'boolean'],
        ]);

        $updates = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => ($data['password'] ?? null) ? Hash::make($data['password']) : $request->user()->password,
            'avatar_url' => $data['avatar_url'] ?? null,
            'memory_enabled' => $request->boolean('memory_enabled'),
            'memory_auto_extract_enabled' => $request->boolean('memory_auto_extract_enabled'),
            'context_depth' => $data['context_depth'] ?? 'high',
            'privacy_allow_diagnostics' => $request->boolean('privacy_allow_diagnostics'),
        ];

        $veniceInput = trim((string) ($data['venice_api_key'] ?? ''));
        $openRouterInput = trim((string) ($data['openrouter_api_key'] ?? ''));
        $veniceKey = $request->boolean('remove_venice_api_key')
            ? null
            : ($veniceInput !== '' ? $veniceInput : $request->user()->venice_api_key);
        $openRouterKey = $request->boolean('remove_openrouter_api_key')
            ? null
            : ($openRouterInput !== '' ? $openRouterInput : $request->user()->openrouter_api_key);

        try {
            $modelCatalog = $catalog->reconcile($veniceKey, $openRouterKey);
        } catch (\Throwable $exception) {
            return back()->withInput($request->except(['venice_api_key', 'openrouter_api_key', 'password', 'password_confirmation']))
                ->withErrors(['provider_keys' => $exception->getMessage().' Your existing keys and model access were kept.']);
        }

        $updates['venice_api_key'] = $veniceKey;
        $updates['openrouter_api_key'] = $openRouterKey;
        $updates['ai_model_catalog'] = $modelCatalog;
        $updates['ai_models_synced_at'] = now();

        $request->user()->update($updates);

        return back()->with('status', 'Settings updated and '.count($modelCatalog).' models reconciled from your provider keys.');
    }

    public function clearMemory(Request $request): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user) {
            $chatIds = Chat::where('user_id', $user->id)->pluck('id');

            if ($chatIds->isNotEmpty()) {
                ChatTurnLog::whereIn('chat_id', $chatIds)->delete();
            }

            MemoryEntry::where('user_id', $user->id)->delete();
            Chat::where('user_id', $user->id)->delete();
        });

        return back()->with('status', 'Chat history and memory cleared.');
    }

    public function destroyAccount(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        $user = $request->user();
        if (! Hash::check((string) $request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        DB::transaction(function () use ($user): void {
            DB::table('chat_turn_logs')->whereIn('chat_id', DB::table('chats')->select('id')->where('user_id', $user->id))->delete();
            $user->newQuery()->whereKey($user->getKey())->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('welcome')->with('status', 'Your Luminary account and stored data were deleted.');
    }

}

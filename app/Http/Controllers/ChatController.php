<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        return view('chat.index', [
            'conversations' => $request->user()->conversations()->withCount('messages')->with(['users', 'messages' => fn ($q) => $q->latest()->limit(1)])->latest('updated_at')->get(),
            'users' => User::whereKeyNot($request->user()->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request)
    {
        $data = $request->validate(['user_id' => 'required|integer|exists:users,id']);
        abort_if((int) $data['user_id'] === $request->user()->id, 422);
        $conversation = DB::transaction(function () use ($data, $request) {
            $pair = [(int) $request->user()->id, (int) $data['user_id']];
            $existing = Conversation::where('is_group', false)->whereHas('users', fn ($q) => $q->whereKey($pair[0]))
                ->whereHas('users', fn ($q) => $q->whereKey($pair[1]))->get()
                ->first(fn ($c) => $c->users()->count() === 2);
            if ($existing) return $existing;
            $conversation = Conversation::create(['is_group' => false]);
            $conversation->users()->attach($pair);
            return $conversation;
        });
        return redirect()->route('chat.show', $conversation);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $this->authorizeMember($request, $conversation);
        return view('chat.show', [
            'conversation' => $conversation->load('users'),
            'messages' => $conversation->messages()->with('user:id,name')->latest()->limit(100)->get()->reverse()->values(),
        ]);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $this->authorizeMember($request, $conversation);
        $after = max(0, (int) $request->query('after', 0));
        return response()->json($conversation->messages()->with('user:id,name')->where('id', '>', $after)->orderBy('id')->limit(100)->get()->map(fn ($m) => [
            'id' => $m->id, 'body' => $m->body, 'user' => $m->user->name,
            'mine' => $m->user_id === $request->user()->id, 'created_at' => $m->created_at->toISOString(),
        ]));
    }

    public function send(Request $request, Conversation $conversation)
    {
        $this->authorizeMember($request, $conversation);
        $data = $request->validate(['body' => 'required|string|max:4000']);
        $message = $conversation->messages()->create(['user_id' => $request->user()->id, 'body' => trim($data['body'])]);
        $conversation->touch();
        return response()->json(['id' => $message->id], 201);
    }

    private function authorizeMember(Request $request, Conversation $conversation): void
    {
        abort_unless($conversation->users()->whereKey($request->user()->id)->exists(), 404);
    }
}

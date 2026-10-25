<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Real-time Chat</h2></x-slot>
    <div class="py-8 max-w-4xl mx-auto space-y-6">
        <section class="bg-white p-6 shadow rounded">
            <h3 class="text-lg font-bold">Start a conversation</h3>
            <form method="post" action="{{ route('chat.create') }}" class="flex gap-3 mt-3">@csrf
                <label class="sr-only" for="user_id">Person</label>
                <select id="user_id" name="user_id" required class="rounded border"><option value="">Choose a person</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded">Open chat</button>
            </form>
        </section>
        <section class="bg-white p-6 shadow rounded"><h3 class="text-lg font-bold">Conversations</h3>
            @forelse($conversations as $conversation)
                <a class="block border-b py-3" href="{{ route('chat.show', $conversation) }}">{{ $conversation->users->where('id', '!=', auth()->id())->pluck('name')->join(', ') }} · {{ $conversation->messages_count }} messages
                    @if($conversation->messages->first()) — {{ \Illuminate\Support\Str::limit($conversation->messages->first()->body, 60) }} @endif
                </a>
            @empty
                <p class="mt-3 text-gray-600">No conversations yet.</p>
            @endforelse
        </section>
    </div>
</x-app-layout>

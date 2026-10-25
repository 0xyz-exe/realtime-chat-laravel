<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Chat: {{ $conversation->users->where('id', '!=', auth()->id())->pluck('name')->join(', ') }}</h2></x-slot>
    <div class="py-8 max-w-4xl mx-auto"><section class="bg-white p-6 shadow rounded">
        <div id="messages" class="h-96 overflow-y-auto space-y-3" aria-live="polite" aria-label="Messages">
            @foreach($messages as $message)
                <article data-id="{{ $message->id }}" class="p-3 rounded {{ $message->user_id === auth()->id() ? 'bg-indigo-100 ml-10' : 'bg-gray-100 mr-10' }}">
                    <b>{{ $message->user->name }}</b><p class="whitespace-pre-wrap break-words">{{ $message->body }}</p><small>{{ $message->created_at->format('M j, H:i') }}</small>
                </article>
            @endforeach
        </div>
        <form id="send" class="mt-4 flex gap-2"><label for="body" class="sr-only">Message</label><input id="body" maxlength="4000" required class="flex-1 rounded border" placeholder="Write a message…"><button class="px-4 py-2 bg-indigo-600 text-white rounded">Send</button></form>
        <p id="error" role="status" class="text-red-700 mt-2"></p>
    </section></div>
    <script>
    (() => {
        const list = document.querySelector('#messages'), form = document.querySelector('#send');
        const input = document.querySelector('#body'), error = document.querySelector('#error');
        let last = Math.max(0, ...[...list.querySelectorAll('[data-id]')].map(item => Number(item.dataset.id)));
        const messagesUrl = @json(route('chat.messages', $conversation));
        const sendUrl = @json(route('chat.send', $conversation));
        const csrf = @json(csrf_token());
        async function poll() {
            try {
                const response = await fetch(`${messagesUrl}?after=${last}`, {headers: {Accept: 'application/json'}});
                if (!response.ok) throw new Error('Messages unavailable');
                for (const message of await response.json()) {
                    const article = document.createElement('article'); article.dataset.id = message.id;
                    article.className = `p-3 rounded ${message.mine ? 'bg-indigo-100 ml-10' : 'bg-gray-100 mr-10'}`;
                    const author = document.createElement('b'); author.textContent = message.user;
                    const body = document.createElement('p'); body.className = 'whitespace-pre-wrap break-words'; body.textContent = message.body;
                    const time = document.createElement('small'); time.textContent = new Date(message.created_at).toLocaleString();
                    article.append(author, body, time); list.append(article); last = Math.max(last, message.id);
                }
                list.scrollTop = list.scrollHeight; error.textContent = '';
            } catch (failure) { error.textContent = failure.message; }
        }
        form.addEventListener('submit', async event => {
            event.preventDefault(); const body = input.value.trim(); if (!body) return;
            try {
                const response = await fetch(sendUrl, {method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json'}, body: JSON.stringify({body})});
                if (!response.ok) throw new Error('Message not sent'); input.value = ''; await poll();
            } catch (failure) { error.textContent = failure.message; }
        });
        poll(); window.setInterval(poll, 2000);
    })();
    </script>
</x-app-layout>

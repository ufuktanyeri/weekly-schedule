@if (session('status'))
    <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        <ul class="list-inside list-disc space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

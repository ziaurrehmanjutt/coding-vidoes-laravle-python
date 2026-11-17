<x-layouts.app title="Add question">
    <h1 class="mb-6 text-2xl font-bold tracking-tight">Add question</h1>

    @include('questions._form', [
        'action' => route('questions.store'),
        'method' => 'POST',
        'submitLabel' => 'Save question',
    ])
</x-layouts.app>

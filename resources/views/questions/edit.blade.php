<x-layouts.app title="Edit question">
    <h1 class="mb-6 text-2xl font-bold tracking-tight">Edit question</h1>

    @include('questions._form', [
        'action' => route('questions.update', $question),
        'method' => 'PUT',
        'submitLabel' => 'Update question',
    ])
</x-layouts.app>

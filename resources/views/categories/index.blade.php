<x-layouts.app title="Categories">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight">Categories</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400">Group questions by topic so they are easy to find. Deleting a category keeps its questions.</p>
    </div>

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('categories.store') }}" class="panel mb-6 flex flex-wrap items-end gap-3">
        @csrf
        <div class="min-w-64 flex-1">
            <label for="name" class="label">New category</label>
            <input id="name" name="name" type="text" required maxlength="60" class="field"
                   value="{{ old('name') }}" placeholder="Recursion">
        </div>
        <x-button type="submit">Add category</x-button>
    </form>

    <div class="panel divide-y divide-slate-200 p-0 dark:divide-slate-800">
        @forelse ($categories as $category)
            <div class="flex flex-wrap items-center gap-3 p-4">
                <form method="POST" action="{{ route('categories.update', $category) }}" class="flex min-w-64 flex-1 items-center gap-2">
                    @csrf
                    @method('PUT')
                    <label for="category-{{ $category->id }}" class="sr-only">Name of {{ $category->name }}</label>
                    <input id="category-{{ $category->id }}" name="name" type="text" required maxlength="60" class="field"
                           value="{{ $category->name }}">
                    <x-button type="submit" variant="secondary">Rename</x-button>
                </form>

                <a href="{{ route('questions.index', ['category' => $category->id]) }}"
                   class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                    {{ $category->questions_count }} {{ Str::plural('question', $category->questions_count) }}
                </a>

                <form method="POST" action="{{ route('categories.destroy', $category) }}"
                      onsubmit="return confirm('Delete this category? Its questions will become uncategorized.')">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger">Delete</x-button>
                </form>
            </div>
        @empty
            <p class="p-6 text-center text-sm text-slate-600 dark:text-slate-400">No categories yet. Add one above.</p>
        @endforelse
    </div>
</x-layouts.app>

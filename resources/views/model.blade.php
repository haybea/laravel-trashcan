@extends('trashcan::layouts.app-' . config('trashcan.css_framework', 'bootstrap'))

@section('content')
    @php $isBootstrap = config('trashcan.css_framework', 'bootstrap') === 'bootstrap'; @endphp

    @include('trashcan::partials.' . config('trashcan.css_framework') . '.sidebar')

    @if($isBootstrap)
        <div class="main-content">
            <div class="p-4">
                {{-- Header --}}
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">
                            <i class="bi bi-trash3 text-secondary me-2"></i>{{ $activeModel['name'] }} Trashcan
                        </h1>
                        <p class="text-muted mb-0">{{ $items->total() }} deleted items</p>
                    </div>

                    <div class="d-flex gap-2 align-items-center">
                        <span id="selectedCount" class="text-muted me-2"></span>

                        {{-- Export Dropdown --}}
                        @if(config('trashcan.export.enabled') && $items->count() > 0)
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-download me-1"></i>Export
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('trashcan.export', $encoded) }}?format=csv"><i class="bi bi-filetype-csv me-2"></i>CSV</a></li>
                                    <li><a class="dropdown-item" href="{{ route('trashcan.export', $encoded) }}?format=json"><i class="bi bi-filetype-json me-2"></i>JSON</a></li>
                                </ul>
                            </div>
                        @endif

                        @if($items->count() > 0)
                            <form action="{{ route('trashcan.bulk-restore', $encoded) }}" method="POST" class="d-inline" id="bulkRestoreForm">
                                @csrf
                                <input type="hidden" name="ids" id="restoreIds">
                                <button type="button" class="btn btn-restore btn-sm bulk-btn text-white" disabled
                                        onclick="document.getElementById('restoreIds').value = JSON.stringify(getSelectedIds()); showConfirmModal('Restore Selected Items', 'Are you sure you want to restore the selected items?', function() { document.getElementById('bulkRestoreForm').submit(); });">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                                </button>
                            </form>
                            <form action="{{ route('trashcan.bulk-force-delete', $encoded) }}" method="POST" class="d-inline" id="bulkDeleteForm">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="ids" id="deleteIds">
                                <button type="button" class="btn btn-danger btn-sm bulk-btn" disabled
                                        onclick="showConfirmModal('Delete Selected Items', 'Are you sure you want to permanently delete the selected items? This action cannot be undone.', function() { document.getElementById('deleteIds').value = JSON.stringify(getSelectedIds()); document.getElementById('bulkDeleteForm').submit(); })">
                                    <i class="bi bi-trash3 me-1"></i>Delete
                                </button>
                            </form>
                            <form action="{{ route('trashcan.empty-trash', $encoded) }}" method="POST" class="d-inline" id="emptyTrashForm">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-outline-danger btn-sm"
                                        onclick="showConfirmModal('Empty Trash', 'Are you sure you want to permanently delete all {{ $activeModel['name'] }} items from trash? This action cannot be undone.', function() { document.getElementById('emptyTrashForm').submit(); })">
                                    <i class="bi bi-trash3-fill me-1"></i>Empty
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Search & Filter --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="from" class="form-control" placeholder="From" value="{{ request('from') }}">
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="to" class="form-control" placeholder="To" value="{{ request('to') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Alerts --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Table --}}
                @if($items->count() > 0)
                    <div class="card shadow-sm border-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th width="40"><input type="checkbox" class="form-check-input" onchange="toggleAll(this)"></th>
                                    @foreach($activeModel['columns'] as $column)
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => $column, 'dir' => request('dir') === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                                {{ ucwords(str_replace('_', ' ', $column)) }}
                                                @if(request('sort') === $column)
                                                    <i class="bi bi-arrow-{{ request('dir') === 'asc' ? 'up' : 'down' }}"></i>
                                                @endif
                                            </a>
                                        </th>
                                    @endforeach
                                    <th width="150" class="text-end">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input item-checkbox" value="{{ $item->id }}"></td>
                                        @foreach($activeModel['columns'] as $column)
                                            <td>
                                                @if($column === 'deleted_at')
                                                    <span class="text-muted small" title="{{ $item->deleted_at }}">{{ $item->deleted_at->diffForHumans() }}</span>
                                                @else
                                                    {{ \Illuminate\Support\Str::limit($item->{$column}, 50) }}
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="text-end">
                                            <form action="{{ route('trashcan.restore', [$encoded, $item->id]) }}" method="POST" class="d-inline" id="restoreForm{{ $item->id }}">
                                                @csrf
                                                <button type="button" class="btn btn-sm btn-restore text-white" title="Restore"
                                                        onclick="showConfirmModal('Restore Item', 'Are you sure you want to restore this item?', function() { document.getElementById('restoreForm{{ $item->id }}').submit(); })">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('trashcan.force-delete', [$encoded, $item->id]) }}" method="POST" class="d-inline" id="deleteForm{{ $item->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Delete permanently"
                                                        onclick="showConfirmModal('Delete Item', 'Are you sure you want to permanently delete this item? This action cannot be undone.', function() { document.getElementById('deleteForm{{ $item->id }}').submit(); })">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-3">{{ $items->links() }}</div>
                @else
                    <div class="card shadow-sm border-0">
                        <div class="empty-state">
                            <i class="bi bi-trash3"></i>
                            <h5 class="mt-3 text-muted">No deleted {{ $activeModel['name'] }} items</h5>
                            <p class="text-muted">Items that are deleted will appear here.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Bootstrap Confirmation Modal --}}
        <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-body text-center p-4">
                        <div class="mb-3">
                            <i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="modal-title mb-2" id="confirmModalTitle">Confirm Action</h5>
                        <p class="text-muted mb-4" id="confirmModalMessage">Are you sure you want to proceed?</p>
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger px-4" id="confirmModalBtn">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @else
        {{-- Tailwind Model View --}}
        <main class="ml-72 min-h-screen">
            <div class="p-8">
                {{-- Header --}}
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                            <i class="ri-delete-bin-line text-gray-400"></i>{{ $activeModel['name'] }} Trashcan
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ $items->total() }} deleted items</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span id="selectedCount" class="text-sm text-gray-500"></span>

                        @if(config('trashcan.export.enabled') && $items->count() > 0)
                            <div class="relative" x-data="{ open: false }">
                                <a href="{{ route('trashcan.export', $encoded) }}?format=csv" class="px-3 py-2 border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-gray-300 text-sm rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">
                                    <i class="ri-download-line mr-1"></i>Export CSV
                                </a>
                            </div>
                        @endif

                        @if($items->count() > 0)
                            <form action="{{ route('trashcan.bulk-restore', $encoded) }}" method="POST" class="inline" id="bulkRestoreForm">
                                @csrf
                                <input type="hidden" name="ids" id="restoreIds">
                                <button type="button" class="bulk-btn px-4 py-2 bg-emerald-500 text-white text-sm rounded-lg hover:bg-emerald-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                        disabled onclick="document.getElementById('restoreIds').value = JSON.stringify(getSelectedIds()); showConfirmModal('Restore Selected Items', 'Are you sure you want to restore the selected items?', function() { document.getElementById('bulkRestoreForm').submit(); })">
                                    <i class="ri-arrow-go-back-line mr-1"></i>Restore
                                </button>
                            </form>
                            <form action="{{ route('trashcan.bulk-force-delete', $encoded) }}" method="POST" class="inline" id="bulkDeleteForm">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="ids" id="deleteIds">
                                <button type="button" class="bulk-btn px-4 py-2 bg-red-500 text-white text-sm rounded-lg hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                        disabled onclick="showConfirmModal('Delete Selected Items', 'Are you sure you want to permanently delete the selected items? This action cannot be undone.', function() { document.getElementById('deleteIds').value = JSON.stringify(getSelectedIds()); document.getElementById('bulkDeleteForm').submit(); })">
                                    <i class="ri-delete-bin-line mr-1"></i>Delete
                                </button>
                            </form>
                            <form action="{{ route('trashcan.empty-trash', $encoded) }}" method="POST" class="inline" id="emptyTrashForm">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="px-4 py-2 border border-red-300 text-red-600 text-sm rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20"
                                        onclick="showConfirmModal('Empty Trash', 'Are you sure you want to permanently delete all {{ $activeModel['name'] }} items from trash? This action cannot be undone.', function() { document.getElementById('emptyTrashForm').submit(); })">
                                    <i class="ri-delete-bin-fill mr-1"></i>Empty
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Search & Filter --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-4 mb-6">
                    <form method="GET" class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <div class="relative">
                                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="search" placeholder="Search..." value="{{ request('search') }}"
                                       class="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-transparent dark:text-white">
                            </div>
                        </div>
                        <input type="date" name="from" value="{{ request('from') }}" class="px-4 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-transparent dark:text-white">
                        <input type="date" name="to" value="{{ request('to') }}" class="px-4 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-transparent dark:text-white">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Filter</button>
                    </form>
                </div>

                {{-- Alerts --}}
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg flex items-center text-green-800 dark:text-green-400">
                        <i class="ri-check-line mr-2"></i>{{ session('success') }}
                    </div>
                @endif

                {{-- Table --}}
                @if($items->count() > 0)
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-slate-700 border-b dark:border-slate-600">
                            <tr>
                                <th class="p-4 w-10"><input type="checkbox" class="rounded" onchange="toggleAll(this)"></th>
                                @foreach($activeModel['columns'] as $column)
                                    <th class="p-4 text-left text-sm font-medium text-gray-600 dark:text-gray-300">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => $column, 'dir' => request('dir') === 'asc' ? 'desc' : 'asc']) }}" class="hover:text-indigo-600">
                                            {{ ucwords(str_replace('_', ' ', $column)) }}
                                            @if(request('sort') === $column)
                                                <i class="ri-arrow-{{ request('dir') === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                            @endif
                                        </a>
                                    </th>
                                @endforeach
                                <th class="p-4 text-right text-sm font-medium text-gray-600 dark:text-gray-300 w-36">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-slate-700">
                            @foreach($items as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <td class="p-4"><input type="checkbox" class="item-checkbox rounded" value="{{ $item->id }}"></td>
                                    @foreach($activeModel['columns'] as $column)
                                        <td class="p-4 text-sm text-gray-700 dark:text-gray-300">
                                            @if($column === 'deleted_at')
                                                <span class="text-gray-400" title="{{ $item->deleted_at }}">{{ $item->deleted_at->diffForHumans() }}</span>
                                            @else
                                                {{ \Illuminate\Support\Str::limit($item->{$column}, 50) }}
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="p-4 text-right">
                                        <form action="{{ route('trashcan.restore', [$encoded, $item->id]) }}" method="POST" class="inline" id="restoreForm{{ $item->id }}">
                                            @csrf
                                            <button type="button" class="p-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg"
                                                    onclick="showConfirmModal('Restore Item', 'Are you sure you want to restore this item?', function() { document.getElementById('restoreForm{{ $item->id }}').submit(); })">
                                                <i class="ri-arrow-go-back-line"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('trashcan.force-delete', [$encoded, $item->id]) }}" method="POST" class="inline" id="deleteForm{{ $item->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"
                                                    onclick="showConfirmModal('Delete Item', 'Are you sure you want to permanently delete this item? This action cannot be undone.', function() { document.getElementById('deleteForm{{ $item->id }}').submit(); })">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $items->links() }}</div>
                @else
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm py-20 text-center">
                        <i class="ri-delete-bin-line text-6xl text-gray-200 dark:text-slate-600"></i>
                        <h5 class="mt-4 text-lg text-gray-500 dark:text-gray-400">No deleted {{ $activeModel['name'] }} items</h5>
                        <p class="text-gray-400">Items that are deleted will appear here.</p>
                    </div>
                @endif
            </div>
        </main>

        {{-- Tailwind Confirmation Modal --}}
        <div id="confirmModal" class="fixed inset-0 z-50 hidden">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onclick="hideConfirmModal()"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-md w-full transform transition-all">
                    <div class="p-6 text-center">
                        <div class="mx-auto w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mb-4">
                            <i class="ri-error-warning-line text-3xl text-red-500"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2" id="confirmModalTitle">Confirm Action</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6" id="confirmModalMessage">Are you sure you want to proceed?</p>
                        <div class="flex gap-3 justify-center">
                            <button type="button" onclick="hideConfirmModal()" class="px-5 py-2.5 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                                Cancel
                            </button>
                            <button type="button" id="confirmModalBtn" class="px-5 py-2.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        let confirmCallback = null;
        const isBootstrap = {{ $isBootstrap ? 'true' : 'false' }};

        function showConfirmModal(title, message, callback) {
            confirmCallback = callback;
            document.getElementById('confirmModalTitle').textContent = title;
            document.getElementById('confirmModalMessage').textContent = message;

            if (isBootstrap) {
                const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
                modal.show();
            } else {
                const modal = document.getElementById('confirmModal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }
        }

        function hideConfirmModal() {
            if (isBootstrap) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
                if (modal) modal.hide();
            } else {
                const modal = document.getElementById('confirmModal');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            }
            confirmCallback = null;
        }

        document.getElementById('confirmModalBtn').addEventListener('click', function() {
            if (confirmCallback) {
                confirmCallback();
            }
            hideConfirmModal();
        });

        // Handle ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideConfirmModal();
            }
        });
    </script>
@endpush
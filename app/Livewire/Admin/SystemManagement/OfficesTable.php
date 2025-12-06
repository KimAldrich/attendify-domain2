<?php

namespace App\Livewire\Admin\SystemManagement;

use App\Models\Office;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class OfficesTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sort = 'name';
    public string $dir  = 'asc';
    public ?int $editingId = null;

    public ?int $busyId = null;

    // form
    public string $name = '';
    public bool $is_active = true;

    public function getTableTargetsProperty(): string
    {
        return 'search,sort,dir,page,perPage';
    }

    protected function rules(): array
    {
        return [
            'name'      => ['required','string','max:255', Rule::unique('offices','name')->ignore($this->editingId)],
            'is_active' => ['boolean'],
        ];
    }

    public function updatingSearch() { $this->resetPage(); }

    public function sortBy(string $field): void
    {
        if ($this->sort === $field) {
            $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->dir  = 'asc';
        }
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset(['editingId','name','is_active']);
        $this->is_active = true;
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'office-modal');
    }

    public function edit(int $id): void
    {
        $this->busyId = $id;
        try {
            $this->resetValidation();
            $o = Office::findOrFail($id);
            $this->editingId = $o->id;
            $this->name      = $o->name;
            $this->is_active = (bool)$o->is_active;

            $this->dispatch('open-modal', name:'office-modal');
        } finally {
            $this->busyId = null;
            $this->dispatch('row-done', id: $id);
        }
    }

    public function save(): void
    {
        $id = $this->editingId;
        $this->busyId = $id;
        try {
            $data = $this->validate();
            $o = $id ? Office::findOrFail($id) : new Office();
            $o->fill($data)->save();

            $this->dispatch('toast', type:'success', message:'Office saved.');
            $this->dispatch('close-modal', name:'office-modal');
        } finally {
            $this->busyId = null;
        }
    }

    public function toggleActive(int $id): void
    {
        $this->busyId = $id;
        try {
            $o = Office::findOrFail($id);
            $o->is_active = !$o->is_active;
            $o->save();
            $this->dispatch('toast', type:'success', message:'Office status updated.');
        } finally {
            $this->busyId = null;
            $this->dispatch('row-done', id: $id);
        }
    }

    public function render()
    {
        $q = Office::query()
            ->when($this->search, fn($q) => $q->where('name','like',"%{$this->search}%"))
            ->orderBy($this->sort, $this->dir);

        return view('livewire.admin.system-management.offices-table', [
            'rows' => $q->paginate(10),
        ]);
    }
}

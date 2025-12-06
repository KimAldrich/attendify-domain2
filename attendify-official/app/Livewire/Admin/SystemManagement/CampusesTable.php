<?php

namespace App\Livewire\Admin\SystemManagement;

use App\Models\Campus;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class CampusesTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sort = 'abbrev';
    public string $dir  = 'asc';
    public ?int $editingId = null;

    public ?int $busyId = null;

    // form
    public string $abbrev = '';
    public string $name = '';
    public bool $is_active = true;

    public function getTableTargetsProperty(): string
    {
        return 'search,sort,dir,page,perPage';
    }

    protected function rules(): array
    {
        return [
            'abbrev' => ['required','string','max:10', Rule::unique('campuses','abbrev')->ignore($this->editingId)],
            'name'   => ['required','string','max:255'],
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
            $this->dir = 'asc';
        }
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset(['editingId','abbrev','name','is_active']);
        $this->is_active = true;
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'campus-modal');
    }

    public function edit(int $id): void
    {
        $this->busyId = $id;
        try {
            $this->resetValidation();
            $c = Campus::findOrFail($id);
            $this->editingId = $c->id;
            $this->abbrev    = $c->abbrev;
            $this->name      = $c->name;
            $this->is_active = (bool) $c->is_active;
            $this->dispatch('open-modal', name:'campus-modal');
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
            $c = $id ? Campus::findOrFail($id) : new Campus();
            $c->fill($data)->save();
            $this->dispatch('toast', type:'success', message:'Campus saved.');
            $this->dispatch('close-modal', name:'campus-modal');
        } finally {
            $this->busyId = null;
        }
    }

    public function toggleActive(int $id): void
    {
        $this->busyId = $id;
        try {
            $c = Campus::findOrFail($id);
            $c->is_active = !$c->is_active;
            $c->save();
            $this->dispatch('toast', type:'success', message:'Campus status updated.');
        } finally {
            $this->busyId = null;
            $this->dispatch('row-done', id: $id);
        }
    }

    public function deactivate(int $id): void
    {
        $this->busyId = $id;
        try {
            $c = Campus::findOrFail($id);
            if ($c->is_active) {
                $c->is_active = false;
                $c->save();
            }
            $this->dispatch('toast', type:'success', message:'Campus deactivated.');
        } finally {
            $this->busyId = null;
            $this->dispatch('row-done', id: $id);
        }
    }

    public function render()
    {
        $q = Campus::query()
            ->when($this->search, fn($q) => $q->where(function($qq){
                $qq->where('abbrev','like',"%{$this->search}%")
                   ->orWhere('name','like',"%{$this->search}%");
            }))
            ->orderBy($this->sort, $this->dir);

        return view('livewire.admin.system-management.campuses-table', [
            'rows' => $q->paginate(10),
        ]);
    }
}

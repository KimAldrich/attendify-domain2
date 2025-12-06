<?php

namespace App\Livewire\Admin\SystemManagement;

use Livewire\Component;

class Page extends Component
{
    public string $tab = 'campuses'; // campuses|departments|offices
    protected $queryString = ['tab'];

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['campuses','departments','offices']) ? $tab : 'campuses';
    }

    public function render()
    {
        return view('livewire.admin.system-management.page');
    }
}

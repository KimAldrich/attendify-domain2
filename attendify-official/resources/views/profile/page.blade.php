<div class="px-6 py-4 space-y-6">
  {{-- Row 1: left (profile photo) + right (basic info) --}}
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-stretch">
    <div class="md:col-span-1">
      @include('livewire.profile.sections.profile-section', [
        'profile'       => $profile,
        'viewerIsOwner' => $viewerIsOwner,
      ])
    </div>

    <div class="md:col-span-3">
      @include('livewire.profile.sections.basic-info-section', [
        'profile'       => $profile,
        'viewerIsOwner' => $viewerIsOwner,
        'infoComplete'  => $this->infoComplete,
        'bio'           => $this->bio,   
        'roleTab'       => $roleTab,
        'is_moderator'  => $is_moderator,        
      ])
    </div>
  </div>

  {{-- Row 2: left (shared fields) + right (tabbed role info) --}}
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-stretch">
    <div class="md:col-span-1">
      @include('livewire.profile.sections.shared-field-section', [
        'profile'          => $profile,
        'address_house'    => $this->address_house,
        'address_brgy'     => $this->address_brgy,
        'address_city'     => $this->address_city,
        'address_province' => $this->address_province,
        'facebook'         => $this->link_facebook,
        'linkedin'         => $this->link_linkedin,
      ])
    </div>

    <div class="md:col-span-3">
      @include('livewire.profile.sections.tabbed-info-section', [
        'roleTab'                => $roleTab,
        'campuses'               => $campuses,
        'departments'            => $departments,
        'offices'                => $offices,
        'organization'           => $organization,
        'student_number'         => $student_number,
        'year_level'             => $year_level,
        'student_department_id'  => $student_department_id,
        'student_campus_id'      => $student_campus_id,
        'is_moderator'           => $is_moderator,
        'is_teaching'            => $is_teaching,
        'faculty_department_id'  => $faculty_department_id,
        'faculty_office_id'      => $faculty_office_id,
        'faculty_campus_id'      => $faculty_campus_id,
        'yearLevels'             => $yearLevels,
        'viewerIsOwner'          => $viewerIsOwner,
      ])
    </div>
  </div>
</div>

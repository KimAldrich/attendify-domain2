<x-app-layout>
  @role('admin')
    @include('classroom.roles.admin')  
  @else
    @include('classroom.roles.faculty') 
  @endrole
</x-app-layout>
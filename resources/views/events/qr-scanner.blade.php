{{-- resources/views/events/qr-scanner.blade.php --}}
<x-app-layout>
  <div class="px-6 pt-6">
    <h2 class="text-2xl font-semibold tracking-tight">QR Scan Station</h2>
    <p class="text-sm text-gray-500">Scan attendee QRs and auto-fetch profile details.</p>
  </div>

  <div class="px-0 md:px-6 py-4">
    <livewire:attendance.scan-station />
  </div>
</x-app-layout>

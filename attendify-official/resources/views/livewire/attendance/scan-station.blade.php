{{-- resources/views/livewire/attendance/scan-station.blade.php --}}
<div class="bg-white rounded-xl ring-1 ring-slate-200 p-3 sm:p-4 min-h-0"
     style="height: calc(100vh - 50px);">
  <div class="grid grid-cols-5 gap-4 h-full">
    {{-- LEFT 4/5 --}}
    <section class="col-span-5 lg:col-span-3 flex flex-col gap-3 h-full min-h-0">
      {{-- Top controls --}}
      <div class="flex gap-2">
        {{-- Event dropdown (hardcoded for now) --}}
        <div class="flex-1 max-w-xs">
          <label class="block text-[11px] uppercase tracking-wide text-slate-500 mb-1">Select Event</label>
          <select wire:model="selectedEvent"
                  class="w-full h-10 rounded-md border border-slate-300 bg-white px-2 text-sm">
            <option value="design-jam-2025">Design Jam 2025</option>
            <option value="tech-expo-2025">Tech Expo 2025</option>
          </select>
        </div>

        {{-- Camera dropdown --}}
        <div class="flex-1 max-w-xs" x-data="cameraPicker()" x-init="init()">
          <label class="block text-[11px] uppercase tracking-wide text-slate-500 mb-1">Open Camera</label>
          <select x-model="selectedId" @change="startStream()" class="w-full h-10 rounded-md border border-slate-300 bg-white px-2 text-sm">
            <option value="">— Choose camera —</option>
            <template x-for="d in devices" :key="d.deviceId">
              <option :value="d.deviceId" x-text="d.label || ('Camera ' + ($index+1))"></option>
            </template>
          </select>
        </div>

{{-- Manual fallback --}}
<div class="flex items-end gap-2 ml-auto">
  <input type="text" placeholder="Enter UID manually…" wire:model.defer="manualUid"
         class="h-10 w-56 rounded-md border border-slate-300 px-2 text-sm" />
  <button wire:click="submitManualUid"
          class="h-10 px-3 rounded-md bg-blue-600 text-white hover:bg-blue-700 text-sm">
    Submit UID
  </button>
</div>

      </div>

      {{-- Camera area --}}
      <div class="relative flex-1 rounded-xl ring-1 ring-slate-200 overflow-hidden"
           x-data="cameraView()" x-init="attach()">
        <video x-ref="video"
       autoplay playsinline muted
       class="absolute inset-0 w-full h-full object-cover"
       style="filter: contrast(1.35) brightness(0.9) saturate(1.05);"></video>
        <div x-show="flash"
        x-transition.opacity
        class="absolute inset-0 border-4 border-green-500 rounded-lg pointer-events-none"
        x-cloak></div>

        {{-- Crosshair overlay --}}
        <div class="pointer-events-none absolute inset-0 grid place-items-center">
          <div class="w-56 h-56 border-2 border-white/90 rounded-lg relative">
            <div class="absolute inset-0">
              <div class="absolute inset-y-1/2 left-0 -translate-y-1/2 w-full h-0.5 bg-white/70"></div>
              <div class="absolute inset-x-1/2 top-0 -translate-x-1/2 w-0.5 h-full bg-white/70"></div>
            </div>
          </div>
        </div>
        <div class="absolute inset-0 bg-gradient-to-b from-black/10 via-transparent to-black/10"></div>
      </div>

      {{-- Latest scanned details --}}
      <div class="grid grid-cols-12 gap-3">
        {{-- photo --}}
        <div class="col-span-5 sm:col-span-5">
            <div class="bg-white rounded-xl ring-1 ring-slate-200 p-3 flex items-center justify-center h-48 w-full">
                @php
                $photo = data_get($lastProfile, 'photo') ?: asset('images/ui/userdefault.jpg');
                @endphp
                <img src="{{ $photo }}" alt="Latest photo" class="h-full w-full object-cover rounded-lg">
            </div>
        </div>

        {{-- name + role + specifics --}}
        <div class="col-span-5 sm:col-span-7">
          <div class="bg-white rounded-xl ring-1 ring-slate-200 p-3 sm:p-4 h-48 flex flex-col">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-lg font-semibold">
                  {{ data_get($lastProfile, 'name', '—') }}
                </div>
                <div class="text-xs text-slate-500">
                  {{ data_get($lastProfile, 'role', '—') }}
                  @if($lastScannedAt)
                    · scanned at {{ $lastScannedAt }}
                  @endif
                </div>
              </div>
              @php $uid = data_get($lastProfile, 'uid'); @endphp
              @if(!empty($uid))
                <div class="text-[11px] text-slate-500">UID: {{ $uid }}</div>
              @endif
            </div>

            {{-- Role-specific info --}}
            <div class="mt-3">
              @php
                $role = data_get($lastProfile, 'role');
                $ri   = (array) data_get($lastProfile, 'role_info', []);
              @endphp

              @if($role === 'student')
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm">
                  <div><span class="text-slate-600">Student No.</span>:
                    <span class="font-medium">{{ data_get($ri, 'student_number', '—') }}</span>
                  </div>
                  <div class="mt-1"><span class="text-slate-600">Year Level</span>:
                    <span class="font-medium">{{ data_get($ri, 'year_level', '—') }}</span>
                  </div>
                  <div class="mt-1 text-xs text-slate-600">
                    Dept ID: {{ data_get($ri, 'department_id', '—') }} · Campus ID: {{ data_get($ri, 'campus_id', '—') }}
                    @if((bool) data_get($ri, 'is_moderator', false))
                      · <span class="font-medium text-blue-700">Moderator</span>
                    @endif
                  </div>
                </div>
              @elseif($role === 'faculty')
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm">
                  <div><span class="text-slate-600">Teaching</span>:
                    <span class="font-medium">{{ data_get($ri, 'is_teaching', false) ? 'Yes' : 'No' }}</span>
                  </div>
                  <div class="mt-1 text-xs text-slate-600">
                    Dept ID: {{ data_get($ri, 'department_id', '—') }} · Office ID: {{ data_get($ri, 'office_id', '—') }} · Campus ID: {{ data_get($ri, 'campus_id', '—') }}
                  </div>
                </div>
              @elseif($role === 'guest')
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm">
                  <div><span class="text-slate-600">Organization</span>:
                    <span class="font-medium">{{ data_get($ri, 'organization', '—') }}</span>
                  </div>
                </div>
              @elseif($role === 'admin')
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                  <div class="text-slate-700">Admin account</div>
                </div>
              @else
                <div class="text-sm text-slate-500">Scan a QR to load user details…</div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </section>

    {{-- RIGHT 1/5: Scan list --}}
    <aside class="col-span-5 lg:col-span-2 flex flex-col h-full min-h-0">
      <div class="mb-2">
        <label class="block text-[11px] uppercase tracking-wide text-slate-500 mb-1">Filter by name</label>
        <input type="text" class="w-full h-10 rounded-md border border-slate-300 px-2 text-sm"
               placeholder="Type to filter…"
               wire:model.live="filter">
      </div>

      <div class="flex-1 overflow-auto space-y-2 pr-1">
        @forelse($this->filteredScans as $row)
          <div class="rounded-lg border border-amber-300 bg-amber-100 p-2">
            <div class="text-sm font-medium text-amber-900">{{ $row['name'] }}</div>
            <div class="text-xs text-amber-800">{{ $row['timestamp'] }}</div>
            <div class="text-[11px] text-amber-800/80 truncate">UID: {{ $row['uid'] }}</div>
          </div>
        @empty
          <div class="text-sm text-slate-500">No scans yet.</div>
        @endforelse
      </div>
    </aside>
  </div>
</div>

@once
  @push('scripts')
    <script>
function cameraPicker(){
  return {
    devices: [],
    selectedId: '',
    lastSent: '',
    async init(){
      try { await navigator.mediaDevices.getUserMedia({ video: true, audio: false }); } catch(_) {}
      const all = await navigator.mediaDevices.enumerateDevices();
      this.devices = all.filter(d => d.kind === 'videoinput');

      // auto-pick first/front camera
      const front = this.devices.find(d => /front|user/i.test(d.label));
      if (front) this.selectedId = front.deviceId;
      else if (this.devices[0]) this.selectedId = this.devices[0].deviceId;

      if (this.selectedId) this.startStream();
    },
    startStream(){
      if (!this.selectedId || this.selectedId === this.lastSent) return;
      this.lastSent = this.selectedId;
      window.dispatchEvent(new CustomEvent('camera-change', {
        detail: { deviceId: this.selectedId }
      }));
    }
  }
}

function cameraView(){
    return {
      codeReader: null,
      runningDeviceId: null,
      flash: false,

      triggerFlash(){
        this.flash = true;
        setTimeout(() => this.flash = false, 300);
      },

      stopReader(){
        if (this.codeReader) {
          if (typeof this.codeReader.stopContinuousDecode === 'function') {
            try { this.codeReader.stopContinuousDecode(); } catch(_) {}
          } else if (typeof this.codeReader.reset === 'function') {
            try { this.codeReader.reset(); } catch(_) {}
          }
        }
        this.codeReader = null;
        this.runningDeviceId = null;
        // ensure we drop any srcObject to avoid “already playing” warnings
        if (this.$refs.video) this.$refs.video.srcObject = null;
      },

      async attach(){
        // mild contrast boost (helps in glare/low light)
        if (this.$refs?.video) {
          this.$refs.video.style.filter = 'contrast(1.35) brightness(0.9) saturate(1.05)';
        }

        // Single handler for camera-change
        window.addEventListener('camera-change', (e) => {
          const deviceId = e.detail?.deviceId || null;
          if (!deviceId) return;
          if (this.runningDeviceId === deviceId) return; // prevent double start on same device

          this.startZXing(deviceId);
        });
      },

      startZXing(deviceId){
        // stop any previous decode session
        this.stopReader();

        if (!window.ZXing) {
          console.error('ZXing not found on window. Did you import @zxing/browser in app.js?');
          return;
        }

        const ZX = window.ZXing;
        const { BrowserMultiFormatReader } = ZX;

        // Build hints IF available; otherwise run without hints
        let reader;
        if (ZX.DecodeHintType) {
          const HT   = ZX.DecodeHintType;
          const HMap = ZX.Map ? new ZX.Map() : new Map();
          HMap.set(HT.TRY_HARDER, true);
          HMap.set(HT.ALSO_INVERTED, true);
          reader = new BrowserMultiFormatReader(HMap, 200); // 200ms loop
        } else {
          reader = new BrowserMultiFormatReader();
        }
        this.codeReader = reader;
        this.runningDeviceId = deviceId;

        // ⚠️ Do NOT await; this is continuous
        reader.decodeFromVideoDevice(deviceId, this.$refs.video, (result, err) => {
          if (result) {
            const raw = result.getText ? result.getText() : String(result.text || result);
            const uid = (raw || '').trim();
            if (uid) {
              this.triggerFlash();
              // pause to avoid rapid duplicate fires
              this.stopReader();

              // Livewire call
              if (window.Livewire?.find) {
                const root = this.$el.closest('[wire\\:id]');
                const id = root ? root.getAttribute('wire:id') : null;
                if (id) window.Livewire.find(id)?.call('handleScan', uid);
              }

              // restart decode shortly after (if operator keeps cam pointed)
              setTimeout(() => {
                if (!this.$refs.video) return;
                // rebuild reader (fresh state)
                this.startZXing(deviceId);
              }, 800);
            }
          } else if (err) {
            // Safe NotFound detection (no instanceof on undefined)
            const isNotFound =
              (ZX.NotFoundException && (err instanceof ZX.NotFoundException)) ||
              err?.name === 'NotFoundException' ||
              err?.constructor?.name === 'NotFoundException';

            if (!isNotFound) {
              console.error('ZXing error:', err);
            }
            // NotFound = no code in frame; swallow silently
          }
        });

        // Ensure video plays; ignore AbortError if ZXing restarts rapidly
        const v = this.$refs.video;
        if (v?.play) v.play().catch(() => {});
      }
    }
  }
    </script>
  @endpush
@endonce

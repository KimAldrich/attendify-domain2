<?php

namespace App\Livewire\Attendance;

use App\Models\User;
use App\Services\FirestoreRest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

use Livewire\Attributes\Layout;
#[Layout('layouts.app')] 
class ScanStation extends Component
{
    public array $events = [
        ['code' => 'design-jam-2025', 'title' => 'Design Jam 2025'],
        ['code' => 'freshers-welcome', 'title' => 'Freshers’ Welcome Fair'],
    ];
    public ?string $selectedEvent = 'design-jam-2025';

    // Camera/device
    public ?string $selectedDeviceId = null;

    // Latest scanned profile (rendered under the camera)
    public ?array $lastProfile = null; // ['uid','name','roles'=>[],'email','photo','role'=>'student|faculty|guest|admin','role_info'=>[]]
    public ?string $lastScannedAt = null; // 'Y-m-d H:i:s'

    // History list
    public array $scans = []; // each: ['uid','name','timestamp']
    public string $filter = '';

    public ?string $manualUid = null;

    public function mount()
    {
        // nothing else; events are hardcoded for now
    }

#[On('qr-scanned')]
public function handleScan(string $raw): void
{
    [$uid, $event, $ver] = $this->parseQr($raw);

    if ($uid === '') return;

    // Optional: align the UI’s selected event with the QR event when provided
    if ($event !== '' && $this->selectedEvent !== $event) {
        $this->selectedEvent = $event;
    }

    if ($this->alreadyScanned($uid, $event)) {
        $this->dispatch('toast', type:'info', message:'Already scanned for this event.');
        return;
    }

    [$profile, $role, $roleInfo] = $this->fetchProfileByUid($uid);

    $this->lastProfile = [
        'uid'       => $uid,
        'name'      => $profile['name'] ?? 'Unknown User',
        'email'     => $profile['email'] ?? null,
        'roles'     => $profile['roles'] ?? [],
        'photo'     => $this->photoUrl($profile),
        'role'      => $role,
        'role_info' => $roleInfo,
    ];
    $this->lastScannedAt = now()->format('Y-m-d H:i:s');

    array_unshift($this->scans, [
        'uid'       => $uid,
        'name'      => $this->lastProfile['name'],
        'timestamp' => $this->lastScannedAt,
        'event'     => $event,
        'ver'       => $ver,
    ]);
}

private function parseQr(string $raw): array
{
    $raw = trim($raw);
    $data = [];

    // Try JSON first
    try {
        /** @var array|null $decoded */
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    } catch (\Throwable $e) {
        // not JSON → fall through as plain UID
    }

    $uid   = (string)($data['uid'] ?? $raw);
    $event = (string)($data['event'] ?? ($this->selectedEvent ?? ''));
    $ver   = $data['ver'] ?? null;

    return [$uid, $event, $ver, $data];
}

    private function alreadyScanned(string $uid, string $event): bool
    {
        foreach ($this->scans as $row) {
            if (($row['uid'] ?? '') === $uid && ($row['event'] ?? '') === $event) {
                return true;
            }
        }
        return false;
    }

public function submitManualUid(): void
{
    $raw = trim((string)$this->manualUid);
    if ($raw === '') return;

    $this->handleScan($raw); // will parse JSON or plain UID
    $this->manualUid = null;
}


    public function getFilteredScansProperty(): array
    {
        $q = Str::lower(trim($this->filter));
        if ($q === '') return $this->scans;

        return array_values(array_filter($this->scans, function ($row) use ($q) {
            return Str::contains(Str::lower($row['name'] ?? ''), $q)
                || Str::contains(Str::lower($row['uid'] ?? ''), $q);
        }));
    }

    /** ---------------------------------------------
     *  Data fetch from MySQL + Firestore
     * ----------------------------------------------*/
    private function fetchProfileByUid(string $uid): array
    {
        /** @var FirestoreRest $fs */
        $fs = app(FirestoreRest::class);

        // From MySQL (for roles, names, email, photo_path mirror if any)
        /** @var User|null $u */
        $u = User::with('roles')->where('firebase_uid', $uid)->first();

        $roles = $u?->roles?->pluck('name')->map(fn($r) => (string)$r)->values()->all() ?? [];
        $role  = $roles[0] ?? 'guest';

        // Firestore: root + role subdocs (for role-specific display)
        $root = $this->tryGet($fs, "users/{$uid}");
        $shared = $this->tryGet($fs, "users/{$uid}/related_info/shared");
        $guest  = $this->tryGet($fs, "users/{$uid}/related_info/guest");
        $student= $this->tryGet($fs, "users/{$uid}/related_info/student");
        $faculty= $this->tryGet($fs, "users/{$uid}/related_info/faculty");

        $name = $u?->name ?? null;
        if (!$name && $u) {
            $first  = trim((string) ($u->first_name ?? ''));
            $middle = trim((string) ($u->middle_name ?? ''));
            $last   = trim((string) ($u->last_name ?? ''));
            $full   = trim($first.' '.($middle ? $middle.' ' : '').$last);
            $name   = $full !== '' ? $full : null;
        }
        if (!$name && isset($root['fields']['name']['stringValue'])) {
            $name = (string) $root['fields']['name']['stringValue'];
        }
        $name = $name ?: '—';

        $roleInfo = [];
        if ($role === 'student' && $student) {
            $f = $student['fields'] ?? [];
            $roleInfo = [
                'student_number' => $f['student_number']['stringValue'] ?? null,
                'year_level'     => $f['year_level']['stringValue'] ?? null,
                'department_id'  => isset($f['department_id']['integerValue']) ? (int)$f['department_id']['integerValue'] : null,
                'campus_id'      => isset($f['campus_id']['integerValue']) ? (int)$f['campus_id']['integerValue'] : null,
                'is_moderator'   => (bool)($f['is_moderator']['booleanValue'] ?? false),
            ];
        } elseif ($role === 'faculty' && $faculty) {
            $f = $faculty['fields'] ?? [];
            $roleInfo = [
                'is_teaching'    => (bool)($f['is_teaching']['booleanValue'] ?? false),
                'department_id'  => isset($f['department_id']['integerValue']) ? (int)$f['department_id']['integerValue'] : null,
                'office_id'      => isset($f['office_id']['integerValue']) ? (int)$f['office_id']['integerValue'] : null,
                'campus_id'      => isset($f['campus_id']['integerValue']) ? (int)$f['campus_id']['integerValue'] : null,
            ];
        } elseif ($role === 'guest' && $guest) {
            $f = $guest['fields'] ?? [];
            $roleInfo = [
                'organization' => $f['organization']['stringValue'] ?? null,
            ];
        }

        $profile = [
            'uid'   => $uid,
            'name'  => $name,
            'email' => $u?->email,
            'roles' => $roles,
            'photo_path' => $u?->photo_path,
        ];

        return [$profile, $role, $roleInfo];
    }

    private function tryGet(FirestoreRest $fs, string $path): ?array
    {
        try { return $fs->get($path); }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()?->getStatusCode() === 404) return null;
            throw $e;
        }
    }

    private function photoUrl(array $profile): string
    {
        $p = Arr::get($profile, 'photo_path');
        if ($p && is_string($p) && $p !== '') return asset($p);
        return asset('images/ui/userdefault.jpg');
    }

    public function render()
    {
        return view('livewire.attendance.scan-station');
    }
}

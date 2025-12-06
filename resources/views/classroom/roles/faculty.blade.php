
@php
$toRow = function(string $time){
[$h,$m] = explode(':',$time);
return (int)$h - 7 + 2;
};

$blocks = [
'BSIT-4A' => [
'dates' => ['Mon'=>'Oct 6', 'Tue'=>'Oct 7', 'Wed'=>'Oct 8', 'Thu'=>'Oct 9', 'Fri'=>'Oct 10'],
'events' => [
    ['course'=>'Mobile App Development','day'=>2,'start'=>'07:00','end'=>'10:00','status'=>'present'],
    ['course'=>'Web Systems & Technologies','day'=>3,'start'=>'10:00','end'=>'12:00','status'=>'present'],
    ['course'=>'Web Systems & Technologies','day'=>5,'start'=>'14:00','end'=>'17:00','status'=>'present'],
    ['course'=>'Mobile App Development','day'=>6,'start'=>'15:00','end'=>'17:00','status'=>'upcoming'],
],
'students' => [
    ['name'=>'Ana Dizon'],['name'=>'Bryle Santos'],['name'=>'Carla Reyes'],
    ['name'=>'Danilo Cruz'],['name'=>'Ella Ramos'],['name'=>'Fidel Lim'],
    ['name'=>'Gina Tan'],['name'=>'Hans Dela Cruz'],['name'=>'Ivy Lopez'],
    ['name'=>'Jonas Uy']
],
'logs' => [
    ['date'=>'Sept 30, 2025','course'=>'Web Systems & Technologies','time'=>'10:00–12:00','present'=>36,'absent'=>2],
    ['date'=>'Oct 2, 2025','course'=>'Web Systems & Technologies','time'=>'14:00–17:00','present'=>37,'absent'=>1],
    ['date'=>'Sept 29, 2025','course'=>'Mobile App Development','time'=>'07:00–10:00','present'=>35,'absent'=>3],
],
],
'BSIT-4B' => [
'dates' => ['Mon'=>'Oct 6', 'Tue'=>'Oct 7', 'Wed'=>'Oct 8', 'Thu'=>'Oct 9', 'Fri'=>'Oct 10'],
'events' => [
    ['course'=>'Web Systems & Technologies','day'=>2,'start'=>'09:00','end'=>'11:00','status'=>'present'],
    ['course'=>'Mobile App Development','day'=>4,'start'=>'13:00','end'=>'15:00','status'=>'present'],
    ['course'=>'Web Systems & Technologies','day'=>5,'start'=>'08:00','end'=>'10:00','status'=>'present'],
    ['course'=>'Mobile App Development','day'=>6,'start'=>'10:00','end'=>'12:00','status'=>'upcoming'],
],
'students' => [
    ['name'=>'Kyle Manzano'],['name'=>'Lara Go'],['name'=>'Mike Perez'],
    ['name'=>'Nina Cruz'],['name'=>'Owen Chua'],['name'=>'Paolo Yao'],
    ['name'=>'Queenie Co'],['name'=>'Ralph Sy'],['name'=>'Sandy Ong'],
    ['name'=>'Tyler Lee']
],
'logs' => [
    ['date'=>'Sept 30, 2025','course'=>'Web Systems & Technologies','time'=>'09:00–11:00','present'=>39,'absent'=>2],
    ['date'=>'Oct 1, 2025','course'=>'Mobile App Development','time'=>'13:00–15:00','present'=>40,'absent'=>1],
],
],
'BSIT-4C' => [
'dates' => ['Mon'=>'Oct 6', 'Tue'=>'Oct 7', 'Wed'=>'Oct 8', 'Thu'=>'Oct 9', 'Fri'=>'Oct 10'],
'events' => [
    ['course'=>'Mobile App Development','day'=>2,'start'=>'07:00','end'=>'09:00','status'=>'present'],
    ['course'=>'Web Systems & Technologies','day'=>3,'start'=>'13:00','end'=>'15:00','status'=>'present'],
    ['course'=>'Mobile App Development','day'=>5,'start'=>'10:00','end'=>'12:00','status'=>'present'],
    ['course'=>'Web Systems & Technologies','day'=>6,'start'=>'15:00','end'=>'17:00','status'=>'upcoming'],
],
'students' => [
    ['name'=>'Ulyssa Tan'],['name'=>'Vince Reyes'],['name'=>'Wen Li'],
    ['name'=>'Xander Yu'],['name'=>'Yana Cruz'],['name'=>'Zed Santos'],
    ['name'=>'Aimee Pineda'],['name'=>'Borg Mendoza'],['name'=>'Cris Go'],
    ['name'=>'Drei Manabat']
],
'logs' => [
    ['date'=>'Sept 29, 2025','course'=>'Mobile App Development','time'=>'07:00–09:00','present'=>34,'absent'=>2],
    ['date'=>'Oct 1, 2025','course'=>'Web Systems & Technologies','time'=>'13:00–15:00','present'=>35,'absent'=>1],
],
],
];

$weekDays = ['Mon','Tue','Wed','Thu','Fri'];

/* Surname-first formatter with basic multi-word surname particles. */
$formatSurnameFirst = function (string $fullName) {
$particles = ['de','del','dela','de la','de los','san','santa','sto.','van','von','van der','di','da','dos','mac','mc','bin','binti'];
$parts = preg_split('/\s+/', trim($fullName));
if (!$parts || count($parts) === 1) {
return ['sort' => strtolower($fullName), 'display' => $fullName];
}
$last = array_pop($parts);
$prev = count($parts) ? strtolower(end($parts)) : '';
if (in_array($prev, $particles, true)) {
$surname = array_pop($parts) . ' ' . $last;
} else {
$surname = $last;
}
$given = implode(' ', $parts);
$display = trim($surname . ', ' . $given);
$sortKey = strtolower($surname) . '|' . strtolower($given);
return ['sort' => $sortKey, 'display' => $display];
};

/* Build surname-first, sorted students with IDs 22-UR-0601+ */
$buildStudents = function(array $raw) use ($formatSurnameFirst) {
$tmp = [];
foreach ($raw as $r) {
$fmt = $formatSurnameFirst($r['name']);
$tmp[] = ['name' => $fmt['display'], 'sort' => $fmt['sort']];
}
usort($tmp, fn($a,$b) => $a['sort'] <=> $b['sort']);
$out = [];
$n = 1;
foreach ($tmp as $t) {
$out[] = [
    'id'   => sprintf('22-UR-%04d', 600 + $n),
    'name' => $t['name'],
];
$n++;
}
return $out;
};

/* Ensure each log’s present+absent equals studentCount (keeps ratio). */
$normalizeLogs = function(array $logs, int $total) {
$out = [];
foreach ($logs as $l) {
$p = (int)($l['present'] ?? 0);
$a = (int)($l['absent'] ?? 0);
$sum = max(1, $p + $a);
$ratio = $total / $sum;
$newP = (int)round($p * $ratio);
$newP = max(0, min($total, $newP));
$newA = $total - $newP;
$l['present'] = $newP;
$l['absent']  = $newA;
$out[] = $l;
}
return $out;
};
@endphp

<main class="p-4 md:p-5">
<!-- 2-column layout; make children stretch the row -->
<div class="grid gap-5 items-stretch md:[grid-template-columns:1.65fr_1fr]">

{{-- LEFT: single calendar --}}
<article class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200 p-4
                h-[72vh] md:h-[78vh] flex flex-col min-h-0">
    <div class="rounded-lg bg-[#001f99] text-white px-3 py-2 mb-3
                flex items-center gap-2 flex-wrap">
    <h3 class="font-semibold mr-auto">My Class Schedule</h3>

    <div class="flex items-center cal-toolbar gap-2">
        <button id="calToday" class="btn-tab h-8 px-3 rounded-md border text-sm" data-view="day">Today</button>
        <button id="calThisWeek" class="btn-tab h-8 px-3 rounded-md border text-sm" data-view="week">Weekly</button>
    </div>
    </div>

    {{-- optional info banner --}}
    <div id="class-indicator"
        class="mt-2 hidden rounded-lg border border-blue-100 bg-blue-50/60 px-3 py-2 text-sm text-slate-800"></div>

    {{-- Calendar fills leftover height --}}
    <div id="tui-student-week" class="mt-3 w-full flex-1 min-h-0"></div>
</article>
<div id="event-popover" class="hidden fixed z-[2002] max-w-xs rounded-xl border border-slate-200 bg-white p-3 shadow-lg"></div>

{{-- RIGHT: details column (selector + panels) --}}
<aside class="h-[72vh] md:h-[78vh] bg-white rounded-xl shadow-sm ring-1 ring-slate-200
                flex flex-col min-h-0">
    <!-- Top bar: Block selector -->
    <div class="border-b border-slate-200 px-4 py-3 flex items-center gap-3">
    <div class="font-semibold text-base md:text-[1.05rem] mr-auto">Block Details</div>

    <label for="blockSelectMaster" class="text-xs md:text-sm text-slate-700">Block</label>
    <select id="blockSelectMaster"
            class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#006dff] focus:ring-4 focus:ring-[#006dff]/30">
        @foreach(array_keys($blocks) as $b)
        <option value="{{ $b }}">{{ $b }}</option>
        @endforeach
    </select>
    </div>

    <!-- Scrollable details area -->
    <div class="p-4 overflow-auto flex-1 min-h-0">
    @php
        // precompute per-block data once
        $computed = [];
        foreach ($blocks as $bn => $blk) {
            $students = $buildStudents($blk['students']);
            $computed[$bn] = [
            'students' => $students,
            'studentCount' => count($students),
            'logs' => $normalizeLogs($blk['logs'], count($students)),
            ];
        }
        $firstKey = array_key_first($blocks);
    @endphp

    @foreach($blocks as $blockName => $block)
        @php
        $studentCount = $computed[$blockName]['studentCount'];
        $students     = $computed[$blockName]['students'];
        $logs         = $computed[$blockName]['logs'];
        @endphp

        <!-- One panel per block; show first, hide the rest -->
        <section class="space-y-4 details-panel {{ $blockName === $firstKey ? '' : 'hidden' }}"
                data-block="{{ $blockName }}">

        <div class="inline-flex items-center gap-3 rounded-2xl border border-[#0033cc]/20 bg-[#006dff]/5 px-4 py-3">
            <div class="text-[#0033cc] text-xs uppercase tracking-wide">Students</div>
            <div class="text-2xl md:text-3xl font-bold text-[#001f99] tabular-nums">{{ $studentCount }}</div>
        </div>

        <!-- Class List -->
        <div class="rounded-xl border border-slate-200 overflow-hidden">
            <div class="bg-[#001f99] text-white px-4 py-2.5 font-medium">Class List — {{ $blockName }}</div>
            <div class="p-3 max-h-[260px] overflow-auto">
            <table class="w-full border-separate [border-spacing:0_6px] text-[0.95rem]">
                <thead>
                <tr class="text-left text-xs text-slate-600">
                    <th class="px-2 py-1.5">Student ID</th>
                    <th class="px-2 py-1.5">Name</th>
                </tr>
                </thead>
                <tbody>
                @foreach($students as $s)
                    <tr class="bg-slate-50 odd:bg-slate-100">
                    <td class="px-2 py-2 border border-slate-200 rounded-l-lg font-medium">{{ $s['id'] }}</td>
                    <td class="px-2 py-2 border border-slate-200 rounded-r-lg">{{ $s['name'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>

        <!-- Attendance Logs -->
        <div class="rounded-xl border border-slate-200 overflow-hidden">
            <div class="bg-[#001f99] text-white px-4 py-2.5 font-medium">Attendance Logs</div>
            <div class="p-3 max-h-[260px] overflow-auto">
            <table class="w-full border-separate [border-spacing:0_6px] text-[0.95rem]">
                <thead>
                <tr class="text-left text-xs text-slate-600">
                    <th class="px-2 py-1.5">Date</th>
                    <th class="px-2 py-1.5">Class</th>
                    <th class="px-2 py-1.5">Time</th>
                    <th class="px-2 py-1.5">Present</th>
                    <th class="px-2 py-1.5">Absent</th>
                </tr>
                </thead>
                <tbody>
                @foreach($logs as $l)
                    <tr class="bg-slate-50 odd:bg-slate-100">
                    <td class="px-2 py-2 border border-slate-200 rounded-l-lg">{{ $l['date'] }}</td>
                    <td class="px-2 py-2 border border-slate-200">{{ $l['course'] }}</td>
                    <td class="px-2 py-2 border border-slate-200">{{ $l['time'] }}</td>
                    <td class="px-2 py-2 border border-slate-200">
                        <span class="inline-block rounded-full border border-[#006dff]/30 bg-[#006dff]/10 text-[#0033cc] px-2 py-0.5 text-xs font-semibold">{{ $l['present'] }}</span>
                    </td>
                    <td class="px-2 py-2 border border-slate-200 rounded-r-lg">
                        <span class="inline-block rounded-full border border-slate-300 bg-slate-100 text-slate-600 px-2 py-0.5 text-xs font-semibold">{{ $l['absent'] }}</span>
                    </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>

        </section>
    @endforeach
    </div>
</aside>

</div>
</main>


@push('scripts')
<script>
const selectEls = Array.from(document.querySelectorAll('#blockSelect'));
const panels = Array.from(document.querySelectorAll('.block-panel'));
function showBlock(name){
    panels.forEach(p => {
    if(p.dataset.block === name){ p.classList.remove('hidden'); }
    else { p.classList.add('hidden'); }
    });
    selectEls.forEach(s => { if(s.value !== name) s.value = name; });
}
if(selectEls.length){
    showBlock(selectEls[0].value);
    selectEls.forEach(s => s.addEventListener('change', e => showBlock(e.target.value)));
}
</script>
@endpush

@push('styles')
<style>
/* ===== Scope everything to this instance ===== */
#tui-student-week{ -ms-overflow-style:none; scrollbar-width:none; }
#tui-student-week *::-webkit-scrollbar{ display:none; }

/* ============== WEEK VIEW HEADER ============== */
/* header strip container */
#tui-student-week .toastui-calendar-week-daynames{
background:#001f99 !important;
border-bottom:1px solid rgba(255,255,255,.12) !important;
height:52px;           /* a touch taller prevents collisions */
}
/* each header cell → stack date/name vertically */
#tui-student-week .toastui-calendar-week-dayname{
display:flex; flex-direction:column; align-items:center; justify-content:center;
padding-top:6px; gap:2px;
}
/* big date number */
#tui-student-week .toastui-calendar-week-dayname-date{
display:block; opacity:1 !important;
color:#fff !important; font-weight:600; font-size:18px; line-height:1.1;
white-space:nowrap;
}
/* small weekday name */
#tui-student-week .toastui-calendar-week-dayname-name{
display:block; opacity:1 !important;
color:rgba(255,255,255,.85) !important; font-size:12px; line-height:1.1;
white-space:nowrap;
}

/* =============== DAY VIEW HEADER =============== */
/* single header cell container */
#tui-student-week .toastui-calendar-dayname{
background:#001f99 !important;
border-bottom:1px solid rgba(255,255,255,.12) !important;
height:52px; display:flex; align-items:center; justify-content:center;
flex-direction:column; gap:2px;
}
#tui-student-week .toastui-calendar-dayname-date{
display:block; opacity:1 !important;
color:#fff !important; font-weight:600; font-size:18px; line-height:1.1;
}
#tui-student-week .toastui-calendar-dayname-name{
display:block; opacity:1 !important;
color:rgba(255,255,255,.85) !important; font-size:12px; line-height:1.1;
}

/* ======= Responsive compression (both views) ======= */
@media (max-width:1024px){
#tui-student-week .toastui-calendar-week-daynames,
#tui-student-week .toastui-calendar-dayname{ height:48px; }
#tui-student-week .toastui-calendar-week-dayname-date,
#tui-student-week .toastui-calendar-dayname-date{ font-size:16px; }
}
@media (max-width:480px){
#tui-student-week .toastui-calendar-week-daynames,
#tui-student-week .toastui-calendar-dayname{ height:44px; }
#tui-student-week .toastui-calendar-week-dayname-date,
#tui-student-week .toastui-calendar-dayname-date{ font-size:15px; }
#tui-student-week .toastui-calendar-week-dayname-name,
#tui-student-week .toastui-calendar-dayname-name{ font-size:11px; }
}
@media (max-width:360px){
/* if extremely narrow, hide weekday names—date stays visible */
#tui-student-week .toastui-calendar-week-dayname-name,
#tui-student-week .toastui-calendar-dayname-name{ display:none; }
}

/* ===== Hour labels + events ===== */
#tui-student-week .toastui-calendar-timegrid-time,
#tui-student-week .toastui-calendar-timegrid-hour{ color:#475569; }
#tui-student-week .toastui-calendar-event{
border-radius:10px; font-size:.85rem; cursor:pointer;
}

/* ===== Toolbar tabs ===== */
.btn-tab{ background:#fff; border-color:#e2e8f0; color:#0f172a; }
.btn-tab:hover{ background:#f8fafc; }
.btn-tab.is-active{
background:#eef2ff; border-color:#c7d2fe; color:#1e3a8a;
box-shadow:inset 0 0 0 1px rgba(30,58,138,.08);
}

/* ===== Popover caret ===== */
#event-popover::before{
content:""; position:absolute; top:-6px; left:16px;
border-width:0 6px 6px 6px; border-style:solid;
border-color:transparent transparent #e5e7eb transparent;
}
#event-popover::after{
content:""; position:absolute; top:-5px; left:16px;
border-width:0 6px 6px 6px; border-style:solid;
border-color:transparent transparent #fff transparent;
}
</style>
@endpush
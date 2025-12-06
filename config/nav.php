<?php
// Navigation Bar Items
return [
    // ── General (no specific permission) ───────────────────────────────────────
    ['label' => 'Dashboard', 'icon' => 'grid-1x2', 'icon_fill' => 'grid-1x2-fill', 'route' => 'dashboard', 'can' => null, 'mgmt' => false],

    // ── Events (everyone authenticated incl. guests) ──────────────────────────
    // View events (for all roles incl. guest)
    ['label' => 'Events', 'icon' => 'calendar-event', 'icon_fill' => 'calendar-event-fill', 'route' => 'events.index', 'can' => 'view events', 'mgmt' => false],

    // ── Attendance (student-facing) ───────────────────────────────────────────
    // Students & admins can view their attendance
    ['label' => 'My Attendance', 'icon' => 'journal-check', 'icon_fill' => 'journal-check', 'route' => 'classroom.index', 'can' => 'view attendance', 'mgmt' => false],

    // ── Management section (faculty/admin; also student moderators) ───────────
    // Manage events (all except guests; you gate with 'manage events')
    ['label' => 'Manage Events', 'icon' => 'calendar3', 'icon_fill' => 'calendar3', 'route' => 'events.manage', 'can' => 'manage events', 'mgmt' => true],

    // Open QR Scanner (faculty/admin)
    ['label' => 'QR Scanner', 'icon' => 'qr-code-scan', 'icon_fill' => 'qr-code', 'route' => 'events.scanner', 'can' => 'open scanner', 'mgmt' => true],

    // Manage attendance (faculty/admin)
    ['label' => 'Manage Attendance', 'icon' => 'person-check', 'icon_fill' => 'person-check', 'route' => 'classroom.manage', 'can' => 'manage attendance', 'mgmt' => true],

    // Admin section that you already had
    ['label' => 'Role Upgrade Requests',
    'icon' => 'shield-check',      
    'icon_fill' => 'shield-check', 
    'route' => 'admin.role-applications.index',
    'can' => 'review role applications',
    'mgmt' => true],

    [
        'label' => 'Student Management',
        'icon' => 'mortarboard',
        'icon_fill' => 'mortarboard-fill',
        'route' => 'admin.students.manage',
        'can' => 'manage users',   // adjust to whatever permission name you use
        'mgmt' => true,
    ],

    ['label' => 'User Management', 'icon' => 'people', 'icon_fill' => 'people-fill', 'route' => 'admin.users.index', 'can' => 'manage users', 'mgmt' => true],
    ['label' => 'System Settings', 'icon' => 'gear', 'icon_fill' => 'gear-fill', 'route' => 'admin.system-management', 'can' => 'manage system', 'mgmt' => true],
];

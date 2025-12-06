<?php

// config/nav.php

return [
    // ── General (no specific permission) ───────────────────────────────────────
    [
        'label'     => 'Dashboard',
        'icon'      => 'grid-1x2',
        'icon_fill' => 'grid-1x2-fill',
        'route'     => 'dashboard',
        'can'       => null,
        'mgmt'      => false,
    ],

    // ── Events (everyone authenticated incl. guests) ──────────────────────────
    [
        'label'     => 'Events',
        'icon'      => 'calendar-event',
        'icon_fill' => 'calendar-event-fill',
        'route'     => 'events.index',
        'can'       => null,
        'mgmt'      => false,
    ],

    // ── Attendance (student-facing) ───────────────────────────────────────────
    [
        'label'       => 'Classes Today',
        'icon'        => 'journal-check',
        'icon_fill'   => 'journal-check',
        'route'       => 'classroom.index', // entry route
        'can_any'     => ['view attendance', 'manage attendance'],
        'mgmt'        => false,

        // ANY of these prefixes will light it up
        'active_prefix' => [
            'classroom.index',                 // /classroom
            'classroom.student.*',             // student: attendance-today, overall-attendance, sections.records
            'classroom.instructor.sections-today',   // instructor: list of today’s sections
            'classroom.instructor.attendance-today', // instructor: per-section today view
        ],
    ],

    [
        'label'       => 'Notifications',
        'icon'        => 'bell',
        'icon_fill'   => 'bell-fill',
        'route'       => 'notifications.index',
        'can'         => null,
        'mgmt'        => false,
    ],

    // ── Management section (faculty/admin; also student moderators) ───────────
    [
        'label'     => 'Manage Events',
        'icon'      => 'calendar3',
        'icon_fill' => 'calendar3',
        'route'     => 'events.manage.index',
        // Anyone who can manage events OR is eligible as co-organizer
        // will see this entry:
        'can_any'   => ['manage events', 'co-organize events'],
        'mgmt'      => true,
        'active_prefix' => [
            'events.manage.*',
        ],
    ],

    [
        'label'     => 'QR Scanner',
        'icon'      => 'qr-code-scan',
        'icon_fill' => 'qr-code',
        'route'     => 'events.scanner',
        // Any of these perms can see the scanner:
        // - manage events  = full event managers
        // - open scanner   = existing scanner perm (keep for backwards compatibility)
        // - staff events   = event staff (often students) who can scan attendance
        'can_any'   => ['manage events', 'open scanner', 'staff events'],
        'mgmt'      => true,
    ],

    [
        'label'         => 'Manage Classes',
        'icon'          => 'person-check',
        'icon_fill'     => 'person-check',
        'route'         => 'classroom.manage',
        'can'           => 'manage attendance',
        'mgmt'          => true,
        'teaching_only' => true,

        // ANY route that starts with these will light it up
        'active_prefix' => [
            'classroom.manage',          // /classroom/manage

            // Admin classroom management
            'classroom.admin.*',         // rooms, periods, instructors, courses, sections, etc.

            // Instructor course/section management (not daily attendance)
            'classroom.instructor.courses.*',
            'classroom.instructor.sections.show',
            'classroom.instructor.sections.schedules.*',
            'classroom.instructor.sections.students.*',
        ],
    ],

    [
        'label'     => 'Role Upgrade Requests',
        'icon'      => 'shield-check',
        'icon_fill' => 'shield-check',
        'route'     => 'admin.role-applications.index',
        'can'       => 'review role applications',
        'mgmt'      => true,
    ],

    [
        'label'     => 'Student Management',
        'icon'      => 'mortarboard',
        'icon_fill' => 'mortarboard-fill',
        'route'     => 'admin.students.manage',
        'can'       => 'manage users',
        'mgmt'      => true,
    ],

    [
        'label'     => 'User Management',
        'icon'      => 'people',
        'icon_fill' => 'people-fill',
        'route'     => 'admin.users.index',
        'can'       => 'manage users',
        'mgmt'      => true,
    ],

    [
        'label'     => 'System Settings',
        'icon'      => 'gear',
        'icon_fill' => 'gear-fill',
        'route'     => 'admin.system-management',
        'can'       => 'manage system',
        'mgmt'      => true,
    ],
];

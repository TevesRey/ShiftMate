<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>ShiftMate System</title>
    <meta name="description" content="SHIFTMATE helps administrators assign employee shifts and lets employees view schedules and submit schedule-related requests.">
    <meta name="theme-color" content="#10172d">
    <meta name="color-scheme" content="light dark">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..700&family=Geist:wght@400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset('shiftmate-ui-assets/modern-2026.css') . '?v=20261121-1'); ?>">
</head>

<body>


    <div id="splashScreen" class="splash-screen" aria-label="Loading ShiftMate">
        <img src="/Logo.png" alt="ShiftMate logo" class="splash-logo" />

        <div class="splash-screen-progress" aria-hidden="true"><span></span></div>
    </div>

    <div id="landingView" class="landing-page">
        

        <main class="landing-hero">
            <div class="landing-copy">
                

                
                <div class="portal-choice-group" aria-label="Choose your ShiftMate portal">
                    <button type="button" class="portal-entry admin-entry" data-entry-portal="admin">
                        <span class="portal-entry-icon" aria-hidden="true">👨🏻‍💻</span>
                        <span class="portal-entry-copy"><strong>Administrator</strong><small>Login as Administrator</small></span>
                        <span class="portal-entry-arrow" aria-hidden="true">↗</span>
                    </button>
                    <button type="button" class="portal-entry employee-entry" data-entry-portal="employee">
                        <span class="portal-entry-icon" aria-hidden="true">👨🏻‍💼</span>
                        <span class="portal-entry-copy"><strong>Employee</strong><small>Login or Register an account</small></span>
                        <span class="portal-entry-arrow" aria-hidden="true">↗</span>
                    </button>
                </div>

            
            </div>
            <div class="landing-visual" aria-label="Decorative weekly schedule preview">
                <div class="orbit orbit-one"></div>
                <div class="orbit orbit-two"></div>
                <div class="float-card float-card-back">

                </div>
                <div class="schedule-preview">
                    <div class="preview-top">
                        <div><small>Our team</small><strong>Weekly schedule</strong></div><span class="preview-dots">•••</span>
                    </div>
                    <div class="preview-week"><span>MON</span><span>TUE</span><span>WED</span><span>THU</span><span>FRI</span></div>
                    <div class="preview-row"><span class="preview-avatar avatar-purple"> <img src="<?php echo e(asset('ayet.png')); ?>"
                                alt=""
                                class="preview-avatar avatar-green"></span>
                        <div class="preview-person"><b>Ayet Arellano</b><small>Team Leader/Frontend Developer</small></div><span class="shift-pill shift-violet">8 AM – 4 PM</span>
                    </div>
                    <div class="preview-row"><span class="preview-avatar avatar-blue"> <img src="<?php echo e(asset('/Anika.png')); ?>"
                                alt="Anika Banggat"
                                class="preview-avatar avatar-green"></span>
                        <div class="preview-person"><b>Anika Banggat</b><small>Documentator</small></div><span class="shift-pill shift-blue">9 AM – 5 PM</span>
                    </div>
                    <div class="preview-row"><span class="preview-avatar avatar-green"> <img src="<?php echo e(asset('/john.png')); ?>"
                                alt="John Rey Televes"
                                class="preview-avatar avatar-green"></span>
                        <div class="preview-person"><b>John Rey Televes</b><small>Backend Developer</small></div><span class="shift-pill shift-green">10 AM – 6 PM</span>
                    </div>
                    <div class="preview-bottom"><span class="preview-live"></span> Team schedule preview <span class="preview-mini">3 members</span></div>
                </div>
                <div class="float-stat"><span class="float-stat-icon">🔄</span>
                    <div><small>Team flow</small><strong>Schedules</strong></div><span class="float-stat-check">✓</span>
                </div>
            </div>
        </main>


        <div id="authView" class="auth-shell hidden">


            <section class="auth-panel">
                <div class="auth-top">
                    <div class="brand-inline"><img src="/Logo.png" alt=""><span>ShiftMate</span></div>
                    <div class="auth-form">
                        <div class="auth-intro">
                            <button type="button" class="back-to-portal" id="backToPortal">← Back</button>
                            <h1 id="authTitle">Employee Log in</h1>
 
                        </div>



                        <form id="loginForm">
                            <label>Email<input
                                    name="email"
                                    type="email"
                                    placeholder="Enter your Email"
                                    required
                                    autocomplete="email" /></label><label>Password<div class="password-field-wrap"><input
                                        name="password"
                                        type="password"
                                        placeholder="Enter your password"
                                        required
                                        autocomplete="current-password" /><button type="button" class="toggle-password" aria-label="Show password" aria-pressed="false">Show</button></div></label><button class="btn primary wide" type="submit">
                                Log In <span>→</span>
                            </button>
                            <p class="form-note"><button class="text-button" type="button" id="showForgotPassword">Forgot password?</button></p>
                            <p class="form-note employee-signup-note" id="employeeSignupNote">
                                Don’t have an account?
                                <button class="text-button" type="button" id="showRegister">Register account</button>
                            </p>
                        </form>
                        <form id="registerForm" class="hidden">
                            <label>Name<input name="name" required placeholder="" autocomplete="name" /></label>
                            <label>Email address<input name="email" type="email" required placeholder="" autocomplete="email" /></label>
                            <label>Password<div class="password-field-wrap"><input name="password" type="password" required minlength="8" placeholder="At least 8 characters" autocomplete="new-password" /><button type="button" class="toggle-password" aria-label="Show password" aria-pressed="false">Show</button></div></label>
                            <label>Confirm password<div class="password-field-wrap"><input name="password_confirmation" type="password" required minlength="8" placeholder="Re-enter your password" autocomplete="new-password" /><button type="button" class="toggle-password" aria-label="Show password" aria-pressed="false">Show</button></div></label>
                            <button class="btn primary wide" type="submit">Register account <span>→</span></button>
                            <p class="form-note">Already have an account? <button class="text-button" type="button" id="showLogin">Log in</button></p>
                        </form>
                        <form id="forgotPasswordForm" class="hidden">
                            <p class="form-note"></p>
                            <label>Email address<input name="email" type="email" required autocomplete="email" placeholder="Enter your login email"></label>
                            <button class="btn primary wide" type="submit">Continue to reset password →</button>
                            <p class="form-note"><button class="text-button" type="button" id="backToLogin">Back to Log in</button></p>
                        </form>
                        <form id="resetPasswordForm" class="hidden">
                            <label>New password<div class="password-field-wrap"><input name="password" type="password" required minlength="8" autocomplete="new-password"><button type="button" class="toggle-password" aria-label="Show password" aria-pressed="false">Show</button></div></label>
                            <label>Confirm new password<div class="password-field-wrap"><input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"><button type="button" class="toggle-password" aria-label="Show password" aria-pressed="false">Show</button></div></label>
                            <button class="btn primary wide" type="submit">Reset password →</button>
                            <p class="form-note"><button class="text-button" type="button" id="resetBackToLogin">Back to Log in</button></p>
                        </form>
                        <div class="auth-error" id="authError" role="alert"></div>
                        <button class="text-button hidden" type="button" id="resendVerification">Resend verification email</button>
                    </div>
            </section>
        </div>
        <div id="appView" class="app-shell hidden">
            <aside class="sidebar" id="sidebar">

                <div class="sidebar-brand"><img src="/Logo.png" alt=""><span>ShiftMate</span></div>
                <nav id="mainNav">
                    <button
                        class="nav-item active admin-only"
                        data-page="dashboard">
                        <span class="nav-icon">▦</span>Overview
                    </button>
                    <button
                        class="nav-item employee-only hidden"
                        data-page="employee-dashboard">
                        <span class="nav-icon">🌐</span>My overview</button><button class="nav-item" data-page="schedule">
                        <span class="nav-icon nav-icon-schedule">🗓️</span>Schedules
                    </button>
                    <button class="nav-item admin-only" data-page="employees">
                        <span class="nav-icon">👨🏻‍💼</span>Employees
                    </button>
                    <button class="nav-item admin-only" data-page="registrations">
                        <span class="nav-icon">📝</span>Employee Registration Requests
                        <span class="nav-count" id="registrationNavCount">0</span>
                    </button>
                    <div class="nav-label sub-label">Schedule requests</div>
                    <button class="nav-item admin-only" data-page="requests">
                        <span class="nav-icon">📰</span>Rest-day requests
                        <span class="nav-count" id="requestNavCount">0</span>
                    </button>
                    <button class="nav-item admin-only" data-page="absences">
                        <span class="nav-icon">🚫</span>Absence requests
                        <span class="nav-count" id="absenceNavCount">0</span>
                    </button>
                    <button class="nav-item employee-only hidden" data-open-form="absenceRequest">
                        <span class="nav-icon">🚫</span>Absence request
                    </button>
                    <button class="nav-item employee-only hidden" data-open-form="restRequest">
                        <span class="nav-icon">📰</span>Rest-day request
                    </button>
                </nav>
            </aside>
            <main class="main-area">
                <header class="topbar">
                    <button
                        class="icon-btn mobile-menu"
                        id="menuBtn"
                        aria-label="Open navigation">
                        ☰
                    </button>
                    <div class="breadcrumbs">
                        <span>Works</span><span class="crumb-slash">/</span><b id="crumbTitle">Overview</b>
                    </div>
                    <div class="top-actions">
                        <div class="notification-wrap" id="notificationWrap">
                            <button type="button" class="notification-trigger" id="notificationBtn" aria-label="Notifications" aria-expanded="false">
                                <span aria-hidden="true">🕭</span><i id="notificationBadge" class="notification-badge hidden">0</i>
                            </button>
                            <section class="notification-panel hidden" id="notificationPanel" aria-label="Notifications">
                                <div class="notification-panel-head">
                                    <div><b>Inbox</b><small>Requests and updates</small></div><button type="button" id="markNotificationsRead" class="notification-mark">Mark all read</button>
                                </div>
                                <div id="notificationList" class="notification-list">
                                    <div class="notification-empty">You’re all caught up.</div>
                                </div>
                            </section>
                        </div>
                        <div class="user-menu-wrap">
                            <button class="user-chip" id="userMenuBtn" type="button" aria-expanded="false" aria-haspopup="true">
                                <div class="avatar" id="userAvatar">M</div>
                                <div>
                                    <b id="userName">Manager</b>
                                    <small id="userRole">Administrator</small>
                                </div>
                                <span class="chevron">↴</span>
                            </button>
                            <div class="user-dropdown hidden" id="userDropdown">
                                <div class="dropdown-user-card">
                                    <div class="dropdown-avatar" id="dropdownAvatar">S</div>
                                    <div><b id="dropdownName">Team members</b><small id="dropdownEmail">Log in to ShiftMate</small></div>
                                </div>
                                <button type="button" id="openProfileBtn"><span class="dropdown-action-icon">👤</span><span><b>My profile</b><small>Account information</small></span><span class="dropdown-arrow"></span></button>
                                <div class="dropdown-divider"></div>
                                <button type="button" class="dropdown-logout" id="topLogoutBtn"><span class="dropdown-action-icon">↩</span><span><b>Log out</b><small>End your current session</small></span></button>
                            </div>
                        </div>
                    </div>
                </header>
                <div class="page-wrap">
                    <div class="notice hidden" id="globalNotice"></div>
                    <section
                        class="page active-page admin-only sm-dashboard-page min-h-full"
                        id="page-dashboard">
                        <div class="page-heading flex items-center justify-between gap-4">
                            <div>
                                <div class="eyebrow">Shift overview</div>
                                <h1 id="greeting">Good day👋</h1>
                                <p>
                                    Here’s your team schedule and daily activity.
                                </p>
                            </div>
                        </div>
                        <div class="stats-grid grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                            <article class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm">
                                <div class="stat-top">
                                    <span>Total employees</span><span class="stat-icon purple">👨🏻‍💼</span>
                                </div>
                                <div class="stat-number" id="totalEmployees">
                                    —
                                </div>
                                <div class="stat-foot">
                                    <span class="tiny-dot purple-dot"></span>Your Total Employees
                                </div>
                            </article>
                            <article class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm">
                                <div class="stat-top">
                                    <span>Scheduled shifts</span><span class="stat-icon blue schedule-stat-icon">🗓️</span>
                                </div>
                                <div class="stat-number" id="totalSchedules">
                                    —
                                </div>
                                <div class="stat-foot">
                                    <span class="tiny-dot blue-dot"></span>Recorded schedule
                                </div>
                            </article>
                            <article class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm">
                                <div class="stat-top">
                                    <span>Rest-day requests</span><span class="stat-icon amber">📰</span>
                                </div>
                                <div class="stat-number" id="pendingRequests">
                                    —
                                </div>
                                <div class="stat-foot">
                                    <span class="tiny-dot amber-dot"></span>Awaiting review
                                </div>
                            </article>
                            <article class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm">
                                <div class="stat-top">
                                    <span>Absence requests</span><span class="stat-icon rose">🚫</span>
                                </div>
                                <div class="stat-number" id="pendingAbsences">
                                    —
                                </div>
                                <div class="stat-foot">
                                    <span class="tiny-dot rose-dot"></span>Awaiting review
                                </div>
                            </article>
                        </div>
                        <div class="dashboard-grid">
                            <article class="panel schedule-panel bg-white rounded-xl border border-slate-100 shadow-sm">
                                <div class="panel-heading">
                                    <div>
                                        <h2>Upcoming schedule</h2>
                                        <p>
                                            Latest entries from your schedule
                                            records
                                        </p>
                                    </div>
                                    <button
                                        class="text-button"
                                        data-go-page="schedule">
                                        View schedules →
                                    </button>
                                </div>
                                <div class="table-scroll">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Shift</th>
                                                <th>Work date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="dashboardScheduleRows">
                                            <tr>
                                                <td
                                                    colspan="4"
                                                    class="empty-cell">
                                                    Please try again later
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </article>
                            <aside class="panel dashboard-attention-panel bg-white rounded-xl border border-slate-100 shadow-sm">
                                <div class="panel-heading">
                                    <div><h2>Needs attention</h2><p>Items waiting for administrator action</p></div>
                                </div>
                                <button type="button" class="dashboard-quick-link" data-go-page="registrations">
                                    <span class="dashboard-quick-icon">＋</span>
                                    <span><b>Employee registrations</b><small>Review new account requests</small></span>
                                    <strong id="dashboardPendingRegistrations">0</strong>
                                </button>
                                <button type="button" class="dashboard-quick-link" data-go-page="requests">
                                    <span class="dashboard-quick-icon">↔</span>
                                    <span><b>Rest-day requests</b><small>Review requested changes</small></span>
                                    <strong id="dashboardPendingRest">0</strong>
                                </button>
                                <button type="button" class="dashboard-quick-link" data-go-page="absences">
                                    <span class="dashboard-quick-icon">＋</span>
                                    <span><b>Absence requests</b><small>Review employee absences</small></span>
                                    <strong id="dashboardPendingAbsence">0</strong>
                                </button>
                            </aside>
                        </div>
                    </section>
                    <section class="page" id="page-my-requests">
                        <div class="page-heading flex items-center justify-between gap-4">
                            <div>
                                <div class="eyebrow">Employee self-service</div>
                                <h1>Request history</h1>
                                <p>Review the status and dates of your submitted absence and rest-day requests.</p>
                            </div>
                        </div>
                        <div class="stats-grid request-stats">
                            <article class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm">
                                <div class="stat-top">
                                    <span>Rest-day requests</span><span class="stat-icon purple">📰</span>
                                </div>
                                <div class="stat-number" id="myDashboardRestCount">
                                    0
                                </div>
                                <div class="stat-foot">
                                    Your submitted requests
                                </div>
                            </article>
                            <article class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm">
                                <div class="stat-top">
                                    <span>Absence requests</span><span class="stat-icon rose">🚫</span>
                                </div>
                                <div class="stat-number" id="myDashboardAbsenceCount">
                                    0
                                </div>
                                <div class="stat-foot">
                                    Your submitted requests
                                </div>
                            </article>
                        </div>
                        <div class="panel">
                            <div class="panel-heading">
                                <div>
                                    <h2>Request history</h2>
                                    <p>
                                        Track the latest status of your
                                        submissions.
                                    </p>
                                </div>
                                <button
                                    class="btn secondary"
                                    id="reloadMyRequests">
                                    ⟳ Refresh
                                </button>
                            </div>
                            <div class="table-scroll">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Date</th>
                                            <th>Details</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="myRequestRows">
                                        <tr>
                                            <td colspan="4" class="empty-cell">
                                                No requests submitted yet.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                    <section class="page admin-only" id="page-employees">
                        <div class="page-heading">
                            <div>
                                <div class="eyebrow">People directory</div>
                                <h1>Employees</h1>
                                <p>
                                    Manage employee profiles and their details.
                                </p>
                            </div>
                        </div>
                        <div class="panel">
                            <div class="toolbar">
                                <div class="search-wrap">
                                    <span>⌕</span><input
                                        id="employeeSearch"
                                        placeholder="Search Employee Name ,ID , or position" />
                                </div>
                                <button
                                    class="btn secondary"
                                    id="reloadEmployees">
                                    ⟳ Refresh
                                </button>
                            </div>
                            <div class="table-scroll">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Employee no.</th>
                                            <th>Role</th>
                                            <th>Position</th>
                                            <th>Rest day</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="employeeRows">
                                        <tr>
                                            <td colspan="5" class="empty-cell">
                                                No employee loaded.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="panel-footer" id="employeeFooter">
                                Showing 0 employees
                            </div>
                        </div>
                    </section>
                    <section
                        class="page employee-only hidden sm-dashboard-page min-h-full"
                        id="page-employee-dashboard">
                        <div class="page-heading">
                            <div>
                                <div class="eyebrow">My overview</div>
                                <h1 id="employeeGreeting">Welcome back 👋</h1>
                                <p>
                                    Your schedule and requests, all in one
                                    place.
                                </p>
                            </div>
                            <button class="btn primary" data-go-page="schedule">
                                View my schedule <span>→</span>
                            </button>
                        </div>
                        <div class="stats-grid grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                            <article class="stat-card bg-white rounded-xl border border-slate-100 shadow-sm">
                                <div class="stat-top">
                                    <span>My scheduled shifts</span><span class="stat-icon purple">◷</span>
                                </div>
                                <div class="stat-number" id="myShiftCount">
                                    0
                                </div>
                                <div class="stat-foot">
                                    Your recorded schedule entries
                                </div>
                            </article>
                            <article class="stat-card">
                                <div class="stat-top">
                                    <span>Rest-day requests</span><span class="stat-icon blue">↔</span>
                                </div>
                                <div class="stat-number" id="myRestCount">
                                    0
                                </div>
                                <div class="stat-foot">
                                    Requests submitted by you
                                </div>
                            </article>
                            <article class="stat-card">
                                <div class="stat-top">
                                    <span>Absence requests</span><span class="stat-icon amber">＋</span>
                                </div>
                                <div class="stat-number" id="myAbsenceCount">
                                    0
                                </div>
                                <div class="stat-foot">
                                    Requests submitted by you
                                </div>
                            </article>
                        </div>
                        <div class="employee-dashboard-lower">
                            <article class="panel employee-upcoming-panel bg-white rounded-xl border border-slate-100 shadow-sm">
                                <div class="panel-heading">
                                    <div>
                                        <h2>Upcoming shifts</h2>
                                        <p>Your next assigned workdays</p>
                                    </div>
                                    <button class="text-button" data-go-page="schedule">Full schedule →</button>
                                </div>
                                <div class="table-scroll">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Shift</th>
                                            </tr>
                                        </thead>
                                        <tbody id="employeeUpcomingRows">
                                            <tr>
                                                <td colspan="2" class="empty-cell">Your schedule will appear here.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </article>
                            <article class="panel employee-status-panel bg-white rounded-xl border border-slate-100 shadow-sm">
                                <div class="panel-heading">
                                    <div>
                                        <h2>Request history</h2>
                                        <p>Track requests you have submitted</p>
                                    </div>
                                </div>
                                <div class="request-snapshot">
                                    <div class="snapshot-row"><span class="snapshot-icon rest">↔</span>
                                        <div><b>Rest-day changes</b><small>Requests sent for review</small></div><strong id="myRestPending">0 pending</strong>
                                    </div>
                                    <div class="snapshot-row"><span class="snapshot-icon absence">＋</span>
                                        <div><b>Emergency absences</b><small>Unexpected absence requests</small></div><strong id="myAbsencePending">0 pending</strong>
                                    </div>
                                </div>
                                <button class="btn secondary wide snapshot-cta" data-go-page="my-requests">View request history <span>→</span></button>
                            </article>
                        </div>
                    </section>
                    <section class="page admin-only" id="page-registrations">
                        <div class="page-heading">
                            <div>
                                <div class="eyebrow">Account management</div>
                                <h1>Employee Registration Requests</h1>
                                <p>Review new employee accounts before allowing access to ShiftMate.</p>
                            </div>
                            <button class="btn secondary" id="reloadRegistrations">Refresh</button>
                        </div>
                        <div class="panel overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <div class="panel-heading">
                                <div>
                                    <h2>Pending registrations</h2>
                                    <p>Check the employee name, email address, and registration date.</p>
                                </div>
                            </div>
                            <div class="table-scroll">
                                <table class="w-full text-left">
                                    <thead><tr><th>Employee name</th><th>Email address</th><th>Registered on</th><th>Status</th><th>Action</th></tr></thead>
                                    <tbody id="registrationRows"><tr><td colspan="5" class="empty-cell">Loading requests…</td></tr></tbody>
                                </table>
                            </div>
                            <div class="panel-footer" id="registrationFooter">No pending registrations</div>
                        </div>
                    </section>
                    <section class="page" id="page-schedule">
                        <div class="page-heading schedule-page-heading">
                            <div>
                                <div class="eyebrow">Plan & organize</div>
                                <h1>Schedules</h1>
                                <p>Plan shifts by employee and keep every workday clear.</p>
                            </div>
                        </div>
                        <div class="schedule-toolbar panel">
                            <div class="schedule-date-controls">
                                <button class="schedule-nav-btn" id="schedulePrevWeek" aria-label="Previous month">‹</button>
                                <button class="schedule-nav-btn" id="scheduleNextWeek" aria-label="Next month">›</button>
                                <div class="schedule-date-range"><span id="scheduleDateRange">This month</span><input type="date" id="scheduleAnchorDate" aria-label="Choose calendar month"></div>
                                <button class="btn secondary" id="scheduleToday">Today</button>
                            </div>
                            <div class="schedule-filters"><span class="schedule-filter-caption">ALL EMPLOYEES</span><button class="btn secondary" id="reloadSchedules">⟳ Refresh</button></div>
                        </div>
                        <div class="schedule-legend"><span><i class="legend-dot shift-dot"></i> Scheduled shift</span><span><i class="legend-dot off-dot"></i> Day off / absence</span><span><i class="legend-dot holiday-dot"></i> Rest day</span><span>🎉 Holiday</span></div>
                        <div class="panel schedule-calendar-panel">
                            <div class="schedule-calendar-scroll">
                                <div id="scheduleCalendar" class="schedule-calendar">
                                    <div class="schedule-calendar-loading">Loading calendar…</div>
                                </div>
                            </div>
                            <div class="panel-footer" id="scheduleFooter">Showing calendar</div>
                        </div>
                    </section>
                    <section class="page admin-only" id="page-requests">
                        <div class="page-heading">
                            <div>
                                <div class="eyebrow">Employee wellbeing</div>
                                <h1>Rest-day requests</h1>
                                <p>
                                    Review requests to change an employee’s
                                    scheduled rest day.
                                </p>
                            </div>
                            <button class="btn secondary" id="reloadRequests">
                                ⟳ Refresh
                            </button>
                        </div>
                        <div class="panel">
                            <div class="toolbar">
                                <div class="search-wrap">
                                    <span>⌕</span><input
                                        id="requestSearch"
                                        placeholder="Search employee or reason..." />
                                </div>
                                <select
                                    id="requestStatusFilter"
                                    class="select-control">
                                    <option value="all">All statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="table-scroll">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Current rest day</th>
                                            <th>Requested rest day</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="requestRows">
                                        <tr>
                                            <td colspan="6" class="empty-cell">
                                                No requests loaded.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="panel-footer" id="requestFooter">
                                Showing 0 requests
                            </div>
                        </div>
                    </section>
                    <section class="page admin-only" id="page-absences">
                        <div class="page-heading">
                            <div>
                                <div class="eyebrow">
                                    Coverage & availability
                                </div>
                                <h1>Absence requests</h1>
                                <p>
                                    Track reported absences and review their
                                    current status.
                                </p>
                            </div>
                            <button class="btn secondary" id="reloadAbsences">
                                ⟳ Refresh
                            </button>
                        </div>
                        <div class="panel">
                            <div class="toolbar">
                                <div class="search-wrap">
                                    <span>⌕</span><input
                                        id="absenceSearch"
                                        placeholder="Search employee or description..." />
                                </div>
                                <select
                                    id="absenceStatusFilter"
                                    class="select-control">
                                    <option value="all">All statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="table-scroll">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Absence date</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="absenceRows">
                                        <tr>
                                            <td colspan="5" class="empty-cell">
                                                No absence requests loaded.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="panel-footer" id="absenceFooter">
                                Showing 0 requests
                            </div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
        <div class="profile-backdrop hidden" id="profileBackdrop" role="presentation">
            <section class="profile-modal" role="dialog" aria-modal="true" aria-labelledby="profileModalTitle">
                <button type="button" class="profile-close" id="closeProfileBtn" aria-label="Close profile">×</button>
                <div class="profile-cover"><span class="profile-orbit profile-orbit-one"></span><span class="profile-orbit profile-orbit-two"></span>
                    <div class="profile-large-avatar" id="profileAvatar">👤</div><span class="profile-status-dot"></span>
                </div>
                <div class="profile-modal-content">
                    <div class="eyebrow">Your account</div>
                    <h2 id="profileModalTitle">My profile</h2>
                    <p class="profile-subtitle">Manage your account details.</p>
                    <div class="profile-photo-actions">
                        <label class="profile-upload-button" for="profilePhotoInput">↑ Upload profile photo</label>
                        <input id="profilePhotoInput" type="file" accept="image/png,image/jpeg,image/webp" hidden />
                    </div>
                    <div class="profile-info-list" id="profileReadOnly">
                        <div class="profile-info-item"><span class="profile-info-icon"></span>
                            <div><small>Full name</small><b id="profileName">—</b></div>
                        </div>
                        <div class="profile-info-item"><span class="profile-info-icon"></span>
                            <div><small>Employee ID</small><b id="profileEmployeeId"></b></div>
                        </div>
                        <div class="profile-info-item"><span class="profile-info-icon"></span>
                            <div><small>Position</small><b id="profilePosition">Not assigned</b></div>
                        </div>
                        <div class="profile-info-item"><span class="profile-info-icon"></span>
                            <div><small>Email address</small><b id="profileEmail">—</b></div>
                        </div>

                    </div>
                    <form id="profileEditForm" class="profile-edit-form hidden">
                        <label>Full name<input name="full_name" id="profileFullNameInput" required maxlength="255" autocomplete="name"></label>
                        <label>Email address<input name="email" id="profileEditEmail" type="email" required maxlength="255"></label>
                        <p id="profileEditError" class="form-error"></p>
                        <button class="btn primary wide" type="submit" id="saveProfileBtn">Save profile</button>
                    </form>
                    <div class="profile-modal-footer"><span><i></i> Account session active</span>
                        <div class="profile-footer-actions"><button type="button" class="btn secondary" id="editProfileBtn">Edit profile</button><button type="button" class="btn primary" id="doneProfileBtn">Done</button></div>
                    </div>
                </div>
            </section>
        </div>
        <div class="logout-backdrop hidden" id="logoutBackdrop" role="presentation">
            <section class="logout-modal" role="dialog" aria-modal="true" aria-labelledby="logoutTitle">
                <div class="logout-icon">↩</div>
                <h2 id="logoutTitle">Are you sure do you want to Log out</h2>
                <p> You can sign back in anytime.</p>
                <div class="logout-actions"><button type="button" class="btn secondary" id="cancelLogoutBtn">Stay in Log in</button><button type="button" class="btn logout-confirm" id="confirmLogoutBtn">log out <span>→</span></button></div>
            </section>
        </div>
        <div class="modal-backdrop hidden" id="modalBackdrop">
            <section
                class="modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="modalTitle">
                <div class="modal-head">
                    <div>
                        <div class="eyebrow">ShiftMate record</div>
                        <h2 id="modalTitle">Add record</h2>
                        <p id="modalSubtitle">Enter the details below.</p>
                    </div>
                    <button class="icon-btn" id="closeModal" aria-label="Close">
                        ×
                    </button>
                </div>
                <form id="recordForm">
                    <div id="modalFields" class="modal-fields"></div>
                    <div class="modal-actions">
                        <button
                            type="button"
                            class="btn secondary"
                            id="cancelModal">
                            Cancel</button><button
                            type="submit"
                            class="btn primary"
                            id="saveRecord">
                            Save record <span>→</span>
                        </button>
                    </div>
                    <div class="auth-error" id="formError" role="alert"></div>
                </form>
            </section>
        </div>
        <div class="toast" id="toast" role="status"></div>
        <script>
            (function() {
                const splash = document.getElementById("splashScreen");
                const landing = document.getElementById("landingView");
                if (landing) landing.style.visibility = "visible";
                window.setTimeout(function() {
                    if (splash) splash.classList.add("hide");
                    window.setTimeout(function() {
                        splash?.remove();
                    }, 220);
                }, 350);
            })();
        </script>
        <script src="<?php echo e(asset('shiftmate-ui-assets/app.js') . '?v=20261121-8'); ?>"></script>
</body>

</html><?php /**PATH C:\Users\sampa\Downloads\SHIFTMATE_SYSTEM\resources\views/shiftmate-ui.blade.php ENDPATH**/ ?>
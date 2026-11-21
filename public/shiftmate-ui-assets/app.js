document.addEventListener("DOMContentLoaded", function () {
    const emailWasVerified = new URLSearchParams(window.location.search).get("email_verified") === "1";
    if (emailWasVerified) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    const landingView = document.getElementById("landingView");
    const authView = document.getElementById("authView");
    const appView = document.getElementById("appView");
    if (!landingView || !authView) return;

    // authView and appView currently live inside landingView, so do not hide their parent.
    // Use display: contents and hide only the landing artwork when opening a portal.
    authView.style.display = "none";
    document.querySelectorAll("[data-entry-portal]").forEach((button) => {
        button.addEventListener("click", () => {
            landingView.style.display = "contents";
            landingView.classList.remove("hidden");
            landingView
                .querySelector(".landing-shade")
                ?.classList.add("portal-view-hidden");
            landingView
                .querySelector(".landing-hero")
                ?.classList.add("portal-view-hidden");
            authView.classList.remove("hidden");
            authView.style.display = "flex";
            if (appView) appView.classList.add("hidden");

            const portal =
                button.dataset.entryPortal === "admin" ? "admin" : "employee";
            setPortal(portal);
            showAuth(false);
            if (emailWasVerified) {
                setAuthError("Your email address is verified. You can log in after administrator approval.");
            }
            const signup = document.getElementById("showRegister");
            const signupNote = document.getElementById("employeeSignupNote");
            if (signupNote)
                signupNote.classList.toggle("hidden", portal === "admin");
            if (signup) signup.disabled = portal === "admin";
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    });
});

/* ShiftMate redesigned frontend. Connects to the Laravel API using Sanctum bearer tokens. */
// Keep API requests on the same origin as the Laravel app. Open through php artisan serve,
// not file:// or VS Code Live Server.
const CONFIG = {
    apiBase: new URL("/api", window.location.origin)
        .toString()
        .replace(/\/$/, ""),
};
const state = {
    portal: "employee",
    token: localStorage.getItem("shiftmate_token") || "",
    user: null,
    employees: [],
    schedules: [],
    restDayAssignments: [],
    shifts: [],
    requests: [],
    pendingRegistrations: [],
    absences: [],
    notifications: [],
    messageRecipients: [],
    editing: null,
};
let lastVerificationEmail = "";
const $ = (s, root = document) => root.querySelector(s);
const $$ = (s, root = document) => [...root.querySelectorAll(s)];
const esc = (value) =>
    String(value ?? "").replace(
        /[&<>"']/g,
        (c) =>
            ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#39;",
            })[c],
    );
const human = (value) =>
    String(value || "")
        .replace(/[_-]/g, " ")
        .replace(/\b\w/g, (c) => c.toUpperCase());
const dateText = (value) => {
    if (!value) return "—";
    const d = new Date(value + "T00:00:00");
    return Number.isNaN(d.getTime())
        ? esc(value)
        : d.toLocaleDateString(undefined, {
            month: "short",
            day: "numeric",
            year: "numeric",
        });
};
const dateInput = (value) => (value ? String(value).slice(0, 10) : "");

// Find the rest day assigned to the signed-in employee by the administrator.
// Prefer the next upcoming assignment; if the schedule only contains past dates,
// show the most recently assigned rest day so the employee can still request a change.
function currentAssignedRestDay() {
    const userId = String(state.user?.id || "");
    if (!userId) return "";
    // The server keeps one "current rest day" per employee (synced with the calendar).
    const current = (state.restDayAssignments || []).find(
        (item) => String(item.user_id || item.user?.id || "") === userId,
    );
    if (current?.rest_day) return String(current.rest_day).slice(0, 10);
    const assigned = state.schedules
        .filter((item) => {
            const assignedUserId = String(item.user_id || item.user?.id || "");
            const shiftName = String(item.shifts?.shift_name || item.shift?.shift_name || "")
                .trim().toLowerCase();
            return assignedUserId === userId && ["restday", "rest day"].includes(shiftName) && item.work_date;
        })
        .map((item) => String(item.work_date).slice(0, 10))
        .sort();
    const today = scheduleDateKey(new Date());
    return assigned.find((date) => date >= today) || assigned.at(-1) || "";
}

// Automatic Philippine holiday calendar: recurring national dates plus movable Holy Week.
// Eid dates are intentionally not guessed because their official dates are announced yearly.
function easterSunday(year) {
    const a = year % 19,
        b = Math.floor(year / 100),
        c = year % 100;
    const d = Math.floor(b / 4),
        e = b % 4,
        f = Math.floor((b + 8) / 25);
    const g = Math.floor((b - f + 1) / 3),
        h = (19 * a + b - d - g + 15) % 30;
    const i = Math.floor(c / 4),
        k = c % 4,
        l = (32 + 2 * e + 2 * i - h - k) % 7;
    const m = Math.floor((a + 11 * h + 22 * l) / 451);
    const month = Math.floor((h + l - 7 * m + 114) / 31);
    const day = ((h + l - 7 * m + 114) % 31) + 1;
    return new Date(year, month - 1, day);
}
function automaticPhilippineHolidays(year) {
    const holidays = {
        [`${year}-01-01`]: "New Year's Day",
        [`${year}-02-25`]: "EDSA People Power Revolution Anniversary",
        [`${year}-04-09`]: "Araw ng Kagitingan",
        [`${year}-05-01`]: "Labor Day",
        [`${year}-06-12`]: "Independence Day",
        [`${year}-08-21`]: "Ninoy Aquino Day",
        [`${year}-08-26`]: "National Heroes Day (last Monday of August)",
        [`${year}-11-01`]: "All Saints' Day",
        [`${year}-11-02`]: "All Souls' Day",
        [`${year}-11-30`]: "Bonifacio Day",
        [`${year}-12-08`]: "Feast of the Immaculate Conception",
        [`${year}-12-25`]: "Christmas Day",
        [`${year}-12-30`]: "Rizal Day",
        [`${year}-12-31`]: "Last Day of the Year",
    };
    // National Heroes Day is the last Monday in August.
    const lastAugust = new Date(year, 8, 0);
    while (lastAugust.getDay() !== 1)
        lastAugust.setDate(lastAugust.getDate() - 1);
    delete holidays[`${year}-08-26`];
    holidays[scheduleDateKey(lastAugust)] = "National Heroes Day";
    const easter = easterSunday(year);
    const goodFriday = new Date(easter);
    goodFriday.setDate(easter.getDate() - 2);
    const maundyThursday = new Date(easter);
    maundyThursday.setDate(easter.getDate() - 3);
    holidays[scheduleDateKey(maundyThursday)] = "Maundy Thursday";
    holidays[scheduleDateKey(goodFriday)] = "Good Friday";
    return holidays;
}
function scheduleHolidayFor(dateKey) {
    const year = Number(String(dateKey).slice(0, 4));
    if (!year) return "";
    return automaticPhilippineHolidays(year)[dateKey] || "";
}
function scheduleDateIsPast(dateKey) {
    return dateKey < scheduleDateKey(new Date());
}
function toast(message) {
    const el = $("#toast");
    el.textContent = message;
    el.classList.add("show");
    clearTimeout(toast.timer);
    toast.timer = setTimeout(() => el.classList.remove("show"), 2800);
}

function setNotice(message, success = false) {
    const el = $("#globalNotice");
    el.textContent = message;
    el.classList.remove("hidden", "success");
    if (success) el.classList.add("success");
}

// Native browser alert for API failures (not an in-website modal/toast).
// A short duplicate guard prevents simultaneous failed dashboard requests from
// opening a stack of identical browser dialogs.
const apiBrowserAlertState = { lastKey: "", lastAt: 0 };
function showApiBrowserAlert(message, key = message) {
    const now = Date.now();
    if (
        apiBrowserAlertState.lastKey === key &&
        now - apiBrowserAlertState.lastAt < 5000
    )
        return;
    apiBrowserAlertState.lastKey = key;
    apiBrowserAlertState.lastAt = now;
    window.alert(
        `ShiftMate API Error\n\n${message}\n\nPlease check that Laravel is running (php artisan serve), your MySQL connection is available, and the API route exists.`,
    );
}

function clearNotice() {
    $("#globalNotice").classList.add("hidden");
}

function setAuthError(message = "") {
    $("#authError").textContent = message;
}

function setConnection(connected) {
    // Connection status is intentionally kept out of the dashboard header UI.
    const el = $(".live-status");
    if (el) {
        el.classList.toggle("connected", connected);
        const label = $("#connectionLabel");
        if (label)
            label.textContent = connected
                ? "API connected"
                : "API not connected";
    }
}

function fullName(x) {
    return (
        x?.full_name ||
        [x?.first_name, x?.last_name].filter(Boolean).join(" ") ||
        x?.name ||
        x?.user?.full_name ||
        x?.user?.name ||
        (x?.user?.first_name || x?.user?.last_name
            ? [x.user.first_name, x.user.last_name].filter(Boolean).join(" ")
            : "") ||
        (x?.employee?.first_name &&
            [x.employee.first_name, x.employee.last_name]
                .filter(Boolean)
                .join(" ")) ||
        x?.employee?.user?.full_name ||
        (x?.employee?.user?.first_name || x?.employee?.user?.last_name
            ? [x.employee.user.first_name, x.employee.user.last_name]
                .filter(Boolean)
                .join(" ")
            : "") ||
        "Unassigned"
    );
}
async function api(path, options = {}) {
    const headers = {
        Accept: "application/json",
        "Content-Type": "application/json",
        ...(state.token ? { Authorization: `Bearer ${state.token}` } : {}),
        ...(options.headers || {}),
    };
    let response;
    try {
        response = await fetch(`${CONFIG.apiBase}${path}`, {
            ...options,
            headers,
        });
    } catch (e) {
        const message = `Cannot connect to the ShiftMate API at ${CONFIG.apiBase}${path}. Open ShiftMate through Laravel at http://127.0.0.1:8000 (not file:// or VS Code Live Server), then run php artisan serve in the project folder. If Laravel is already running, check its terminal for errors and confirm routes with php artisan route:list --path=api.`;
        showApiBrowserAlert(message, `network:${path}`);
        throw new Error(message);
    }
    const text = await response.text();
    let data = null;
    try {
        data = text ? JSON.parse(text) : null;
    } catch {
        data = text;
    }
    if (!response.ok) {
        if (response.status === 401) {
            signOut(false);
        }
        const message =
            data?.message ||
            (data?.errors
                ? Object.values(data.errors).flat().join(" ")
                : "Request failed");
        showApiBrowserAlert(
            `${path}: ${message} (HTTP ${response.status})`,
            `http:${path}:${response.status}:${message}`,
        );
        const error = new Error(message);
        error.status = response.status;
        error.code = data?.code;
        error.email = data?.email;
        throw error;
    }
    return data;
}

function setPortal(portal) {
    state.portal = portal === "admin" ? "admin" : "employee";
    $$("[data-portal]").forEach((b) =>
        b.classList.toggle("active", b.dataset.portal === state.portal),
    );
    $("#authTitle").textContent =
        `${state.portal === "admin" ? "Administrator" : "Employee"} ${$("#registerForm").classList.contains("hidden") ? "sign in" : "sign up"}`;
}

function showAuth(register = false) {
    $("#appView").classList.add("hidden");
    $("#authView").classList.remove("hidden");
    $("#loginForm").classList.toggle("hidden", register);
    $("#registerForm").classList.toggle("hidden", !register);
    $("#forgotPasswordForm")?.classList.add("hidden");
    $("#resetPasswordForm")?.classList.add("hidden");
    const portalSwitch = $(".portal-switch");
    if (portalSwitch) portalSwitch.classList.toggle("hidden", true);
    const signupNote = $("#employeeSignupNote");
    if (signupNote)
        signupNote.classList.toggle("hidden", state.portal === "admin");
    $("#authTitle").textContent =
        `${state.portal === "admin" ? "Administrator" : "Employee"} ${register ? "Registration" : "Login"}`;
    $("#authSubtitle").textContent = register
        ? "Register an account to continue."
        : "Login with your email and password.";
    setAuthError("");
    $("#resendVerification")?.classList.add("hidden");
}

function photoUrl(path) {
    if (!path) return "";
    if (/^https?:\/\//i.test(path)) return path;
    return `/storage/${String(path).replace(/^\/+/, "")}`;
}

function applyAvatar(selector, path, fallback) {
    const el = $(selector);
    if (!el) return;
    const url = photoUrl(path);
    el.textContent = url ? "" : fallback;
    el.style.backgroundImage = url ? `url("${url.replace(/["\\]/g, "")}")` : "";
    el.classList.toggle("has-photo", Boolean(url));
}

function showApp() {
    $("#authView").classList.add("hidden");
    $("#appView").classList.remove("hidden");
    // After a page refresh the landing artwork was never hidden (only the portal buttons did it),
    // so the big "ShiftMate" hero text showed through behind the dashboard. Hide it here too.
    {
        const landing = document.getElementById("landingView");
        if (landing) {
            landing.style.display = "contents";
            landing.classList.remove("hidden");
            landing.querySelector(".landing-shade")?.classList.add("portal-view-hidden");
            landing.querySelector(".landing-hero")?.classList.add("portal-view-hidden");
        }
    }
    const u = state.user || {};
    const name = fullName(u) === "Unassigned" ? "Unassigned" : fullName(u);
    const role = String(u.role || "employee").toLowerCase();
    document.body.dataset.role = role;
    const isStaff = ["admin", "manager"].includes(role);
    $("#userName").textContent = name;
    $("#userRole").textContent = isStaff ? human(role) : "";
    $("#userRole").classList.toggle("hidden", !isStaff);
    applyAvatar(
        "#userAvatar",
        u.profile_photo_path,
        initials(name).slice(0, 1) || "S",
    );
    $("#dropdownName").textContent = name;
    $("#dropdownEmail").textContent = u.email || "Signed in to ShiftMate";
    applyAvatar(
        "#dropdownAvatar",
        u.profile_photo_path,
        initials(name).slice(0, 1) || "S",
    );
    applyAvatar(
        "#profileAvatar",
        u.profile_photo_path,
        initials(name).slice(0, 1) || "S",
    );
    $("#profileName").textContent = name;
    $("#profileEmail").textContent = u.email || "No email provided";
    const linkedEmployee = state.employees.find(
        (employee) => String(employee.user_id) === String(u.id),
    );
    const employeeIdDisplay = $("#profileEmployeeId");
    if (employeeIdDisplay) {
        const row = employeeIdDisplay.closest(".profile-info-item");
        if (row) row.classList.toggle("hidden", role !== "employee");
        employeeIdDisplay.textContent = linkedEmployee?.employee_number
            ? String(linkedEmployee.employee_number)
            : "Pending assignment";
    }
    const profilePosition = $("#profilePosition");
    if (profilePosition) {
        const row = profilePosition.closest(".profile-info-item");
        if (row) row.classList.toggle("hidden", role !== "employee");
        profilePosition.textContent =
            linkedEmployee?.position || "Not assigned";
        if (linkedEmployee?.position) {
            profilePosition.title = `Your position is ${linkedEmployee.position}. `;
            const row = profilePosition.closest(".profile-info-item");
            if (row && !$("#positionLockedNote", row))
                row.insertAdjacentHTML(
                    "beforeend",
                    '<small id="positionLockedNote" class="position-locked-note">',
                );
        }
    }
    const profileRole = $("#profileRole");
    if (profileRole) profileRole.textContent = human(role);
    const fullNameInput = $("#profileFullNameInput");
    if (fullNameInput)
        fullNameInput.value =
            u.full_name ||
            [u.first_name, u.last_name].filter(Boolean).join(" ") ||
            u.name ||
            "";
    $("#profileEditEmail").value = u.email || "";
    const hour = new Date().getHours();
    $("#greeting").textContent =
        `Good ${hour < 12 ? "morning" : hour < 18 ? "afternoon" : "evening"}, ${name.split(" ")[0]} 👋`;
    $$(".employee-only").forEach((el) =>
        el.classList.toggle("hidden", isStaff),
    );
    $$(".admin-only").forEach((el) => el.classList.toggle("hidden", !isStaff));
    const registrationNav = $('[data-page="registrations"]');
    const registrationPage = $("#page-registrations");
    const isAdminAccount = role === "admin";
    if (registrationNav) registrationNav.classList.toggle("hidden", !isAdminAccount);
    if (registrationPage) registrationPage.classList.toggle("hidden", !isAdminAccount);
    $("#userDropdown").classList.add("hidden");
    $("#userMenuBtn").setAttribute("aria-expanded", "false");
    $$(
        '[data-open-form="schedule"],[data-open-form="employee"],[data-open-form="shift"]',
    ).forEach((el) => el.classList.toggle("hidden", !isStaff));
    {
        let savedPage = "";
        try { savedPage = localStorage.getItem("shiftmate_page") || ""; } catch (e) { }
        const pageExists = savedPage && document.getElementById(`page-${savedPage}`);
        showPage(pageExists ? savedPage : (isStaff ? "dashboard" : "employee-dashboard"));
    }
    setConnection(true);
}
async function login(email, password) {
    const data = await api("/login", {
        method: "POST",
        body: JSON.stringify({ email, password, portal: state.portal }),
    });
    if (!data?.access_token || !data?.user)
        throw new Error(
            "Login succeeded but the server did not return a valid session. Please try again.",
        );
    state.token = data.access_token;
    state.user = data.user;
    state.portal = ["admin", "manager"].includes(
        String(data.user.role || "").toLowerCase(),
    )
        ? "admin"
        : "employee";
    localStorage.setItem("shiftmate_token", state.token);
    showApp();
    await loadAll();
    showPage(
        ["admin", "manager"].includes(
            String(state.user?.role || "employee").toLowerCase(),
        )
            ? "dashboard"
            : "employee-dashboard",
    );
}
async function register(form) {
    const values = Object.fromEntries(new FormData(form));
    values.role = "employee";
    const data = await api("/register", {
        method: "POST",
        body: JSON.stringify(values),
    });
    if (!data?.approval_required || !data?.user) {
        throw new Error(data?.message || "Registration could not be submitted. Please try again.");
    }
    lastVerificationEmail = String(values.email || "");
    form.reset();
    setAuthError(data.message || "Registration submitted. Wait for administrator approval.");
    if (data.verification_email_sent === false) {
        $("#resendVerification")?.classList.remove("hidden");
    }
    return;
}

async function signOut(callApi = true) {
    const token = state.token;
    if (callApi && token) {
        try {
            await api("/logout", { method: "POST", body: "{}" });
        } catch (e) {
            console.warn("Server sign-out could not be confirmed:", e.message);
        }
    }
    state.token = "";
    state.user = null;
    localStorage.removeItem("shiftmate_token"); try { localStorage.removeItem("shiftmate_page"); } catch (e) { }
    state.employees = [];
    state.schedules = [];
    state.shifts = [];
    state.requests = [];
    state.absences = [];
    state.notifications = [];
    state.editing = null;
    setConnection(false);
    $("#appView").classList.add("hidden");
    showAuth(false);
    toast("Logged out successfully.");
}

function showPage(name) {
    const isStaff = ["admin", "manager"].includes(
        String(state.user?.role || "employee").toLowerCase(),
    );
    if (
        !isStaff &&
        !["employee-dashboard", "schedule", "my-requests"].includes(name)
    )
        name = "employee-dashboard";
    try { localStorage.setItem("shiftmate_page", name); } catch (e) { }
    $$(".page").forEach((p) =>
        p.classList.toggle("active-page", p.id === `page-${name}`),
    );
    $$(".nav-item[data-page]").forEach((b) =>
        b.classList.toggle("active", b.dataset.page === name),
    );
    $("#crumbTitle").textContent =
        {
            dashboard: "Admin Overview",
            "employee-dashboard": "My overview",
            schedule: isStaff ? "Schedules" : "My schedule",
            employees: "Employees",
            shifts: "Shift schedule",
            requests: "Rest-day requests",

            absences: "Absence requests",
            "my-requests": "Request history",
        }[name] || human(name);
    const h = $("#page-schedule .page-heading h1"),
        d = $("#page-schedule .page-heading p"),
        a = $('#page-schedule [data-open-form="schedule"]');
    if (h) {
        h.textContent = isStaff ? "Schedules" : "My schedule";
        d.textContent = isStaff
            ? "View work assignments and manage schedule records."
            : "View your assigned shifts and rest days.";
        if (a) a.classList.toggle("hidden", !isStaff);
    }
    $("#sidebar").classList.remove("open");
}

function statusPill(value) {
    const v = String(value || "unknown").toLowerCase();
    return `<span class="status-pill ${["active", "inactive", "pending", "approved", "rejected"].includes(v) ? v : "neutral"}">${esc(human(v))}</span>`;
}

function initials(name) {
    return String(name || "?")
        .split(/\s+/)
        .slice(0, 2)
        .map((x) => x[0] || "")
        .join("")
        .toUpperCase();
}

function person(name, sub = "") {
    return `<div class="person-cell"><span class="person-avatar">${esc(initials(name))}</span><span><b>${esc(name)}</b>${sub ? `<small>${esc(sub)}</small>` : ""}</span></div>`;
}
function emptyRow(cols, message = "No records found.") {
    return `<tr><td class="empty-cell" colspan="${cols}">${esc(message)}</td></tr>`;
}
function searchMatch(record, query, keys) {
    if (!query) return true;
    const hay = keys
        .map((k) => record?.[k])
        .join(" ")
        .toLowerCase();
    return hay.includes(query.toLowerCase());
}
function employeeNameFor(id) {
    const e = state.employees.find((x) => String(x.id) === String(id));
    return e ? fullName(e) : "Employee #" + (id ?? "—");
}
function shiftDisplayName(value, id = null) {
    const name = String(value || "").trim();
    if (/^morning(?: shift)?$/i.test(name)) return "Morning Shift";
    if (/^afternoon(?: shift)?$/i.test(name)) return "Afternoon Shift";
    if (/^night(?: shift)?$/i.test(name)) return "Night Shift";
    if (name) return name;
    const shift = state.shifts.find((x) => String(x.id) === String(id));
    if (shift?.start_time) {
        const hour = Number(String(shift.start_time).slice(0, 2));
        if (hour >= 5 && hour < 12) return "Morning Shift";
        if (hour >= 12 && hour < 18) return "Afternoon Shift";
        return "Night Shift";
    }
    return "Assigned Shift";
}
function shiftNameFor(s) {
    return shiftDisplayName(
        s?.shifts?.shift_name ||
        s?.shift?.shift_name ||
        state.shifts.find((x) => String(x.id) === String(s?.shift_id))
            ?.shift_name,
        s?.shift_id,
    );
}
function renderDashboard() {
    $("#totalEmployees").textContent = state.employees.length;
    $("#totalSchedules").textContent = state.schedules.length;
    const pendingR = state.requests.filter(
        (x) => String(x.status).toLowerCase() === "pending",
    );
    const pendingA = state.absences.filter(
        (x) => String(x.status).toLowerCase() === "pending",
    );
    $("#pendingRequests").textContent = pendingR.length;
    $("#pendingAbsences").textContent = pendingA.length;
    const pendingRegistrations = (state.pendingRegistrations || []).length;
    if ($("#dashboardPendingRegistrations")) $("#dashboardPendingRegistrations").textContent = pendingRegistrations;
    if ($("#dashboardPendingRest")) $("#dashboardPendingRest").textContent = pendingR.length;
    if ($("#dashboardPendingAbsence")) $("#dashboardPendingAbsence").textContent = pendingA.length;
    $("#requestNavCount").textContent = pendingR.length;
    $("#absenceNavCount").textContent = pendingA.length;
    const today = new Date().toISOString().slice(0, 10);
    const orderedSchedules = [...state.schedules].sort((a, b) =>
        String(a.work_date || "").localeCompare(String(b.work_date || "")),
    );
    const futureSchedules = orderedSchedules.filter(
        (s) => String(s.work_date || "").slice(0, 10) >= today,
    );
    const recent = (
        futureSchedules.length ? futureSchedules : orderedSchedules
    ).slice(0, 6);
    $("#dashboardScheduleRows").innerHTML = recent.length
        ? recent
            .map(
                (s) =>
                    `<tr><td>${person(s.user && fullName(s.user) !== "Unassigned" ? fullName(s.user) : employeeNameFor(s.user_id), s.user?.email || "")}</td><td>${esc(shiftNameFor(s))}</td><td>${dateText(s.work_date)}</td><td>${statusPill(s.status || "active")}</td></tr>`,
            )
            .join("")
        : emptyRow(
            3,
            "No schedule records yet. Add a schedule to get started.",
        );
}
function renderEmployees() {
    const q = $("#employeeSearch").value.trim();
    const list = state.employees.filter((x) => {
        const searchable = [
            fullName(x),
            x.employee_number,
            x.position,
            x.user?.role,
            x.user?.email,
            x.user?.full_name,
        ]
            .filter(Boolean)
            .join(" ")
            .toLowerCase();
        return searchable.includes(q.toLowerCase());
    });
    $("#employeeRows").innerHTML = list.length
        ? list
            .map((x) => {
                const position = String(x.position || "Not set").trim();
                const positionIsSet =
                    position !== "" && position.toLowerCase() !== "not set";
                const positionCell = positionIsSet
                    ? `<span class="position-chip">${esc(position)}</span><small class="position-locked-note">Position locked</small>`
                    : `<span class="position-chip">Not set</span>`;
                const action = positionIsSet
                    ? `<span class="position-locked-note">Cannot edit</span>`
                    : `<button class="small-action" data-edit="employee" data-id="${esc(x.id)}">Set position</button>`;
                return `<tr><td>${person(fullName(x), x.user?.email || "")}</td><td>${esc(x.employee_number || "—")}</td><td>${statusPill(x.user?.role || "employee")}</td><td>${positionCell}</td><td><div class="row-actions">${action}</div></td></tr>`;
            })
            .join("")
        : emptyRow(5, "No matching employees.");
    $("#employeeFooter").textContent =
        `Showing ${list.length} of ${state.employees.length} employees`;
}
function scheduleDateValue(value) {
    const d = value
        ? new Date(`${String(value).slice(0, 10)}T12:00:00`)
        : new Date();
    return Number.isNaN(d.getTime()) ? new Date() : d;
}
function scheduleDateKey(date) {
    const d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
}
function scheduleWeekDays() {
    const anchor = scheduleDateValue(
        $("#scheduleAnchorDate")?.value || scheduleDateKey(new Date()),
    );
    const monday = new Date(
        anchor.getFullYear(),
        anchor.getMonth(),
        anchor.getDate(),
    );
    monday.setDate(monday.getDate() - ((monday.getDay() + 6) % 7));
    return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(monday);
        d.setDate(monday.getDate() + i);
        return d;
    });
}
function formatShiftTime(value) {
    if (!value) return "";
    const parts = String(value).slice(0, 5).split(":");
    if (parts.length < 2 || !parts.every((v) => /^\d+$/.test(v)))
        return String(value);
    const h = Number(parts[0]),
        m = Number(parts[1]);
    const suffix = h >= 12 ? "PM" : "AM";
    return `${h % 12 || 12}:${String(m).padStart(2, "0")} ${suffix}`;
}
function renderSchedules() {
    const isStaff = ["admin", "manager"].includes(
        String(state.user?.role || "employee").toLowerCase(),
    );
    const anchor = scheduleDateValue(
        $("#scheduleAnchorDate")?.value || scheduleDateKey(new Date()),
    );
    const weekDays = scheduleWeekDays();
    const range = $("#scheduleDateRange");
    if (range) {
        const first = weekDays[0],
            last = weekDays[6];
        range.textContent = `${first.toLocaleDateString("en-US", { month: "short", day: "numeric" })} – ${last.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" })}`;
    }
    const selectedMember = "all";
    const members = isStaff
        ? state.employees.filter(
            (e) =>
                String(e.user?.role || "employee").toLowerCase() ===
                "employee",
        )
        : state.employees.filter(
            (e) =>
                String(e.user_id || e.user?.id || "") ===
                String(state.user?.id || ""),
        );
    const memberByUserId = new Map();
    state.employees.forEach((e) => {
        if (e.user_id != null) memberByUserId.set(String(e.user_id), e);
        if (e.user?.id != null) memberByUserId.set(String(e.user.id), e);
    });
    const shiftOrder = {
        morning: 1,
        afternoon: 2,
        evening: 3,
        night: 3,
        restday: 4,
        "rest day": 4,
    };
    const shiftLabels = {
        morning: "Morning",
        afternoon: "Afternoon",
        evening: "Evening",
        night: "Night",
        restday: "Rest day",
        "rest day": "Rest day",
    };
    const usableShifts = state.shifts
        .filter((shift) => {
            const name = String(shift.shift_name || "").toLowerCase();
            return Object.prototype.hasOwnProperty.call(shiftOrder, name);
        })
        .sort(
            (a, b) =>
                (shiftOrder[String(a.shift_name || "").toLowerCase()] || 99) -
                (shiftOrder[String(b.shift_name || "").toLowerCase()] || 99),
        );
    const calendarShiftOptions = usableShifts
        .map((shift) => {
            const name = String(shift.shift_name || "").toLowerCase();
            return `<option value="${esc(shift.id)}">${esc(shiftLabels[name] || shift.shift_name)}</option>`;
        })
        .join("");
    const calendar = $("#scheduleCalendar");
    if (calendar) {
        const weekdays = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
        const head = `<div class="weekly-calendar-head"><div class="weekly-employee-head">Employee</div>${weekDays.map((day, i) => `<div class="weekly-day-head ${i === 6 ? "weekly-sunday" : ""} ${scheduleDateKey(day) === scheduleDateKey(new Date()) ? "is-today" : ""}"><b>${weekdays[i]}</b><strong>${day.getDate()}</strong><small>${day.toLocaleDateString("en-US", { month: "short" })}</small>${i === 6 ? '<span class="sunday-label">CLOSED</span>' : ""}</div>`).join("")}</div>`;
        const rows = members
            .map((employee) => {
                const userId = String(
                    employee.user_id || employee.user?.id || "",
                );
                const name =
                    [employee.first_name, employee.last_name]
                        .filter(Boolean)
                        .join(" ")
                        .trim() ||
                    employee.user?.full_name ||
                    fullName(employee);
                const photo =
                    employee.profile_photo_path ||
                    employee.user?.profile_photo_path;
                const avatar = photo
                    ? `<img class="weekly-employee-avatar" src="${esc(photoUrl(photo))}" alt="" loading="lazy">`
                    : `<span class="weekly-employee-avatar">${esc(initials(name) || "E")}</span>`;
                const position = String(employee.position || "").trim();
                const cells = weekDays
                    .map((day, i) => {
                        const key = scheduleDateKey(day);
                        const sunday = i === 6;
                        const holiday = scheduleHolidayFor(key);
                        const past = scheduleDateIsPast(key);
                        const entry = state.schedules.find(
                            (x) =>
                                String(x.work_date || "").slice(0, 10) ===
                                key &&
                                String(x.user_id || x.user?.id || "") ===
                                userId,
                        );
                        const absence = state.absences.some(
                            (r) =>
                                String(r.status).toLowerCase() === "approved" &&
                                String(r.absence_date || "").slice(0, 10) ===
                                key &&
                                String(
                                    r.employee?.user_id || r.user_id || "",
                                ) === userId,
                        );
                        if (sunday && !entry)
                            return `<td class="weekly-day-cell weekly-sunday"><div class="weekly-slot weekly-slot-closed"><b>Closed</b></div></td>`;
                        if (holiday && !entry)
                            return `<td class="weekly-day-cell weekly-holiday-cell"><div class="weekly-slot weekly-slot-closed" title="${esc(holiday)}"><b>Closed</b><small>🎉 ${esc(holiday)}</small></div></td>`;
                        if (absence)
                            return `<td class="weekly-day-cell"><div class="weekly-slot weekly-event weekly-event-off"><b>Absent</b></div></td>`;
                        if (entry) {
                            const shift =
                                entry.shifts ||
                                entry.shift ||
                                state.shifts.find(
                                    (sh) =>
                                        String(sh.id) ===
                                        String(entry.shift_id),
                                ) ||
                                {};
                            const shiftKey = String(
                                shift.shift_name || "",
                            ).toLowerCase();
                            const isRest = shiftKey.includes("rest");
                            const label =
                                shiftLabels[shiftKey] ||
                                shift.shift_name ||
                                shiftNameFor(entry);
                            const timeIn = isRest ? "" : formatShiftTime(entry.start_time || shift.start_time);
                            const timeOut = isRest ? "" : formatShiftTime(entry.end_time || shift.end_time);
                            const time = timeIn || timeOut ? `In ${timeIn || "—"} · Out ${timeOut || "—"}` : "";
                            const cls = isRest
                                ? "weekly-slot weekly-event weekly-event-rest"
                                : "weekly-slot weekly-event";
                            return `<td class="weekly-day-cell"><div class="${cls}" title="${esc(name + " · " + label + (time ? " · " + time : ""))}"><b>${esc(label)} <span class="schedule-lock" title="Locked after assignment">🔒</span></b>${time ? `<small>${esc(time)}</small>` : ""}${position ? `<small>${esc(position)}</small>` : ""}</div></td>`;
                        }
                        if (
                            isStaff &&
                            !past &&
                            position &&
                            ["barista", "cashier"].includes(
                                position.toLowerCase(),
                            )
                        ) {
                            return `<td class="weekly-day-cell"><select class="schedule-shift-dropdown weekly-slot weekly-assignment" data-schedule-date="${key}" data-schedule-user="${esc(userId)}" aria-label="Assign shift to ${esc(name)} on ${key}"><option value="">＋ Assign</option>${calendarShiftOptions}</select></td>`;
                        }
                        return `<td class="weekly-day-cell"><div class="weekly-slot weekly-slot-empty">${past ? "—" : !position ? "Set position first" : "—"}</div></td>`;
                    })
                    .join("");
                return `<div class="weekly-calendar-row"><div class="weekly-employee-cell">${avatar}<span class="weekly-employee-details"><b>${esc(name)}</b><small>${esc(position || "Position not assigned")}</small></span></div>${cells}</div>`;
            })
            .join("");
        calendar.innerHTML =
            head +
            `<div class="weekly-calendar-body">${rows || '<div class="weekly-empty">No employees found.</div>'}</div>`;
    }
    const footer = $("#scheduleFooter");
    const visibleRecords = state.schedules.filter(
        (x) =>
            weekDays.some(
                (d) =>
                    scheduleDateKey(d) ===
                    String(x.work_date || "").slice(0, 10),
            ) &&
            (isStaff ||
                String(x.user_id || x.user?.id || "") ===
                String(state.user?.id || "")),
    );
    if (footer)
        footer.textContent = `${range?.textContent || "This week"} · ${visibleRecords.length} scheduled record${visibleRecords.length === 1 ? "" : "s"} · Sunday closed`;
}
function renderShifts() {
    // Shift templates are now managed through the fixed schedule dropdown, not a separate page.
    if (!$("#shiftSearch") || !$("#shiftRows") || !$("#shiftFooter")) return;
    const q = $("#shiftSearch").value.trim();
    const list = state.shifts.filter((x) =>
        searchMatch(x, q, [
            "shift_name",
            "description",
            "start_time",
            "end_time",
        ]),
    );
    $("#shiftRows").innerHTML = list.length
        ? list
            .map(
                (x) =>
                    `<tr><td><b>${esc(shiftDisplayName(x.shift_name, x.id))}</b></td><td>${esc(
                        String(x.start_time || "—")
                            .slice(0, 16)
                            .replace("T", " "),
                    )}</td><td>${esc(
                        String(x.end_time || "—")
                            .slice(0, 16)
                            .replace("T", " "),
                    )}</td><td>${esc(x.description || "—")}</td><td><div class="row-actions"><button class="small-action" data-edit="shift" data-id="${esc(x.id)}">Edit</button><button class="small-action danger" data-delete="shifts" data-id="${esc(x.id)}">Delete</button></div></td></tr>`,
            )
            .join("")
        : emptyRow(5, "No shifts found.");
    $("#shiftFooter").textContent =
        `Showing ${list.length} of ${state.shifts.length} shifts`;
}
function renderRequests() {
    const q = $("#requestSearch").value.trim(),
        status = $("#requestStatusFilter").value;
    const list = state.requests.filter(
        (x) =>
            (status === "all" || String(x.status).toLowerCase() === status) &&
            `${x.employee ? fullName(x.employee) : employeeNameFor(x.employee_id)} ${x.reason || ""}`
                .toLowerCase()
                .includes(q.toLowerCase()),
    );
    $("#requestRows").innerHTML = list.length
        ? list
            .map(
                (x) =>
                    `<tr><td>${person(x.employee ? fullName(x.employee) : employeeNameFor(x.employee_id))}</td><td>${dateText(x.current_rest_day)}</td><td>${dateText(x.requested_rest_day || x.request_rest_day)}</td><td>${esc(x.reason || "—")}</td><td>${statusPill(x.status)}</td><td><div class="row-actions">${String(x.status).toLowerCase() === "pending" ? `<button class="small-action" data-review="rest-day-requests" data-id="${esc(x.id)}" data-status="approved">Approve</button><button class="small-action danger" data-review="rest-day-requests" data-id="${esc(x.id)}" data-status="rejected">Reject</button>` : ""}<button class="small-action danger" data-delete="rest-day-requests" data-id="${esc(x.id)}">Delete</button></div></td></tr>`,
            )
            .join("")
        : emptyRow(6, "No rest-day requests match your filters.");
    $("#requestFooter").textContent =
        `Showing ${list.length} of ${state.requests.length} requests`;
}

function renderRegistrations() {
    const rows = $("#registrationRows");
    if (!rows) return;
    const list = state.pendingRegistrations || [];
    const count = $("#registrationNavCount");
    if (count) {
        count.textContent = String(list.length);
        count.classList.toggle("hidden", list.length === 0);
    }
    rows.innerHTML = list.length ? list.map((user) => `
        <tr>
            <td><b>${esc(user.full_name || "Unnamed employee")}</b></td>
            <td>${esc(user.email || "—")}</td>
            <td>${dateText(String(user.created_at || "").slice(0, 10))}</td>
            <td>${statusPill("pending")}</td>
            <td><div class="row-actions">
                <button type="button" class="small-action" data-registration-review="${esc(user.id)}" data-decision="approved">Approve</button>
                <button type="button" class="small-action danger" data-registration-review="${esc(user.id)}" data-decision="rejected">Reject</button>
            </div></td>
        </tr>`).join("") : emptyRow(5, "There are no pending employee registrations.");
    const footer = $("#registrationFooter");
    if (footer) footer.textContent = `${list.length} pending registration${list.length === 1 ? "" : "s"}`;
}
async function reviewRegistration(userId, decision, button) {
    const action = decision === "approved" ? "approve" : "reject";
    if (!confirm(`Are you sure you want to ${action} this employee account?`)) return;
    if (button) button.disabled = true;
    try {
        const result = await api(`/admin/registrations/${userId}/review`, {
            method: "POST",
            body: JSON.stringify({ decision }),
        });
        toast(result.message || `Registration ${decision}.`);
        await loadAll();
    } catch (error) {
        toast(error.message || "Could not review this registration.");
    } finally {
        if (button) button.disabled = false;
    }
}

function renderAbsences() {
    const q = $("#absenceSearch").value.trim(),
        status = $("#absenceStatusFilter").value;
    const list = state.absences.filter(
        (x) =>
            (status === "all" || String(x.status).toLowerCase() === status) &&
            `${x.employee ? fullName(x.employee) : employeeNameFor(x.employee_id)} ${x.description || ""}`
                .toLowerCase()
                .includes(q.toLowerCase()),
    );
    $("#absenceRows").innerHTML = list.length
        ? list
            .map(
                (x) =>
                    `<tr><td>${person(x.employee ? fullName(x.employee) : employeeNameFor(x.employee_id))}</td><td>${dateText(x.absence_date)}</td><td>${esc(x.description || "—")}</td><td>${statusPill(x.status)}</td><td><div class="row-actions">${String(x.status).toLowerCase() === "pending" ? `<button class="small-action" data-review="absence-requests" data-id="${esc(x.id)}" data-status="approved">Approve</button><button class="small-action danger" data-review="absence-requests" data-id="${esc(x.id)}" data-status="rejected">Reject</button>` : ""}<button class="small-action danger" data-delete="absence-requests" data-id="${esc(x.id)}">Delete</button></div></td></tr>`,
            )
            .join("")
        : emptyRow(5, "No absence requests match your filters.");
    $("#absenceFooter").textContent =
        `Showing ${list.length} of ${state.absences.length} requests`;
}
function renderEmployeeDashboard() {
    const me = state.user || {};
    const employee = state.employees.find(
        (e) => String(e.user_id) === String(me.id),
    );
    const ownSchedules = state.schedules
        .filter(
            (x) =>
                String(x.user_id || x.employee?.user_id || "") ===
                String(me.id) ||
                (employee && String(x.employee_id) === String(employee.id)),
        )
        .sort((a, b) =>
            String(a.work_date || "").localeCompare(String(b.work_date || "")),
        );
    const ownRest = state.requests.filter(
        (x) =>
            String(x.employee?.user_id || x.user_id || "") === String(me.id) ||
            (employee && String(x.employee_id) === String(employee.id)),
    );
    const ownAbs = state.absences.filter(
        (x) =>
            String(x.employee?.user_id || x.user_id || "") === String(me.id) ||
            (employee && String(x.employee_id) === String(employee.id)),
    );
    const todayKey = scheduleDateKey(new Date());
    const approvedAbsenceDates = ownAbs
        .filter((r) => String(r.status).toLowerCase() === "approved")
        .map((r) => String(r.absence_date || "").slice(0, 10));
    const scheduleByDate = new Map(
        ownSchedules.map((x) => [String(x.work_date || "").slice(0, 10), x]),
    );
    const candidateDates = new Set([
        ...scheduleByDate.keys(),
        ...approvedAbsenceDates,
    ]);
    const upcoming = [...candidateDates]
        .filter((d) => d && d >= todayKey)
        .sort()
        .slice(0, 5)
        .map((date) => {
            const schedule = scheduleByDate.get(date);
            const status = approvedAbsenceDates.includes(date)
                ? "Absent"
                : schedule
                    ? shiftNameFor(schedule)
                    : "—";
            return { date, status };
        });
    $("#employeeUpcomingRows").innerHTML = upcoming.length
        ? upcoming
            .map(
                (x) =>
                    `<tr><td><b>${dateText(x.date)}</b></td><td>${esc(x.status)}</td></tr>`,
            )
            .join("")
        : emptyRow(
            2,
            ownSchedules.length
                ? "No upcoming shifts scheduled."
                : "No assigned shifts yet.",
        );
    $("#myDashboardRestCount").textContent = ownRest.length;
    $("#myDashboardAbsenceCount").textContent = ownAbs.length;
    $("#myRestPending").textContent =
        `${ownRest.filter((x) => String(x.status).toLowerCase() === "pending").length} pending`;
    $("#myAbsencePending").textContent =
        `${ownAbs.filter((x) => String(x.status).toLowerCase() === "pending").length} pending`;
}
function renderAll() {
    // One broken panel must never blank the rest of the app, so each render is isolated.
    [
        renderDashboard,
        renderEmployeeDashboard,
        renderEmployees,
        renderRegistrations,
        renderSchedules,
        renderShifts,
        renderRequests,
        renderAbsences,
        renderMyRequests,
    ].forEach((render) => {
        try {
            render();
        } catch (error) {
            console.error(`ShiftMate: ${render.name} failed`, error);
        }
    });
}
function renderMyRequests() {
    const employee = state.employees.find(
        (e) => String(e.user_id) === String(state.user?.id),
    );
    const ownId = employee?.id;
    const rests = state.requests.filter(
        (r) => ownId && String(r.employee_id) === String(ownId),
    );
    const abs = state.absences.filter(
        (r) => ownId && String(r.employee_id) === String(ownId),
    );
    $("#myRestCount").textContent = rests.length;
    $("#myAbsenceCount").textContent = abs.length;
    const rows = [
        ...rests.map((r) => ({
            type: "Rest-day request",
            date: r.requested_rest_day || r.request_rest_day,
            detail: `Current rest day: ${dateText(r.current_rest_day)} · ${r.reason || ""}`,
            status: r.status,
        })),
        ...abs.map((r) => ({
            type: "Absence request",
            date: r.absence_date,
            detail: r.description,
            status: r.status,
        })),
    ].sort((a, b) => String(b.date).localeCompare(String(a.date)));
    $("#myRequestRows").innerHTML = rows.length
        ? rows
            .map(
                (r) =>
                    `<tr><td>${esc(r.type)}</td><td>${dateText(r.date)}</td><td>${esc(r.detail || "—")}</td><td>${statusPill(r.status)}</td></tr>`,
            )
            .join("")
        : emptyRow(
            4,
            employee
                ? "No requests submitted yet."
                : "Your account is not linked to an employee profile yet. Contact your administrator.",
        );
}

function renderNotifications() {
    const list = $("#notificationList");
    const badge = $("#notificationBadge");
    if (!list || !badge) return;
    const items = state.notifications || [];
    const unread = items.filter((n) => !n.is_read).length;
    badge.textContent = unread > 9 ? "9+" : String(unread);
    badge.classList.toggle("hidden", unread === 0);
    const isAdmin = ["admin", "manager"].includes(
        String(state.user?.role || "employee").toLowerCase(),
    );
    list.innerHTML = items.length
        ? items
            .slice(0, 20)
            .map((n) => {
                const isRequest = [
                    "absence_request",
                    "rest_day_request",
                ].includes(String(n.type));
                const isRegistration = String(n.type) === "employee_registration";
                return `<article class="notification-card ${n.is_read ? "" : "is-unread"}" data-notification-card="${esc(n.id)}" data-notification-type="${esc(n.type)}">
            <div class="notification-item-icon">${isRequest || isRegistration ? "▣" : "♢"}</div>
            <div class="notification-item-copy"><b>${esc(n.title || "ShiftMate update")}</b><span>${esc(n.message || "")}</span><small>${esc(n.created_at ? new Date(n.created_at).toLocaleString() : "Just now")}</small>
            <div class="notification-actions"><button type="button" data-notification-read="${esc(n.id)}">${n.is_read ? "Read" : "Mark read"}</button>${isAdmin && isRequest ? `<button type="button" data-notification-reply="${esc(n.id)}">Reply</button>` : ""}<button type="button" class="notification-delete" data-notification-delete="${esc(n.id)}">Delete</button></div></div>
            ${n.is_read ? "" : '<i class="unread-dot"></i>'}
        </article>`;
            })
            .join("")
        : '<div class="notification-empty">No notifications yet. New requests will appear here.</div>';
}
function playNotificationChime() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const ctx = window.shiftMateAudioContext || new AudioContext();
        window.shiftMateAudioContext = ctx;
        if (ctx.state === "suspended") ctx.resume().catch(() => { });
        [880, 1174].forEach((frequency, i) => {
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();
            oscillator.frequency.value = frequency;
            oscillator.type = "sine";
            gain.gain.setValueAtTime(0.0001, ctx.currentTime + i * 0.13);
            gain.gain.exponentialRampToValueAtTime(
                0.12,
                ctx.currentTime + i * 0.13 + 0.02,
            );
            gain.gain.exponentialRampToValueAtTime(
                0.0001,
                ctx.currentTime + i * 0.13 + 0.18,
            );
            oscillator.connect(gain);
            gain.connect(ctx.destination);
            oscillator.start(ctx.currentTime + i * 0.13);
            oscillator.stop(ctx.currentTime + i * 0.13 + 0.2);
        });
        // Keep the unlocked context alive so future polling alerts can chime too.
    } catch (_) { }
}
function signalNewNotifications(newUnread) {
    if (!newUnread?.length) return;
    const isAdminUser = ["admin", "manager"].includes(
        String(state.user?.role || "").toLowerCase(),
    );
    const relevantAlerts = isAdminUser
        ? newUnread.filter((n) =>
            ["absence_request", "rest_day_request"].includes(String(n.type)),
        )
        : newUnread.filter((n) =>
            [
                "request_decision",
                "schedule_update",
                "request_reply",
                "absence_decision",
                "rest_day_decision",
            ].includes(String(n.type)),
        );
    if (!relevantAlerts.length) return;
    const bell = $("#notificationBtn");
    if (bell) {
        bell.classList.remove("notification-ringing");
        void bell.offsetWidth;
        bell.classList.add("notification-ringing");
        setTimeout(() => bell.classList.remove("notification-ringing"), 2600);
    }
    playNotificationChime();
    if ("Notification" in window && Notification.permission === "granted") {
        relevantAlerts.forEach((n) => {
            try {
                new Notification(n.title || "ShiftMate update", {
                    body: n.message || "You have a new update.",
                });
            } catch (_) { }
        });
    }
}
async function refreshNotifications(silent = true) {
    if (!state.token) return;
    try {
        const data = await api("/notifications");
        const incoming = Array.isArray(data) ? data : data?.data || [];
        const oldIds = new Set(
            (state.notifications || []).map((n) => String(n.id)),
        );
        const newUnread = incoming.filter(
            (n) => !n.is_read && !oldIds.has(String(n.id)),
        );
        state.notifications = incoming;
        renderNotifications();
        if (!silent && newUnread.length)
            toast(
                newUnread.length === 1
                    ? "New notification received."
                    : `${newUnread.length} new notifications received.`,
            );
        signalNewNotifications(newUnread);
    } catch (e) {
        console.warn("Notifications could not refresh:", e.message);
    }
}
async function markNotificationRead(id) {
    const n = state.notifications.find((x) => String(x.id) === String(id));
    if (!n || n.is_read) return;
    try {
        await api(`/notifications/${id}`, {
            method: "PATCH",
            body: JSON.stringify({ is_read: true }),
        });
        n.is_read = true;
        renderNotifications();
    } catch (e) {
        toast(`Could not update notification: ${e.message}`);
    }
}
async function loadResource(key, endpoint) {
    try {
        const data = await api(endpoint);
        state[key] = Array.isArray(data) ? data : data?.data || [];
    } catch (e) {
        console.error(`ShiftMate API request failed for ${endpoint}:`, e);
        state[key] = [];
        setNotice(`Could not load ${endpoint}. ${e.message}`);
    }
}
async function loadAll() {
    clearNotice();
    const priorNotificationIds = new Set(
        (state.notifications || []).map((n) => String(n.id)),
    );
    const isStaff = ["admin", "manager"].includes(
        String(state.user?.role || "employee").toLowerCase(),
    );
    const resources = isStaff
        ? [
            ["employees", "/employees"],
            ["schedules", "/schedules"],
            ["shifts", "/shifts"],
            ["requests", "/rest-day-requests"],
            ["absences", "/absence-requests"],
            ["restDayAssignments", "/rest-day-assignments"],
        ]
        : [
            ["employees", "/employees"],
            ["schedules", "/schedules"],
            ["requests", "/rest-day-requests"],
            ["absences", "/absence-requests"],
            ["restDayAssignments", "/rest-day-assignments"],
        ];
    if (String(state.user?.role || "").toLowerCase() === "admin") {
        resources.push(["pendingRegistrations", "/admin/registrations/pending"]);
    }
    resources.push(["notifications", "/notifications"]);
    const results = await Promise.allSettled(
        resources.map(async ([key, path]) => {
            const data = await api(path);
            state[key] = Array.isArray(data) ? data : data?.data || [];
            return { key, path };
        }),
    );
    const failures = results
        .map((result, index) => ({ result, resource: resources[index] }))
        .filter((item) => item.result.status === "rejected")
        .map((item) => ({
            path: item.resource[1],
            message: item.result.reason?.message || "Request failed",
        }));
    const justArrived = (state.notifications || []).filter(
        (n) => !n.is_read && !priorNotificationIds.has(String(n.id)),
    );
    renderAll();
    renderNotifications();
    signalNewNotifications(justArrived);
    if (!isStaff) maybeShowRestDayPopup();
    const linkedEmployeeForProfile = state.employees.find(
        (employee) => String(employee.user_id) === String(state.user?.id),
    );
    const employeeIdDisplay = $("#profileEmployeeId");
    if (employeeIdDisplay) {
        const row = employeeIdDisplay.closest(".profile-info-item");
        const employeeRole =
            String(state.user?.role || "employee").toLowerCase() === "employee";
        if (row) row.classList.toggle("hidden", !employeeRole);
        employeeIdDisplay.textContent =
            linkedEmployeeForProfile?.employee_number
                ? String(linkedEmployeeForProfile.employee_number)
                : "Pending assignment";
    }
    if (!isStaff) {
        const me = state.user || {};
        const emp = state.employees.find(
            (e) => String(e.user_id) === String(me.id),
        );
        const ownSchedules = state.schedules.filter(
            (x) =>
                String(
                    x.user_id || x.employee?.user_id || x.employee_id || "",
                ) === String(me.id) ||
                (emp && String(x.employee_id) === String(emp.id)),
        );
        const ownRest = state.requests.filter(
            (x) =>
                String(x.employee?.user_id || x.user_id || "") ===
                String(me.id) ||
                (emp && String(x.employee_id) === String(emp.id)),
        );
        const ownAbs = state.absences.filter(
            (x) =>
                String(x.employee?.user_id || x.user_id || "") ===
                String(me.id) ||
                (emp && String(x.employee_id) === String(emp.id)),
        );
        if ($("#myShiftCount"))
            $("#myShiftCount").textContent = ownSchedules.length;
        if ($("#myRestCount")) $("#myRestCount").textContent = ownRest.length;
        if ($("#myAbsenceCount"))
            $("#myAbsenceCount").textContent = ownAbs.length;
        if ($("#employeeGreeting"))
            $("#employeeGreeting").textContent =
                `Welcome back, ${fullName(me).split(" ")[0] || "there"} 👋`;
    }
    setConnection(failures.length === 0);
    if (failures.length) {
        setNotice(
            `Some database records could not be loaded: ${failures.map((failure) => `${failure.path} (${failure.message})`).join("; ")}. Check the database tables and Laravel log.`,
        );
    } else {
        clearNotice();
    }
}
const field = (name, label, type = "text", opts = {}) => ({
    name,
    label,
    type,
    ...opts,
});
function formSchema(type, record = {}) {
    const commonStatus = () =>
        field("status", "Status", "select", {
            options: [
                ["active", "Active"],
                ["inactive", "Inactive"],
            ],
        });
    if (type === "employee")
        return {
            title: "Set employee position",
            subtitle:
                "Choose the employee's position once. It will be locked after saving.",
            endpoint: "employees",
            fields: [
                field("position", "Position", "select", {
                    required: true,
                    options: [
                        ["Barista", "Barista"],
                        ["Cashier", "Cashier"],
                    ],
                }),
            ],
        };
    if (type === "shift")
        return {
            title: record.id ? "Edit shift" : "Add shift",
            subtitle:
                "Set the shift name and working hours used in the calendar.",
            endpoint: "shifts",
            fields: [
                field("shift_name", "Shift name", "text", { required: true }),
                field("start_time", "Start time", "time", { required: true }),
                field("end_time", "End time", "time", { required: true }),
                field("description", "Description", "textarea", {
                    required: true,
                    full: true,
                }),
            ],
        };
    if (type === "schedule")
        return {
            title: record.id ? "Edit schedule" : "Create schedule",
            subtitle: "Assign a shift to a user for a work date.",
            endpoint: "schedules",
            fields: [
                field("user_id", "Employee ID", "select", {
                    required: true,
                    options: state.employees.map((e) => [
                        String(e.user_id || e.user?.id || e.id),
                        `${e.employee_number || e.id} — ${fullName(e)}`,
                    ]),
                }),
                field("shift_id", "Shift schedule", "select", {
                    required: true,
                    options: state.shifts
                        .filter((s) =>
                            [
                                "morning",
                                "afternoon",
                                "evening",
                                "restday",
                            ].includes(
                                String(s.shift_name || "").toLowerCase(),
                            ),
                        )
                        .sort((a, b) => {
                            const order = {
                                morning: 1,
                                afternoon: 2,
                                evening: 3,
                                restday: 4,
                            };
                            return (
                                (order[
                                    String(a.shift_name || "").toLowerCase()
                                ] || 99) -
                                (order[
                                    String(b.shift_name || "").toLowerCase()
                                ] || 99)
                            );
                        })
                        .map((s) => {
                            const labels = {
                                morning: "Morning",
                                afternoon: "Afternoon",
                                evening: "Night",
                                restday: "Rest day",
                            };
                            return [
                                String(s.id),
                                labels[
                                String(s.shift_name || "").toLowerCase()
                                ] || shiftDisplayName(s.shift_name, s.id),
                            ];
                        }),
                }),
            ],
        };
    if (type === "restRequest") {
        const currentRestDay = currentAssignedRestDay();
        return {
            title: "Request rest-day change",
            subtitle:
                "Your current rest day is assigned by the administrator. Choose the new date you would like to request.",
            endpoint: "rest-day-requests",
            fields: [
                field(
                    "current_rest_day",
                    "Current rest day assigned by admin",
                    "date",
                    {
                        required: Boolean(currentRestDay),
                        readonly: true,
                        value: currentRestDay,
                    },
                ),
                field(
                    "requested_rest_day",
                    "New rest day you are requesting",
                    "date",
                    {
                        required: true,
                    },
                ),
                field("reason", "Reason for changing your rest day", "select", {
                    required: true,
                    full: true,
                    options: [
                        ["Personal commitment", "Personal commitment"],
                        ["Family matter", "Family matter"],
                        ["Health appointment", "Health appointment"],
                        [
                            "School / important appointment",
                            "School / important appointment",
                        ],
                        ["Others", "Others"],
                    ],
                }),
            ],
        };
    }
    if (type === "absenceRequest")
        return {
            title: "Report unexpected absence",
            subtitle: "Choose the date and explain your emergency absence.",
            endpoint: "absence-requests",
            fields: [
                field("absence_date", "Absence date", "date", {
                    required: true,
                }),
                field("description", "Reason", "textarea", {
                    required: true,
                    full: true,
                }),
            ],
        };
    return null;
}
function openForm(type, record = null) {
    const schema = formSchema(type, record || {});
    if (!schema) {
        toast("This form is not available.");
        return;
    }
    state.editing = { type, record, endpoint: schema.endpoint };
    $("#modalTitle").textContent = schema.title;
    $("#modalSubtitle").textContent = schema.subtitle;
    $("#formError").textContent = "";
    $("#modalFields").innerHTML = schema.fields
        .map((f) => {
            const linkedForForm = record?.user_id
                ? state.employees.find(
                    (e) =>
                        String(e.user_id || e.user?.id) ===
                        String(record.user_id),
                )
                : null;
            const value =
                record?.[f.name] ??
                f.value ??
                (type === "schedule" && f.name === "position"
                    ? linkedForForm?.position || "Barista"
                    : f.name === "status"
                        ? "active"
                        : "");
            const cls = f.full ? "field-full" : "";
            let control = "";
            if (f.type === "select") {
                control = `<select name="${f.name}" ${f.required ? "required" : ""}><option value="">Choose ${esc(f.label.toLowerCase())}</option>${(f.options || []).map((o) => `<option value="${esc(o[0])}" ${String(value) === String(o[0]) ? "selected" : ""}>${esc(o[1])}</option>`).join("")}</select>`;
            } else if (f.type === "multiselect") {
                control = `<select name="${f.name}" multiple size="${Math.min(Math.max((f.options || []).length, 3), 6)}" class="multi-select-control" ${f.required ? "required" : ""}>${(f.options || []).map((o) => `<option value="${esc(o[0])}">${esc(o[1])}</option>`).join("")}</select><small class="field-hint">Hold Ctrl (Windows) or Command (Mac) to select multiple shifts.</small>`;
            } else if (f.type === "textarea") {
                control = `<textarea name="${f.name}" ${f.required ? "required" : ""}>${esc(value)}</textarea>`;
            } else {
                const val =
                    f.type === "datetime-local" && value
                        ? String(value).replace(" ", "T").slice(0, 16)
                        : f.type === "time" && value
                            ? String(value).slice(0, 5)
                            : value;
                control = `<input name="${f.name}" type="${f.type}" value="${esc(val)}" ${f.required ? "required" : ""} ${f.readonly ? 'readonly aria-readonly="true"' : ""} ${["datetime-local", "time"].includes(f.type) ? 'step="60"' : ""}>`;
            }
            return `<label class="${cls}">${esc(f.label)}${control}</label>`;
        })
        .join("");
    if (type === "restRequest") {
        $("#modalFields").insertAdjacentHTML(
            "beforeend",
            '<label class="field-full hidden" id="otherReasonWrap">Please specify your reason<textarea name="other_reason" id="otherReasonInput" maxlength="500"></textarea></label>',
        );
        const reasonSelect = $("#modalFields [name=reason]");
        const otherWrap = $("#otherReasonWrap");
        const toggleOther = () => {
            const show = reasonSelect?.value === "Others";
            otherWrap?.classList.toggle("hidden", !show);
            const input = $("#otherReasonInput");
            if (input) input.required = show;
        };
        reasonSelect?.addEventListener("change", toggleOther);
        toggleOther();
    }
    enhanceDateInputs($("#modalFields"));
    $("#modalBackdrop").classList.remove("hidden");
}
// Date fields always read mm-dd-yy (the browser otherwise shows locale text such as "Nov 25, 2026").
// The native input stays in charge of the value and the calendar picker; only its text is replaced.
function enhanceDateInputs(root = document) {
    root.querySelectorAll('input[type="date"]:not([data-mm-ready]):not(#scheduleAnchorDate)').forEach((input) => {
        input.dataset.mmReady = "1";
        input.setAttribute("lang", "en-US");
        const style = getComputedStyle(input);
        const wrap = document.createElement("span");
        wrap.className = "date-mm-wrap";
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);
        const text = document.createElement("span");
        text.className = "date-mm-text";
        text.style.paddingLeft = style.paddingLeft;
        text.style.color = style.color;
        wrap.appendChild(text);
        input.classList.add("date-mm-input");
        const refresh = () => {
            const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(input.value || "");
            text.textContent = m ? `${m[2]}-${m[3]}-${m[1].slice(2)}` : "mm-dd-yy";
            text.classList.toggle("is-empty", !m);
        };
        input.addEventListener("input", refresh);
        input.addEventListener("change", refresh);
        input.addEventListener("click", () => {
            try { input.showPicker?.(); } catch (e) { /* picker unavailable (e.g. readonly) */ }
        });
        refresh();
    });
}
function closeModal() {
    $("#modalBackdrop").classList.add("hidden");
    state.editing = null;
}
async function saveRecord(event) {
    event.preventDefault();
    if (!state.editing) return;
    const { type, record, endpoint } = state.editing;
    const formData = new FormData(event.currentTarget);
    const values = Object.fromEntries(formData);
    if (type === "restRequest") {
        if (values.reason === "Others") {
            values.reason = String(values.other_reason || "").trim();
            if (!values.reason) {
                $("#formError").textContent = "Please type your reason.";
                return;
            }
        }
        delete values.other_reason;
    }
    const selectedShiftIds =
        type === "schedule" && !record?.id
            ? formData.getAll("shift_ids").map(Number).filter(Number.isFinite)
            : [];
    if (type === "employee" || type === "schedule") {
        if (values.user_id) values.user_id = Number(values.user_id);
        if (values.shift_id) values.shift_id = Number(values.shift_id);
        if (values.employee_number)
            values.employee_number = Number(values.employee_number);
    }
    if (type === "schedule") {
        // Never create or edit a schedule for a date before today.
        const requestedDate = String(
            record?.work_date ||
            $("#scheduleAnchorDate")?.value ||
            new Date().toISOString().slice(0, 10),
        ).slice(0, 10);
        if (requestedDate < scheduleDateKey(new Date())) {
            $("#formError").textContent =
                "Past dates are closed. You can only assign shifts for today or future dates.";
            return;
        }
        // The calendar cell supplies the date; the standalone action defaults to today.
        values.work_date =
            record?.work_date ||
            $("#scheduleAnchorDate")?.value ||
            new Date().toISOString().slice(0, 10);
        // Schedule records use the database enum: active/inactive only.
        // Calendar UI labels such as "scheduled" are not valid database statuses.
        values.status = record?.id
            ? record.status === "inactive"
                ? "inactive"
                : "active"
            : "active";
        // Position is managed only from Edit Employee and is never part of a schedule payload.
        delete values.position;
        if (!values.shift_id) {
            $("#formError").textContent = "Choose a shift schedule.";
            return;
        }
        const chosenShift = state.shifts.find((item) => Number(item.id) === Number(values.shift_id));
        const chosenName = String(chosenShift?.shift_name || "").trim().toLowerCase();
        const isRestDayChoice = ["restday", "rest day"].includes(chosenName);
        // Time in / time out are set automatically by the server from the shift template,
        // and the schedule is locked as soon as it is assigned.
        delete values.start_time;
        delete values.end_time;
    }
    if (type === "shift") {
        values.start_time =
            values.start_time.length === 5
                ? values.start_time + ":00"
                : values.start_time;
        values.end_time =
            values.end_time.length === 5
                ? values.end_time + ":00"
                : values.end_time;
    }
    if (type === "employee" && record?.id && !values.user_id)
        delete values.user_id;
    if (type === "employee") {
        delete values.department;
        delete values.contact_number;
    }
    if (type === "restRequest") {
        if (!values.current_rest_day) {
            $("#formError").textContent =
                "No rest day is currently assigned to your account. Please contact your administrator before requesting a change.";
            return;
        }
        if (!values.requested_rest_day) {
            $("#formError").textContent =
                "Choose the new rest day you want to request.";
            return;
        }
        if (values.current_rest_day === values.requested_rest_day) {
            $("#formError").textContent =
                "Your requested rest day must be different from your currently assigned rest day.";
            return;
        }
    }
    if (type === "restRequest" || type === "absenceRequest") {
        const employee = state.employees.find(
            (e) => String(e.user_id) === String(state.user?.id),
        );
        if (!employee) {
            $("#formError").textContent =
                "Your employee profile is not linked to this account yet. Contact your administrator.";
            return;
        }
        values.employee_id = String(employee.id);
        values.status = "pending";
        values.reviewed_by = state.user.id;
        values.reviewed_at = new Date()
            .toISOString()
            .slice(0, 19)
            .replace("T", " ");
    }
    const btn = $("#saveRecord");
    btn.disabled = true;
    btn.textContent = "Saving…";
    try {
        const url = record?.id ? `/${endpoint}/${record.id}` : `/${endpoint}`;
        await api(url, {
            method: record?.id ? "PUT" : "POST",
            body: JSON.stringify(values),
        });
        closeModal();
        toast(
            record?.id
                ? "Record updated successfully."
                : "Record created successfully.",
        );
        await loadAll();
    } catch (e) {
        $("#formError").textContent = e.message;
    } finally {
        btn.disabled = false;
        btn.innerHTML = "Save record <span>→</span>";
    }
}
async function deleteRecord(endpoint, id) {
    if (!confirm("Delete this record? This action cannot be undone.")) return;
    try {
        await api(`/${endpoint}/${id}`, { method: "DELETE" });
        toast("Record deleted.");
        await loadAll();
    } catch (e) {
        toast(e.message);
    }
}
async function reviewRequest(endpoint, id, status) {
    const list =
        endpoint === "rest-day-requests" ? state.requests : state.absences;
    const rec = list.find((x) => String(x.id) === String(id));
    if (!rec) return;
    const body = {
        status,
        reviewed_by: String(state.user?.id || ""),
        reviewed_at: new Date().toISOString().slice(0, 19).replace("T", " "),
    };
    try {
        await api(`/${endpoint}/${id}`, {
            method: "PUT",
            body: JSON.stringify(body),
        });
        toast(`Request ${status}.`);
        await loadAll();
    } catch (e) {
        toast(`Could not update request: ${e.message}`);
    }
}
async function refreshUser() {
    try {
        state.user = await api("/me");
    } catch (e) {
        // Only an invalid/expired token should log the person out.
        // A network hiccup or a temporary 500 must NOT lose the session on refresh.
        if (/unauthenticated|unauthorized|401/i.test(String(e?.message || "")) || e?.status === 401) {
            signOut(false);
            showAuth(false);
            return;
        }
        try { await new Promise((r) => setTimeout(r, 1200)); state.user = await api("/me"); }
        catch (e2) {
            if (e2?.status === 401) { signOut(false); showAuth(false); return; }
            showAuth(false);
            toast("Cannot reach the server right now. Please refresh in a moment.");
            return;
        }
    }
    showApp();
    try { await loadAll(); } catch (e) { toast("Some data could not be loaded. Please refresh."); }
}
$("#loginForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    setAuthError("");
    const v = Object.fromEntries(new FormData(e.currentTarget));
    const btn = $('button[type="submit"]', e.currentTarget);
    btn.disabled = true;
    try {
        await login(v.email, v.password);
    } catch (err) {
        const raw = String(err.message || "").toLowerCase();
        if (err.code === "EMAIL_NOT_VERIFIED") {
            lastVerificationEmail = v.email;
            $("#resendVerification")?.classList.remove("hidden");
        }
        setAuthError(
            (err.status === 401 ||
                raw.includes("provided credentials are incorrect")) &&
                err.code !== "PORTAL_ROLE_MISMATCH"
                ? "The email address or password you entered is incorrect. Please verify your credentials and try again."
                : err.message,
        );
    } finally {
        btn.disabled = false;
    }
});
$("#resendVerification")?.addEventListener("click", async () => {
    const email = lastVerificationEmail || $("#loginForm input[name=email]")?.value?.trim();
    if (!email) {
        setAuthError("Enter your registered email address first.");
        return;
    }
    const button = $("#resendVerification");
    if (button) button.disabled = true;
    try {
        const result = await api("/email/verification-notification", {
            method: "POST",
            body: JSON.stringify({ email }),
        });
        setAuthError(result.message || "If verification is needed, a new email has been sent.");
    } catch (error) {
        setAuthError(error.message || "Could not resend the verification email.");
    } finally {
        if (button) button.disabled = false;
    }
});

$("#registerForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    setAuthError("");
    const btn = $('button[type="submit"]', e.currentTarget);
    btn.disabled = true;
    try {
        await register(e.currentTarget);
    } catch (err) {
        setAuthError(err.message);
    } finally {
        btn.disabled = false;
    }
});
$$("[data-portal]").forEach((b) =>
    b.addEventListener("click", () => {
        // Clear a previously saved session when choosing a different portal.
        // The server will still validate the account role on login.
        if (state.token || state.user) {
            state.token = "";
            state.user = null;
            localStorage.removeItem("shiftmate_token"); try { localStorage.removeItem("shiftmate_page"); } catch (e) { }
            state.employees = [];
            state.schedules = [];
            state.shifts = [];
            state.requests = [];
            state.absences = [];
            $("#appView").classList.add("hidden");
        }
        setPortal(b.dataset.portal);
        showAuth(false);
    }),
);
$("#backToPortal")?.addEventListener("click", () => {
    $("#authView").style.display = "none";
    $("#authView").classList.add("hidden");
    $("#landingView").style.display = "";
    $("#landingView").classList.remove("hidden");
    $("#landingView .landing-shade")?.classList.remove("portal-view-hidden");
    $("#landingView .landing-hero")?.classList.remove("portal-view-hidden");
    state.portal = "employee";
    showAuth(false);
});
$("#showRegister").addEventListener("click", () => {
    if (state.portal === "admin") {
        setAuthError(
            "Administrator accounts are managed by the system owner. Please use the provided admin credentials.",
        );
        return;
    }
    showAuth(true);
});
$("#showLogin").addEventListener("click", () => showAuth(false));
// Password recovery uses Laravel's password broker and signed reset token.
$("#showForgotPassword")?.addEventListener("click", () => {
    $("#loginForm").classList.add("hidden");
    $("#registerForm").classList.add("hidden");
    $("#forgotPasswordForm").classList.remove("hidden");
    $("#resetPasswordForm").classList.add("hidden");
    $(".portal-switch")?.classList.add("hidden");
    $("#authTitle").textContent = "Reset your password";
    $("#authSubtitle").textContent =
        "Enter your registered email to reset your password.";
    setAuthError("");
});
$("#backToLogin")?.addEventListener("click", () => showAuth(false));
$("#resetBackToLogin")?.addEventListener("click", () => {
    history.replaceState({}, "", location.pathname);
    showAuth(false);
});
$("#forgotPasswordForm")?.addEventListener("submit", async (event) => {
    event.preventDefault();
    setAuthError("");
    const btn = $('button[type="submit"]', event.currentTarget);
    btn.disabled = true;
    try {
        const values = Object.fromEntries(new FormData(event.currentTarget));
        const data = await api("/forgot-password", {
            method: "POST",
            body: JSON.stringify(values),
        });
        if (data.reset_url) {
            // Local development: skip showing/copying a reset URL and open the reset form immediately.
            const resetUrl = new URL(data.reset_url, window.location.origin);
            const token =
                resetUrl.pathname.match(/\/reset-password\/([^/]+)/)?.[1] ||
                resetUrl.searchParams.get("token") ||
                "";
            const email = resetUrl.searchParams.get("email") || values.email;
            if (!token || !email)
                throw new Error(
                    "Could not prepare the password reset form. Please try again.",
                );
            history.replaceState(
                {},
                "",
                `${location.pathname}?token=${encodeURIComponent(token)}&email=${encodeURIComponent(email)}`,
            );
            $("#forgotPasswordForm").classList.add("hidden");
            $("#loginForm").classList.add("hidden");
            $("#registerForm").classList.add("hidden");
            $("#resetPasswordForm").classList.remove("hidden");
            $(".portal-switch")?.classList.add("hidden");
            $("#authTitle").textContent = "Choose a new password";
            $("#authSubtitle").textContent =
                "Your account was found. Enter and confirm your new password.";
            setAuthError("");
            toast("Account found. Choose your new password.");
        } else {
            setAuthError(
                data.message ||
                "If your account exists, password reset instructions have been sent to your email.",
            );
        }
    } catch (error) {
        setAuthError(error.message);
    } finally {
        btn.disabled = false;
    }
});
$("#resetPasswordForm")?.addEventListener("submit", async (event) => {
    event.preventDefault();
    setAuthError("");
    const btn = $('button[type="submit"]', event.currentTarget);
    btn.disabled = true;
    try {
        const values = Object.fromEntries(new FormData(event.currentTarget));
        const resetParams = new URLSearchParams(location.search);
        values.email = resetParams.get("email") || "";
        values.token =
            resetParams.get("token") ||
            location.pathname.match(/\/reset-password\/([^/]+)/)?.[1] ||
            "";
        const data = await api("/reset-password", {
            method: "POST",
            body: JSON.stringify(values),
        });
        toast(data.message || "Password reset successfully. Please log in.");
        history.replaceState({}, "", location.pathname);
        showAuth(false);
        $("#loginForm").reset();
    } catch (error) {
        setAuthError(error.message);
    } finally {
        btn.disabled = false;
    }
});
const resetQuery = new URLSearchParams(location.search);
const resetTokenFromPath = location.pathname.match(
    /\/reset-password\/([^/]+)/,
)?.[1];
if (
    (resetQuery.has("token") || resetTokenFromPath) &&
    resetQuery.has("email")
) {
    showAuth(false);
    $("#loginForm").classList.add("hidden");
    $("#forgotPasswordForm")?.classList.add("hidden");
    $("#resetPasswordForm")?.classList.remove("hidden");
    $(".portal-switch")?.classList.add("hidden");
    $("#authTitle").textContent = "Choose a new password";
    $("#authSubtitle").textContent =
        "Create a strong password for your account.";
}

function openProfile() {
    $("#userDropdown").classList.add("hidden");
    $("#userMenuBtn").setAttribute("aria-expanded", "false");
    $("#profileEditForm").classList.add("hidden");
    $("#profileReadOnly").classList.remove("hidden");
    $("#editProfileBtn").classList.remove("hidden");
    $("#profileEditError").textContent = "";
    $("#profileBackdrop").classList.remove("hidden");
}
function closeProfile() {
    $("#profileBackdrop").classList.add("hidden");
}
$("#editProfileBtn").addEventListener("click", () => {
    $("#profileReadOnly").classList.add("hidden");
    $("#profileEditForm").classList.remove("hidden");
    $("#editProfileBtn").classList.add("hidden");
    const isEmployee =
        String(state.user?.role || "employee").toLowerCase() === "employee";
    const contactLabel = $("#contactNumberLabel");
    if (contactLabel) contactLabel.classList.toggle("hidden", !isEmployee);
    $("#profileFullNameInput").focus();
});
$("#profileEditForm").addEventListener("submit", async (event) => {
    event.preventDefault();
    const button = $("#saveProfileBtn");
    const values = Object.fromEntries(new FormData(event.currentTarget));
    delete values.contact_number;
    button.disabled = true;
    button.textContent = "Saving…";
    $("#profileEditError").textContent = "";
    try {
        const data = await api("/profile", {
            method: "PATCH",
            body: JSON.stringify(values),
        });
        state.user = data.user || state.user;
        showApp();
        $("#profileEditForm").classList.add("hidden");
        $("#profileReadOnly").classList.remove("hidden");
        $("#editProfileBtn").classList.remove("hidden");
        toast("Profile updated successfully.");
    } catch (error) {
        $("#profileEditError").textContent =
            error.message || "Could not update profile.";
    } finally {
        button.disabled = false;
        button.textContent = "Save profile";
    }
});
function openLogoutConfirm() {
    $("#userDropdown").classList.add("hidden");
    $("#userMenuBtn").setAttribute("aria-expanded", "false");
    $("#logoutBackdrop").classList.remove("hidden");
}
function closeLogoutConfirm() {
    $("#logoutBackdrop").classList.add("hidden");
}
$("#openProfileBtn").addEventListener("click", openProfile);
$("#closeProfileBtn").addEventListener("click", closeProfile);
$("#doneProfileBtn").addEventListener("click", closeProfile);
$("#profileBackdrop").addEventListener("click", (e) => {
    if (e.target.id === "profileBackdrop") closeProfile();
});
$("#topLogoutBtn").addEventListener("click", openLogoutConfirm);
$("#cancelLogoutBtn").addEventListener("click", closeLogoutConfirm);
$("#confirmLogoutBtn").addEventListener("click", async () => {
    closeLogoutConfirm();
    await signOut(true);
});
$("#logoutBackdrop").addEventListener("click", (e) => {
    if (e.target.id === "logoutBackdrop") closeLogoutConfirm();
});
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        closeProfile();
        closeLogoutConfirm();
    }
});
$("#userMenuBtn").addEventListener("click", () => {
    const menu = $("#userDropdown");
    const opening = menu.classList.contains("hidden");
    menu.classList.toggle("hidden", !opening);
    $("#userMenuBtn").setAttribute("aria-expanded", String(opening));
});
document.addEventListener("click", (e) => {
    if (!e.target.closest(".user-menu-wrap")) {
        $("#userDropdown").classList.add("hidden");
        $("#userMenuBtn").setAttribute("aria-expanded", "false");
    }
});
const profilePhotoInput = $("#profilePhotoInput");
if (profilePhotoInput)
    profilePhotoInput.addEventListener("change", async (event) => {
        const file = event.target.files?.[0];
        if (!file) return;
        if (
            !/^image\/(png|jpeg|webp)$/.test(file.type) ||
            file.size > 2 * 1024 * 1024
        ) {
            toast("Choose a PNG, JPG or WebP image under 2 MB.");
            event.target.value = "";
            return;
        }
        const form = new FormData();
        form.append("photo", file);
        try {
            let response;
            try {
                response = await fetch(`${CONFIG.apiBase}/profile/photo`, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        ...(state.token
                            ? { Authorization: `Bearer ${state.token}` }
                            : {}),
                    },
                    body: form,
                });
            } catch (networkError) {
                showApiBrowserAlert(
                    `Cannot connect to ${CONFIG.apiBase}/profile/photo. Check that Laravel is running.`,
                    "network:/profile/photo",
                );
                throw networkError;
            }
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                showApiBrowserAlert(
                    data.message ||
                    `Profile photo upload failed (HTTP ${response.status}).`,
                    `http:/profile/photo:${response.status}`,
                );
                throw new Error(
                    data.message || "Could not upload profile photo.",
                );
            }
            state.user = data.user || state.user;
            showApp();
            toast("Profile photo updated.");
        } catch (error) {
            toast(error.message || "Could not upload profile photo.");
        } finally {
            event.target.value = "";
        }
    });
$("#reloadRegistrations")?.addEventListener("click", loadAll);
if ($("#reloadMyRequests"))
    $("#reloadMyRequests").addEventListener("click", loadAll);
$("#menuBtn").addEventListener("click", () =>
    $("#sidebar").classList.toggle("open"),
);
$("#closeModal").addEventListener("click", closeModal);
$("#cancelModal").addEventListener("click", closeModal);
$("#modalBackdrop").addEventListener("click", (e) => {
    if (e.target.id === "modalBackdrop") closeModal();
});
$("#recordForm").addEventListener("submit", saveRecord);
$("#mainNav").addEventListener("click", (e) => {
    const b = e.target.closest("[data-page]");
    if (b) showPage(b.dataset.page);
});
document.addEventListener("click", (e) => {
    const go = e.target.closest("[data-go-page]");
    if (go) showPage(go.dataset.goPage);
    const add = e.target.closest("[data-open-form]");
    if (add) openForm(add.dataset.openForm);
    const edit = e.target.closest("[data-edit]");
    if (edit) {
        const map = {
            employee: "employees",
            shift: "shifts",
        };
        if (edit.dataset.edit === "schedule") {
            toast(
                "Schedules are locked after assignment and cannot be edited.",
            );
            return;
        }
        const rec = state[map[edit.dataset.edit]]?.find(
            (x) => String(x.id) === String(edit.dataset.id),
        );
        if (rec) openForm(edit.dataset.edit, rec);
    }
    const del = e.target.closest("[data-delete]");
    if (del) deleteRecord(del.dataset.delete, del.dataset.id);
    const assignRest = e.target.closest("[data-assign-rest-day]");
    if (assignRest) {
        assignRestDay(assignRest.dataset.assignRestDay, assignRest.dataset.employeeName, assignRest);
        return;
    }
    const registrationReview = e.target.closest("[data-registration-review]");
    if (registrationReview) {
        reviewRegistration(registrationReview.dataset.registrationReview, registrationReview.dataset.decision, registrationReview);
        return;
    }
    const registrationNotice = e.target.closest('[data-notification-card]');
    if (registrationNotice && registrationNotice.dataset.notificationType === "employee_registration") {
        showPage("registrations");
    }
    const review = e.target.closest("[data-review]");
    if (review)
        reviewRequest(
            review.dataset.review,
            review.dataset.id,
            review.dataset.status,
        );
});
const scheduleAnchor = $("#scheduleAnchorDate");
if (scheduleAnchor) {
    scheduleAnchor.value = scheduleDateKey(new Date());
    scheduleAnchor.addEventListener("change", renderSchedules);
}
$("#schedulePrevWeek")?.addEventListener("click", () => {
    const d = scheduleDateValue(scheduleAnchor.value);
    d.setDate(d.getDate() - 7);
    scheduleAnchor.value = scheduleDateKey(d);
    renderSchedules();
});
$("#scheduleNextWeek")?.addEventListener("click", () => {
    const d = scheduleDateValue(scheduleAnchor.value);
    d.setDate(d.getDate() + 7);
    scheduleAnchor.value = scheduleDateKey(d);
    renderSchedules();
});
$("#scheduleToday")?.addEventListener("click", () => {
    scheduleAnchor.value = scheduleDateKey(new Date());
    renderSchedules();
});

document.addEventListener("change", async (e) => {
    const dropdown = e.target.closest(
        ".schedule-shift-dropdown[data-schedule-date]",
    );
    if (!dropdown || !dropdown.value) return;

    const shiftId = Number(dropdown.value);
    const userId = Number(dropdown.dataset.scheduleUser);
    const workDate = dropdown.dataset.scheduleDate;
    if (!shiftId || !userId || !workDate) return;
    if (scheduleDateIsPast(workDate)) {
        toast("Past dates are closed. Choose today or a future date.");
        dropdown.value = "";
        return;
    }

    const selectedShift = state.shifts.find((shift) => Number(shift.id) === shiftId);
    const selectedShiftName = String(selectedShift?.shift_name || "").trim().toLowerCase();
    const isRestDay = ["restday", "rest day"].includes(selectedShiftName);
    let chosenTimes = {};
    if (!isRestDay) {
        // Admin picks Time in / Time out from dropdowns; the shift template is the default.
        chosenTimes = await pickShiftTimes(
            shiftDisplayName(selectedShiftName, shiftId),
            String(selectedShift?.start_time || "").slice(0, 5),
            String(selectedShift?.end_time || "").slice(0, 5),
        );
        if (!chosenTimes) {
            dropdown.value = "";
            return;
        }
    }
    dropdown.disabled = true;
    try {
        await api("/schedules", {
            method: "POST",
            body: JSON.stringify({
                user_id: userId,
                shift_id: shiftId,
                work_date: workDate,
                status: "active",
                ...chosenTimes,
            }),
        });
        toast(isRestDay ? "Rest day assigned. It is now the employee’s current rest day." : "Shift assigned and locked with the time in and time out you selected.");
        await loadAll();
    } catch (error) {
        toast(error?.message || "Unable to assign shift.");
        dropdown.value = "";
        dropdown.disabled = false;
    }
});
// Admin assigns an employee's rest day. It becomes the employee's CURRENT rest day,
// shown in their request form, and appears on the schedule calendar.
async function assignRestDay(userId, employeeName, button) {
    const existing = (state.restDayAssignments || []).find(
        (r) => String(r.user_id || r.user?.id || "") === String(userId),
    );
    const chosen = await pickRestDate(employeeName, existing?.rest_day ? String(existing.rest_day).slice(0, 10) : "");
    if (!chosen) return;
    if (button) button.disabled = true;
    try {
        await api("/rest-day-assignments", {
            method: "POST",
            body: JSON.stringify({ user_id: Number(userId), rest_day: chosen }),
        });
        toast("Rest day saved. It is now the employee’s current rest day.");
        await loadAll();
    } catch (error) {
        toast(error?.message || "Could not save the rest day.");
    } finally {
        if (button) button.disabled = false;
    }
}
function pickRestDate(employeeName, currentValue) {
    return new Promise((resolve) => {
        const overlay = document.createElement("div");
        overlay.style.cssText = "position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:9999;";
        overlay.innerHTML = `<div role="dialog" aria-modal="true" style="background:#fff;color:#222;border-radius:14px;padding:22px;width:min(92vw,360px);box-shadow:0 18px 50px rgba(0,0,0,.3)">
            <h3 style="margin:0 0 4px">Assign rest day</h3>
            <p style="margin:0 0 14px;font-size:13px;opacity:.7">${esc(employeeName || "Employee")} · this becomes their current rest day.</p>
            <input type="date" id="pickRestDate" class="select-control" style="width:100%" min="${scheduleDateKey(new Date())}" value="${esc(currentValue)}">
            <div id="pickRestError" style="color:#c0392b;font-size:13px;min-height:18px;margin-top:6px"></div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
                <button type="button" class="btn secondary" id="pickRestCancel">Cancel</button>
                <button type="button" class="btn primary" id="pickRestOk">Save rest day</button></div></div>`;
        document.body.appendChild(overlay);
        enhanceDateInputs(overlay);
        const close = (value) => { overlay.remove(); resolve(value); };
        overlay.querySelector("#pickRestCancel").onclick = () => close(null);
        overlay.addEventListener("click", (ev) => { if (ev.target === overlay) close(null); });
        overlay.querySelector("#pickRestOk").onclick = () => {
            const value = overlay.querySelector("#pickRestDate").value;
            const error = overlay.querySelector("#pickRestError");
            if (!value) { error.textContent = "Choose a date."; return; }
            if (value < scheduleDateKey(new Date())) { error.textContent = "Past dates cannot be a rest day."; return; }
            if (scheduleDateValue(value).getDay() === 0) { error.textContent = "Sunday is already closed. Choose another day."; return; }
            const holiday = scheduleHolidayFor(value);
            if (holiday) { error.textContent = `${holiday} is a closed holiday. Choose another day.`; return; }
            close(value);
        };
    });
}
// Pops up for the employee whenever the rest day assigned by the admin is new or has changed.
function maybeShowRestDayPopup() {
    const userId = String(state.user?.id || "");
    const current = currentAssignedRestDay();
    if (!userId || !current || document.getElementById("restDayPopup")) return;
    const key = `shiftmate_seen_rest_day_${userId}`;
    let seen = "";
    try { seen = localStorage.getItem(key) || ""; } catch (e) { }
    if (seen === current) return;
    const pretty = new Date(current + "T00:00:00").toLocaleDateString(undefined, { weekday: "long", month: "long", day: "numeric", year: "numeric" });
    const overlay = document.createElement("div");
    overlay.id = "restDayPopup";
    overlay.style.cssText = "position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:9999;";
    overlay.innerHTML = `<div role="dialog" aria-modal="true" style="background:#fff;color:#222;border-radius:14px;padding:24px;width:min(92vw,380px);text-align:center;box-shadow:0 18px 50px rgba(0,0,0,.3)">
        <div style="font-size:40px">🛌</div>
        <h3 style="margin:6px 0">Your current rest day</h3>
        <p style="margin:0 0 4px;font-size:13px;opacity:.7">Assigned by your administrator</p>
        <p style="font-size:20px;font-weight:700;margin:8px 0 18px">${esc(pretty)}</p>
        <button type="button" class="btn primary" id="restDayPopupOk">OK, got it</button></div>`;
    document.body.appendChild(overlay);
    overlay.querySelector("#restDayPopupOk").onclick = () => {
        try { localStorage.setItem(key, current); } catch (e) { }
        overlay.remove();
    };
}
// Small dialog with Time in / Time out dropdowns (30-minute steps). Resolves to
// { start_time, end_time } or null when cancelled.
function pickShiftTimes(shiftLabel, defaultIn, defaultOut) {
    return new Promise((resolve) => {
        const options = [];
        for (let h = 0; h < 24; h++)
            for (const m of [0, 30]) {
                const v = `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}`;
                options.push([v, formatShiftTime(v)]);
            }
        const build = (selected) =>
            options
                .map(([v, label]) => `<option value="${v}"${v === selected ? " selected" : ""}>${label}</option>`)
                .join("");
        const overlay = document.createElement("div");
        overlay.style.cssText = "position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:9999;";
        overlay.innerHTML = `<div role="dialog" aria-modal="true" style="background:#fff;color:#222;border-radius:14px;padding:22px;width:min(92vw,360px);box-shadow:0 18px 50px rgba(0,0,0,.3);font:inherit">
            <h3 style="margin:0 0 4px">${esc(shiftLabel)} shift</h3>
            <p style="margin:0 0 14px;font-size:13px;opacity:.7">Select the time in and time out. The schedule locks once assigned.</p>
            <label style="display:block;font-size:13px;margin-bottom:10px">Time in
                <select id="pickTimeIn" class="select-control" style="width:100%;margin-top:4px">${build(defaultIn)}</select></label>
            <label style="display:block;font-size:13px;margin-bottom:10px">Time out
                <select id="pickTimeOut" class="select-control" style="width:100%;margin-top:4px">${build(defaultOut)}</select></label>
            <div id="pickTimeError" style="color:#c0392b;font-size:13px;min-height:18px"></div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
                <button type="button" class="btn secondary" id="pickTimeCancel">Cancel</button>
                <button type="button" class="btn primary" id="pickTimeOk">Assign &amp; lock</button></div></div>`;
        document.body.appendChild(overlay);
        const close = (value) => { overlay.remove(); resolve(value); };
        overlay.querySelector("#pickTimeCancel").onclick = () => close(null);
        overlay.addEventListener("click", (ev) => { if (ev.target === overlay) close(null); });
        overlay.querySelector("#pickTimeOk").onclick = () => {
            const start = overlay.querySelector("#pickTimeIn").value;
            const end = overlay.querySelector("#pickTimeOut").value;
            if (start === end) {
                overlay.querySelector("#pickTimeError").textContent = "Time out must be different from time in.";
                return;
            }
            close({ start_time: start, end_time: end });
        };
    });
}
[
    ["employeeSearch", renderEmployees],
    ["scheduleSearch", renderSchedules],
    ["shiftSearch", renderShifts],
    ["requestSearch", renderRequests],
    ["requestStatusFilter", renderRequests],
    ["absenceSearch", renderAbsences],
    ["absenceStatusFilter", renderAbsences],
].forEach(([id, fn]) => {
    const el = $("#" + id);
    if (el) {
        el.addEventListener("input", fn);
        el.addEventListener("search", fn);
    }
});
[
    ["reloadEmployees", "employees"],
    ["reloadSchedules", "schedules"],
    ["reloadShifts", "shifts"],
    ["reloadRequests", "requests"],
    ["reloadAbsences", "absences"],
].forEach(([id]) => {
    // Some dashboard controls are intentionally absent on this layout.
    // Never let a missing optional button stop the rest of app.js from binding,
    // especially the notification bell and inbox actions below.
    const element = $("#" + id);
    if (element) element.addEventListener("click", loadAll);
});
$("#notificationBtn")?.addEventListener("click", async (event) => {
    event.preventDefault();
    event.stopPropagation();
    const panel = $("#notificationPanel");
    const button = $("#notificationBtn");
    if (!panel || !button) return;
    // Unlock browser audio from a direct user gesture so later notification chimes can play.
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (AudioContext) {
            window.shiftMateAudioContext ||= new AudioContext();
            if (window.shiftMateAudioContext.state === "suspended")
                await window.shiftMateAudioContext.resume();
        }
    } catch (_) { }
    if ("Notification" in window && Notification.permission === "default") {
        try {
            await Notification.requestPermission();
        } catch (_) { }
    }
    const opening = panel.classList.contains("hidden");
    panel.classList.toggle("hidden", !opening);
    button.setAttribute("aria-expanded", String(opening));
    if (opening) await refreshNotifications(false);
});
$("#markNotificationsRead")?.addEventListener("click", async () => {
    const unread = (state.notifications || []).filter((n) => !n.is_read);
    await Promise.allSettled(
        unread.map((n) =>
            api(`/notifications/${n.id}`, {
                method: "PATCH",
                body: JSON.stringify({ is_read: true }),
            }),
        ),
    );
    state.notifications.forEach((n) => (n.is_read = true));
    renderNotifications();
});
$("#notificationList")?.addEventListener("click", async (e) => {
    const read = e.target.closest("[data-notification-read]");
    const del = e.target.closest("[data-notification-delete]");
    const reply = e.target.closest("[data-notification-reply]");
    const card = e.target.closest("[data-notification-card]");
    if (read) {
        await markNotificationRead(read.dataset.notificationRead);
        return;
    }
    if (del) {
        e.stopPropagation();
        if (!confirm("Delete this notification?")) return;
        try {
            await api(`/notifications/${del.dataset.notificationDelete}`, {
                method: "DELETE",
            });
            state.notifications = state.notifications.filter(
                (n) => String(n.id) !== String(del.dataset.notificationDelete),
            );
            renderNotifications();
            toast("Notification deleted.");
        } catch (err) {
            toast(err.message || "Could not delete notification.");
        }
        return;
    }
    if (reply) {
        e.stopPropagation();
        const n = state.notifications.find(
            (x) => String(x.id) === String(reply.dataset.notificationReply),
        );
        if (!n) return;
        const message = prompt("Write a reply to this employee:");
        if (!message || !message.trim()) return;
        try {
            await api(`/notifications/${n.id}/reply`, {
                method: "POST",
                body: JSON.stringify({ message: message.trim() }),
            });
            await markNotificationRead(n.id);
            toast("Reply sent to employee.");
        } catch (err) {
            toast(err.message || "Could not send reply.");
        }
        return;
    }
    if (card) {
        const n = state.notifications.find(
            (x) => String(x.id) === String(card.dataset.notificationCard),
        );
        if (!n) return;
        await markNotificationRead(n.id);
        if (
            ["admin", "manager"].includes(
                String(state.user?.role || "").toLowerCase(),
            )
        ) {
            if (n.type === "absence_request") showPage("absences");
            else if (n.type === "rest_day_request") showPage("requests");
        }
    }
});
document.addEventListener("click", (e) => {
    const wrap = $("#notificationWrap");
    if (wrap && !wrap.contains(e.target)) {
        $("#notificationPanel")?.classList.add("hidden");
        $("#notificationBtn")?.setAttribute("aria-expanded", "false");
    }
});
setInterval(() => refreshNotifications(false), 15000);

(async function init() {
    try {
        if (state.token) await refreshUser();
        else showAuth(false);
    } finally {
        // Tell the page the UI is ready so the splash screen can fade out.
        window.__shiftmateReady = true;
        window.dispatchEvent(new Event("shiftmate:ready"));
    }
})();

/* Show/hide password without changing authentication behavior. */
document.addEventListener("click", (event) => {
    const toggle = event.target.closest(".toggle-password");
    if (!toggle) return;
    const wrap = toggle.closest(".password-field-wrap");
    const input = wrap?.querySelector("input");
    if (!input) return;
    const showing = input.type === "password";
    input.type = showing ? "text" : "password";
    toggle.textContent = showing ? "Hide" : "Show";
    toggle.setAttribute(
        "aria-label",
        showing ? "Hide password" : "Show password",
    );
    toggle.setAttribute("aria-pressed", String(showing));
});

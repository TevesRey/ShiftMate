<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dashboard, login, register } from '@/routes';
import { index as teamsIndex } from '@/routes/teams';

const page = usePage();
const dashboardUrl = computed(() =>
    page.props.currentTeam
        ? dashboard(page.props.currentTeam.slug).url
        : teamsIndex().url,
);

const features = [
    {
        number: '01',
        title: 'Plan every shift',
        description:
            'Keep the team schedule in one clear view. See who is working and where coverage is needed.',
    },
    {
        number: '02',
        title: 'Know your team',
        description:
            'Organize employees by team and give people the context they need to plan their week.',
    },
    {
        number: '03',
        title: 'Handle requests clearly',
        description:
            'Keep absence and rest day requests visible alongside the schedules they affect.',
    },
];

const week = [
    { day: 'MON', date: '12', shifts: ['AM', 'PM', 'AM'] },
    { day: 'TUE', date: '13', shifts: ['AM', 'AM', 'PM'] },
    { day: 'WED', date: '14', shifts: ['PM', 'AM', 'AM'] },
    { day: 'THU', date: '15', shifts: ['AM', 'PM', 'PM'] },
    { day: 'FRI', date: '16', shifts: ['AM', 'AM', 'PM'] },
];
</script>

<template>
    <Head title="ShiftMate — better shifts, smoother days">
        <meta
            name="description"
            content="Plan team schedules, coordinate shifts, and keep employee requests organized with ShiftMate."
        />
    </Head>

    <div class="min-h-screen overflow-hidden bg-[#f7f8f4] text-slate-950">
        <header class="relative z-10 mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8 lg:px-12">
            <a href="#top" class="flex items-center gap-3" aria-label="ShiftMate home">
                <span class="flex size-10 items-center justify-center rounded-2xl bg-emerald-700 text-white shadow-lg shadow-emerald-950/10">
                    <svg viewBox="0 0 24 24" fill="none" class="size-6" aria-hidden="true">
                        <path d="M7 3v3m10-3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        <path d="m9 14 2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <span class="text-xl font-bold tracking-tight">ShiftMate</span>
            </a>
            <nav class="flex items-center gap-3 sm:gap-6" aria-label="Main navigation">
                <a href="#features" class="hidden text-sm font-medium text-slate-600 transition hover:text-slate-950 sm:inline">Features</a>
                <Link v-if="$page.props.auth.user" :href="dashboardUrl" class="rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800">Go to dashboard</Link>
                <template v-else>
                    <Link :href="login()" class="text-sm font-semibold text-slate-700 transition hover:text-slate-950">Log in</Link>
                    <Link :href="register()" class="rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800">Get started</Link>
                </template>
            </nav>
        </header>

        <main id="top">
            <section class="relative mx-auto grid max-w-7xl items-center gap-14 px-5 pb-20 pt-12 sm:px-8 sm:pb-28 sm:pt-16 lg:grid-cols-[0.92fr_1.08fr] lg:gap-8 lg:px-12 lg:pb-32 lg:pt-20">
                <div class="relative z-10 max-w-xl">
                    <div class="mb-7 inline-flex items-center gap-2 rounded-full border border-emerald-900/10 bg-white/80 px-3.5 py-2 text-xs font-semibold tracking-wide text-emerald-800 shadow-sm">
                        <span class="size-2 rounded-full bg-emerald-500"></span>
                        TEAM SCHEDULING, MADE CLEAR
                    </div>
                    <h1 class="text-5xl leading-[1.04] font-semibold tracking-[-0.055em] sm:text-6xl lg:text-[4.4rem]">
                        Better shifts.<br />
                        <span class="text-emerald-700">Smoother days.</span>
                    </h1>
                    <p class="mt-6 max-w-lg text-lg leading-8 text-slate-600">
                        Keep schedules, teams, and time-off requests together. ShiftMate helps everyone know what’s next.
                    </p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <Link v-if="$page.props.auth.user" :href="dashboardUrl" class="inline-flex items-center justify-center gap-2 rounded-full bg-emerald-700 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-900/15 transition hover:-translate-y-0.5 hover:bg-emerald-800">
                            Open your dashboard
                            <svg viewBox="0 0 20 20" fill="none" class="size-4" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        </Link>
                        <Link v-else :href="register()" class="inline-flex items-center justify-center gap-2 rounded-full bg-emerald-700 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-900/15 transition hover:-translate-y-0.5 hover:bg-emerald-800">
                            Get started
                            <svg viewBox="0 0 20 20" fill="none" class="size-4" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        </Link>
                        <a href="#features" class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white/70 px-6 py-3.5 text-sm font-semibold text-slate-800 transition hover:border-slate-400 hover:bg-white">Explore features</a>
                    </div>
                    <p class="mt-5 text-xs text-slate-500">A calmer way to keep your team on the same page.</p>
                </div>

                <div class="relative mx-auto w-full max-w-2xl lg:ml-auto">
                    <div class="absolute -inset-8 rounded-[3rem] bg-emerald-200/35 blur-3xl"></div>
                    <div class="relative rotate-[1deg] rounded-[1.75rem] border border-white bg-white p-3 shadow-[0_35px_100px_-35px_rgba(15,50,39,0.35)] sm:p-5">
                        <div class="flex items-center justify-between border-b border-slate-100 px-2 pb-4 sm:px-3">
                            <div>
                                <p class="text-xs font-medium text-slate-400">YOUR TEAM</p>
                                <h2 class="mt-1 text-lg font-semibold tracking-tight">Weekly schedule</h2>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800">May 12 – 16</span>
                        </div>
                        <div class="grid grid-cols-[5rem_repeat(5,minmax(0,1fr))] gap-2 px-1 pt-4 text-center sm:grid-cols-[6rem_repeat(5,minmax(0,1fr))] sm:gap-3">
                            <div></div>
                            <div v-for="day in week" :key="day.day" class="pb-2">
                                <p class="text-[10px] font-semibold tracking-wider text-slate-400">{{ day.day }}</p>
                                <p class="mt-1 text-sm font-semibold">{{ day.date }}</p>
                            </div>
                            <template v-for="(person, index) in ['Maya Chen', 'Alex Rivera', 'Jordan Lee']" :key="person">
                                <div class="flex items-center gap-2 py-2 text-left">
                                    <span :class="['flex size-7 shrink-0 items-center justify-center rounded-full text-[10px] font-bold', index === 0 ? 'bg-amber-100 text-amber-800' : index === 1 ? 'bg-sky-100 text-sky-800' : 'bg-violet-100 text-violet-800']">{{ person.split(' ').map((part) => part[0]).join('') }}</span>
                                    <span class="hidden truncate text-xs font-medium text-slate-600 sm:block">{{ person }}</span>
                                </div>
                                <div v-for="(day, dayIndex) in week" :key="`${person}-${day.day}`" class="flex items-center justify-center py-2">
                                    <span :class="['w-full rounded-lg py-2 text-[10px] font-semibold sm:py-2.5 sm:text-xs', day.shifts[(dayIndex + index) % day.shifts.length] === 'AM' ? 'bg-emerald-50 text-emerald-800' : 'bg-orange-50 text-orange-800']">{{ day.shifts[(dayIndex + index) % day.shifts.length] }}</span>
                                </div>
                            </template>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-slate-100 px-2 pt-4 text-[11px] font-medium text-slate-500 sm:px-3">
                            <span class="flex items-center gap-1.5"><i class="size-2 rounded-full bg-emerald-400"></i> Morning shift</span>
                            <span class="flex items-center gap-1.5"><i class="size-2 rounded-full bg-orange-400"></i> Afternoon shift</span>
                            <span class="ml-auto rounded-full bg-slate-50 px-3 py-1.5 text-slate-600">3 teammates</span>
                        </div>
                    </div>
                    <div class="absolute -bottom-7 -left-3 rounded-2xl border border-emerald-100 bg-white px-4 py-3 shadow-xl shadow-emerald-950/10 sm:-left-7">
                        <div class="flex items-center gap-3">
                            <span class="flex size-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800">
                                <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true"><path d="M7 3v3m10-3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
                            </span>
                            <div><p class="text-[10px] font-medium text-slate-400">UP NEXT</p><p class="text-xs font-semibold text-slate-800">Team schedule is ready</p></div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="features" class="border-y border-slate-200/70 bg-white py-20 sm:py-24">
                <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
                    <div class="max-w-2xl">
                        <p class="text-xs font-bold tracking-[0.18em] text-emerald-700">MADE FOR THE WHOLE TEAM</p>
                        <h2 class="mt-4 text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">Less back and forth.<br class="hidden sm:block" /> More time for the work.</h2>
                        <p class="mt-4 leading-7 text-slate-600">Bring the everyday pieces of workforce planning into one straightforward workspace.</p>
                    </div>
                    <div class="mt-12 grid gap-5 md:grid-cols-3">
                        <article v-for="feature in features" :key="feature.number" class="rounded-2xl border border-slate-200 bg-[#fbfcfa] p-6 sm:p-7">
                            <p class="text-sm font-semibold text-emerald-700">{{ feature.number }}</p>
                            <h3 class="mt-8 text-lg font-semibold tracking-tight">{{ feature.title }}</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ feature.description }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-7 px-5 py-16 sm:px-8 sm:py-20 md:flex-row md:items-center lg:px-12">
                <div>
                    <p class="text-xs font-bold tracking-[0.18em] text-emerald-700">READY WHEN YOU ARE</p>
                    <h2 class="mt-3 text-3xl font-semibold tracking-[-0.04em]">Make the next schedule simpler.</h2>
                </div>
                <Link v-if="$page.props.auth.user" :href="dashboardUrl" class="inline-flex items-center gap-2 rounded-full bg-slate-950 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-emerald-800">Go to dashboard <span aria-hidden="true">→</span></Link>
                <Link v-else :href="register()" class="inline-flex items-center gap-2 rounded-full bg-slate-950 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-emerald-800">Create your account <span aria-hidden="true">→</span></Link>
            </section>
        </main>

        <footer class="border-t border-slate-200/70 bg-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-12">
                <span class="font-semibold text-slate-800">ShiftMate</span>
                <span>Clear schedules. Better teamwork.</span>
            </div>
        </footer>
    </div>
</template>

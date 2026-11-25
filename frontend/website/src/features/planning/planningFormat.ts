import type { HabitPlanningEntry, PlanningSchedule } from '../../api/types'

/**
 * How a week, a day and a schedule are written on the planning. Days travel as "YYYY-MM-DD" and are
 * read as local calendar days: no instant, no timezone to slip a day by.
 */

const DAY_LABEL = new Intl.DateTimeFormat('fr-FR', { weekday: 'short', day: 'numeric' })
const MONTH_DAY = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })

export const WEEKDAYS: { value: number; short: string; long: string }[] = [
    { value: 1, short: 'L', long: 'lundi' },
    { value: 2, short: 'M', long: 'mardi' },
    { value: 3, short: 'M', long: 'mercredi' },
    { value: 4, short: 'J', long: 'jeudi' },
    { value: 5, short: 'V', long: 'vendredi' },
    { value: 6, short: 'S', long: 'samedi' },
    { value: 7, short: 'D', long: 'dimanche' },
]

export const MONTH_WEEKS: { value: number; label: string }[] = [
    { value: 1, label: '1er' },
    { value: 2, label: '2e' },
    { value: 3, label: '3e' },
    { value: 4, label: '4e' },
    { value: 5, label: '5e' },
    { value: -1, label: 'Dernier' },
]

/**
 * « Tous les jours » is not a rule of the server's: it is a weekly rule on all seven days, every
 * week. The form offers it as a choice of its own — the one a habit starts with — and writes it
 * out as that weekly rule.
 */
export const DAILY = 'daily'
const EVERY_WEEKDAY = [1, 2, 3, 4, 5, 6, 7]

export const RULES: { value: string; label: string }[] = [
    { value: DAILY, label: 'Tous les jours' },
    { value: 'once', label: 'Une seule fois' },
    { value: 'weekly', label: 'Certains jours de la semaine' },
    { value: 'monthly_weekday', label: 'Le N-ième jour de la semaine du mois' },
    { value: 'monthly_day', label: 'Un jour fixe du mois' },
]

/** The choice the form shows for a schedule: « tous les jours » for a weekly rule on every day of every week. */
export function ruleChoiceOf(schedule: Pick<PlanningSchedule, 'rule' | 'weekdays' | 'weekInterval'>): string {
    return schedule.rule === 'weekly' && schedule.weekInterval === 1 && EVERY_WEEKDAY.every((day) => schedule.weekdays.includes(day))
        ? DAILY
        : schedule.rule
}

/** What the server is sent for the form's choice: « tous les jours » written out as the weekly rule it is. */
export function scheduleRuleOf(choice: string, weekdays: number[], weekInterval: number): Pick<PlanningSchedule, 'rule' | 'weekdays' | 'weekInterval'> {
    return choice === DAILY ? { rule: 'weekly', weekdays: EVERY_WEEKDAY, weekInterval: 1 } : { rule: choice, weekdays, weekInterval }
}

/** A calendar day from its "YYYY-MM-DD", at local midnight. */
export function parseDay(day: string): Date {
    const [year, month, date] = day.split('-').map(Number)

    return new Date(year, month - 1, date)
}

/** The other way round. */
export function dayKey(date: Date): string {
    return `${date.getFullYear()}-${`${date.getMonth() + 1}`.padStart(2, '0')}-${`${date.getDate()}`.padStart(2, '0')}`
}

export function addDays(date: Date, days: number): Date {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate() + days)
}

/** The Monday of the week the day is in: weeks run Monday to Sunday. */
export function mondayOf(date: Date): Date {
    return addDays(date, -((date.getDay() + 6) % 7))
}

/** « lun. 23 ». */
export function formatDayLabel(date: Date): string {
    return DAY_LABEL.format(date)
}

/** « Semaine du 23 novembre 2026 ». */
export function formatWeek(monday: Date): string {
    return `Semaine du ${MONTH_DAY.format(monday)}`
}

/** "18:00" as minutes since midnight. */
export function minutesOf(time: string | null): number | null {
    if (time === null) {
        return null
    }
    const [hours, minutes] = time.split(':').map(Number)

    return hours * 60 + minutes
}

/** « 18:00 · 1 h 15 », « 18:00 », or nothing within the day. */
export function formatTimeSpan(schedule: Pick<PlanningSchedule, 'startTime' | 'durationInMinutes'>): string | null {
    if (schedule.startTime === null) {
        return null
    }
    if (schedule.durationInMinutes === null) {
        return schedule.startTime
    }

    const hours = Math.floor(schedule.durationInMinutes / 60)
    const minutes = schedule.durationInMinutes % 60
    const length = hours === 0 ? `${minutes} min` : `${hours} h${minutes === 0 ? '' : ` ${`${minutes}`.padStart(2, '0')}`}`

    return `${schedule.startTime} · ${length}`
}

/** The rule in words: « mardi et jeudi », « quotidien », « le lundi, une semaine sur 2 », « le 1er et le dernier samedi du mois ». */
export function formatRule(schedule: PlanningSchedule): string {
    const days = joinFr(schedule.weekdays.map((value) => WEEKDAYS.find((one) => one.value === value)?.long ?? ''))

    switch (schedule.rule) {
        case 'once':
            return `le ${MONTH_DAY.format(parseDay(schedule.startDate))}`
        case 'weekly':
            if (schedule.weekdays.length === 7) {
                return schedule.weekInterval === 1 ? 'quotidien' : `quotidien, une semaine sur ${schedule.weekInterval}`
            }

            return schedule.weekInterval === 1 ? days : `le ${days}, une semaine sur ${schedule.weekInterval}`
        case 'monthly_weekday': {
            const ranks = joinFr(schedule.monthWeeks.map((value) => (MONTH_WEEKS.find((one) => one.value === value)?.label ?? '').toLowerCase()))

            return `le ${ranks} ${days} du mois`
        }
        case 'monthly_day':
            return `le ${joinFr(schedule.monthDays.map((value) => (value === 1 ? '1er' : `${value}`)))} du mois`
        default:
            return schedule.rule
    }
}

/**
 * The days a habit counts, in words — « quotidien », « lundi et jeudi · 07:00 » — its
 * entries one after the other. Null while they are not known yet.
 */
export function formatHabitRhythm(habitId: number, entries: HabitPlanningEntry[]): string | null {
    const own = entries.filter((entry) => entry.habitId === habitId)
    if (own.length === 0) {
        return null
    }

    return own
        .map((entry) => {
            const time = formatTimeSpan(entry.schedule)

            return time === null ? formatRule(entry.schedule) : `${formatRule(entry.schedule)} · ${time}`
        })
        .join(' — ')
}

/** « lundi, mercredi, vendredi »: commas only, no « et » before the last. */
function joinFr(parts: string[]): string {
    return parts.join(', ')
}

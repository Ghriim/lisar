import { X } from 'lucide-react'
import type { HabitDay } from '../../api/types'
import { parseDay, WEEKDAYS } from '../planning/planningFormat'

type DayState = 'done' | 'missed' | 'today' | 'off'

const SPOKEN: Record<DayState, string> = {
    done: 'fait',
    missed: 'manqué',
    today: 'à faire',
    off: 'non prévu',
}

/**
 * A habit's last seven days, oldest first, today always last: each day's initial — today's in the
 * accent — over a dot that says how the day went. Read whole by a screen reader.
 */
export function HabitWeek({ days }: { days: HabitDay[] }) {
    const last = days.length - 1
    const lines = days.map((day, index) => {
        // ISO weekday: Monday 1 … Sunday 7.
        const weekday = WEEKDAYS[(parseDay(day.day).getDay() + 6) % 7]

        return { day, weekday, isToday: index === last, state: stateOf(day, index === last) }
    })
    const spoken = lines.map(({ weekday, isToday, state }) => `${isToday ? 'aujourd’hui' : weekday.long} ${SPOKEN[state]}`).join(', ')

    return (
        <div className="habit-week" role="img" aria-label={`Sept derniers jours : ${spoken}`}>
            {lines.map(({ day, weekday, isToday, state }) => (
                <div key={day.day} className="habit-week-day" aria-hidden>
                    <span className={isToday ? 'habit-week-initial habit-week-initial-today' : 'habit-week-initial'}>{weekday.short}</span>
                    <span className={`habit-pip habit-pip-${state}`}>{state === 'missed' && <X size={10} strokeWidth={3} />}</span>
                </div>
            ))}
        </div>
    )
}

/** Kept, planned or not, is done; a planned day gone by unkept is missed; today, planned, is still to do. */
function stateOf(day: HabitDay, isToday: boolean): DayState {
    if (day.isCompleted) {
        return 'done'
    }
    if (!day.isPlanned) {
        return 'off'
    }

    return isToday ? 'today' : 'missed'
}

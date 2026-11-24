import { Check } from 'lucide-react'
import type { HabitDay } from '../../api/types'

/**
 * The last seven days, oldest to today: an empty circle for a day not kept, a checked one for a
 * day kept. A planned habit's days its planning does not ask for are dimmed — kept on one, it is a
 * bonus. The run is seen, not counted at the person.
 */
export function HabitStreak({ days }: { days: HabitDay[] }) {
    const planned = days.filter((day) => day.isPlanned)
    const kept = planned.filter((day) => day.isCompleted).length

    return (
        <span className="habit-streak" role="img" aria-label={`${kept} des ${planned.length} derniers jours prévus tenus`}>
            {days.map((day) => (
                <span
                    key={day.day}
                    className={['habit-dot', day.isCompleted ? 'habit-dot-done' : '', day.isPlanned ? '' : 'habit-dot-unplanned'].filter(Boolean).join(' ')}
                    aria-hidden
                >
                    {day.isCompleted && <Check size={11} strokeWidth={3} />}
                </span>
            ))}
        </span>
    )
}

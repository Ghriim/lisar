import { Check } from 'lucide-react'
import type { HabitDay } from '../../api/types'

/**
 * The last seven days, oldest to today: an empty circle for a day not kept, a checked one for a
 * day kept. The run is seen, not counted at the person.
 */
export function HabitStreak({ days }: { days: HabitDay[] }) {
    const kept = days.filter((day) => day.isCompleted).length

    return (
        <span className="habit-streak" role="img" aria-label={`${kept} des ${days.length} derniers jours tenus`}>
            {days.map((day) => (
                <span
                    key={day.day}
                    className={day.isCompleted ? 'habit-dot habit-dot-done' : 'habit-dot'}
                    aria-hidden
                >
                    {day.isCompleted && <Check size={11} strokeWidth={3} />}
                </span>
            ))}
        </span>
    )
}

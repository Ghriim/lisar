import type { LucideIcon } from 'lucide-react'
import { humanise } from '../api/violations'

export interface RatingLevel {
    value: number
    icon: LucideIcon
    /** What the level means. It is the tooltip, and it is what a screen reader hears. */
    label: string
}

interface RatingScaleProps {
    label: string
    levels: RatingLevel[]
    value: number | null
    /**
     * Called with null when the chosen level is clicked again: that is how a rating is taken
     * back, which matters wherever rating oneself is optional.
     */
    onChange: (value: number | null) => void
    errors?: string[]
    disabled?: boolean
}

/**
 * A scale one picks a level on, each level drawn as an icon — a mood on waking today, whatever
 * gets rated next tomorrow.
 *
 * Nothing is chosen until someone chooses: the scale has no middle it falls back to. It borrows
 * the field vocabulary rather than inventing one, so it sits in a form like anything else.
 */
export function RatingScale({
    label,
    levels,
    value,
    onChange,
    errors = [],
    disabled = false,
}: RatingScaleProps) {
    return (
        <div className="field">
            <span className="field-label">{label}</span>

            <div className="rating-scale" role="group" aria-label={label}>
                {levels.map((level) => {
                    const chosen = value === level.value

                    return (
                        <span key={level.value} className="tooltip-host" data-tooltip={level.label}>
                            <button
                                type="button"
                                className={chosen ? 'rating-level rating-level-chosen' : 'rating-level'}
                                aria-label={level.label}
                                aria-pressed={chosen}
                                disabled={disabled}
                                onClick={() => onChange(chosen ? null : level.value)}
                            >
                                <level.icon size={26} strokeWidth={1.8} aria-hidden />
                            </button>
                        </span>
                    )
                })}
            </div>

            {errors.map((error) => (
                <span key={error} className="field-error">
                    {humanise(error)}
                </span>
            ))}
        </div>
    )
}

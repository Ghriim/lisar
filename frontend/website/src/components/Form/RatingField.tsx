import type { LucideIcon } from 'lucide-react'
import { Tooltip } from '../Tooltip'
import { FieldFrame, type FieldProps } from './Field'

export interface RatingLevel {
    value: number
    icon: LucideIcon
    /** What the level means. It is the tooltip, and it is what a screen reader hears. */
    label: string
}

interface RatingFieldProps extends FieldProps {
    levels: RatingLevel[]
    value: number | null
    /**
     * Called with null when the chosen level is clicked again: that is how a rating is taken
     * back, which matters wherever rating oneself is optional.
     */
    onChange: (value: number | null) => void
}

/**
 * A scale one picks a level on, each level drawn as an icon — a mood on waking today, whatever
 * gets rated next tomorrow.
 *
 * Nothing is chosen until someone chooses: the scale has no middle it falls back to.
 */
export function RatingField({ label, isMandatory = false, errors, disabled = false, levels, value, onChange }: RatingFieldProps) {
    return (
        <FieldFrame label={label} isMandatory={isMandatory} errors={errors} group>
            <div className="rating-scale">
                {levels.map((level) => {
                    const chosen = value === level.value

                    return (
                        <Tooltip key={level.value} label={level.label}>
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
                        </Tooltip>
                    )
                })}
            </div>
        </FieldFrame>
    )
}

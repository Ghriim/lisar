import { Check } from 'lucide-react'
import { Tooltip } from '../Tooltip'

interface CheckToggleProps {
    checked: boolean
    /** The verb, for the tooltip: « Terminer », « Valider ». */
    label: string
    /** What it applies to, folded into the spoken label only — as on `IconButton`. */
    subject?: string
    onToggle: () => void
    disabled?: boolean
    /** Something late: a red frame while unchecked. */
    danger?: boolean
    /** 40px instead of 44, for a control among other small ones — a habit's line. */
    small?: boolean
}

/**
 * A box that is ticked and unticked by the same gesture: empty with an accent frame, then filled
 * with the accent and a dark check. It says whether it is pressed, which an icon alone never could.
 */
export function CheckToggle({ checked, label, subject, onToggle, disabled = false, danger = false, small = false }: CheckToggleProps) {
    const classes = ['check-toggle']
    if (danger && !checked) {
        classes.push('check-toggle-danger')
    }
    if (small) {
        classes.push('check-toggle-small')
    }

    return (
        <Tooltip label={label}>
            <button
                type="button"
                className={classes.join(' ')}
                aria-label={subject === undefined ? label : `${label} « ${subject} »`}
                aria-pressed={checked}
                disabled={disabled}
                onClick={onToggle}
            >
                {checked && <Check size={small ? 18 : 20} strokeWidth={2.4} aria-hidden />}
            </button>
        </Tooltip>
    )
}

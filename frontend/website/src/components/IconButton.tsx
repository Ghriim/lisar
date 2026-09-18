import type { LucideIcon } from 'lucide-react'

interface IconButtonProps {
    icon: LucideIcon
    /**
     * The verb, short: it is what the tooltip shows on hover. An icon never acts without saying
     * what it does — see docs/dev/frontend-conventions.md.
     */
    label: string
    /**
     * What the action applies to. Folded into the spoken label only — a screen reader hears
     * "Terminer « Ranger le garage »", the tooltip stays "Terminer".
     */
    subject?: string
    onClick: () => void
    variant?: 'default' | 'danger'
    disabled?: boolean
    /** Smaller, for a control that sits inside a line of text rather than beside it. */
    small?: boolean
    /**
     * Set only on a button that folds something away: it tells a screen reader whether what it
     * controls is open, which an icon alone never could.
     */
    expanded?: boolean
}

export function IconButton({
    icon: Icon,
    label,
    subject,
    onClick,
    variant = 'default',
    disabled = false,
    small = false,
    expanded,
}: IconButtonProps) {
    const classes = ['icon-button']
    if (variant === 'danger') {
        classes.push('icon-button-danger')
    }
    if (small) {
        classes.push('icon-button-small')
    }

    return (
        <span className="tooltip-host" data-tooltip={label}>
            <button
                type="button"
                className={classes.join(' ')}
                aria-label={subject === undefined ? label : `${label} « ${subject} »`}
                aria-expanded={expanded}
                disabled={disabled}
                onClick={onClick}
            >
                <Icon size={small ? 13 : 15} strokeWidth={2} aria-hidden />
            </button>
        </span>
    )
}

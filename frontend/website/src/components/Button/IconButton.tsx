import type { LucideIcon } from 'lucide-react'
import { Tooltip } from '../Tooltip'

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
    /** `primary` wears the accent: the section's main action — « Créer ». */
    variant?: 'default' | 'primary' | 'danger'
    disabled?: boolean
    /** Smaller, for a control that sits inside a line of text rather than beside it. */
    small?: boolean
    /** 44px, framed: a section's action beside its title. */
    large?: boolean
    /** 40px, framed: among other controls of the same size — a habit's line. */
    medium?: boolean
    /**
     * 60px, lit, its icon drawn large: the one thing a card is for — a tracker's « Ajouter 250 mL ».
     * The icon is then what the card is about (a glass), and `badge` says what the button does to it.
     */
    hero?: boolean
    /** A small square on the corner of a `hero` button, with the action's own icon: a plus, a pencil. */
    badge?: LucideIcon
    /**
     * Set on a button that turns something on and off — a task's star: lit and filled while on,
     * and `aria-pressed` says which to a screen reader.
     */
    pressed?: boolean
    /** Set on a button that opens a menu: a screen reader announces it as such. */
    opensMenu?: boolean
    /** Borderless and dim: a control in a card's corner, out of the way of its content — the « ⋯ » of a tracker. */
    bare?: boolean
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
    large = false,
    medium = false,
    hero = false,
    badge: Badge,
    opensMenu = false,
    bare = false,
    pressed,
    expanded,
}: IconButtonProps) {
    const classes = ['icon-button']
    if (variant === 'danger') {
        classes.push('icon-button-danger')
    }
    if (variant === 'primary') {
        classes.push('icon-button-primary')
    }
    if (small) {
        classes.push('icon-button-small')
    }
    if (large || medium) {
        classes.push('icon-button-large')
    }
    if (medium) {
        classes.push('icon-button-medium')
    }
    if (hero) {
        classes.push('icon-button-hero')
    }
    if (bare) {
        classes.push('icon-button-bare')
    }
    if (pressed === true) {
        classes.push('icon-button-pressed')
    }

    return (
        <Tooltip label={label}>
            <button
                type="button"
                className={classes.join(' ')}
                aria-label={subject === undefined ? label : `${label} « ${subject} »`}
                aria-expanded={expanded}
                aria-haspopup={opensMenu ? 'menu' : undefined}
                aria-pressed={pressed}
                disabled={disabled}
                onClick={onClick}
            >
                <Icon size={hero ? 32 : small ? 13 : large ? 18 : medium ? 16 : 15} strokeWidth={hero ? 1.4 : 2} aria-hidden />
                {hero && Badge !== undefined && (
                    <span className="icon-button-badge" aria-hidden>
                        <Badge size={12} strokeWidth={2.4} />
                    </span>
                )}
            </button>
        </Tooltip>
    )
}

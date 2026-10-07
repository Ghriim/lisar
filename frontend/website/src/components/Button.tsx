import type { LucideIcon } from 'lucide-react'
import type { ReactNode } from 'react'

export type ButtonVariant = 'primary' | 'quiet' | 'danger'

interface ButtonProps {
    /** A verb, and nothing but the verb. See docs/dev/frontend-conventions.md. */
    children: ReactNode
    /** Drawn before the verb, never instead of it: the same icon the action wears everywhere. */
    icon?: LucideIcon
    variant?: ButtonVariant
    submit?: boolean
    disabled?: boolean
    onClick?: () => void
}

const CLASSES: Record<ButtonVariant, string> = {
    primary: 'button',
    quiet: 'button button-quiet',
    danger: 'button button-danger',
}

export function Button({
    children,
    icon: Icon,
    variant = 'primary',
    submit = false,
    disabled = false,
    onClick,
}: ButtonProps) {
    return (
        <button
            type={submit ? 'submit' : 'button'}
            className={CLASSES[variant]}
            disabled={disabled}
            onClick={onClick}
        >
            {Icon !== undefined && <Icon size={15} strokeWidth={2} aria-hidden />}
            {children}
        </button>
    )
}

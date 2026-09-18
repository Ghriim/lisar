import type { ReactNode } from 'react'

export type ButtonVariant = 'primary' | 'quiet' | 'danger'

interface ButtonProps {
    /** A verb, and nothing but the verb. See docs/dev/frontend-conventions.md. */
    children: ReactNode
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
            {children}
        </button>
    )
}

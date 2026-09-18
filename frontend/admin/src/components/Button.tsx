import { Button as AntButton } from 'antd'
import type { ReactNode } from 'react'

export type ButtonVariant = 'primary' | 'default' | 'danger' | 'text'

interface ButtonProps {
    /** A verb, and nothing but the verb. See docs/dev/frontend-conventions.md. */
    children: ReactNode
    variant?: ButtonVariant
    icon?: ReactNode
    loading?: boolean
    disabled?: boolean
    size?: 'small' | 'middle'
    /** Submits the form it sits in, rather than calling onClick. */
    submit?: boolean
    onClick?: () => void
}

export function Button({
    children,
    variant = 'default',
    icon,
    loading = false,
    disabled = false,
    size = 'middle',
    submit = false,
    onClick,
}: ButtonProps) {
    return (
        <AntButton
            type={variant === 'primary' ? 'primary' : variant === 'text' ? 'text' : 'default'}
            danger={variant === 'danger'}
            icon={icon}
            loading={loading}
            disabled={disabled}
            size={size}
            htmlType={submit ? 'submit' : 'button'}
            onClick={onClick}
        >
            {children}
        </AntButton>
    )
}

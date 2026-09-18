import { Popconfirm } from 'antd'
import type { ReactNode } from 'react'
import { Button, type ButtonVariant } from './Button'

interface ConfirmButtonProps {
    /** The question, asked plainly: "Supprimer cette priorité ?" */
    question: string
    children: ReactNode
    onConfirm: () => void
    loading?: boolean
    variant?: ButtonVariant
    size?: 'small' | 'middle'
}

/**
 * An action that cannot be undone asks first. The wording of the two buttons is fixed here, so
 * no screen invents its own way of saying "Annuler".
 */
export function ConfirmButton({
    question,
    children,
    onConfirm,
    loading = false,
    variant = 'danger',
    size = 'small',
}: ConfirmButtonProps) {
    return (
        <Popconfirm title={question} okText="Confirmer" cancelText="Annuler" onConfirm={onConfirm}>
            <Button variant={variant} size={size} loading={loading}>
                {children}
            </Button>
        </Popconfirm>
    )
}

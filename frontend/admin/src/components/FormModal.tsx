import { Modal } from 'antd'
import type { ReactNode } from 'react'
import { Button } from './Button'
import { Form, FormActions } from './Form'

interface FormModalProps<TValues extends object> {
    title: string
    initialValues?: Partial<TValues>
    /** The verb that commits. "Annuler" is added before it, and never changes. */
    submitLabel: string
    pending?: boolean
    onCancel: () => void
    onSubmit: (values: TValues) => void
    children: ReactNode
}

/**
 * A form in a window. Render it only while it is open — mounting is what resets the fields
 * between two uses, so nothing carries over from the row edited before.
 *
 * Ant's own footer is replaced: it aligns right with the confirm button second, and the house
 * rule is centred with cancel first.
 */
export function FormModal<TValues extends object>({
    title,
    initialValues,
    submitLabel,
    pending = false,
    onCancel,
    onSubmit,
    children,
}: FormModalProps<TValues>) {
    return (
        <Modal open title={title} footer={null} onCancel={onCancel}>
            <Form<TValues> initialValues={initialValues} onSubmit={onSubmit}>
                {children}

                <FormActions>
                    <Button onClick={onCancel}>Annuler</Button>
                    <Button variant="primary" submit loading={pending}>
                        {submitLabel}
                    </Button>
                </FormActions>
            </Form>
        </Modal>
    )
}

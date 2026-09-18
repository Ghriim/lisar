import { Button } from './Button'
import { FormActions } from './FormActions'
import { Modal } from './Modal'

interface ConfirmDialogProps {
    title: string
    /** What will happen, plainly. Deleting a quest takes its sub-quests with it, and says so. */
    question: string
    /** The verb that goes through with it. */
    confirmLabel: string
    onConfirm: () => void
    onCancel: () => void
}

/**
 * Asked before anything that cannot be undone. The System window, its three ways out, and the
 * house rule for the buttons — centred, cancel first.
 */
export function ConfirmDialog({ title, question, confirmLabel, onConfirm, onCancel }: ConfirmDialogProps) {
    return (
        <Modal title={title} onClose={onCancel}>
            <p className="system-text dim" style={{ marginTop: 0 }}>
                {question}
            </p>

            <FormActions>
                <Button variant="quiet" onClick={onCancel}>
                    Annuler
                </Button>
                <Button variant="danger" onClick={onConfirm}>
                    {confirmLabel}
                </Button>
            </FormActions>
        </Modal>
    )
}

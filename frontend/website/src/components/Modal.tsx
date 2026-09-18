import { X } from 'lucide-react'
import { useEffect, type ReactNode } from 'react'
import { IconButton } from './IconButton'
import { createPortal } from 'react-dom'
import { SystemPanel } from './SystemPanel'

interface ModalProps {
    title: string
    onClose: () => void
    children: ReactNode
}

/**
 * A System window projected over the app: the page behind it blurs, and the three ways out —
 * the cross, a click outside, Escape — all do the same thing.
 */
export function Modal({ title, onClose, children }: ModalProps) {
    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose()
            }
        }

        // The page behind must not scroll under the window.
        const { overflow } = document.body.style
        document.body.style.overflow = 'hidden'
        document.addEventListener('keydown', onKeyDown)

        return () => {
            document.body.style.overflow = overflow
            document.removeEventListener('keydown', onKeyDown)
        }
    }, [onClose])

    return createPortal(
        <div
            className="modal-backdrop"
            // On mousedown rather than click, and only when the press started on the backdrop
            // itself: a selection dragged out of the form must not close the window.
            onMouseDown={(event) => {
                if (event.target === event.currentTarget) {
                    onClose()
                }
            }}
        >
            <div className="modal" role="dialog" aria-modal="true" aria-label={title}>
                <SystemPanel
                    title={title}
                    actions={<IconButton icon={X} label="Fermer" onClick={onClose} />}
                >
                    {children}
                </SystemPanel>
            </div>
        </div>,
        document.body,
    )
}

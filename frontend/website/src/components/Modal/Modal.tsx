import { X } from 'lucide-react'
import { useEffect, type ReactNode } from 'react'
import { IconButton } from '../Button/IconButton'
import { createPortal } from 'react-dom'

interface ModalProps {
    title: string
    onClose: () => void
    children: ReactNode
    /**
     * As tall as the screen allows from the start, whatever it holds: for a window whose content
     * grows and shrinks as one types — a list filtered by a search — and must not jump about.
     */
    tall?: boolean
}

/**
 * A System window projected over the app: the page behind it blurs, and the three ways out —
 * the cross, a click outside, Escape — all do the same thing. Its title and its cross stay put
 * inside the window; only what is under them scrolls.
 */
export function Modal({ title, onClose, children, tall = false }: ModalProps) {
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
            <div className={tall ? 'modal modal-tall' : 'modal'} role="dialog" aria-modal="true" aria-label={title}>
                <section className="panel modal-panel">
                    <header className="row spread panel-title modal-head">
                        <span>[ {title} ]</span>
                        <IconButton icon={X} label="Fermer" onClick={onClose} />
                    </header>
                    <div className="modal-body">{children}</div>
                </section>
            </div>
        </div>,
        document.body,
    )
}

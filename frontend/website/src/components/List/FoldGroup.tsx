import { ChevronRight } from 'lucide-react'
import { useState, type ReactNode } from 'react'

interface FoldGroupProps {
    /** The group's name, set in the monospace. */
    label: string
    /** Beside the name, quieter: how many it holds. */
    count?: string
    /** On the right, in the accent: a note about what is not here — « +1 dans Aujourd'hui ». */
    note?: string
    children: ReactNode
}

/**
 * A group whose heading folds and unfolds it, open to start with. The heading is one button, so
 * the whole line is the target, and it says whether what it controls is open.
 */
export function FoldGroup({ label, count, note, children }: FoldGroupProps) {
    const [open, setOpen] = useState(true)

    return (
        <div className="fold-group">
            <button type="button" className="fold-group-heading" aria-expanded={open} onClick={() => setOpen(!open)}>
                <ChevronRight size={14} strokeWidth={2} className={open ? 'fold-group-chevron fold-group-chevron-open' : 'fold-group-chevron'} aria-hidden />
                <span className="fold-group-label">{label}</span>
                {count !== undefined && <span className="fold-group-count">{count}</span>}
                {note !== undefined && <span className="fold-group-note">{note}</span>}
            </button>
            {open && children}
        </div>
    )
}

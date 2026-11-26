import { Ellipsis } from 'lucide-react'
import { useEffect, useId, useRef, useState, type KeyboardEvent } from 'react'
import { IconButton } from './IconButton'

export interface MenuItem {
    label: string
    /** Absent while what it leads to is not built yet: the item is listed, and closes the menu. */
    onSelect?: () => void
}

interface MenuButtonProps {
    /** Said by the tooltip, and with `subject` to a screen reader: « Options « hydratation » ». */
    label: string
    subject?: string
    items: MenuItem[]
}

/**
 * The « ⋯ » in a card's corner and the menu it drops: what can be done around the card's thing,
 * rather than with it — its history, its settings. Escape, a click outside, or an item closes it.
 */
export function MenuButton({ label, subject, items }: MenuButtonProps) {
    const [open, setOpen] = useState(false)
    const host = useRef<HTMLDivElement>(null)
    const menuId = useId()

    useEffect(() => {
        if (!open) {
            return undefined
        }

        const onPointerDown = (event: PointerEvent) => {
            if (host.current !== null && !host.current.contains(event.target as Node)) {
                setOpen(false)
            }
        }
        const onKeyDown = (event: globalThis.KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false)
            }
        }
        document.addEventListener('pointerdown', onPointerDown)
        document.addEventListener('keydown', onKeyDown)
        // The first item takes the focus, so the keyboard carries on from the button into the menu.
        host.current?.querySelector<HTMLButtonElement>('[role="menuitem"]')?.focus()

        return () => {
            document.removeEventListener('pointerdown', onPointerDown)
            document.removeEventListener('keydown', onKeyDown)
        }
    }, [open])

    // Up and down move between the items, as in any menu.
    const onMenuKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
        if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
            return
        }
        event.preventDefault()
        const all = [...(host.current?.querySelectorAll<HTMLButtonElement>('[role="menuitem"]') ?? [])]
        const at = all.indexOf(document.activeElement as HTMLButtonElement)
        const next = event.key === 'ArrowDown' ? (at + 1) % all.length : (at - 1 + all.length) % all.length
        all[next]?.focus()
    }

    return (
        <div className="menu-button" ref={host}>
            <IconButton icon={Ellipsis} label={label} subject={subject} bare opensMenu expanded={open} onClick={() => setOpen(!open)} />

            {open && (
                <div className="menu" role="menu" id={menuId} aria-label={subject === undefined ? label : `${label} « ${subject} »`} onKeyDown={onMenuKeyDown}>
                    {items.map((item) => (
                        <button
                            key={item.label}
                            type="button"
                            role="menuitem"
                            className="menu-item"
                            onClick={() => {
                                setOpen(false)
                                item.onSelect?.()
                            }}
                        >
                            {item.label}
                        </button>
                    ))}
                </div>
            )}
        </div>
    )
}

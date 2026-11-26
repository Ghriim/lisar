import { createElement } from 'react'
import type { Category } from '../../api/types'
import { categoryIconOf } from './categoryIcons'

/** A category's icon, drawn; nothing when it has none — no category, or a personal one. */
export function CategoryGlyph({ category, size }: { category: Category | null; size: number }) {
    const icon = categoryIconOf(category)

    return icon === null ? null : createElement(icon, { size, strokeWidth: 1.6, 'aria-hidden': true })
}

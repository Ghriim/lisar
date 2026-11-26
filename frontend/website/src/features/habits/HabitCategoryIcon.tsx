import type { Category } from '../../api/types'
import { Tooltip } from '../../components'
import { CategoryGlyph } from '../categories/CategoryGlyph'
import { categoryIconOf } from '../categories/categoryIcons'

/**
 * A habit's category, as a small icon on its name's line — the category's label is not shown
 * anywhere else, so the icon names it on hover and to a screen reader. Nothing without an icon.
 */
export function HabitCategoryIcon({ category }: { category: Category | null }) {
    if (category === null || categoryIconOf(category) === null) {
        return undefined
    }

    return (
        <Tooltip label={category.label}>
            <span className="habit-category-icon" role="img" aria-label={category.label} tabIndex={0}>
                <CategoryGlyph category={category} size={14} />
            </span>
        </Tooltip>
    )
}

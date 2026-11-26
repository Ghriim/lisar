import type { Category } from '../../api/types'

export const NO_CATEGORY = 'Sans catégorie'

/**
 * Things under their category's label, in the order the categories first appear, with the ones
 * sitting in none at the end. A task list comes already sorted that way; a habit list does not,
 * and is grouped the same.
 */
export function groupByCategory<TItem>(
    items: TItem[],
    categoryOf: (item: TItem) => Category | null,
): { label: string; items: TItem[] }[] {
    const groups = new Map<string, TItem[]>()
    const unfiled: TItem[] = []

    for (const item of items) {
        const category = categoryOf(item)

        if (category === null) {
            unfiled.push(item)
        } else {
            groups.set(category.label, [...(groups.get(category.label) ?? []), item])
        }
    }

    const grouped = [...groups].map(([label, grouped]) => ({ label, items: grouped }))

    return unfiled.length === 0 ? grouped : [...grouped, { label: NO_CATEGORY, items: unfiled }]
}

/**
 * The same, but flat while nothing has a category: a single « Sans catégorie » heading over every
 * line would say nothing. What a list of habits uses — few of them sit in one yet.
 */
export function groupByCategoryIfAny<TItem>(
    items: TItem[],
    categoryOf: (item: TItem) => Category | null,
): { label?: string; items: TItem[] }[] {
    return items.some((item) => categoryOf(item) !== null) ? groupByCategory(items, categoryOf) : [{ items }]
}

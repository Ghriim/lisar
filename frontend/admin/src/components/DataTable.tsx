import { Table } from 'antd'
import type { ReactNode } from 'react'

export interface Column<TRow> {
    key: string
    title: string
    width?: number
    align?: 'left' | 'right'
    render: (row: TRow) => ReactNode
}

export interface Pagination {
    page: number
    perPage: number
    total: number
    onChange: (page: number) => void
}

interface DataTableProps<TRow> {
    rows: TRow[]
    columns: Column<TRow>[]
    rowKey: (row: TRow) => string | number
    loading?: boolean
    /** Omitted means the whole set fits on one screen and says so. */
    pagination?: Pagination
    emptyText?: string
}

/**
 * Every table in the back-office. Columns are described by our own shape rather than Ant's, so a
 * page never has to know what renders them.
 */
export function DataTable<TRow extends object>({
    rows,
    columns,
    rowKey,
    loading = false,
    pagination,
    emptyText = 'Rien à afficher',
}: DataTableProps<TRow>) {
    return (
        <Table<TRow>
            rowKey={rowKey}
            loading={loading}
            dataSource={rows}
            locale={{ emptyText }}
            pagination={
                pagination === undefined
                    ? false
                    : {
                          current: pagination.page,
                          pageSize: pagination.perPage,
                          total: pagination.total,
                          showSizeChanger: false,
                          onChange: pagination.onChange,
                      }
            }
            columns={columns.map((column) => ({
                key: column.key,
                title: column.title,
                width: column.width,
                align: column.align,
                render: (_: unknown, row: TRow) => column.render(row),
            }))}
        />
    )
}

import { Flex } from 'antd'
import type { CSSProperties, ReactNode } from 'react'

interface LayoutProps {
    children: ReactNode
    gap?: number
    align?: 'start' | 'center' | 'end'
    justify?: 'start' | 'center' | 'end' | 'space-between'
    wrap?: boolean
    style?: CSSProperties
}

/** A row of things. */
export function Row({ children, gap = 8, align = 'center', justify = 'start', wrap = false, style }: LayoutProps) {
    return (
        <Flex align={align} justify={justify} gap={gap} wrap={wrap} style={style}>
            {children}
        </Flex>
    )
}

/** A column of things. */
export function Stack({ children, gap = 16, align = 'start', justify = 'start', style }: LayoutProps) {
    return (
        <Flex vertical align={align} justify={justify} gap={gap} style={style}>
            {children}
        </Flex>
    )
}

/** Centres its content in the whole viewport: the sign-in card, the loading spinner. */
export function Centered({ children }: { children: ReactNode }) {
    return (
        <Flex align="center" justify="center" style={{ minHeight: '100vh', padding: 24 }}>
            {children}
        </Flex>
    )
}

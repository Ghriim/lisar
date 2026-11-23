import type { CSSProperties, ReactNode } from 'react'

export interface CalendarItem {
    key: string
    /** Minutes since midnight; null for an item that holds within the day, at the head of its column. */
    startInMinutes: number | null
    /** Null draws a mark at its hour rather than a block. */
    durationInMinutes: number | null
    content: ReactNode
}

export interface CalendarDay {
    key: string
    /** « lun. 23 ». */
    label: string
    isToday: boolean
    items: CalendarItem[]
}

/** The grid shows at least this span, so that an empty evening still reads as a day. */
const DEFAULT_FIRST_HOUR = 7
const DEFAULT_LAST_HOUR = 21
/** A mark without a duration still takes this much room, to be read. */
const MARK_MINUTES = 30

/**
 * Seven days side by side: what holds within the day at the head of each column, the rest on an
 * hour grid, as tall as it lasts. Narrow, the days stack and each one lists its items in order.
 * It knows nothing of what the items are: the screen hands it their content.
 */
export function WeekCalendar({ days }: { days: CalendarDay[] }) {
    const timed = days.flatMap((day) => day.items).filter((item) => item.startInMinutes !== null)
    const firstHour = Math.min(DEFAULT_FIRST_HOUR, ...timed.map((item) => Math.floor((item.startInMinutes ?? 0) / 60)))
    const lastHour = Math.max(
        DEFAULT_LAST_HOUR,
        ...timed.map((item) => Math.ceil(((item.startInMinutes ?? 0) + (item.durationInMinutes ?? MARK_MINUTES)) / 60)),
    )
    const hours = Array.from({ length: Math.min(24, lastHour) - firstHour }, (_, index) => firstHour + index)

    return (
        <div className="week-calendar" style={{ '--calendar-hours': hours.length } as CSSProperties}>
            <div className="week-calendar-gutter" aria-hidden>
                <div className="week-calendar-head" />
                <div className="week-calendar-allday" />
                <div className="week-calendar-grid">
                    {hours.map((hour) => (
                        <span key={hour} className="week-calendar-hour">
                            {`${hour}`.padStart(2, '0')}:00
                        </span>
                    ))}
                </div>
            </div>

            {days.map((day) => {
                const allDay = day.items.filter((item) => item.startInMinutes === null)
                const atTimes = day.items
                    .filter((item) => item.startInMinutes !== null)
                    .sort((one, other) => (one.startInMinutes ?? 0) - (other.startInMinutes ?? 0))

                return (
                    <section
                        key={day.key}
                        className={day.isToday ? 'week-calendar-day week-calendar-today' : 'week-calendar-day'}
                        aria-label={day.label}
                    >
                        <h2 className="week-calendar-head">{day.label}</h2>
                        <div className="week-calendar-allday">
                            {allDay.map((item) => (
                                <div key={item.key} className="week-calendar-item">
                                    {item.content}
                                </div>
                            ))}
                        </div>
                        <div className="week-calendar-grid">
                            {atTimes.map((item) => (
                                <div
                                    key={item.key}
                                    className="week-calendar-item week-calendar-timed"
                                    style={
                                        {
                                            '--item-top': ((item.startInMinutes ?? 0) - firstHour * 60) / 60,
                                            '--item-height': (item.durationInMinutes ?? MARK_MINUTES) / 60,
                                        } as CSSProperties
                                    }
                                >
                                    {item.content}
                                </div>
                            ))}
                        </div>
                    </section>
                )
            })}
        </div>
    )
}

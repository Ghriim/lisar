import { useReloadOnDayChange } from '../components'
import { HabitPanel } from '../features/habits/HabitPanel'
import { HydrationWidget } from '../features/hydration/HydrationWidget'
import { useHydrationToday } from '../features/hydration/queries'
import { SleepWidget } from '../features/sleep/SleepWidget'
import { StepWidget } from '../features/steps/StepWidget'
import { QuestLog } from '../features/tasks/QuestLog'
import { WeightWidget } from '../features/weight/WeightWidget'

export function DashboardPage() {
    // When the day turns under a page left open, the whole screen is stale, not one widget: the
    // day's totals, its goal, its entries. So the page is what watches for it — and the day
    // comes from the API, never from the browser, because the timezone days are counted in is a
    // back-end decision and computing it here is how the two start disagreeing.
    //
    // This reads the same cached query the hydration widget does, so it costs no extra request.
    const hydration = useHydrationToday()
    useReloadOnDayChange(hydration.data?.day, hydration.refetch)

    return (
        <>
            <div className="tracker-row" style={{ marginBottom: 'calc(var(--step) * 3)' }}>
                <HydrationWidget />
                <StepWidget />
                <SleepWidget />
                <WeightWidget />
            </div>

            <div className="main-columns">
                <QuestLog />
                <HabitPanel />
            </div>
        </>
    )
}

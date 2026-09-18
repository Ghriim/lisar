export interface Tab<TValue extends string> {
    value: TValue
    label: string
}

interface TabsProps<TValue extends string> {
    tabs: Tab<TValue>[]
    current: TValue
    onChange: (value: TValue) => void
}

export function Tabs<TValue extends string>({ tabs, current, onChange }: TabsProps<TValue>) {
    return (
        <div className="tabs" role="tablist">
            {tabs.map((tab) => (
                <button
                    key={tab.value}
                    type="button"
                    role="tab"
                    className="tab"
                    aria-selected={tab.value === current}
                    onClick={() => onChange(tab.value)}
                >
                    {tab.label}
                </button>
            ))}
        </div>
    )
}

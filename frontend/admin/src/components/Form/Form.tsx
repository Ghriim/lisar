import { Form as AntForm, Typography } from 'antd'
import { useCallback, useState, type ReactNode } from 'react'
import { RequiredMark } from './RequiredMark'
import { MandatoryFields, type RegisterMandatory } from './useMandatory'

/**
 * Called on every edit with the fields just changed and all of them; what it returns is written
 * back into the form. A field that fills in another — equipments ticking what a set records —
 * says so here, without the page ever holding the form itself.
 */
export type ValuesChangeHandler<TValues> = (changed: Partial<TValues>, values: TValues) => Partial<TValues> | void

interface FormProps<TValues> {
    initialValues?: Partial<TValues>
    onSubmit: (values: TValues) => void
    onValuesChange?: ValuesChangeHandler<TValues>
    children: ReactNode
}

/**
 * Every form in the back-office is one of these: vertical labels, an asterisk after each
 * mandatory one, and the fields below bound by name. It opens on the legend `* Champ obligatoire`
 * as soon as one of its fields is mandatory, and on nothing otherwise — see
 * docs/dev/frontend-conventions.md, §2.1.
 */
export function Form<TValues extends object>({ initialValues, onSubmit, onValuesChange, children }: FormProps<TValues>) {
    const [form] = AntForm.useForm<TValues>()
    const [mandatoryCount, setMandatoryCount] = useState(0)
    const register = useCallback<RegisterMandatory>(() => {
        setMandatoryCount((count) => count + 1)

        return () => setMandatoryCount((count) => count - 1)
    }, [])

    return (
        <MandatoryFields.Provider value={register}>
            <AntForm<TValues>
                form={form}
                layout="vertical"
                requiredMark={(label, { required }) => (
                    <>
                        {label}
                        {required && (
                            <>
                                {' '}
                                <RequiredMark />
                            </>
                        )}
                    </>
                )}
                initialValues={initialValues}
                onFinish={onSubmit}
                onValuesChange={(changed, values) => {
                    const patch = onValuesChange?.(changed, values)
                    if (patch) {
                        form.setFieldsValue(patch as Parameters<typeof form.setFieldsValue>[0])
                    }
                }}
            >
                {mandatoryCount > 0 && (
                    <Typography.Paragraph type="secondary">
                        <RequiredMark /> Champ obligatoire
                    </Typography.Paragraph>
                )}
                {children}
            </AntForm>
        </MandatoryFields.Provider>
    )
}

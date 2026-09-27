import { Form as AntForm, Input, InputNumber, Select, Switch } from 'antd'
import type { ReactNode } from 'react'
import { Row } from './Layout'

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
 * Every form in the back-office is one of these: vertical labels, no asterisks, and the fields
 * below bound by name.
 */
export function Form<TValues extends object>({ initialValues, onSubmit, onValuesChange, children }: FormProps<TValues>) {
    const [form] = AntForm.useForm<TValues>()

    return (
        <AntForm<TValues>
            form={form}
            layout="vertical"
            requiredMark={false}
            initialValues={initialValues}
            onFinish={onSubmit}
            onValuesChange={(changed, values) => {
                const patch = onValuesChange?.(changed, values)
                if (patch) {
                    form.setFieldsValue(patch as Parameters<typeof form.setFieldsValue>[0])
                }
            }}
        >
            {children}
        </AntForm>
    )
}

/**
 * The house rule, in one place: centred, cancel first, the verb that commits second.
 * See docs/dev/frontend-conventions.md.
 */
export function FormActions({ children }: { children: ReactNode }) {
    return (
        <Row justify="center" gap={8}>
            {children}
        </Row>
    )
}

interface FieldProps {
    name: string
    label: string
    required?: boolean
    placeholder?: string
    /** A line under the field explaining what it does to the rest of the app. */
    hint?: string
    autoFocus?: boolean
    disabled?: boolean
}

function rulesFor(required: boolean) {
    return required ? [{ required: true, message: 'Requis' }] : undefined
}

export function TextField({ name, label, required = false, placeholder, hint, autoFocus, disabled }: FieldProps) {
    return (
        <AntForm.Item label={label} name={name} rules={rulesFor(required)} extra={hint}>
            <Input placeholder={placeholder} autoFocus={autoFocus} disabled={disabled} />
        </AntForm.Item>
    )
}

export function EmailField(props: FieldProps) {
    return (
        <AntForm.Item label={props.label} name={props.name} rules={rulesFor(props.required ?? false)}>
            <Input type="email" autoComplete="email" autoFocus={props.autoFocus} />
        </AntForm.Item>
    )
}

export function PasswordField(props: FieldProps) {
    return (
        <AntForm.Item label={props.label} name={props.name} rules={rulesFor(props.required ?? false)}>
            <Input.Password autoComplete="current-password" />
        </AntForm.Item>
    )
}

interface NumberFieldProps extends FieldProps {
    min?: number
    max?: number
}

export function NumberField({ name, label, required = false, hint, min, max }: NumberFieldProps) {
    return (
        <AntForm.Item label={label} name={name} rules={rulesFor(required)} extra={hint}>
            <InputNumber min={min} max={max} style={{ width: '100%' }} />
        </AntForm.Item>
    )
}

export interface SelectOption {
    value: string
    label: ReactNode
}

/** Options shown under a heading — muscles under their group. */
export interface SelectOptionGroup {
    label: string
    options: SelectOption[]
}

interface SelectFieldProps extends FieldProps {
    options: SelectOption[] | SelectOptionGroup[]
    /** Several values at once; the field then holds a list, empty when nothing is picked. */
    multiple?: boolean
    /** Typing narrows the options by their label, which must then be text. */
    searchable?: boolean
}

export function SelectField({
    name,
    label,
    required = false,
    hint,
    placeholder,
    options,
    multiple = false,
    searchable = false,
}: SelectFieldProps) {
    return (
        <AntForm.Item label={label} name={name} rules={rulesFor(required)} extra={hint}>
            <Select<string | string[], SelectOption | SelectOptionGroup>
                options={options}
                mode={multiple ? 'multiple' : undefined}
                placeholder={placeholder}
                showSearch={searchable ? { optionFilterProp: 'label' } : false}
            />
        </AntForm.Item>
    )
}

export function SwitchField({ name, label, hint, disabled }: FieldProps) {
    return (
        <AntForm.Item label={label} name={name} valuePropName="checked" extra={hint}>
            <Switch disabled={disabled} />
        </AntForm.Item>
    )
}

interface TextAreaFieldProps extends FieldProps {
    rows?: number
}

export function TextAreaField({ name, label, required = false, placeholder, hint, rows = 3 }: TextAreaFieldProps) {
    return (
        <AntForm.Item label={label} name={name} rules={rulesFor(required)} extra={hint}>
            <Input.TextArea rows={rows} placeholder={placeholder} />
        </AntForm.Item>
    )
}

interface TextAreaProps {
    value: string
    onChange: (value: string) => void
    placeholder?: string
    rows?: number
}

/**
 * Standalone, outside a Form: the note box on an account is a single control, not a form, so it
 * takes a value and a handler rather than a field name.
 */
export function TextArea({ placeholder, rows = 3, value, onChange }: TextAreaProps) {
    return (
        <Input.TextArea
            rows={rows}
            value={value}
            placeholder={placeholder}
            onChange={(event) => onChange(event.target.value)}
        />
    )
}

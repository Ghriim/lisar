import { Form as AntForm, Switch } from 'antd'
import type { FieldProps } from './fieldProps'

/** On or off: it always holds a value, so it is never mandatory. */
export function SwitchField({ name, label, hint, disabled }: Omit<FieldProps, 'isMandatory'>) {
    return (
        <AntForm.Item label={label} name={name} valuePropName="checked" extra={hint}>
            <Switch disabled={disabled} />
        </AntForm.Item>
    )
}

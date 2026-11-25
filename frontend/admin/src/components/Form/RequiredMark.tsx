import { theme } from 'antd'

/** The asterisk after a mandatory label, and in the legend that explains it. */
export function RequiredMark() {
    const { token } = theme.useToken()

    return (
        <span aria-hidden="true" style={{ color: token.colorPrimary }}>
            *
        </span>
    )
}

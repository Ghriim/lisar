import { LogOut, PanelLeftClose, PanelLeftOpen, type LucideIcon } from 'lucide-react'
import { Link, NavLink } from 'react-router-dom'

export interface NavItem {
    to: string
    label: string
    icon: LucideIcon
}

interface SideNavProps {
    /** Aligned at the top: the screens the app is for. */
    primary: NavItem[]
    /** Pushed to the bottom: everything around them. The way out always comes last. */
    secondary: NavItem[]
    collapsed: boolean
    /** Absent when the width decides on its own — a phone always gets icons only. */
    onToggle?: () => void
    onSignOut: () => void
}

/**
 * The menu down the left edge. Folded, it keeps only the icons, and each one names itself on
 * hover like any other icon-only control.
 */
export function SideNav({ primary, secondary, collapsed, onToggle, onSignOut }: SideNavProps) {
    return (
        <nav className={collapsed ? 'side-nav side-nav-collapsed' : 'side-nav'} aria-label="Menu principal">
            <div className="side-nav-head">
                <Link to="/" className="side-nav-brand side-nav-tip" data-tooltip="LISAR" aria-label="LISAR — accueil">
                    {collapsed ? (
                        <span className="wordmark">L</span>
                    ) : (
                        <>
                            <span className="wordmark">LISAR</span>
                            <span className="wordmark-sub">Life is a RPG</span>
                        </>
                    )}
                </Link>

                {onToggle !== undefined && (
                    <SideNavButton
                        icon={collapsed ? PanelLeftOpen : PanelLeftClose}
                        label={collapsed ? 'Déplier' : 'Replier'}
                        onClick={onToggle}
                        expanded={!collapsed}
                        iconOnly
                    />
                )}
            </div>

            <ul className="side-nav-list">
                {primary.map((item) => (
                    <SideNavLink key={item.to} item={item} />
                ))}
            </ul>

            <ul className="side-nav-list side-nav-bottom">
                {secondary.map((item) => (
                    <SideNavLink key={item.to} item={item} />
                ))}
                <li>
                    <SideNavButton icon={LogOut} label="Se déconnecter" onClick={onSignOut} />
                </li>
            </ul>
        </nav>
    )
}

function SideNavLink({ item: { to, label, icon: Icon } }: { item: NavItem }) {
    return (
        <li>
            {/* `end` on the root only, or the dashboard would light up under every other page. */}
            <NavLink to={to} end={to === '/'} className="side-nav-item side-nav-tip" data-tooltip={label} aria-label={label}>
                <Icon size={18} strokeWidth={2} aria-hidden />
                <span className="side-nav-label">{label}</span>
            </NavLink>
        </li>
    )
}

interface SideNavButtonProps {
    icon: LucideIcon
    label: string
    onClick: () => void
    expanded?: boolean
    /** Never shows its label, folded or not — so it names itself on hover in both states. */
    iconOnly?: boolean
}

function SideNavButton({ icon: Icon, label, onClick, expanded, iconOnly = false }: SideNavButtonProps) {
    return (
        <button
            type="button"
            className={iconOnly ? 'side-nav-item side-nav-tip side-nav-icon-only' : 'side-nav-item side-nav-tip'}
            data-tooltip={label}
            aria-label={label}
            aria-expanded={expanded}
            onClick={onClick}
        >
            <Icon size={18} strokeWidth={2} aria-hidden />
            {!iconOnly && <span className="side-nav-label">{label}</span>}
        </button>
    )
}

import React, { useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';

const navigate = (event, href) => {
    if (
        event.defaultPrevented
        || event.button !== 0
        || event.metaKey
        || event.ctrlKey
        || event.shiftKey
        || event.altKey
    ) return;

    if (window.Livewire?.navigate) {
        event.preventDefault();
        window.Livewire.navigate(href);
    }
};

const Icon = ({ name }) => {
    const paths = {
        dashboard: <><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></>,
        users: <><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></>,
        report: <><path d="M4 3h16v18H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></>,
        facility: <><path d="M3 21h18M5 21V7l7-4 7 4v14"/><path d="M9 10h2v2H9zM13 10h2v2h-2zM9 15h2v2H9zM13 15h2v2h-2z"/></>,
        amenities: <><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 8h8v8H8z"/></>,
        request: <><path d="M6 3h12v18H6z"/><path d="M9 8h6M9 12h6M9 16h4"/></>,
        schedule: <><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></>,
        feedback: <><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/><path d="M8 9h8M8 13h5"/></>,
        archive: <><path d="M3 6h18M5 6v15h14V6M4 3h16v3M9 11h6"/></>,
        profile: <><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></>,
        appearance: <><path d="M12 3a9 9 0 1 0 9 9c0-1.1-.2-2.15-.56-3.12A7 7 0 0 1 12 3z"/></>,
        logout: <><path d="M10 17l5-5-5-5M15 12H3"/><path d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"/></>,
    };
    return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">{paths[name] ?? paths.dashboard}</svg>;
};

function useOutsideClick(ref, close) {
    useEffect(() => {
        const handler = (event) => {
            if (ref.current && !ref.current.contains(event.target)) close();
        };
        document.addEventListener('pointerdown', handler);
        return () => document.removeEventListener('pointerdown', handler);
    }, [close, ref]);
}

function Notifications({ data, csrfToken }) {
    const [open, setOpen] = useState(false);
    const [unread, setUnread] = useState(data.unread);
    const [items, setItems] = useState([]);
    const [loaded, setLoaded] = useState(false);
    const [loading, setLoading] = useState(false);
    const ref = useRef(null);
    useOutsideClick(ref, () => setOpen(false));

    const toggle = async () => {
        const next = !open;
        setOpen(next);
        if (next && !loaded && !loading) {
            setLoading(true);
            fetch(data.recentUrl, { headers: { Accept: 'application/json' } })
                .then(response => response.ok ? response.json() : Promise.reject())
                .then(payload => {
                    setItems(payload.items ?? []);
                    setLoaded(true);
                })
                .finally(() => setLoading(false));
        }
        if (next && unread > 0) {
            setUnread(0);
            fetch(data.markReadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            }).catch(() => setUnread(data.unread));
        }
    };

    return <div className="react-dropdown" ref={ref}>
        <button className="react-icon-button" onClick={toggle} aria-expanded={open} aria-label={`${unread} unread notifications`}>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
            {unread > 0 && <span className="react-badge">{unread > 99 ? '99+' : unread}</span>}
        </button>
        {open && <div className="react-menu react-notification-menu">
            <div className="react-menu-title"><div><strong>Notifications</strong><small>{unread} unread</small></div><span>Updates</span></div>
            <div className="react-notification-list">
                {loading ? <div className="react-empty-state">Loading notifications…</div> : items.length ? items.map(item => <a href={item.actionUrl ?? data.destination} onClick={(event) => navigate(event, item.actionUrl ?? data.destination)} key={item.id} className="react-notification-item">
                    {item.unread && unread > 0 && <i />}
                    <strong>{item.message}</strong>
                    {item.facility && <span>{item.facility}</span>}
                    {item.reason && <em>Reason: {item.reason}</em>}
                    <small>{item.time}</small>
                </a>) : <div className="react-empty-state">No notifications yet.</div>}
            </div>
        </div>}
    </div>;
}

function AccountMenu({ user, csrfToken }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);
    useOutsideClick(ref, () => setOpen(false));
    const logout = async () => {
        if (!await window.confirmLogout()) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = user.logoutUrl;
        const token = document.createElement('input');
        token.type = 'hidden'; token.name = '_token'; token.value = csrfToken;
        form.appendChild(token); document.body.appendChild(form); form.submit();
    };

    return <div className="react-dropdown" ref={ref}>
        <button className="react-account-trigger" onClick={() => setOpen(!open)} aria-expanded={open}>
            <Avatar user={user}/><span className="react-account-name">{user.name}</span><span className={`react-chevron ${open ? 'up' : ''}`}>⌄</span>
        </button>
        {open && <div className="react-menu react-account-menu">
            <div className="react-user-summary"><Avatar user={user}/><div><strong>{user.name}</strong><small>{user.email}</small></div></div>
            <a href={user.profileUrl} onClick={(event) => navigate(event, user.profileUrl)}><Icon name="profile"/> <span>Profile settings</span></a>
            <button onClick={logout}><Icon name="logout"/> <span>Log out</span></button>
        </div>}
    </div>;
}

const Avatar = ({ user }) => user.avatar
    ? <img className="react-avatar" src={user.avatar} alt=""/>
    : <span className="react-avatar react-avatar-fallback">{user.initials}</span>;

function AppShell({ props }) {
    const [mobileOpen, setMobileOpen] = useState(false);
    const [collapsed, setCollapsed] = useState(() => localStorage.getItem('ui.sidebar.collapsed') === 'true');
    const [groups, setGroups] = useState(() => Object.fromEntries(props.navigation.map((group, index) => {
        const archiveIsActive = group.label === 'Archives' && group.items.some((item) => {
            const target = new URL(item.href, window.location.origin);
            const current = new URL(window.location.href);
            return target.pathname === current.pathname && target.search === current.search;
        });

        return [index, group.label !== 'Archives' || archiveIsActive];
    })));
    const [currentUrl, setCurrentUrl] = useState(() => window.location.href);

    useEffect(() => {
        document.body.classList.toggle('react-sidebar-collapsed', collapsed);
        localStorage.setItem('ui.sidebar.collapsed', String(collapsed));
    }, [collapsed]);

    useEffect(() => {
        const close = () => setMobileOpen(false);
        window.addEventListener('resize', close);
        return () => window.removeEventListener('resize', close);
    }, []);

    useEffect(() => {
        const syncLocation = () => {
            setCurrentUrl(window.location.href);
            setMobileOpen(false);
        };
        document.addEventListener('livewire:navigated', syncLocation);
        window.addEventListener('popstate', syncLocation);
        return () => {
            document.removeEventListener('livewire:navigated', syncLocation);
            window.removeEventListener('popstate', syncLocation);
        };
    }, []);

    const isActive = (item) => {
        const target = new URL(item.href, window.location.origin);
        const current = new URL(currentUrl);

        return target.pathname === current.pathname
            && target.search === current.search;
    };

    return <>
        <header className="react-app-header">
            <button
                className="react-sidebar-toggle"
                onClick={() => window.innerWidth < 1024 ? setMobileOpen(true) : setCollapsed(!collapsed)}
                aria-label={window.innerWidth < 1024 ? 'Open navigation' : collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                aria-expanded={window.innerWidth < 1024 ? mobileOpen : !collapsed}
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
                    <path d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <span className="react-header-spacer"/>
            <Notifications data={props.notifications} csrfToken={props.csrfToken}/>
            <AccountMenu user={props.user} csrfToken={props.csrfToken}/>
        </header>
        {mobileOpen && <button className="react-sidebar-backdrop" onClick={() => setMobileOpen(false)} aria-label="Close navigation"/>}
        <aside className={`react-app-sidebar ${collapsed ? 'is-collapsed' : ''} ${mobileOpen ? 'is-mobile-open' : ''}`}>
            <div className="react-sidebar-brand">
                <a href={props.brandUrl} onClick={(event) => navigate(event, props.brandUrl)}>
                    <picture>
                        <source media="(max-width: 63.999rem)" srcSet={props.collapsedLogoUrl ?? props.logoUrl}/>
                        <img
                            src={collapsed ? (props.collapsedLogoUrl ?? props.logoUrl) : props.logoUrl}
                            alt="SIEL SPACE"
                        />
                    </picture>
                </a>
            </div>
            <nav>
                {props.navigation.map((group, index) => {
                    const isArchiveGroup = group.label === 'Archives';

                    return <section key={group.label} className={isArchiveGroup ? 'react-archive-group' : ''}>
                        {isArchiveGroup ? <button
                            className={`react-archive-toggle ${groups[index] ? 'is-open' : ''}`}
                            onClick={() => setGroups({...groups, [index]: !groups[index]})}
                            aria-expanded={groups[index]}
                            title="Archives"
                        >
                            <Icon name="archive"/><span>Archives</span><b aria-hidden="true">⌄</b>
                        </button> : <button className="react-group-label" onClick={() => setGroups({...groups, [index]: !groups[index]})}>
                            <span>{group.label}</span><span>{groups[index] ? '−' : '+'}</span>
                        </button>}
                        {groups[index] && <div className={isArchiveGroup ? 'react-archive-items' : ''}>{group.items.map(item => <a key={`${group.label}-${item.label}`} href={item.href} onClick={(event) => navigate(event, item.href)} className={isActive(item) ? 'is-active' : ''} title={item.label}>
                            <Icon name={item.icon}/><span>{item.label}</span>
                        </a>)}</div>}
                    </section>;
                })}
            </nav>
            <div className="react-sidebar-footer"><Avatar user={props.user}/><span><strong>{props.user.name}</strong><small>{props.user.email}</small></span></div>
        </aside>
    </>;
}

const mountedShells = new WeakMap();

function mountAppShell() {
    const rootElement = document.getElementById('react-app-shell');
    const propsElement = document.getElementById('react-app-shell-props');

    if (!rootElement || !propsElement || mountedShells.has(rootElement)) {
        document.body.classList.toggle('react-shell-mounted', Boolean(rootElement));
        return;
    }

    try {
        const root = createRoot(rootElement);
        root.render(<AppShell props={JSON.parse(propsElement.textContent)}/>);
        mountedShells.set(rootElement, root);
        document.body.classList.add('react-shell-mounted');
    } catch (error) {
        document.body.classList.remove('react-shell-mounted');
        console.error('Unable to mount the application navigation shell.', error);
    }
}

mountAppShell();
document.addEventListener('DOMContentLoaded', mountAppShell);
document.addEventListener('livewire:navigated', mountAppShell);

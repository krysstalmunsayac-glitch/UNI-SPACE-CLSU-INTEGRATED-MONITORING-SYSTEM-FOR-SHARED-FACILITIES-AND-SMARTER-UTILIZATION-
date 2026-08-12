import React from 'react';
import { createRoot } from 'react-dom/client';

const navigate = (event, href) => {
    if (window.Livewire?.navigate && event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) {
        event.preventDefault();
        window.Livewire.navigate(href);
    }
};

const icons = {
    profile: (
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="8" r="4" stroke="currentColor" strokeWidth="1.8" />
            <path d="M4.5 21a7.5 7.5 0 0 1 15 0" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
        </svg>
    ),
    password: (
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <rect x="4" y="10" width="16" height="11" rx="3" stroke="currentColor" strokeWidth="1.8" />
            <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14.5v2" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
        </svg>
    ),
};

function SettingsNavigation({ active, urls }) {
    const items = [
        { id: 'profile', label: 'Profile', description: 'Personal information' },
        { id: 'password', label: 'Password', description: 'Account security' },
    ];

    return (
        <aside className="settings-nav-card">
            <div className="settings-nav-intro">
                <span>SIEL SPACE</span>
                <h2>Your account</h2>
                <p>Keep your details secure and your workspace personalized.</p>
            </div>

            <nav aria-label="Settings navigation">
                {items.map((item) => (
                    <a
                        key={item.id}
                        href={urls[item.id]}
                        onClick={(event) => navigate(event, urls[item.id])}
                        className={`settings-react-link ${active === item.id ? 'is-active' : ''}`}
                        aria-current={active === item.id ? 'page' : undefined}
                    >
                        <span className="settings-react-icon">{icons[item.id]}</span>
                        <span>
                            <strong>{item.label}</strong>
                            <small>{item.description}</small>
                        </span>
                        <span className="settings-link-arrow" aria-hidden="true">›</span>
                    </a>
                ))}
            </nav>

            <div className="settings-security-note">
                <span aria-hidden="true">✓</span>
                <p><strong>Protected account</strong>Your information is securely stored.</p>
            </div>
        </aside>
    );
}

function mountSettingsNavigation() {
    document.querySelectorAll('[data-settings-navigation]').forEach((element) => {
        if (element.dataset.reactMounted === 'true') return;

        element.dataset.reactMounted = 'true';
        createRoot(element).render(
            <SettingsNavigation
                active={element.dataset.active}
                urls={{
                    profile: element.dataset.profileUrl,
                    password: element.dataset.passwordUrl,
                }}
            />,
        );
    });
}

document.addEventListener('DOMContentLoaded', mountSettingsNavigation);
document.addEventListener('livewire:navigated', mountSettingsNavigation);

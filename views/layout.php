<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f0f11">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="AURA">
    <link rel="manifest" href="<?= BASE_URL ?>public/manifest.json">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>public/icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>public/icons/favicon-32.png">
    <title><?= APP_NAME ?> — Kadeřnický salon</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/style.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
</head>
<body>

<!-- NAVIGAČNÍ RAIL -->
<nav id="nav-rail" class="nav-rail">
    <div class="nav-rail-top">
        <span class="nav-logo">A</span>
    </div>
    <button class="nav-rail-btn active" data-view="dashboard" data-tip="Přehled">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    </button>
    <button class="nav-rail-btn" data-view="clients" data-tip="Klienti">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </button>
    <button class="nav-rail-btn" data-view="accounting" data-tip="Účetnictví">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    </button>
    <button class="nav-rail-btn" id="btn-quick-sale" data-tip="Rychlý prodej">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
    </button>
    <div style="flex:1"></div>
    <button class="nav-rail-btn" id="btn-theme-toggle" data-tip="Světlý/Tmavý režim">
        <svg id="theme-icon-moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg id="theme-icon-sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" hidden><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
    </button>
    <button class="nav-rail-btn" data-view="settings" data-tip="Nastavení">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
    </button>
    <button class="nav-rail-btn" id="btn-logout" data-tip="Odhlásit se">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </button>
</nav>

<!-- LEVÝ PANEL — seznam klientů -->
<aside id="sidebar" hidden>
    <header class="sidebar-header">
        <span class="app-logo"><?= APP_NAME ?></span>
        <span class="app-subtitle">Kartotéka klientů</span>
    </header>

    <div class="search-wrap">
        <input type="search" id="client-search" placeholder="Hledat klienta…" autocomplete="off">
        <button id="btn-toggle-inactive" class="btn-toggle-inactive" title="Zobrazit neaktivní klienty">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
        </button>
    </div>

    <ul id="client-list" class="client-list">
        <?php foreach ($clients as $c): ?>
        <li class="client-item" data-id="<?= $c['id'] ?>">
            <span class="client-name"><?= e($c['full_name']) ?></span>
            <span class="client-subtitle"><?= e($c['phone'] ?? '') ?></span>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="sidebar-count" id="client-count"><?= count($clients) ?> klientů</div>

    <div class="sidebar-footer">
        <button id="btn-new-client" class="btn btn-new-client">+ Nový klient</button>
    </div>
</aside>

<!-- PRAVÝ PANEL — detail + historie -->
<main id="main" hidden>

    <!-- Kontextový header -->
    <div class="main-header">
        <span id="main-header-text"></span>
    </div>

    <div class="main-scroll">

    <!-- Prázdný stav -->
    <div id="empty-state" class="empty-state">
        <div class="empty-icon">✦</div>
        <p>Vyberte klienta ze seznamu</p>
    </div>

    <!-- Detail klienta -->
    <section id="client-detail" class="panel" hidden>
        <div class="client-profile">
            <div class="client-avatar" id="detail-avatar">—</div>
            <div class="client-info">
                <h2 id="detail-name">—</h2>
                <div class="client-meta">
                    <span id="detail-phone">—</span>
                    <span class="meta-sep">·</span>
                    <span id="detail-status-text">—</span>
                </div>
            </div>
            <div class="client-actions-top">
                <button id="btn-edit-client" class="btn-visit-action" data-tip="Upravit klienta"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg></button>
                <button id="btn-delete-client" class="btn-visit-action btn-danger-icon" data-tip="Smazat klienta"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
                <button id="btn-new-sale" class="btn btn-ghost btn-sm">+ Prodej</button>
                <button id="btn-new-visit" class="btn btn-primary">+ Nová návštěva</button>
            </div>
        </div>
        <div class="client-stats" id="client-stats"></div>
        <div class="client-notes" id="client-notes" hidden>
            <span class="client-notes-label">Poznámky</span>
            <p id="client-notes-text"></p>
        </div>
    </section>

    <!-- Záložky Historie / Prodeje / Poznámky -->
    <div class="detail-tabs" id="detail-tabs" hidden>
        <button class="detail-tab active" data-tab="visits">Historie návštěv</button>
        <button class="detail-tab" data-tab="sales">Prodeje</button>
        <button class="detail-tab" data-tab="notes">Poznámky</button>
    </div>

    <!-- Historie návštěv -->
    <section id="visit-history" class="panel panel-grow" hidden>
        <div class="panel-header">
            <h3>HISTORIE NÁVŠTĚV</h3>
            <div class="visit-filters" id="visit-filters">
                <button class="vf-pill active" data-months="">Vše</button>
                <button class="vf-pill" data-months="3">3 měs.</button>
                <button class="vf-pill" data-months="6">6 měs.</button>
                <button class="vf-pill" data-months="12">Rok</button>
                <span class="vf-sep"></span>
                <input type="text" id="vf-search" class="vf-search" placeholder="Hledat datum…" autocomplete="off">
            </div>
        </div>
        <ul id="visit-list" class="visit-list"></ul>
    </section>

    <!-- Prodeje klienta -->
    <section id="sales-history" class="panel panel-grow" hidden>
        <div class="panel-header">
            <h3>PRODEJE PRODUKTŮ</h3>
        </div>
        <ul id="sales-list" class="visit-list"></ul>
    </section>

    <!-- Poznámky klienta -->
    <section id="notes-history" class="panel panel-grow" hidden>
        <div class="panel-header">
            <h3>POZNÁMKY</h3>
            <button id="btn-add-note" class="btn btn-ghost btn-sm">+ Poznámka</button>
        </div>
        <div id="note-form-wrap" class="note-form-wrap" hidden>
            <textarea id="note-input" class="note-input" rows="3" placeholder="Napište poznámku…"></textarea>
            <div class="note-form-actions">
                <button id="note-save" class="btn btn-primary btn-sm">Uložit</button>
                <button id="note-cancel" class="btn btn-ghost btn-sm">Zrušit</button>
            </div>
        </div>
        <ul id="note-list" class="note-list"></ul>
    </section>

    </div><!-- /main-scroll -->

    <!-- Editor receptur (overlay přes pravý panel) -->
    <div id="formula-overlay" class="formula-overlay" hidden>

        <div class="fo-header">
            <div class="fo-profile">
                <button id="btn-fo-back" type="button" class="btn-fo-back" title="Zpět">←</button>
                <div class="fo-avatar" id="fo-avatar">—</div>
                <div class="fo-profile-info">
                    <span class="fo-profile-name" id="fo-client-name"></span>
                    <span class="fo-profile-sub" id="fo-profile-sub">Nová návštěva · dnes</span>
                    <input type="date" id="fo-visit-date" class="fo-visit-date" title="Datum návštěvy">
                </div>
            </div>

            <h4 class="fo-section-label">RECEPTURA <span id="fo-bowl-count" class="fo-bowl-count"></span></h4>

            <div class="fo-toggles" id="fo-toggles"></div>

            <div class="fo-actions-bar" id="fo-actions-bar"></div>
        </div>

        <div class="fo-scroll-area">
            <div class="fo-shortcuts">
                <kbd>Ctrl+S</kbd> uložit · 
                <kbd>Tab</kbd> produkt → gramáž · 
                <kbd>Enter</kbd> nový produkt · 
                <kbd>Enter na prázdném</kbd> → oxidant · 
                <kbd>Shift+Enter</kbd> nová miska · 
                <kbd>Ctrl+⌫</kbd> smazat řádek · 
                <kbd>Ctrl+Del</kbd> smazat misku
            </div>

            <div id="fo-bowls" class="fo-bowls"></div>
        </div>

        <div class="fo-footer">
            <textarea id="fo-note" class="fo-note" placeholder="Poznámka k návštěvě…" rows="2"></textarea>
            <div class="fo-footer-buttons">
                <button id="btn-cancel-visit" class="btn btn-outline btn-cancel-visit">Zrušit</button>
                <button id="btn-save-visit" class="btn btn-primary btn-save-visit">Uložit návštěvu</button>
            </div>
        </div>

    </div><!-- /formula-overlay -->

</main>

<!-- ═══════════════════════════════════════════════════════════════════════════
     MODÁLNÍ OKNO — nový / editace klienta
═══════════════════════════════════════════════════════════════════════════ -->
<div id="client-modal" class="modal-backdrop" hidden role="dialog" aria-modal="true">
    <div class="modal">
        <h3 id="modal-title">Nový klient</h3>
        <form id="client-form" autocomplete="off">
            <input type="hidden" id="cf-id" name="id">
            <div class="form-row">
                <label for="cf-name">Jméno a příjmení <span class="required">*</span></label>
                <input type="text" id="cf-name" name="full_name" required>
            </div>
            <div class="form-row">
                <label for="cf-phone">Telefon</label>
                <input type="tel" id="cf-phone" name="phone">
            </div>
            <div class="form-row">
                <label for="cf-status">Status</label>
                <select id="cf-status" name="status">
                    <option value="active">Aktivní</option>
                    <option value="vip">VIP</option>
                    <option value="inactive">Neaktivní</option>
                </select>
            </div>
            <div class="form-row">
                <label for="cf-notes">Poznámky</label>
                <textarea id="cf-notes" name="notes" rows="3"></textarea>
            </div>
            <div class="form-row">
                <label>Štítky</label>
                <div class="tag-picker" id="cf-tags">
                    <label class="tag-pick tag-pink"><input type="checkbox" value="pink"><span></span> Růžový</label>
                    <label class="tag-pick tag-purple"><input type="checkbox" value="purple"><span></span> Fialový</label>
                    <label class="tag-pick tag-blue"><input type="checkbox" value="blue"><span></span> Modrý</label>
                    <label class="tag-pick tag-green"><input type="checkbox" value="green"><span></span> Zelený</label>
                    <label class="tag-pick tag-yellow"><input type="checkbox" value="yellow"><span></span> Žlutý</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-modal-cancel" class="btn btn-ghost">Zrušit</button>
                <button type="submit" class="btn btn-primary">Uložit</button>
            </div>
        </form>
    </div>
</div>

<!-- Receipt modal -->
<div id="receipt-modal" class="modal-backdrop" hidden role="dialog" aria-modal="true">
    <div class="modal modal-receipt">
        <div class="receipt-modal-header">
            <h3>Náhled účtenky</h3>
            <button type="button" id="btn-receipt-close" class="btn btn-ghost btn-sm">✕</button>
        </div>
        <div id="receipt-content" class="receipt-body"></div>
        <div class="modal-footer">
            <button type="button" id="btn-receipt-cancel" class="btn btn-ghost">Zavřít</button>
            <button type="button" id="btn-receipt-print" class="btn btn-primary"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg> Tisknout</button>
        </div>
    </div>
</div>

<!-- Toast notifikace -->
<div id="toast" class="toast" hidden></div>

<!-- Confirm modal -->
<div id="confirm-modal" class="modal-backdrop" hidden role="dialog" aria-modal="true">
    <div class="modal modal-sm">
        <h3 id="confirm-title">Potvrdit</h3>
        <p id="confirm-message"></p>
        <div class="modal-footer">
            <button type="button" id="btn-confirm-cancel" class="btn btn-ghost">Zrušit</button>
            <button type="button" id="btn-confirm-ok" class="btn btn-danger">Smazat</button>
        </div>
    </div>
</div>

<!-- Prompt modal -->
<div id="prompt-modal" class="modal-backdrop" hidden role="dialog" aria-modal="true">
    <div class="modal modal-sm">
        <h3 id="prompt-title">Zadat hodnotu</h3>
        <div class="form-row">
            <input type="text" id="prompt-input" placeholder="">
        </div>
        <div class="modal-footer">
            <button type="button" id="btn-prompt-cancel" class="btn btn-ghost">Zrušit</button>
            <button type="button" id="btn-prompt-ok" class="btn btn-primary">OK</button>
        </div>
    </div>
</div>

<!-- Save visit modal (cena) -->
<div id="save-visit-modal" class="modal-backdrop" hidden role="dialog" aria-modal="true">
    <div class="modal modal-sm">
        <h3>Uložit návštěvu</h3>
        <div class="form-row">
            <label for="sv-price">Cena návštěvy (Kč)</label>
            <input type="number" id="sv-price" min="0" step="1" placeholder="0">
        </div>
        <div class="modal-footer">
            <button type="button" id="btn-sv-cancel" class="btn btn-ghost">Zrušit</button>
            <button type="button" id="btn-sv-save" class="btn btn-primary">Uložit</button>
        </div>
    </div>
</div>

<!-- Billing modal -->
<div id="billing-modal" class="modal-backdrop" hidden role="dialog" aria-modal="true">
    <div class="modal modal-billing">
        <h3>Vyúčtování</h3>
        <form id="billing-form" autocomplete="off">
            <input type="hidden" id="bf-visit-id">
            <div class="billing-cols">
                <div class="billing-col billing-col-products">
                    <h4>Produkty na doma</h4>
                    <div id="billing-products" class="billing-product-list">
                        <!-- dynamicky JS -->
                    </div>
                    <button type="button" id="btn-billing-add-product" class="btn btn-ghost btn-sm" style="align-self:flex-start">+ Přidat produkt</button>
                </div>
                <div class="billing-col billing-col-payment">
                    <h4>Platba</h4>
                    <p id="billing-visit-info" class="billing-visit-info"></p>
                    <div id="billing-summary" class="billing-summary"></div>
                    <div class="form-row">
                        <label for="bf-amount">Částka od klienta (Kč)</label>
                        <input type="number" id="bf-amount" min="0" step="1" placeholder="0" required>
                    </div>
                    <div class="form-row">
                        <label for="bf-change">Vrátit (Kč)</label>
                        <input type="number" id="bf-change" min="0" step="1" placeholder="0" value="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-billing-cancel" class="btn btn-ghost">Zrušit</button>
                <button type="submit" class="btn btn-primary">Uložit</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     PRODEJ — modální okno
═══════════════════════════════════════════════════════════════════════════ -->
<div id="sale-modal" class="modal-backdrop" hidden role="dialog" aria-modal="true">
    <div class="modal modal-sale">
        <h3>Nový prodej</h3>
        <div id="sale-items" class="sale-items">
            <!-- dynamicky JS -->
        </div>
        <button type="button" id="btn-sale-add-row" class="btn btn-ghost btn-sm" style="margin-bottom:12px">+ Přidat produkt</button>
        <div class="form-row">
            <label for="sale-note">Poznámka</label>
            <input type="text" id="sale-note" placeholder="Volitelná poznámka…">
        </div>
        <div class="sale-total">
            Celkem: <strong id="sale-total-value">0 Kč</strong>
        </div>
        <div class="modal-footer">
            <button type="button" id="btn-sale-cancel" class="btn btn-ghost">Zrušit</button>
            <button type="button" id="btn-sale-save" class="btn btn-primary">Uložit prodej</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     NASTAVENÍ — číselníky
═══════════════════════════════════════════════════════════════════════════ -->
<!-- ═══════════════════════════════════════════════════════════════════════════
     DASHBOARD
═══════════════════════════════════════════════════════════════════════════ -->
<div id="dashboard-view" class="dashboard-view">
    <div class="dash-header">
        <h2>Přehled</h2>
        <span class="dash-date" id="dash-date"></span>
    </div>
    <div class="dash-grid">
        <div class="dash-card dash-card-hero">
            <span class="dash-card-value" id="dash-total-revenue">—</span>
            <span class="dash-card-label">Tržby celkem</span>
            <div class="dash-card-breakdown">
                <span>Služby <strong id="dash-revenue">—</strong></span>
                <span>Produkty <strong id="dash-retail-revenue">—</strong></span>
            </div>
        </div>
        <div class="dash-card">
            <span class="dash-card-value" id="dash-clients">—</span>
            <span class="dash-card-label">Klientů</span>
        </div>
        <div class="dash-card">
            <span class="dash-card-value" id="dash-visits">—</span>
            <span class="dash-card-label">Návštěv celkem</span>
        </div>
        <div class="dash-card">
            <span class="dash-card-value" id="dash-month-visits">—</span>
            <span class="dash-card-label">Tento měsíc</span>
        </div>
    </div>
    <div class="dash-bottom">
        <div class="dash-section">
            <h3>Návštěvy po měsících</h3>
            <div class="dash-chart" id="dash-chart"></div>
        </div>
        <div class="dash-section">
            <h3>Poslední návštěvy</h3>
            <ul class="dash-recent" id="dash-recent"></ul>
        </div>
        <div class="dash-section">
            <h3>Nevyúčtované návštěvy</h3>
            <ul class="dash-recent" id="dash-unpaid"></ul>
        </div>
        <div class="dash-section dash-section-full">
            <h3>Retence klientů <span class="dash-retention-hint">Klienti bez návštěvy déle než 6 týdnů</span></h3>
            <ul class="dash-recent" id="dash-retention"></ul>
        </div>
    </div>
</div>

<div id="settings-view" class="settings-view" hidden>
    <div class="settings-header">
        <h2>Nastavení</h2>
    </div>
    <!-- Hlavní záložky nastavení -->
    <div class="settings-main-tabs">
        <button class="settings-main-tab active" data-settings-tab="codelists">Číselníky</button>
        <button class="settings-main-tab" data-settings-tab="salon">Salon</button>
        <button class="settings-main-tab" data-settings-tab="auth">Přihlášení</button>
        <button class="settings-main-tab" data-settings-tab="backup">Záloha DB</button>
        <button class="settings-main-tab" data-settings-tab="about">O aplikaci</button>
    </div>

    <!-- ═══ TAB: Číselníky ═══ -->
    <div id="settings-panel-codelists" class="settings-panel">
        <div class="settings-tabs">
            <button class="settings-tab active" data-cl-type="service">Úkony</button>
            <button class="settings-tab" data-cl-type="ratio">Poměry</button>
            <button class="settings-tab" data-cl-type="bowl">Misky</button>
            <button class="settings-tab" data-cl-type="material">Materiály</button>
            <button class="settings-tab" data-cl-type="retail">Produkty na doma</button>
            <button class="settings-tab" data-cl-type="tag">Štítky</button>
        </div>
        <div class="settings-content">
            <div class="settings-toolbar">
                <input type="text" id="cl-search" class="cl-search" placeholder="Hledat..." autocomplete="off">
                <button id="btn-cl-add" class="btn btn-primary btn-sm">+ Přidat</button>
            </div>
            <!-- Code list table (Úkony, Poměry, Misky) -->
            <div id="cl-table-wrap">
                <table class="cl-table">
                    <thead>
                        <tr>
                            <th>Ikona</th>
                            <th>Název</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cl-tbody"></tbody>
                </table>
                <div id="cl-pagination" class="cl-pagination"></div>
            </div>
            <!-- Material accordion (Materiály) -->
            <div id="mat-accordion" hidden></div>
            <!-- Retail accordion (Produkty na doma) -->
            <div id="retail-accordion" hidden></div>
            <!-- Tags list (Štítky) -->
            <div id="tag-list-wrap" hidden></div>
        </div>
    </div>

    <!-- ═══ TAB: Salon ═══ -->
    <div id="settings-panel-salon" class="settings-panel" hidden>
        <div class="settings-content">
            <div class="settings-section">
                <div class="settings-section-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </div>
                <h3>Údaje o salonu</h3>
                <p class="settings-section-desc">Tyto údaje se zobrazí na tiskových účtenkách.</p>
                <form id="salon-settings-form" class="settings-form" autocomplete="off">
                    <div class="form-row">
                        <label for="salon-name">Název salonu</label>
                        <input type="text" id="salon-name" placeholder="např. Salon Aura">
                    </div>
                    <div class="form-row">
                        <label for="salon-address">Adresa</label>
                        <input type="text" id="salon-address" placeholder="např. Hlavní 123, Praha">
                    </div>
                    <div class="form-row">
                        <label for="salon-phone">Telefon</label>
                        <input type="text" id="salon-phone" placeholder="např. +420 123 456 789">
                    </div>
                    <div class="form-row">
                        <label for="salon-ico">IČO</label>
                        <input type="text" id="salon-ico" placeholder="např. 12345678">
                    </div>
                    <div class="form-row">
                        <label for="salon-note">Doplňující text na účtence</label>
                        <input type="text" id="salon-note" placeholder="např. Děkujeme za návštěvu!">
                    </div>
                    <div id="salon-msg" class="settings-msg" hidden></div>
                    <div class="settings-form-actions">
                        <button type="submit" class="btn btn-primary">Uložit údaje</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ═══ TAB: Přihlášení ═══ -->
    <div id="settings-panel-auth" class="settings-panel" hidden>
        <div class="settings-content">
            <div class="settings-section">
                <div class="settings-section-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <h3>Změna přihlašovacích údajů</h3>
                <p class="settings-section-desc">Změňte uživatelské jméno a heslo pro přihlášení do aplikace.</p>
                <form id="auth-settings-form" class="settings-form" autocomplete="off">
                    <div class="form-row">
                        <label for="auth-username">Uživatelské jméno</label>
                        <input type="text" id="auth-username" required autocomplete="off">
                    </div>
                    <div class="form-row">
                        <label for="auth-current-pass">Aktuální heslo <span class="required">*</span></label>
                        <input type="password" id="auth-current-pass" required autocomplete="off">
                    </div>
                    <div class="form-row">
                        <label for="auth-new-pass">Nové heslo</label>
                        <input type="password" id="auth-new-pass" autocomplete="new-password" placeholder="Ponechte prázdné pro zachování">
                    </div>
                    <div class="form-row">
                        <label for="auth-new-pass2">Nové heslo znovu</label>
                        <input type="password" id="auth-new-pass2" autocomplete="new-password">
                    </div>
                    <div id="auth-msg" class="settings-msg" hidden></div>
                    <div class="settings-form-actions">
                        <button type="submit" class="btn btn-primary">Uložit změny</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ═══ TAB: Záloha DB ═══ -->
    <div id="settings-panel-backup" class="settings-panel" hidden>
        <div class="settings-content">
            <div class="settings-section">
                <div class="settings-section-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                </div>
                <h3>Záloha databáze</h3>
                <p class="settings-section-desc">Stáhněte kompletní zálohu databáze ve formátu SQL. Záloha obsahuje všechna data — klienty, návštěvy, produkty, prodeje a nastavení.</p>
                <div class="settings-form-actions">
                    <button id="btn-db-backup" class="btn btn-primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Stáhnout zálohu (.sql)
                    </button>
                </div>
                <p class="settings-hint">Doporučujeme provádět zálohu pravidelně, ideálně týdně.</p>
            </div>
        </div>
    </div>

    <!-- ═══ TAB: O aplikaci ═══ -->
    <div id="settings-panel-about" class="settings-panel" hidden>
        <div class="settings-content">
            <div class="settings-about">
                <div class="about-logo">A</div>
                <h3>AURA</h3>
                <p class="about-subtitle">Kadeřnický salon — správa klientů</p>
                <dl class="about-info">
                    <dt>Verze</dt><dd>1.0.0</dd>
                    <dt>Technologie</dt><dd>PHP 8 · MySQL 8 · Vanilla JS</dd>
                    <dt>Autor</dt><dd>Martin Vítek</dd>
                </dl>
                <p class="about-copy">&copy; <?= date('Y') ?> AURA. Vytvořeno s láskou.</p>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     ÚČETNÍ PŘEHLEDY
═══════════════════════════════════════════════════════════════════════════ -->
<div id="accounting-view" class="accounting-view" hidden>
    <div class="acc-header">
        <h2>Účetní přehledy</h2>
        <div class="acc-header-actions">
            <select id="acc-year" class="acc-select"></select>
            <button class="btn btn-sm btn-outline" id="acc-export-csv">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export CSV
            </button>
        </div>
    </div>

    <!-- Roční přehled tabulka -->
    <div class="acc-table-wrap">
        <table class="acc-table" id="acc-yearly-table">
            <thead>
                <tr>
                    <th>Měsíc</th>
                    <th class="num">Návštěvy</th>
                    <th class="num">Služby</th>
                    <th class="num">Prodeje</th>
                    <th class="num">Produkty</th>
                    <th class="num">Celkem</th>
                </tr>
            </thead>
            <tbody id="acc-yearly-body"></tbody>
            <tfoot id="acc-yearly-foot"></tfoot>
        </table>
    </div>

    <!-- Denní uzávěrka -->
    <div class="acc-daily">
        <div class="acc-daily-header">
            <h3>Denní uzávěrka</h3>
            <input type="date" id="acc-daily-date" class="acc-input">
            <button class="btn btn-sm btn-accent" id="acc-close-day">Uzavřít den</button>
        </div>
        <div id="acc-daily-content" class="acc-daily-content"></div>
    </div>
</div>

<!-- Modal pro číselník -->
<div id="cl-modal" class="modal-backdrop" hidden role="dialog" aria-modal="true">
    <div class="modal modal-sm">
        <h3 id="cl-modal-title">Přidat položku</h3>
        <form id="cl-form" autocomplete="off">
            <input type="hidden" id="clf-id">
            <input type="hidden" id="clf-type">
            <div class="form-row">
                <label for="clf-name">Název <span class="required">*</span></label>
                <input type="text" id="clf-name" required>
            </div>
            <div class="form-row" id="clf-icon-row">
                <label for="clf-icon">Ikona (emoji)</label>
                <input type="text" id="clf-icon" placeholder="např. ✂️">
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-cl-cancel" class="btn btn-ghost">Zrušit</button>
                <button type="submit" class="btn btn-primary">Uložit</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal pro štítek -->
<div id="tag-modal" class="modal-backdrop" hidden role="dialog" aria-modal="true">
    <div class="modal modal-sm">
        <h3 id="tag-modal-title">Přidat štítek</h3>
        <form id="tag-form" autocomplete="off">
            <input type="hidden" id="tagf-id">
            <div class="form-row">
                <label for="tagf-name">Název štítku <span class="required">*</span></label>
                <input type="text" id="tagf-name" required placeholder="např. Blond specialistka">
            </div>
            <div class="form-row">
                <label for="tagf-color">Barva</label>
                <div class="tag-color-picker">
                    <input type="color" id="tagf-color" value="#a78bfa">
                    <div class="tag-color-presets" id="tag-color-presets">
                        <button type="button" class="tag-color-btn" data-color="#ef4444" style="background:#ef4444"></button>
                        <button type="button" class="tag-color-btn" data-color="#f59e0b" style="background:#f59e0b"></button>
                        <button type="button" class="tag-color-btn" data-color="#22c55e" style="background:#22c55e"></button>
                        <button type="button" class="tag-color-btn" data-color="#06b6d4" style="background:#06b6d4"></button>
                        <button type="button" class="tag-color-btn" data-color="#3b82f6" style="background:#3b82f6"></button>
                        <button type="button" class="tag-color-btn" data-color="#8b5cf6" style="background:#8b5cf6"></button>
                        <button type="button" class="tag-color-btn" data-color="#ec4899" style="background:#ec4899"></button>
                        <button type="button" class="tag-color-btn" data-color="#64748b" style="background:#64748b"></button>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <label>Náhled</label>
                <div id="tag-preview" class="tag-preview"></div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-tag-cancel" class="btn btn-ghost">Zrušit</button>
                <button type="submit" class="btn btn-primary">Uložit</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal pro materiál (produkt) -->
<div id="mat-modal" class="modal-backdrop" hidden role="dialog" aria-modal="true">
    <div class="modal modal-sm">
        <h3 id="mat-modal-title">Přidat materiál</h3>
        <form id="mat-form" autocomplete="off">
            <input type="hidden" id="matf-id">
            <div class="form-row">
                <label for="matf-series">Řada <span class="required">*</span></label>
                <input type="text" id="matf-series" required placeholder="např. Inoa">
            </div>
            <div class="form-row">
                <label for="matf-title">Název <span class="required">*</span></label>
                <input type="text" id="matf-title" required placeholder="např. Inoa 6.0">
            </div>
            <div class="form-row">
                <label for="matf-category">Typ</label>
                <input type="text" id="matf-category" placeholder="např. Barva">
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-mat-cancel" class="btn btn-ghost">Zrušit</button>
                <button type="submit" class="btn btn-primary">Uložit</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal pro retail produkt -->
<div id="retail-modal" class="modal-backdrop" hidden role="dialog" aria-modal="true">
    <div class="modal modal-sm">
        <h3 id="retail-modal-title">Přidat produkt</h3>
        <form id="retail-form" autocomplete="off">
            <input type="hidden" id="retf-id">
            <div class="form-row">
                <label for="retf-series">Řada <span class="required">*</span></label>
                <input type="text" id="retf-series" required placeholder="např. Absolut Repair">
            </div>
            <div class="form-row">
                <label for="retf-title">Název <span class="required">*</span></label>
                <input type="text" id="retf-title" required placeholder="např. Absolut Repair Šampon">
            </div>
            <div class="form-row">
                <label for="retf-volume">Balení</label>
                <input type="text" id="retf-volume" placeholder="např. 300 ml">
            </div>
            <div class="form-row">
                <label for="retf-category">Typ</label>
                <input type="text" id="retf-category" placeholder="např. Retail">
            </div>
            <div class="form-row">
                <label for="retf-price">Cena (Kč)</label>
                <input type="number" id="retf-price" min="0" step="1" placeholder="0">
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-retail-cancel" class="btn btn-ghost">Zrušit</button>
                <button type="submit" class="btn btn-primary">Uložit</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= BASE_URL ?>public/js/app.js?v=<?= time() ?>"></script>
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= BASE_URL ?>sw.js').catch(() => {});
}
</script>
</body>
</html>

/* ═══════════════════════════════════════════════════════════════════════════
   AURA — hlavní aplikační logika (vanilla JS)
   ═══════════════════════════════════════════════════════════════════════════ */

'use strict';

// ── Konstanty ─────────────────────────────────────────────────────────────────
const BASE = document.querySelector('base')?.href ?? '/';

// Barvy misek (cyklicky)
const BOWL_COLORS = [
    '#e53935', '#5c6bc0', '#34d399', '#fbbf24',
    '#f472b6', '#fb923c', '#e879f9', '#2dd4bf',
];

// Poměry oxidant (fallback)
const RATIOS = ['1:1', '1:1.5', '1:2', '2:1'];

// Akce pro přidání misek (fallback)
const BOWL_ACTIONS = ['Odrost', 'Délky', 'Melír', 'Toner', 'Odbarvení', 'Balayage'];

// ── Cache číselníků ───────────────────────────────────────────────────────────
const codeListCache = { service: [], ratio: [], bowl: [], material: [] };

function getRatios() {
    return codeListCache.ratio.length ? codeListCache.ratio.map(r => r.name) : RATIOS;
}

// ── Stav aplikace ─────────────────────────────────────────────────────────────
const state = {
    activeClientId: null,
    activeClientName: '',
    editingVisitId: null,   // null = nová návštěva
    visitOffset: 0,
    expandVisitId: null,
};

// ── Pomocné funkce ────────────────────────────────────────────────────────────

async function api(path, opts = {}) {
    const res = await fetch(path, {
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        ...opts,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error ?? `HTTP ${res.status}`);
    return data;
}

function toast(msg, type = 'success') {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.className = `toast toast-${type}`;
    el.hidden = false;
    clearTimeout(el._t);
    el._t = setTimeout(() => { el.hidden = true; }, type === 'error' ? 6000 : 3000);
}

// ── Modální confirm / prompt ──────────────────────────────────────────────────

function modalConfirm(message, { title = 'Potvrdit', okText = 'Smazat', okClass = 'btn btn-danger', html = false } = {}) {
    return new Promise(resolve => {
        const modal = document.getElementById('confirm-modal');
        document.getElementById('confirm-title').textContent = title;
        const msgEl = document.getElementById('confirm-message');
        if (html) {
            msgEl.innerHTML = message;
        } else {
            msgEl.textContent = message;
        }
        const btnOk = document.getElementById('btn-confirm-ok');
        btnOk.textContent = okText;
        btnOk.className = okClass;
        modal.hidden = false;

        function cleanup(result) {
            modal.hidden = true;
            btnOk.removeEventListener('click', onOk);
            document.getElementById('btn-confirm-cancel').removeEventListener('click', onCancel);
            modal.removeEventListener('click', onBackdrop);
            resolve(result);
        }
        function onOk() { cleanup(true); }
        function onCancel() { cleanup(false); }
        function onBackdrop(ev) { if (ev.target === modal) cleanup(false); }

        btnOk.addEventListener('click', onOk);
        document.getElementById('btn-confirm-cancel').addEventListener('click', onCancel);
        modal.addEventListener('click', onBackdrop);
    });
}

function modalPrompt(title, { placeholder = '', defaultValue = '' } = {}) {
    return new Promise(resolve => {
        const modal = document.getElementById('prompt-modal');
        document.getElementById('prompt-title').textContent = title;
        const input = document.getElementById('prompt-input');
        input.placeholder = placeholder;
        input.value = defaultValue;
        modal.hidden = false;
        input.focus();

        function cleanup(result) {
            modal.hidden = true;
            document.getElementById('btn-prompt-ok').removeEventListener('click', onOk);
            document.getElementById('btn-prompt-cancel').removeEventListener('click', onCancel);
            modal.removeEventListener('click', onBackdrop);
            input.removeEventListener('keydown', onKey);
            resolve(result);
        }
        function onOk() { cleanup(input.value.trim() || null); }
        function onCancel() { cleanup(null); }
        function onBackdrop(ev) { if (ev.target === modal) cleanup(null); }
        function onKey(ev) { if (ev.key === 'Enter') { ev.preventDefault(); onOk(); } }

        document.getElementById('btn-prompt-ok').addEventListener('click', onOk);
        document.getElementById('btn-prompt-cancel').addEventListener('click', onCancel);
        modal.addEventListener('click', onBackdrop);
        input.addEventListener('keydown', onKey);
    });
}

function fmtDate(iso) {
    if (!iso) return '—';
    const [y, m, d] = iso.split(/[-T ]/);
    return `${+d}. ${+m}. ${y}`;
}

function e(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function getInitials(name) {
    return (name || '').split(' ').map(n => n.charAt(0).toUpperCase()).slice(0, 2).join('');
}

// ── Vyhledávání klientů ───────────────────────────────────────────────────────

let searchTimer;
let showInactive = false;

document.getElementById('client-search').addEventListener('input', ev => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => renderClientList(ev.target.value.trim()), 220);
});

document.getElementById('btn-toggle-inactive').addEventListener('click', () => {
    showInactive = !showInactive;
    const btn = document.getElementById('btn-toggle-inactive');
    btn.classList.toggle('active', showInactive);
    btn.title = showInactive ? 'Skrýt neaktivní klienty' : 'Zobrazit neaktivní klienty';
    renderClientList(document.getElementById('client-search').value.trim());
});

async function renderClientList(q = '') {
    const inactiveParam = showInactive ? '&inactive=1' : '';
    const clients = await api(`/clients/search?q=${encodeURIComponent(q)}${inactiveParam}`).catch(() => []);
    const ul = document.getElementById('client-list');
    ul.innerHTML = clients.map(c => {
        const tags = c.tags_json ? (typeof c.tags_json === 'string' ? JSON.parse(c.tags_json) : c.tags_json) : [];
        const inactiveClass = c.status === 'inactive' ? ' client-inactive' : '';
        return `
        <li class="client-item${c.id === state.activeClientId ? ' active' : ''}${inactiveClass}"
            data-id="${c.id}">
            <span class="client-name">${e(c.full_name)}${renderTagDots(tags)}</span>
            <span class="client-subtitle">${e(c.phone ?? '')}${c.status === 'inactive' ? ' · Neaktivní' : ''}</span>
        </li>`;
    }).join('');

    ul.querySelectorAll('.client-item').forEach(li =>
        li.addEventListener('click', () => loadClient(+li.dataset.id)));

    // Update count
    const countEl = document.getElementById('client-count');
    const n = clients.length;
    countEl.textContent = n === 0 ? 'Žádní klienti' : n === 1 ? '1 klient' : n < 5 ? `${n} klienti` : `${n} klientů`;
}

// ── Detail klienta ────────────────────────────────────────────────────────────

async function loadClient(id, { skipVisits = false } = {}) {
    state.activeClientId = id;
    document.querySelectorAll('.client-item').forEach(li =>
        li.classList.toggle('active', +li.dataset.id === id));

    const client = await api(`/clients/show/${id}`).catch(err => {
        toast(err.message, 'error'); return null;
    });
    if (!client) return;

    state.activeClientName = client.full_name;

    // Zobraz panely
    document.getElementById('empty-state').hidden = true;
    document.getElementById('client-detail').hidden = false;
    document.getElementById('detail-tabs').hidden = false;
    showDetailTab('visits');
    closeFormulaOverlay();

    // Avatar
    document.getElementById('detail-avatar').textContent = getInitials(client.full_name);

    // Naplň detail
    document.getElementById('detail-name').textContent = client.full_name;
    document.getElementById('detail-phone').textContent = client.phone || '—';

    const statusLabels = { active: 'Aktivní', vip: 'VIP', inactive: 'Neaktivní' };
    document.getElementById('detail-status-text').textContent = statusLabels[client.status] || client.status;

    // Header
    document.getElementById('main-header-text').textContent = client.full_name;

    // Stats pills
    const stats = document.getElementById('client-stats');
    const pills = [];
    if (client.visit_count != null) {
        pills.push(`<span class="stat-pill">Návštěv: <strong>${client.visit_count}</strong></span>`);
    }
    const svcSpent = Number(client.total_spent) || 0;
    const retSpent = Number(client.total_retail) || 0;
    const totalAll = svcSpent + retSpent;
    if (totalAll > 0) {
        pills.push(`<span class="stat-pill">Služby: <strong>${svcSpent.toLocaleString('cs-CZ')} Kč</strong></span>`);
    }
    if (retSpent > 0) {
        pills.push(`<span class="stat-pill">Produkty: <strong>${retSpent.toLocaleString('cs-CZ')} Kč</strong></span>`);
    }
    if (totalAll > 0) {
        pills.push(`<span class="stat-pill stat-pill-accent">Celkem: <strong>${totalAll.toLocaleString('cs-CZ')} Kč</strong></span>`);
    }
    if (client.last_visit) {
        pills.push(`<span class="stat-pill">Poslední návštěva: <strong>${fmtDate(client.last_visit)}</strong></span>`);
    }
    if (client.formula_summary) {
        pills.push(`<span class="stat-pill">Receptura: <strong>${e(client.formula_summary)}</strong></span>`);
    }
    const clientTags = Array.isArray(client.tags) ? client.tags : [];
    if (clientTags.length) {
        pills.push(renderTagPills(clientTags));
    }
    stats.innerHTML = pills.join('');

    // Notes
    const notesEl = document.getElementById('client-notes');
    const notesText = document.getElementById('client-notes-text');
    if (client.notes && client.notes.trim()) {
        notesText.textContent = client.notes;
        notesEl.hidden = false;
    } else {
        notesEl.hidden = true;
    }

    // Ulož pro editaci
    document.getElementById('btn-edit-client').dataset.client = JSON.stringify(client);

    // Reset filter
    document.querySelectorAll('.vf-pill').forEach((p, i) => p.classList.toggle('active', i === 0));
    document.getElementById('vf-search').value = '';

    // Načti historii
    if (!skipVisits) loadVisitHistory(id);
}

// ── Formulář klienta (modal) ──────────────────────────────────────────────────

const modal   = document.getElementById('client-modal');
const cForm   = document.getElementById('client-form');

document.getElementById('btn-new-client').addEventListener('click', () => openModal());

document.getElementById('btn-edit-client').addEventListener('click', ev => {
    const c = JSON.parse(ev.currentTarget.dataset.client ?? '{}');
    openModal(c);
});

document.getElementById('btn-modal-cancel').addEventListener('click', closeModal);
modal.addEventListener('click', ev => { if (ev.target === modal) closeModal(); });

async function openModal(client = null) {
    document.getElementById('modal-title').textContent = client ? 'Upravit klienta' : 'Nový klient';
    document.getElementById('cf-id').value      = client?.id ?? '';
    document.getElementById('cf-name').value    = client?.full_name ?? '';
    document.getElementById('cf-phone').value   = client?.phone ?? '';
    document.getElementById('cf-status').value  = client?.status ?? 'active';
    document.getElementById('cf-notes').value   = client?.notes ?? '';
    // Tags — load definitions dynamically
    const allTags = await api('/tags/index').catch(() => []);
    const clientTagIds = Array.isArray(client?.tags) ? client.tags.map(t => t.id ?? t) : [];
    const picker = document.getElementById('cf-tags');
    picker.innerHTML = allTags.map(t => {
        const checked = clientTagIds.includes(t.id) ? ' checked' : '';
        return `<label class="tag-pick" style="color:${esc(t.color)};background:${hexBg(t.color)}">
            <input type="checkbox" value="${t.id}"${checked}><span></span> ${esc(t.name)}</label>`;
    }).join('');
    if (!allTags.length) picker.innerHTML = '<span style="color:var(--color-muted);font-size:12px">Zatím nemáte žádné štítky — vytvořte je v Nastavení → Číselníky</span>';
    modal.hidden = false;
    document.getElementById('cf-name').focus();
}

function closeModal() {
    modal.hidden = true;
    cForm.reset();
}

cForm.addEventListener('submit', async ev => {
    ev.preventDefault();
    const id = document.getElementById('cf-id').value;
    const data = {
        full_name: document.getElementById('cf-name').value,
        phone:     document.getElementById('cf-phone').value,
        status:    document.getElementById('cf-status').value,
        notes:     document.getElementById('cf-notes').value,
        tags:      [...document.querySelectorAll('#cf-tags input:checked')].map(cb => +cb.value),
    };
    try {
        if (id) {
            await api(`/clients/update/${id}`, { method: 'POST', body: JSON.stringify(data) });
            toast('Klient uložen');
            loadClient(+id);
        } else {
            const res = await api('/clients/store', { method: 'POST', body: JSON.stringify(data) });
            toast('Klient vytvořen');
            await renderClientList();
            loadClient(res.id);
        }
        closeModal();
    } catch (err) {
        toast(err.message, 'error');
    }
});

// ── Smazání klienta ───────────────────────────────────────────────────────────

document.getElementById('btn-delete-client').addEventListener('click', async () => {
    const id = state.activeClientId;
    if (!id) return;
    const ok = await modalConfirm('Opravdu smazat klienta včetně všech návštěv?', {
        title: 'Smazat klienta',
        okText: 'Smazat',
    });
    if (!ok) return;
    await api(`/clients/delete/${id}`, { method: 'POST' }).catch(err => toast(err.message, 'error'));
    state.activeClientId = null;
    document.getElementById('client-detail').hidden = true;
    document.getElementById('visit-history').hidden = true;
    document.getElementById('sales-history').hidden = true;
    document.getElementById('detail-tabs').hidden = true;
    document.getElementById('empty-state').hidden = false;
    document.getElementById('main-header-text').textContent = '';
    renderClientList();
    toast('Klient smazán');
});

// ── Historie návštěv ──────────────────────────────────────────────────────────

const SERVICE_ICONS = {
    metal:    { icon: '🛡️', label: 'Metal Detox' },
    cut:      { icon: '✂️', label: 'Stříhání' },
    blow:     { icon: '💨', label: 'Foukání' },
    straight: { icon: '▰',  label: 'Žehlení' },
    curl:     { icon: '〰️', label: 'Kulmování' },
};

function getServiceInfo(key) {
    // Try cache first (key = code_list id), then fallback to hardcoded
    const cached = codeListCache.service.find(s => String(s.id) === String(key));
    if (cached) return { icon: cached.icon || '', label: cached.name };
    return SERVICE_ICONS[key] || { icon: '', label: key };
}

function renderServiceToggles(selected = []) {
    const container = document.getElementById('fo-toggles');
    const services = codeListCache.service.length ? codeListCache.service : Object.entries(SERVICE_ICONS).map(([k, v]) => ({ id: k, name: v.label, icon: v.icon }));
    container.innerHTML = services.map(s => {
        const val = String(s.id);
        const checked = selected.includes(val) ? ' checked' : '';
        return `<label class="fo-toggle" title="${e(s.name)}">
            <input type="checkbox" value="${e(val)}"${checked}><span class="fo-toggle-icon">${e(s.icon || '')}</span><span class="fo-toggle-label">${e(s.name)}</span>
        </label>`;
    }).join('');
}

function renderServiceIcons(colorFormulaStr) {
    try {
        const formula = typeof colorFormulaStr === 'string' ? JSON.parse(colorFormulaStr) : colorFormulaStr;
        const services = formula?.services ?? [];
        if (!services.length) return '';
        return `<div class="visit-services">${services.map(s => {
            const info = getServiceInfo(s);
            return info ? `<span title="${info.label}">${info.icon} ${info.label}</span>` : '';
        }).join('')}</div>`;
    } catch { return ''; }
}

function renderFormulaSummary(colorFormulaStr) {
    if (!colorFormulaStr) return '<span class="text-muted">Bez receptury</span>';
    try {
        const formula = typeof colorFormulaStr === 'string' ? JSON.parse(colorFormulaStr) : colorFormulaStr;
        if (!formula || !formula.bowls || !formula.bowls.length) return '<span class="text-muted">Bez receptury</span>';

        return formula.bowls.map(bowl => {
            const label = bowl.label || '';
            const prods = (bowl.products || []).map(p => `${p.name} ${p.amount}g`).join(' + ');
            const ox = bowl.oxidant ? ` + ${bowl.oxidant.name} ${bowl.oxidant.amount}ml` : '';
            return `${label}: ${prods}${ox}`;
        }).join(' · ');
    } catch {
        return '<span class="text-muted">Bez receptury</span>';
    }
}

const VISITS_PER_PAGE = 20;

function renderVisitCard(v) {
    const isPaid = v.billing_status === 'paid';
    const priceTag = v.price ? `${v.price} Kč` : '';
    const paidBadge = isPaid ? `<span class="badge badge-paid">Vyúčtováno${v.billing_amount ? ' · ' + v.billing_amount + ' Kč' : ''}</span>` : (priceTag ? `<span class="badge badge-price">${priceTag}</span>` : '');
    return `
    <li class="visit-card${isPaid ? ' visit-paid' : ''}" data-id="${v.id}">
        <div class="visit-card-header" role="button">
            <div class="visit-card-date">${fmtDate(v.visit_date)}</div>
            <div class="visit-card-summary">${e(v.service_name || '—')}</div>
            ${paidBadge}
            <svg class="visit-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="visit-card-body" hidden>
            <div class="visit-card-service">${e(v.service_name || '—')}</div>
            ${renderServiceIcons(v.color_formula)}
            <div class="visit-card-formula">${renderFormulaSummary(v.color_formula)}</div>
            <div class="visit-card-actions">
                <button class="btn-visit-action btn-edit-visit" data-id="${v.id}" data-tip="Upravit"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg></button>
                <button class="btn-visit-action btn-copy-visit" data-id="${v.id}" data-tip="Kopírovat"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button>
                <button class="btn-visit-action btn-preview-recipe" data-id="${v.id}" data-tip="Náhled receptury"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></button>
                <button class="btn-visit-action btn-billing-visit" data-id="${v.id}" data-status="${e(v.billing_status)}" data-tip="${isPaid ? 'Zrušit vyúčtování' : 'Vyúčtovat'}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></button>
                ${isPaid ? `<button class="btn-visit-action btn-print-receipt" data-id="${v.id}" data-tip="Účtenka"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg></button>` : ''}
                <button class="btn-visit-action btn-delete-visit btn-danger-icon" data-id="${v.id}" data-tip="Smazat"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
            </div>
        </div>
    </li>`;
}

// Delegovaný handler na #visit-list — nastaví se jednou, funguje pro všechny karty
(function initVisitDelegation() {
    const ul = document.getElementById('visit-list');
    ul.addEventListener('click', async ev => {
        const target = ev.target;

        // ── Collapse / expand toggle ──
        const hdr = target.closest('.visit-card-header[role="button"]');
        if (hdr && !target.closest('.btn-visit-action')) {
            const card = hdr.closest('.visit-card');
            const body = card.querySelector('.visit-card-body');
            const open = !body.hidden;
            body.hidden = open;
            card.classList.toggle('visit-open', !open);
            return;
        }

        const btn = target.closest('.btn-visit-action');
        if (!btn) return;
        ev.stopPropagation();
        const clientId = state.activeClientId;
        const vid = btn.dataset.id;

        // ── Edit ──
        if (btn.classList.contains('btn-edit-visit')) {
            openFormulaEditor(+vid);
            return;
        }

        // ── Copy ──
        if (btn.classList.contains('btn-copy-visit')) {
            await copyVisit(+vid);
            return;
        }

        // ── Preview recipe ──
        if (btn.classList.contains('btn-preview-recipe')) {
            openRecipePreview(+vid);
            return;
        }

        // ── Billing ──
        if (btn.classList.contains('btn-billing-visit')) {
            const current = btn.dataset.status;
            if (current === 'paid') {
                const ok = await modalConfirm('Zrušit vyúčtování této návštěvy? Případné prodeje produktů budou také smazány.', {
                    title: 'Zrušit vyúčtování',
                    okText: 'Zrušit vyúčtování',
                    okClass: 'btn btn-primary',
                });
                if (!ok) return;
                await api(`/visits/update/${vid}`, {
                    method: 'POST',
                    body: JSON.stringify({ client_id: clientId, billing_status: 'unpaid', billing_amount: null, billing_change: null }),
                }).catch(err => toast(err.message, 'error'));
                await api(`/sales/delete-by-visit/${vid}`, { method: 'POST' }).catch(() => {});
                toast('Vyúčtování zrušeno');
                loadVisitHistory(clientId);
                loadClient(clientId);
            } else {
                openBillingModal(vid, clientId);
            }
            return;
        }

        // ── Delete ──
        if (btn.classList.contains('btn-delete-visit')) {
            const ok = await modalConfirm('Opravdu smazat tuto návštěvu? Tuto akci nelze vrátit.', {
                title: 'Smazat návštěvu',
                okText: 'Smazat',
                okClass: 'btn btn-danger',
            });
            if (!ok) return;
            try {
                await api(`/visits/delete/${vid}`, { method: 'POST' });
                toast('Návštěva smazána');
                loadVisitHistory(clientId);
                loadClient(clientId);
            } catch (err) {
                toast(err.message, 'error');
            }
            return;
        }

        // ── Print receipt ──
        if (btn.classList.contains('btn-print-receipt')) {
            await printReceipt(+vid);
        }
    });
})();

async function loadVisitHistory(clientId) {
    const ul = document.getElementById('visit-list');
    ul.innerHTML = '';
    state.visitOffset = 0;
    await loadMoreVisits(clientId);
}

async function loadMoreVisits(clientId) {
    const ul = document.getElementById('visit-list');
    const offset = state.visitOffset || 0;
    // Build filter params
    const activeMonths = document.querySelector('.vf-pill.active')?.dataset.months || '';
    let url = `/visits/index/${clientId}?limit=${VISITS_PER_PAGE}&offset=${offset}`;
    if (activeMonths) url += `&months=${activeMonths}`;
    const visits = await api(url).catch(() => []);

    // Client-side date text filter
    const dateQuery = (document.getElementById('vf-search').value || '').trim().toLowerCase();
    const filtered = dateQuery ? visits.filter(v => fmtDate(v.visit_date).toLowerCase().includes(dateQuery)) : visits;

    // Remove existing "load more" button
    ul.querySelector('.visit-load-more')?.remove();

    if (!filtered.length && offset === 0) {
        ul.innerHTML = '<li class="text-muted" style="padding:12px 0;font-size:13px">Žádné návštěvy</li>';
        return;
    }

    const fragment = document.createDocumentFragment();
    const temp = document.createElement('div');
    temp.innerHTML = filtered.map(renderVisitCard).join('');
    while (temp.firstChild) fragment.appendChild(temp.firstChild);

    ul.appendChild(fragment);

    // Auto-expand visit after save — collapse all, then expand the target
    if (state.expandVisitId != null) {
        // Collapse all open cards first
        ul.querySelectorAll('.visit-card.visit-open').forEach(c => {
            c.classList.remove('visit-open');
            const b = c.querySelector('.visit-card-body');
            if (b) b.hidden = true;
        });

        const eid = String(state.expandVisitId);
        let card = ul.querySelector(`.visit-card[data-id="${eid}"]`);
        if (!card) card = ul.querySelector('.visit-card');
        if (card) {
            const body = card.querySelector('.visit-card-body');
            if (body) {
                body.removeAttribute('hidden');
                body.style.display = '';
                card.classList.add('visit-open');
                setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 100);
            }
        }
        state.expandVisitId = null;
    }

    state.visitOffset = offset + visits.length;

    if (visits.length >= VISITS_PER_PAGE) {
        const more = document.createElement('li');
        more.className = 'visit-load-more';
        more.innerHTML = '<button class="btn btn-ghost btn-load-more">Načíst další návštěvy…</button>';
        more.querySelector('button').addEventListener('click', () => loadMoreVisits(clientId));
        ul.appendChild(more);
    }
}

// ── Filtr návštěv ─────────────────────────────────────────────────────────────

document.querySelectorAll('.vf-pill').forEach(pill => {
    pill.addEventListener('click', () => {
        document.querySelectorAll('.vf-pill').forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        document.getElementById('vf-search').value = '';
        if (state.activeClientId) loadVisitHistory(state.activeClientId);
    });
});

let vfTimer;
document.getElementById('vf-search').addEventListener('input', () => {
    clearTimeout(vfTimer);
    vfTimer = setTimeout(() => {
        if (state.activeClientId) loadVisitHistory(state.activeClientId);
    }, 300);
});

// ── Záložky Historie / Prodeje ────────────────────────────────────────────────

function showDetailTab(tab) {
    document.querySelectorAll('.detail-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
    document.getElementById('visit-history').hidden = tab !== 'visits';
    document.getElementById('sales-history').hidden = tab !== 'sales';
    document.getElementById('notes-history').hidden = tab !== 'notes';
    if (tab === 'sales' && state.activeClientId) loadSalesHistory(state.activeClientId);
    if (tab === 'notes' && state.activeClientId) loadNotes(state.activeClientId);
}

document.querySelectorAll('.detail-tab').forEach(tab => {
    tab.addEventListener('click', () => showDetailTab(tab.dataset.tab));
});

// ── Prodeje klienta ───────────────────────────────────────────────────────────

async function loadSalesHistory(clientId) {
    const ul = document.getElementById('sales-list');
    const sales = await api(`/sales/index/${clientId}`).catch(() => []);
    if (!sales.length) {
        ul.innerHTML = '<li class="text-muted" style="padding:12px 0;font-size:13px">Žádné prodeje</li>';
        return;
    }
    ul.innerHTML = sales.map(sale => {
        const items = (sale.items || []).map(it =>
            `<span class="sale-item-line">${e(it.title || it.name)}${it.volume ? ' <span class="sale-vol">' + e(it.volume) + '</span>' : ''} × ${it.qty}${it.unit_price ? ' · ' + Number(it.unit_price).toLocaleString('cs-CZ') + ' Kč' : ''}</span>`
        ).join('');
        return `<li class="sale-card" data-id="${sale.id}">
            <div class="sale-card-header">
                <span class="sale-date">${fmtDate(sale.created_at)}</span>
                <span class="sale-total-badge">${Number(sale.total).toLocaleString('cs-CZ')} Kč</span>
                <button class="btn-visit-action btn-sale-edit" data-id="${sale.id}" data-tip="Upravit"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg></button>
                <button class="btn-visit-action btn-danger-icon btn-sale-del" data-id="${sale.id}" data-tip="Smazat"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
            </div>
            <div class="sale-card-items">${items}</div>
            ${sale.note ? '<div class="sale-note">' + e(sale.note) + '</div>' : ''}
        </li>`;
    }).join('');

    // Edit sale
    ul.querySelectorAll('.btn-sale-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            const sale = sales.find(s => s.id == btn.dataset.id);
            if (sale) openSaleModal(sale, clientId);
        });
    });

    // Delete sale
    ul.querySelectorAll('.btn-sale-del').forEach(btn => {
        btn.addEventListener('click', async () => {
            const ok = await modalConfirm('Opravdu smazat tento prodej?', {
                title: 'Smazat prodej',
                okText: 'Smazat',
                okClass: 'btn btn-danger',
            });
            if (!ok) return;
            try {
                await api(`/sales/delete/${btn.dataset.id}`, { method: 'POST' });
                toast('Prodej smazán');
                loadSalesHistory(clientId);
            } catch (err) {
                toast(err.message, 'error');
            }
        });
    });
}

// ── Formulář prodeje (modal) ──────────────────────────────────────────────────

const saleModal = document.getElementById('sale-modal');

document.getElementById('btn-new-sale').addEventListener('click', () => {
    if (!state.activeClientId) return;
    openSaleModal(null, state.activeClientId);
});

document.getElementById('btn-quick-sale').addEventListener('click', () => {
    openSaleModal(null, null);
});

// ── Logout ────────────────────────────────────────────────────────────────────
document.getElementById('btn-logout').addEventListener('click', async () => {
    const confirmed = await modalConfirm('Opravdu se chcete odhlásit?', {
        title: 'Odhlášení',
        okText: 'Odhlásit se',
        okClass: 'btn btn-danger'
    });
    if (!confirmed) return;
    await fetch('/auth/logout', { method: 'POST' });
    // Bypass service worker cache
    if ('caches' in window) {
        const keys = await caches.keys();
        await Promise.all(keys.map(k => caches.delete(k)));
    }
    window.location.replace('/');
});

function openSaleModal(sale, clientId) {
    state.editingSaleId = sale ? sale.id : null;
    state.saleClientId = clientId !== undefined ? clientId : (sale ? sale.client_id : state.activeClientId);
    const h3 = saleModal.querySelector('h3');
    h3.textContent = sale ? 'Upravit prodej' : (state.saleClientId ? 'Nový prodej' : 'Rychlý prodej');
    document.getElementById('sale-items').innerHTML = '';
    document.getElementById('sale-note').value = sale ? (sale.note || '') : '';
    if (sale && sale.items) {
        const items = typeof sale.items === 'string' ? JSON.parse(sale.items) : sale.items;
        items.forEach(item => {
            addSaleRow();
            const row = document.getElementById('sale-items').lastElementChild;
            row.querySelector('.sale-product-input').value = item.title + (item.volume ? ' ' + item.volume : '');
            row.querySelector('.sale-product-id').value = item.product_id || '';
            row.querySelector('.sale-product-volume').value = item.volume || '';
            row.querySelector('.sale-qty').value = item.qty || 1;
            row.querySelector('.sale-price').value = item.unit_price || '';
        });
    } else {
        addSaleRow();
    }
    updateSaleTotal();
    saleModal.hidden = false;
}

document.getElementById('btn-sale-cancel').addEventListener('click', () => { saleModal.hidden = true; });
saleModal.addEventListener('click', ev => { if (ev.target === saleModal) saleModal.hidden = true; });

document.getElementById('btn-sale-add-row').addEventListener('click', () => addSaleRow());

function addSaleRow() {
    const wrap = document.getElementById('sale-items');
    const row = document.createElement('div');
    row.className = 'sale-row';
    row.innerHTML = `
        <input type="text" class="sale-product-input" placeholder="Hledat produkt…" autocomplete="off">
        <input type="hidden" class="sale-product-id">
        <input type="hidden" class="sale-product-volume">
        <input type="number" class="sale-qty" value="1" min="1" step="1">
        <input type="number" class="sale-price" placeholder="Cena" min="0" step="1">
        <button type="button" class="btn-visit-action btn-danger-icon btn-sale-rm" data-tip="Odebrat"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
    `;
    wrap.appendChild(row);

    // Autocomplete for retail products
    const input = row.querySelector('.sale-product-input');
    attachRetailAutocomplete(input, row);

    // Remove row button
    row.querySelector('.btn-sale-rm').addEventListener('click', () => {
        row.remove();
        updateSaleTotal();
    });

    // Update total on qty/price change
    row.querySelector('.sale-qty').addEventListener('input', updateSaleTotal);
    row.querySelector('.sale-price').addEventListener('input', updateSaleTotal);

    input.focus();
}

function attachRetailAutocomplete(input, row) {
    let acWrap = null;
    let debounce = null;
    let mouseOnAc = false;

    // Wrap input in a relative container for dropdown positioning
    const wrapper = document.createElement('div');
    wrapper.className = 'sale-ac-wrap';
    input.parentElement.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    input.addEventListener('input', () => {
        clearTimeout(debounce);
        const q = input.value.trim();
        if (q.length < 1) { removeAc(); return; }
        debounce = setTimeout(async () => {
            const results = await api(`/products/search?q=${encodeURIComponent(q)}&retail=1`).catch(() => []);
            showAc(results);
        }, 200);
    });

    input.addEventListener('keydown', ev => {
        if (!acWrap) return;
        const items = acWrap.querySelectorAll('.ac-item');
        let idx = [...items].findIndex(i => i.classList.contains('ac-active'));
        if (ev.key === 'ArrowDown') { ev.preventDefault(); setAcIdx(items, idx + 1); }
        else if (ev.key === 'ArrowUp') { ev.preventDefault(); setAcIdx(items, idx - 1); }
        else if (ev.key === 'Enter' && idx >= 0) { ev.preventDefault(); items[idx].click(); }
        else if (ev.key === 'Escape') { removeAc(); }
    });

    input.addEventListener('blur', () => {
        if (!mouseOnAc) removeAc();
    });

    function showAc(results) {
        removeAc();
        if (!results.length) return;
        acWrap = document.createElement('div');
        acWrap.className = 'ac-dropdown';
        acWrap.innerHTML = results.map((p, i) =>
            `<div class="ac-item ${i === 0 ? 'ac-active' : ''}" data-id="${p.id}" data-title="${e(p.title)}" data-volume="${e(p.volume || '')}" data-price="${p.default_price || ''}">${e(p.title)}${p.volume ? ' <span class="ac-vol">' + e(p.volume) + '</span>' : ''}${p.default_price ? ' · ' + p.default_price + ' Kč' : ''}</div>`
        ).join('');

        // Position fixed relative to input to avoid overflow clipping
        const rect = input.getBoundingClientRect();
        acWrap.style.position = 'fixed';
        acWrap.style.left = rect.left + 'px';
        acWrap.style.top = rect.bottom + 'px';
        acWrap.style.width = rect.width + 'px';
        document.body.appendChild(acWrap);

        acWrap.addEventListener('mouseenter', () => { mouseOnAc = true; });
        acWrap.addEventListener('mouseleave', () => { mouseOnAc = false; });

        acWrap.querySelectorAll('.ac-item').forEach(item => {
            item.addEventListener('click', ev => {
                ev.preventDefault();
                selectAc(item);
            });
        });
    }

    function selectAc(item) {
        input.value = item.dataset.title + (item.dataset.volume ? ' ' + item.dataset.volume : '');
        row.querySelector('.sale-product-id').value = item.dataset.id;
        row.querySelector('.sale-product-volume').value = item.dataset.volume;
        if (item.dataset.price && !row.querySelector('.sale-price').value) {
            row.querySelector('.sale-price').value = item.dataset.price;
        }
        removeAc();
        updateSaleTotal();
        row.querySelector('.sale-qty').focus();
    }

    function setAcIdx(items, idx) {
        items.forEach(i => i.classList.remove('ac-active'));
        if (idx < 0) idx = items.length - 1;
        if (idx >= items.length) idx = 0;
        items[idx]?.classList.add('ac-active');
        items[idx]?.scrollIntoView({ block: 'nearest' });
    }

    function removeAc() {
        if (acWrap) { acWrap.remove(); acWrap = null; }
    }
}

function updateSaleTotal() {
    let total = 0;
    document.querySelectorAll('.sale-row').forEach(row => {
        const qty = parseInt(row.querySelector('.sale-qty').value) || 0;
        const price = parseFloat(row.querySelector('.sale-price').value) || 0;
        total += qty * price;
    });
    document.getElementById('sale-total-value').textContent = total.toLocaleString('cs-CZ') + ' Kč';
}

document.getElementById('btn-sale-save').addEventListener('click', async () => {
    const rows = document.querySelectorAll('.sale-row');
    const items = [];
    for (const row of rows) {
        const title = row.querySelector('.sale-product-input').value.trim();
        if (!title) continue;
        items.push({
            product_id: row.querySelector('.sale-product-id').value || null,
            title: title,
            volume: row.querySelector('.sale-product-volume').value || null,
            qty: parseInt(row.querySelector('.sale-qty').value) || 1,
            unit_price: parseFloat(row.querySelector('.sale-price').value) || 0,
        });
    }
    if (!items.length) { toast('Přidejte alespoň jeden produkt', 'error'); return; }

    const url = state.editingSaleId ? `/sales/update/${state.editingSaleId}` : '/sales/store';
    try {
        await api(url, {
            method: 'POST',
            body: JSON.stringify({
                client_id: state.saleClientId || null,
                items,
                note: document.getElementById('sale-note').value.trim() || null,
            }),
        });
        saleModal.hidden = true;
        state.editingSaleId = null;
        toast('Prodej uložen');
        if (state.saleClientId && state.activeClientId === state.saleClientId) {
            showDetailTab('sales');
            loadClient(state.activeClientId);
        }
        state.saleClientId = null;
    } catch (err) {
        toast(err.message, 'error');
    }
});

// ── Kopírování návštěvy ───────────────────────────────────────────────────────

async function copyVisit(visitId) {
    const visit = await api(`/visits/show/${visitId}`).catch(() => null);
    if (!visit) return;
    openFormulaEditor(null, visit);
}

// ── Editor receptur ───────────────────────────────────────────────────────────

document.getElementById('btn-new-visit').addEventListener('click', () => openFormulaEditor(null));
document.getElementById('btn-save-visit').addEventListener('click', onSaveVisitClick);
document.getElementById('btn-fo-back').addEventListener('click', tryCloseFormulaOverlay);
document.getElementById('btn-cancel-visit').addEventListener('click', tryCloseFormulaOverlay);

function renderActionBar(isNew = false) {
    const bar = document.getElementById('fo-actions-bar');
    const bowlPresets = codeListCache.bowl.length ? codeListCache.bowl : BOWL_ACTIONS.map(a => ({ name: a }));
    bar.innerHTML = bowlPresets.map(b =>
        `<button type="button" class="action-add-btn" data-action="${e(b.name)}">+ ${e(b.name)}</button>`
    ).join('') + `<button type="button" class="action-add-btn action-add-custom">+</button>`
      + (isNew ? `<button type="button" class="action-add-btn action-last-formula" title="Načíst recepturu z poslední návštěvy">⟲ Poslední</button>` : '');

    bar.querySelectorAll('.action-add-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const action = btn.dataset.action;
            if (btn.classList.contains('action-last-formula')) {
                loadLastFormula();
            } else if (action) {
                addBowl(null, { label: action });
            } else {
                modalPrompt('Název sekce:', { placeholder: 'Např. Přeliv, Keratinový zábal…' }).then(label => {
                    if (label) addBowl(null, { label });
                });
            }
        });
    });
}

async function loadLastFormula() {
    const clientId = state.activeClientId;
    if (!clientId) return;
    try {
        const visits = await api(`/visits/index/${clientId}?limit=1`);
        if (!visits.length) { toast('Žádná předchozí návštěva', 'error'); return; }
        const visit = await api(`/visits/show/${visits[0].id}`);
        if (!visit?.color_formula) { toast('Poslední návštěva nemá recepturu', 'error'); return; }
        document.getElementById('fo-bowls').innerHTML = '';
        renderServiceToggles();
        fillEditorFromVisit(visit);
        toast('Receptura načtena z ' + fmtDate(visit.visit_date));
    } catch (err) {
        toast(err.message, 'error');
    }
}

function openFormulaOverlay() {
    document.getElementById('formula-overlay').hidden = false;
    document.getElementById('fo-avatar').textContent = getInitials(state.activeClientName);
    document.getElementById('fo-client-name').textContent = state.activeClientName;
}
function closeFormulaOverlay() {
    document.getElementById('formula-overlay').hidden = true;
    document.getElementById('fo-bowls').innerHTML = '';
    state.editingVisitId = null;
}

function isFormulaEditorDirty() {
    const hasBowls = document.querySelectorAll('#fo-bowls .bowl').length > 0;
    const hasServices = document.querySelectorAll('.fo-toggle input:checked').length > 0;
    const hasNote = document.getElementById('fo-note').value.trim().length > 0;
    return hasBowls || hasServices || hasNote;
}

async function tryCloseFormulaOverlay() {
    if (isFormulaEditorDirty()) {
        const ok = await modalConfirm('Máte neuložené změny. Opravdu chcete zavřít editor?', {
            title: 'Zavřít editor',
            okText: 'Zavřít',
            okClass: 'btn btn-danger',
        });
        if (!ok) return;
    }
    closeFormulaOverlay();
}

function updateBowlCount() {
    const n = document.querySelectorAll('#fo-bowls .bowl').length;
    document.getElementById('fo-bowl-count').textContent = n || '';
}
new MutationObserver(updateBowlCount).observe(document.getElementById('fo-bowls'), { childList: true });

async function openFormulaEditor(visitId, copyData = null) {
    state.editingVisitId = visitId;
    state.editingVisitData = null;

    // Reset
    document.getElementById('fo-bowls').innerHTML = '';
    document.getElementById('fo-note').value = '';
    renderServiceToggles();

    const dateInput = document.getElementById('fo-visit-date');
    const today = new Date().toISOString().slice(0, 10);

    if (visitId) {
        const visit = await api(`/visits/show/${visitId}`).catch(() => null);
        if (visit) {
            state.editingVisitData = visit;
            document.getElementById('fo-profile-sub').textContent = `Úprava návštěvy · ${fmtDate(visit.visit_date)}`;
            dateInput.value = visit.visit_date;
            dateInput.hidden = true;
            fillEditorFromVisit(visit);
        }
    } else if (copyData) {
        document.getElementById('fo-profile-sub').textContent = 'Kopie návštěvy';
        dateInput.value = today;
        dateInput.hidden = false;
        fillEditorFromVisit(copyData);
    } else {
        document.getElementById('fo-profile-sub').textContent = 'Nová návštěva';
        dateInput.value = today;
        dateInput.hidden = false;
    }

    renderActionBar(!visitId);
    openFormulaOverlay();
}

function fillEditorFromVisit(visit) {
    document.getElementById('fo-note').value = visit.note ?? '';
    const formula = visit.color_formula;
    if (formula) {
        (formula.bowls ?? []).forEach(b => addBowl(null, b));
        renderServiceToggles(formula.services?.map(String) ?? []);
    }
}

// ── Miska ─────────────────────────────────────────────────────────────────────

function addBowl(ev = null, data = null) {
    const bowls   = document.getElementById('fo-bowls');
    const idx     = bowls.querySelectorAll('.bowl').length;
    const color   = data?.color ?? BOWL_COLORS[idx % BOWL_COLORS.length];
    const label   = data?.label ?? `Miska ${idx + 1}`;

    const bowl = document.createElement('div');
    bowl.className = 'bowl';
    bowl.style.setProperty('--bowl-color', color);

    bowl.innerHTML = `
        <div class="bowl-header">
            <span class="bowl-color-dot" style="background:${color}"></span>
            <input type="text" class="bowl-label-input" value="${e(label)}" placeholder="Název sekce">
            <button type="button" class="bowl-remove" title="Odebrat sekci">×</button>
        </div>
        <div class="bowl-products"></div>
        <div class="oxidant-row">
            <div class="oxidant-name-wrap">
                <input type="text" class="product-name-input oxidant-name"
                       placeholder="Oxidant…" autocomplete="off" value="${e(data?.oxidant?.name ?? '')}">
            </div>
            <select class="oxidant-ratio-select">
                ${getRatios().map(r => `<option${r === (data?.oxidant?.ratio ?? '1:1') ? ' selected' : ''}>${r}</option>`).join('')}
            </select>
            <input type="number" class="product-amount-input oxidant-amount-input"
                   placeholder="0" min="0" step="1" value="${data?.oxidant?.amount ?? ''}" readonly>
            <span class="product-unit">ml</span>
        </div>
        <div class="oxidant-summary">
            <span class="oxidant-summary-total">Materiál: <strong>0 g</strong> · Oxidant: <strong>0 ml</strong></span>
        </div>`;

    // Odebrat sekci
    bowl.querySelector('.bowl-remove').addEventListener('click', () => bowl.remove());

    const productsEl = bowl.querySelector('.bowl-products');

    // Přidat produkty
    const prods = data?.products ?? [{}];
    prods.forEach(p => addProductRow(productsEl, bowl, p));

    // Oxidant autocomplete
    attachAutocomplete(bowl.querySelector('.oxidant-name'), 'Oxy');

    // Přepočet oxidantu
    bowl.querySelector('.oxidant-ratio-select').addEventListener('change', () => recalcOxidant(bowl));

    bowls.appendChild(bowl);
    recalcOxidant(bowl);

    // Scroll do view a focus
    bowl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    const firstInput = productsEl.querySelector('.product-name-input');
    if (firstInput) setTimeout(() => firstInput.focus(), 50);
}

// ── Řádek produktu ────────────────────────────────────────────────────────────

function addProductRow(container, bowl, data = {}) {
    const row = document.createElement('div');
    row.className = 'product-row';
    row.innerHTML = `
        <div class="product-name-wrap">
            <input type="text" class="product-name-input"
                   placeholder="Produkt…" autocomplete="off" value="${e(data.name ?? '')}">
        </div>
        <input type="number" class="product-amount-input"
               placeholder="0" min="0" step="1" value="${data.amount ?? ''}">
        <span class="product-unit">g</span>
        <button type="button" class="product-remove" tabindex="-1">×</button>`;

    row.querySelector('.product-remove').addEventListener('click', () => { row.remove(); recalcOxidant(bowl); });
    attachAutocomplete(row.querySelector('.product-name-input'), null, 0);

    // Přepočet oxidantu při změně gramáže
    row.querySelector('.product-amount-input').addEventListener('input', () => recalcOxidant(bowl));

    // Klávesové zkratky
    row.querySelector('.product-name-input').addEventListener('keydown', ev => {
        handleProductKeydown(ev, row, container, bowl);
    });
    row.querySelector('.product-amount-input').addEventListener('keydown', ev => {
        handleAmountKeydown(ev, row, container, bowl);
    });

    container.appendChild(row);
    return row;
}

// ── Klávesové ovládání ────────────────────────────────────────────────────────

// Global keyboard shortcuts
document.addEventListener('keydown', ev => {
    if (ev.key === 'k' && (ev.metaKey || ev.ctrlKey)) {
        ev.preventDefault();
        const search = document.getElementById('client-search');
        if (search) { search.focus(); search.select(); }
    }
});

document.getElementById('formula-overlay').addEventListener('keydown', ev => {
    if (ev.key === 'Escape') {
        ev.preventDefault();
        tryCloseFormulaOverlay();
        return;
    }
    if (ev.key === 's' && (ev.metaKey || ev.ctrlKey)) {
        ev.preventDefault();
        onSaveVisitClick();
        return;
    }
    if (ev.key === 'Enter' && ev.shiftKey) {
        ev.preventDefault();
        addBowl();
    }
    if (ev.key === 'Delete' && (ev.ctrlKey || ev.metaKey)) {
        ev.preventDefault();
        const focused = document.activeElement;
        const bowl = focused?.closest('.bowl');
        if (bowl) bowl.remove();
    }
    if (ev.key === 'Backspace' && (ev.ctrlKey || ev.metaKey)) {
        const focused = document.activeElement;
        const row = focused?.closest('.product-row');
        if (row) {
            ev.preventDefault();
            const bowl = row.closest('.bowl');
            const prevRow = row.previousElementSibling;
            const nextRow = row.nextElementSibling;
            row.remove();
            if (bowl) recalcOxidant(bowl);
            // Focus previous or next row
            const target = prevRow?.querySelector('.product-name-input') ?? nextRow?.querySelector('.product-name-input');
            if (target) target.focus();
        }
    }
});

function handleProductKeydown(ev, row, container, bowl) {
    const input = ev.currentTarget;
    if (ev.key === 'Tab' && !ev.shiftKey) {
        ev.preventDefault();
        row.querySelector('.product-amount-input').focus();
        return;
    }
    if (ev.key === 'Enter' && !ev.shiftKey) {
        ev.preventDefault();
        if (input.value.trim() === '') {
            row.remove();
            bowl.querySelector('.oxidant-name').focus();
        } else {
            addProductRow(container, bowl, {});
            const rows = container.querySelectorAll('.product-row');
            rows[rows.length - 1].querySelector('.product-name-input').focus();
        }
    }
}

function handleAmountKeydown(ev, row, container, bowl) {
    if (ev.key === 'Enter' && !ev.shiftKey) {
        ev.preventDefault();
        addProductRow(container, bowl, {});
        const rows = container.querySelectorAll('.product-row');
        rows[rows.length - 1].querySelector('.product-name-input').focus();
    }
}

// ── Výpočet oxidantu ──────────────────────────────────────────────────────────

function recalcOxidant(bowl) {
    const rows   = bowl.querySelectorAll('.bowl-products .product-amount-input');
    const total  = Array.from(rows).reduce((s, i) => s + (parseFloat(i.value) || 0), 0);
    const ratio  = bowl.querySelector('.oxidant-ratio-select').value;
    const [a, b] = ratio.split(':').map(Number);
    const oxidantAmt = total > 0 ? Math.round((total * b) / a) : 0;
    const oxInput = bowl.querySelector('.oxidant-amount-input');
    if (oxInput) {
        oxInput.value = oxidantAmt > 0 ? oxidantAmt : '';
    }
    // Update summary bar
    const summaryTotal = bowl.querySelector('.oxidant-summary-total');
    if (summaryTotal) summaryTotal.innerHTML = `Materiál: <strong>${total} g</strong> · Oxidant: <strong>${oxidantAmt} ml</strong>`;
}

// ── Autocomplete produktů ─────────────────────────────────────────────────────

function attachAutocomplete(input, categoryFilter = null, retailFilter = null) {
    let dropdown = null;
    let acTimer;
    let acIndex = -1;

    input.addEventListener('input', () => {
        const q = input.value.trim();
        clearTimeout(acTimer);
        if (q.length < 1) { closeAC(); return; }
        acTimer = setTimeout(() => fetchAC(q), 180);
    });

    input.addEventListener('keydown', ev => {
        if (!dropdown) return;
        const items = dropdown.querySelectorAll('.ac-item');
        if (ev.key === 'ArrowDown') {
            ev.preventDefault();
            acIndex = Math.min(acIndex + 1, items.length - 1);
            updateFocus(items);
        } else if (ev.key === 'ArrowUp') {
            ev.preventDefault();
            acIndex = Math.max(acIndex - 1, 0);
            updateFocus(items);
        } else if (ev.key === 'Enter' && acIndex >= 0) {
            ev.preventDefault();
            ev.stopImmediatePropagation();
            items[acIndex]?.click();
        } else if (ev.key === 'Escape') {
            closeAC();
        }
    });

    input.addEventListener('blur', () => setTimeout(closeAC, 150));

    async function fetchAC(q) {
        let url = `/products/search?q=${encodeURIComponent(q)}`;
        if (categoryFilter) url += `&cat=${encodeURIComponent(categoryFilter)}`;
        if (retailFilter !== null) url += `&retail=${retailFilter}`;
        const results = await api(url).catch(() => []);
        if (!results.length) { closeAC(); return; }
        renderAC(results);
    }

    function renderAC(items) {
        closeAC();
        acIndex = -1;
        dropdown = document.createElement('div');
        dropdown.className = 'autocomplete-dropdown';
        dropdown.innerHTML = items.map((it, i) => `
            <div class="ac-item" data-index="${i}">
                <div>${e(it.title)}</div>
                <div class="ac-item-category">${e(it.category ?? '')}</div>
            </div>`).join('');

        dropdown.querySelectorAll('.ac-item').forEach((el, i) => {
            function selectItem() {
                input.value = items[i].title;
                closeAC();
                const bowl = input.closest('.bowl');
                if (bowl) recalcOxidant(bowl);
                // Jump to the amount field next to this input
                const row = input.closest('.product-row, .oxidant-row');
                if (row) {
                    const amtInput = row.querySelector('.product-amount-input');
                    if (amtInput) setTimeout(() => amtInput.focus(), 0);
                }
            }
            el.addEventListener('mousedown', selectItem);
            el.addEventListener('click', selectItem);
        });

        const wrap = input.closest('.product-name-wrap, .oxidant-name-wrap');
        if (wrap) {
            wrap.style.position = 'relative';
            wrap.appendChild(dropdown);
        }
    }

    function updateFocus(items) {
        items.forEach((el, i) => el.classList.toggle('focused', i === acIndex));
        items[acIndex]?.scrollIntoView({ block: 'nearest' });
    }

    function closeAC() {
        dropdown?.remove();
        dropdown = null;
        acIndex = -1;
    }
}

// ── Uložení návštěvy ──────────────────────────────────────────────────────────

// ── Validace & uložení návštěvy ────────────────────────────────────────────────

function validateFormula() {
    const bowls = document.querySelectorAll('.bowl');
    const hasServices = document.querySelectorAll('.fo-toggle input:checked').length > 0;

    if (!bowls.length && !hasServices) {
        toast('Přidejte alespoň jednu misku nebo vyberte službu', 'error');
        return false;
    }
    let valid = true;
    bowls.forEach(bowl => {
        const label = bowl.querySelector('.bowl-label-input');
        if (!label.value.trim()) {
            label.style.borderColor = 'var(--color-danger)';
            valid = false;
        } else {
            label.style.borderColor = '';
        }
        const prods = bowl.querySelectorAll('.bowl-products .product-row');
        let hasProduct = false;
        prods.forEach(row => {
            const nameInput = row.querySelector('.product-name-input');
            const amtInput = row.querySelector('.product-amount-input');
            const name = nameInput.value.trim();
            const amt = parseFloat(amtInput.value) || 0;
            if (name && amt > 0) {
                hasProduct = true;
                nameInput.style.borderColor = '';
                amtInput.style.borderColor = '';
            } else if (name && amt <= 0) {
                amtInput.style.borderColor = 'var(--color-danger)';
                valid = false;
            } else if (!name && amt > 0) {
                nameInput.style.borderColor = 'var(--color-danger)';
                valid = false;
            }
        });
        if (!hasProduct) {
            toast('Každá miska musí mít alespoň jeden produkt s gramáží', 'error');
            valid = false;
        }
    });
    return valid;
}

function onSaveVisitClick() {
    if (!validateFormula()) return;
    // Show price modal
    const svModal = document.getElementById('save-visit-modal');
    const priceInput = document.getElementById('sv-price');
    priceInput.value = state.editingVisitData?.price ?? '';
    svModal.hidden = false;
    priceInput.focus();
}

document.getElementById('btn-sv-cancel').addEventListener('click', () => {
    document.getElementById('save-visit-modal').hidden = true;
});
document.getElementById('save-visit-modal').addEventListener('click', ev => {
    if (ev.target === ev.currentTarget) ev.currentTarget.hidden = true;
});
document.getElementById('sv-price').addEventListener('keydown', ev => {
    if (ev.key === 'Enter') { ev.preventDefault(); document.getElementById('btn-sv-save').click(); }
});
document.getElementById('btn-sv-save').addEventListener('click', () => {
    document.getElementById('save-visit-modal').hidden = true;
    const price = parseFloat(document.getElementById('sv-price').value) || null;
    saveVisit(price);
});

async function saveVisit(price = null) {
    const clientId = state.activeClientId;
    if (!clientId) return;

    const bowls = Array.from(document.querySelectorAll('.bowl')).map(bowl => {
        const products = Array.from(bowl.querySelectorAll('.bowl-products .product-row')).map(row => ({
            name:   row.querySelector('.product-name-input').value.trim(),
            amount: parseFloat(row.querySelector('.product-amount-input').value) || 0,
        })).filter(p => p.name);

        const ratioSel = bowl.querySelector('.oxidant-ratio-select');
        const oxName   = bowl.querySelector('.oxidant-name').value.trim();
        const oxAmtInput = bowl.querySelector('.oxidant-amount-input');
        const oxAmt    = parseFloat(oxAmtInput?.value) || 0;

        return {
            label:    bowl.querySelector('.bowl-label-input').value.trim(),
            color:    getComputedStyle(bowl).getPropertyValue('--bowl-color').trim(),
            products,
            oxidant: oxName ? {
                name:   oxName,
                ratio:  ratioSel.value,
                amount: oxAmt,
            } : null,
        };
    });

    const services = Array.from(document.querySelectorAll('.fo-toggle input:checked')).map(cb => cb.value);
    const serviceLabels = services.map(s => getServiceInfo(s).label).filter(Boolean);
    const actions = bowls.map(b => b.label).filter(Boolean);
    const allLabels = [...serviceLabels, ...actions].filter(Boolean);
    const existing = state.editingVisitData;

    const payload = {
        client_id:      clientId,
        visit_date:     existing?.visit_date ?? document.getElementById('fo-visit-date').value,
        service_name:   existing?.service_name ?? allLabels.join(', '),
        note:           document.getElementById('fo-note').value.trim(),
        price:          price,
        billing_status: existing?.billing_status ?? 'unpaid',
        color_formula:  { actions, bowls, services },
    };

    try {
        const vid = state.editingVisitId;
        let expandId;
        if (vid) {
            await api(`/visits/update/${vid}`, { method: 'POST', body: JSON.stringify(payload) });
            toast('Návštěva aktualizována');
            expandId = vid;
        } else {
            const res = await api('/visits/store', { method: 'POST', body: JSON.stringify(payload) });
            toast('Návštěva uložena');
            expandId = res?.id;
        }
        state.expandVisitId = expandId ?? null;
        closeFormulaOverlay();
        await loadVisitHistory(clientId);
        loadClient(clientId, { skipVisits: true });
    } catch (err) {
        toast(err.message, 'error');
    }
}

// ── Recipe preview modal ──────────────────────────────────────────────────────

async function openRecipePreview(visitId) {
    const visit = await api(`/visits/show/${visitId}`).catch(() => null);
    if (!visit) { toast('Návštěva nenalezena', 'error'); return; }

    const formula = visit.color_formula;
    const clientName = state.activeClientName || '';
    const date = fmtDate(visit.visit_date);

    document.getElementById('recipe-preview-title').textContent = `${clientName} · ${date}`;

    let html = '';
    if (visit.service_name) {
        html += `<div class="rp-services">${e(visit.service_name)}</div>`;
    }
    if (formula?.bowls?.length) {
        html += formula.bowls.map(bowl => {
            const prods = (bowl.products || [])
                .map(p => `<div class="rp-product">${e(p.name)} <strong>${p.amount}g</strong></div>`)
                .join('');
            const ox = bowl.oxidant
                ? `<div class="rp-oxidant">${e(bowl.oxidant.name)} ${bowl.oxidant.ratio} → <strong>${bowl.oxidant.amount}ml</strong></div>`
                : '';
            return `<div class="rp-bowl">
                <div class="rp-bowl-label" style="border-left:3px solid ${bowl.color || 'var(--color-accent)'}">${e(bowl.label || 'Miska')}</div>
                ${prods}${ox}
            </div>`;
        }).join('');
    } else {
        html = '<span class="text-muted">Bez receptury</span>';
    }
    if (visit.note) {
        html += `<div class="rp-note">${e(visit.note)}</div>`;
    }

    document.getElementById('recipe-preview-content').innerHTML = html;
    document.getElementById('recipe-preview-modal').hidden = false;
}

document.getElementById('btn-recipe-close').addEventListener('click', () => {
    document.getElementById('recipe-preview-modal').hidden = true;
});
document.getElementById('recipe-preview-modal').addEventListener('click', ev => {
    if (ev.target === ev.currentTarget) ev.currentTarget.hidden = true;
});
document.getElementById('btn-recipe-copy').addEventListener('click', () => {
    const content = document.getElementById('recipe-preview-content');
    const text = content.innerText;
    navigator.clipboard.writeText(text).then(() => toast('Zkopírováno do schránky'));
});
document.getElementById('btn-recipe-print').addEventListener('click', () => {
    window.print();
});

// ── Billing modal ─────────────────────────────────────────────────────────────

const billingModal = document.getElementById('billing-modal');
const billingForm  = document.getElementById('billing-form');

async function openBillingModal(visitId, clientId) {
    document.getElementById('bf-visit-id').value = visitId;
    billingForm.dataset.clientId = clientId;
    document.getElementById('billing-products').innerHTML = '';
    document.getElementById('billing-summary').innerHTML = '';

    // Load visit price
    const visit = await api(`/visits/show/${visitId}`).catch(() => null);
    const price = visit?.price ?? 0;
    billingForm.dataset.visitPrice = price;
    document.getElementById('billing-visit-info').textContent = price ? `Služby: ${Number(price).toLocaleString('cs-CZ')} Kč` : 'Cena není zadaná';

    // Load existing sale linked to this visit
    const existingSale = await api(`/sales/for-visit/${visitId}`).catch(() => null);
    if (existingSale && existingSale.items && existingSale.items.length) {
        existingSale.items.forEach(item => {
            addBillingProductRow();
            const row = document.getElementById('billing-products').lastElementChild;
            row.querySelector('.sale-product-input').value = item.title + (item.volume ? ' ' + item.volume : '');
            row.querySelector('.sale-product-id').value = item.product_id || '';
            row.querySelector('.sale-product-volume').value = item.volume || '';
            row.querySelector('.sale-qty').value = item.qty || 1;
            row.querySelector('.sale-price').value = item.unit_price || '';
        });
    }

    updateBillingTotal();
    billingModal.hidden = false;
    const amtInput = document.getElementById('bf-amount');
    amtInput.focus();
    requestAnimationFrame(() => amtInput.select());
}

function addBillingProductRow() {
    const wrap = document.getElementById('billing-products');
    const row = document.createElement('div');
    row.className = 'sale-row';
    row.innerHTML = `
        <input type="text" class="sale-product-input" placeholder="Hledat produkt…" autocomplete="off">
        <input type="hidden" class="sale-product-id">
        <input type="hidden" class="sale-product-volume">
        <input type="number" class="sale-qty" value="1" min="1" step="1">
        <input type="number" class="sale-price" placeholder="Cena" min="0" step="1">
        <button type="button" class="btn-visit-action btn-danger-icon btn-sale-rm" data-tip="Odebrat"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
    `;
    wrap.appendChild(row);
    const input = row.querySelector('.sale-product-input');
    attachRetailAutocomplete(input, row);
    row.querySelector('.btn-sale-rm').addEventListener('click', () => { row.remove(); updateBillingTotal(); });
    row.querySelector('.sale-qty').addEventListener('input', updateBillingTotal);
    row.querySelector('.sale-price').addEventListener('input', updateBillingTotal);
    input.focus();
}

function updateBillingTotal() {
    const visitPrice = parseFloat(billingForm.dataset.visitPrice) || 0;
    let productsTotal = 0;
    document.querySelectorAll('#billing-products .sale-row').forEach(row => {
        const qty = parseInt(row.querySelector('.sale-qty').value) || 0;
        const price = parseFloat(row.querySelector('.sale-price').value) || 0;
        productsTotal += qty * price;
    });
    const total = visitPrice + productsTotal;
    billingForm.dataset.price = total;

    const summary = document.getElementById('billing-summary');
    if (productsTotal > 0) {
        summary.innerHTML = `<div class="billing-breakdown">
            <span>Služby: ${Number(visitPrice).toLocaleString('cs-CZ')} Kč</span>
            <span>Produkty: ${Number(productsTotal).toLocaleString('cs-CZ')} Kč</span>
            <strong>Celkem: ${Number(total).toLocaleString('cs-CZ')} Kč</strong>
        </div>`;
    } else {
        summary.innerHTML = '';
    }

    document.getElementById('bf-amount').value = total || '';
    document.getElementById('bf-change').value = '0';
}

document.getElementById('btn-billing-add-product').addEventListener('click', () => addBillingProductRow());

// Prevent Enter from submitting billing form
billingForm.addEventListener('keydown', ev => {
    if (ev.key === 'Enter') ev.preventDefault();
});

// Auto-výpočet vrácení
document.getElementById('bf-amount').addEventListener('input', () => {
    const price = parseFloat(billingForm.dataset.price) || 0;
    const paid = parseFloat(document.getElementById('bf-amount').value) || 0;
    const change = Math.max(0, paid - price);
    document.getElementById('bf-change').value = change;
});

document.getElementById('btn-billing-cancel').addEventListener('click', () => {
    billingModal.hidden = true;
});
billingModal.addEventListener('click', ev => {
    if (ev.target === billingModal) billingModal.hidden = true;
});

billingForm.addEventListener('submit', async ev => {
    ev.preventDefault();
    // Only allow submit from button click, not Enter key
    if (!ev.submitter) return;
    const vid = document.getElementById('bf-visit-id').value;
    const clientId = +billingForm.dataset.clientId;
    const amount = parseFloat(document.getElementById('bf-amount').value) || 0;
    const change = parseFloat(document.getElementById('bf-change').value) || 0;

    // Collect product items
    const productItems = [];
    document.querySelectorAll('#billing-products .sale-row').forEach(row => {
        const title = row.querySelector('.sale-product-input').value.trim();
        if (!title) return;
        productItems.push({
            product_id: row.querySelector('.sale-product-id').value || null,
            title: title,
            volume: row.querySelector('.sale-product-volume').value || null,
            qty: parseInt(row.querySelector('.sale-qty').value) || 1,
            unit_price: parseFloat(row.querySelector('.sale-price').value) || 0,
        });
    });

    try {
        // Save billing
        await api(`/visits/update/${vid}`, {
            method: 'POST',
            body: JSON.stringify({
                client_id: clientId,
                billing_status: 'paid',
                billing_amount: amount,
                billing_change: change,
            }),
        });

        // Delete old sale for this visit, then create new one if products
        await api(`/sales/delete-by-visit/${vid}`, { method: 'POST' }).catch(() => {});
        if (productItems.length) {
            await api('/sales/store', {
                method: 'POST',
                body: JSON.stringify({
                    client_id: clientId,
                    visit_id: +vid,
                    items: productItems,
                    note: null,
                }),
            });
        }

        billingModal.hidden = true;
        toast('Vyúčtováno');
        state.expandVisitId = +vid;
        loadVisitHistory(clientId);
        loadClient(clientId, { skipVisits: true });
    } catch (err) {
        toast(err.message, 'error');
    }
});

// ── Settings: Data overview ───────────────────────────────────────────────────

async function loadDataStats() {
    try {
        const stats = await api('/settings/data-stats');
        const grid = document.getElementById('data-stats-grid');
        grid.innerHTML = stats.map(t =>
            `<div class="data-stat-item">
                <span class="data-stat-value">${t.count}</span>
                <span class="data-stat-label">${e(t.label)}</span>
            </div>`
        ).join('');
    } catch (err) {
        toast(err.message, 'error');
    }
}

document.getElementById('btn-purge-data').addEventListener('click', async () => {
    const ok = await modalConfirm(
        'Tato akce nenávratně smaže všechny klienty, návštěvy, poznámky, prodeje a denní uzávěrky. Číselníky a ceník zůstanou.<br><br>Pro potvrzení napište <strong>SMAZAT</strong>.',
        { title: 'Vymazat provozní data', okText: 'Vymazat vše', okClass: 'btn btn-danger', html: true }
    );
    if (!ok) return;

    const confirm = prompt('Napište SMAZAT pro potvrzení:');
    if (confirm !== 'SMAZAT') { toast('Smazání zrušeno'); return; }

    try {
        const res = await api('/settings/purge-data', {
            method: 'POST',
            body: JSON.stringify({ confirm: 'SMAZAT' }),
        });
        toast(res.message);
        loadDataStats();
    } catch (err) {
        toast(err.message, 'error');
    }
});

// ── Statistiky ────────────────────────────────────────────────────────────────

async function loadStats() {
    const period = document.getElementById('stats-period').value;
    loadConsumption(period);
}

async function loadConsumption(period = 'month') {
    try {
        const data = await api(`/stats/consumption?period=${period}`);
        renderConsumption(data);
    } catch (err) {
        toast(err.message, 'error');
    }
}

function renderConsumption(data) {
    // Summary
    const totalG = data.products.filter(p => p.unit === 'g').reduce((s, p) => s + p.total_grams, 0);
    const totalMl = data.products.filter(p => p.unit === 'ml').reduce((s, p) => s + p.total_grams, 0);
    const uniqueProducts = data.products.length;

    document.getElementById('stats-summary').innerHTML = `
        <div class="stats-card"><span class="stats-card-value">${data.total_visits}</span><span class="stats-card-label">Návštěv s recepturou</span></div>
        <div class="stats-card"><span class="stats-card-value">${uniqueProducts}</span><span class="stats-card-label">Použitých produktů</span></div>
        <div class="stats-card"><span class="stats-card-value">${Math.round(totalG)}g</span><span class="stats-card-label">Materiál celkem</span></div>
        <div class="stats-card"><span class="stats-card-value">${Math.round(totalMl)}ml</span><span class="stats-card-label">Oxidant celkem</span></div>
    `;

    // Product list
    state.statsProducts = data.products;
    renderProductList(data.products);

    // Unused
    const unusedEl = document.getElementById('stats-unused-list');
    if (data.unused.length) {
        unusedEl.innerHTML = data.unused.map(u =>
            `<div class="stats-unused-item">
                <span>${e(u.name)}</span>
                <span class="text-muted">${u.last_used ? fmtDate(u.last_used) : 'Nikdy nepoužito'}</span>
            </div>`
        ).join('');
    } else {
        unusedEl.innerHTML = '<span class="text-muted">Všechny produkty byly nedávno použity</span>';
    }
}

function renderProductList(products) {
    const list = document.getElementById('stats-product-list');
    if (!products.length) {
        list.innerHTML = '<span class="text-muted">Žádná data pro zvolené období</span>';
        return;
    }
    const maxGrams = products[0]?.total_grams || 1;
    list.innerHTML = products.map((p, i) =>
        `<div class="stats-product-row">
            <span class="stats-product-rank">${i + 1}.</span>
            <div class="stats-product-info">
                <div class="stats-product-name">${e(p.name)}</div>
                <div class="stats-product-bar-wrap">
                    <div class="stats-product-bar" style="width:${Math.round(p.total_grams / maxGrams * 100)}%"></div>
                </div>
            </div>
            <span class="stats-product-amount">${Math.round(p.total_grams)}${p.unit}</span>
            <span class="stats-product-count">${p.usage_count}×</span>
        </div>`
    ).join('');
}

document.getElementById('stats-period').addEventListener('change', () => {
    loadConsumption(document.getElementById('stats-period').value);
});

document.getElementById('stats-search').addEventListener('input', ev => {
    const q = ev.target.value.toLowerCase();
    const filtered = (state.statsProducts || []).filter(p => p.name.toLowerCase().includes(q));
    renderProductList(filtered);
});

// ── Dashboard ─────────────────────────────────────────────────────────────────

async function loadDashboard() {
    // date label
    const now = new Date();
    const months = ['ledna','února','března','dubna','května','června','července','srpna','září','října','listopadu','prosince'];
    document.getElementById('dash-date').textContent = `${now.getDate()}. ${months[now.getMonth()]} ${now.getFullYear()}`;

    const data = await api('/dashboard/stats').catch(() => null);
    if (!data) return;

    document.getElementById('dash-clients').textContent = data.client_count;
    document.getElementById('dash-visits').textContent = data.visit_count;
    document.getElementById('dash-month-visits').textContent = data.month_visits;
    document.getElementById('dash-revenue').textContent = (data.revenue || 0).toLocaleString('cs-CZ') + ' Kč';
    document.getElementById('dash-retail-revenue').textContent = (data.retail_revenue || 0).toLocaleString('cs-CZ') + ' Kč';
    document.getElementById('dash-total-revenue').textContent = ((data.revenue || 0) + (data.retail_revenue || 0)).toLocaleString('cs-CZ') + ' Kč';

    const ul = document.getElementById('dash-recent');
    if (!data.recent.length) {
        ul.innerHTML = '<li class="dash-recent-empty">Zatím žádné návštěvy</li>';
        return;
    }
    ul.innerHTML = data.recent.map(v => {
        const d = new Date(v.visit_date);
        const dateStr = d.toLocaleDateString('cs-CZ');
        return `<li class="dash-recent-item">
            <span class="dash-recent-name">${e(v.full_name)}</span>
            <span class="dash-recent-date">${dateStr}</span>
        </li>`;
    }).join('');

    // Unpaid visits
    const unpaidUl = document.getElementById('dash-unpaid');
    if (!data.unpaid || !data.unpaid.length) {
        unpaidUl.innerHTML = '<li class="dash-recent-empty">Žádné nevyúčtované návštěvy 🎉</li>';
    } else {
        unpaidUl.innerHTML = data.unpaid.map(v => {
            const d = new Date(v.visit_date);
            const dateStr = d.toLocaleDateString('cs-CZ');
            const price = v.price ? `${Number(v.price).toLocaleString('cs-CZ')} Kč` : 'bez ceny';
            return `<li class="dash-recent-item dash-unpaid-item" data-client-id="${v.client_id}">
                <span class="dash-recent-name">${e(v.full_name)}</span>
                <span class="dash-unpaid-price">${price}</span>
                <span class="dash-recent-date">${dateStr}</span>
            </li>`;
        }).join('');
        unpaidUl.querySelectorAll('.dash-unpaid-item').forEach(li => {
            li.style.cursor = 'pointer';
            li.addEventListener('click', () => {
                document.querySelector('[data-view="clients"]').click();
                loadClient(+li.dataset.clientId);
            });
        });
    }

    // Chart
    renderDashChart(data.monthly || []);

    // Retention
    const retUl = document.getElementById('dash-retention');
    if (!data.retention || !data.retention.length) {
        retUl.innerHTML = '<li class="dash-recent-empty">Všichni klienti jsou aktivní 🎉</li>';
    } else {
        retUl.innerHTML = data.retention.map(r => {
            const days = r.days_since;
            let level = 'ret-warn';
            if (days > 90) level = 'ret-danger';
            else if (days > 60) level = 'ret-orange';
            return `<li class="dash-recent-item dash-retention-item ${level}" data-client-id="${r.id}" style="cursor:pointer">
                <span class="dash-recent-name">${e(r.full_name)}</span>
                <span class="dash-retention-days">${days} dní</span>
                <span class="dash-recent-date">${r.last_visit ? new Date(r.last_visit).toLocaleDateString('cs-CZ') : 'nikdy'}</span>
            </li>`;
        }).join('');
        retUl.querySelectorAll('.dash-retention-item').forEach(li => {
            li.addEventListener('click', () => {
                document.querySelector('[data-view="clients"]').click();
                loadClient(+li.dataset.clientId);
            });
        });
    }
}

function renderDashChart(monthly) {
    const container = document.getElementById('dash-chart');
    if (!monthly.length) {
        container.innerHTML = '<span class="dash-recent-empty">Zatím žádná data</span>';
        return;
    }
    const monthNames = ['Led','Úno','Bře','Dub','Kvě','Čvn','Čvc','Srp','Zář','Říj','Lis','Pro'];
    const maxCnt = Math.max(...monthly.map(m => m.cnt), 1);
    container.innerHTML = `<div class="dash-bars">${monthly.map(m => {
        const pct = Math.round((m.cnt / maxCnt) * 100);
        const mon = parseInt(m.month.split('-')[1], 10) - 1;
        return `<div class="dash-bar-col">
            <span class="dash-bar-value">${m.cnt}</span>
            <div class="dash-bar" style="height:${Math.max(pct, 4)}%"></div>
            <span class="dash-bar-label">${monthNames[mon]}</span>
        </div>`;
    }).join('')}</div>`;
}

// ── Init ──────────────────────────────────────────────────────────────────────

document.getElementById('client-list').addEventListener('click', ev => {
    const li = ev.target.closest('.client-item');
    if (li) loadClient(+li.dataset.id);
});

// ── Navigation Rail ───────────────────────────────────────────────────────────

document.querySelectorAll('.nav-rail-btn[data-view]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.nav-rail-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const view = btn.dataset.view;
        document.getElementById('dashboard-view').hidden = view !== 'dashboard';
        document.getElementById('sidebar').hidden = view !== 'clients';
        document.getElementById('main').hidden = view !== 'clients';
        document.getElementById('settings-view').hidden = view !== 'settings';
        document.getElementById('accounting-view').hidden = view !== 'accounting';
        document.getElementById('stats-view').hidden = view !== 'stats';
        if (view === 'dashboard') loadDashboard();
        if (view === 'settings') loadCodeList();
        if (view === 'accounting') loadAccounting();
        if (view === 'stats') loadStats();
    });
});

// ── Settings Main Tabs ────────────────────────────────────────────────────────
let activeSettingsTab = 'codelists';

document.querySelectorAll('.settings-main-tab').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.settings-main-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeSettingsTab = btn.dataset.settingsTab;
        document.querySelectorAll('.settings-panel').forEach(p => p.hidden = true);
        document.getElementById('settings-panel-' + activeSettingsTab).hidden = false;

        if (activeSettingsTab === 'auth') loadAuthSettings();
        if (activeSettingsTab === 'salon') loadSalonSettings();
        if (activeSettingsTab === 'data') loadDataStats();
    });
});

async function loadAuthSettings() {
    try {
        const data = await api('/settings/get-username');
        document.getElementById('auth-username').value = data.username || '';
    } catch {}
}

document.getElementById('auth-settings-form').addEventListener('submit', async ev => {
    ev.preventDefault();
    const msgEl = document.getElementById('auth-msg');
    const username = document.getElementById('auth-username').value.trim();
    const currentPass = document.getElementById('auth-current-pass').value;
    const newPass = document.getElementById('auth-new-pass').value;
    const newPass2 = document.getElementById('auth-new-pass2').value;

    msgEl.hidden = true;
    msgEl.className = 'settings-msg';

    try {
        const res = await fetch('/settings/change-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username,
                current_password: currentPass,
                new_password: newPass,
                new_password2: newPass2
            })
        });
        const data = await res.json();
        if (!res.ok) {
            msgEl.textContent = data.error || 'Chyba';
            msgEl.classList.add('msg-error');
        } else {
            msgEl.textContent = data.message || 'Uloženo';
            msgEl.classList.add('msg-success');
            document.getElementById('auth-current-pass').value = '';
            document.getElementById('auth-new-pass').value = '';
            document.getElementById('auth-new-pass2').value = '';
        }
        msgEl.hidden = false;
    } catch {
        msgEl.textContent = 'Chyba připojení';
        msgEl.classList.add('msg-error');
        msgEl.hidden = false;
    }
});

document.getElementById('btn-db-backup').addEventListener('click', () => {
    window.location.href = '/settings/backup';
});

// ── Code Lists (Číselníky) ────────────────────────────────────────────────────

let activeClType = 'service';
let clSearchQuery = '';
let clCurrentPage = 1;
const CL_PER_PAGE = 20;

document.getElementById('cl-search').addEventListener('input', ev => {
    clSearchQuery = ev.target.value.trim().toLowerCase();
    clCurrentPage = 1;
    if (activeClType === 'material') {
        renderMaterialAccordion();
    } else if (activeClType === 'retail') {
        renderRetailAccordion();
    } else {
        renderClItems();
    }
});

let clAllItems = [];
let matGroupedData = {};
let retailGroupedData = {};

document.querySelectorAll('.settings-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        activeClType = tab.dataset.clType;
        clSearchQuery = '';
        clCurrentPage = 1;
        document.getElementById('cl-search').value = '';

        const isMat = activeClType === 'material';
        const isRetail = activeClType === 'retail';
        const isTag = activeClType === 'tag';
        document.getElementById('cl-table-wrap').hidden = isMat || isRetail || isTag;
        document.getElementById('mat-accordion').hidden = !isMat;
        document.getElementById('retail-accordion').hidden = !isRetail;
        document.getElementById('tag-list-wrap').hidden = !isTag;

        if (!isMat && !isRetail && !isTag) {
            const showIcon = activeClType === 'service';
            document.querySelectorAll('.cl-table .cl-icon, .cl-table th:nth-child(1)').forEach(el => {
                el.style.display = showIcon ? '' : 'none';
            });
        }
        loadCodeList();
    });
});

async function loadCodeList() {
    if (activeClType === 'tag') {
        await loadTagList();
    } else if (activeClType === 'material') {
        matGroupedData = await api('/products/grouped').catch(() => ({}));
        renderMaterialAccordion();
    } else if (activeClType === 'retail') {
        retailGroupedData = await api('/products/grouped-retail').catch(() => ({}));
        renderRetailAccordion();
    } else {
        clAllItems = await api(`/codelists/index?type=${activeClType}`).catch(() => []);
        renderClItems();
    }
}

function renderClItems() {
    const showIcon = activeClType === 'service';
    let items = clAllItems;

    // Filter by search
    if (clSearchQuery) {
        items = items.filter(item => item.name.toLowerCase().includes(clSearchQuery));
    }

    // Pagination
    const totalPages = Math.ceil(items.length / CL_PER_PAGE) || 1;
    if (clCurrentPage > totalPages) clCurrentPage = totalPages;
    const start = (clCurrentPage - 1) * CL_PER_PAGE;
    const pageItems = items.slice(start, start + CL_PER_PAGE);

    const tbody = document.getElementById('cl-tbody');
    tbody.innerHTML = pageItems.map(item => `
        <tr data-id="${item.id}" draggable="true">
            <td class="cl-icon" ${showIcon ? '' : 'style="display:none"'}>${e(item.icon || '')}</td>
            <td>${e(item.name)}</td>
            <td class="cl-actions">
                <button class="btn-visit-action btn-cl-edit" data-id="${item.id}" data-tip="Upravit"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg></button>
                <button class="btn-visit-action btn-danger-icon btn-cl-delete" data-id="${item.id}" data-tip="Smazat"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
            </td>
        </tr>`).join('');

    // Drag & drop reorder (only when not searching)
    if (!clSearchQuery) initClDragDrop(tbody);

    // Also hide the th for icon column
    document.querySelectorAll('.cl-table th:nth-child(1)').forEach(el => {
        el.style.display = showIcon ? '' : 'none';
    });

    // Pagination controls
    const pagEl = document.getElementById('cl-pagination');
    if (totalPages <= 1) {
        pagEl.innerHTML = '';
    } else {
        let html = '';
        for (let p = 1; p <= totalPages; p++) {
            html += `<button class="cl-page-btn ${p === clCurrentPage ? 'active' : ''}" data-page="${p}">${p}</button>`;
        }
        pagEl.innerHTML = html;
        pagEl.querySelectorAll('.cl-page-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                clCurrentPage = +btn.dataset.page;
                renderClItems();
            });
        });
    }

    // Edit
    tbody.querySelectorAll('.btn-cl-edit').forEach(btn => {
        btn.addEventListener('click', async () => {
            const item = clAllItems.find(i => i.id == btn.dataset.id);
            if (!item) return;
            openClModal(item);
        });
    });

    // Delete
    tbody.querySelectorAll('.btn-cl-delete').forEach(btn => {
        btn.addEventListener('click', async () => {
            const ok = await modalConfirm('Opravdu smazat tuto položku?', {
                title: 'Smazat položku',
                okText: 'Smazat',
                okClass: 'btn btn-danger',
            });
            if (!ok) return;
            try {
                await api(`/codelists/delete/${btn.dataset.id}`, { method: 'POST' });
                toast('Položka smazána');
                loadCodeList();
            } catch (err) {
                toast(err.message, 'error');
            }
        });
    });
}

// Drag & drop for code list reorder
function initClDragDrop(tbody) {
    let dragRow = null;
    tbody.querySelectorAll('tr[draggable]').forEach(row => {
        row.addEventListener('dragstart', ev => {
            dragRow = row;
            row.classList.add('cl-dragging');
            ev.dataTransfer.effectAllowed = 'move';
        });
        row.addEventListener('dragend', () => {
            row.classList.remove('cl-dragging');
            tbody.querySelectorAll('tr').forEach(r => r.classList.remove('cl-drag-over'));
            dragRow = null;
        });
        row.addEventListener('dragover', ev => {
            ev.preventDefault();
            ev.dataTransfer.dropEffect = 'move';
            if (row !== dragRow) {
                tbody.querySelectorAll('tr').forEach(r => r.classList.remove('cl-drag-over'));
                row.classList.add('cl-drag-over');
            }
        });
        row.addEventListener('dragleave', () => {
            row.classList.remove('cl-drag-over');
        });
        row.addEventListener('drop', async ev => {
            ev.preventDefault();
            row.classList.remove('cl-drag-over');
            if (!dragRow || dragRow === row) return;
            // DOM reorder
            const rows = [...tbody.querySelectorAll('tr')];
            const fromIdx = rows.indexOf(dragRow);
            const toIdx = rows.indexOf(row);
            if (fromIdx < toIdx) {
                row.after(dragRow);
            } else {
                row.before(dragRow);
            }
            // Save new order
            const ids = [...tbody.querySelectorAll('tr')].map(r => +r.dataset.id);
            try {
                await api('/codelists/reorder', { method: 'POST', body: JSON.stringify({ ids }) });
                refreshCodeListCache();
            } catch (err) {
                toast(err.message, 'error');
                loadCodeList();
            }
        });
    });
}

// ── Material Accordion ────────────────────────────────────────────────────────

let matExpandedSeries = new Set();

function renderMaterialAccordion() {
    const wrap = document.getElementById('mat-accordion');
    const q = clSearchQuery;
    let html = '';
    const seriesNames = Object.keys(matGroupedData).sort((a, b) => a.localeCompare(b, 'cs'));

    for (const series of seriesNames) {
        let items = matGroupedData[series];
        if (q) {
            items = items.filter(it => it.title.toLowerCase().includes(q) || (it.category || '').toLowerCase().includes(q));
            if (items.length === 0) continue;
        }
        const expanded = q || matExpandedSeries.has(series);
        html += `<div class="mat-group" data-series="${e(series)}">
            <div class="mat-group-header" role="button">
                <svg class="mat-chevron ${expanded ? 'open' : ''}" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                <span class="mat-group-name">${e(series)}</span>
                <span class="mat-group-count">${items.length}</span>
            </div>
            <div class="mat-group-body" ${expanded ? '' : 'style="display:none"'}>
                <table class="cl-table mat-table">
                    <tbody>
                        ${items.map(it => `<tr data-id="${it.id}">
                            <td>${e(it.title)}</td>
                            <td class="mat-cat">${e(it.category || '')}</td>
                            <td class="cl-actions">
                                <button class="btn-visit-action btn-mat-edit" data-id="${it.id}" data-tip="Upravit"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg></button>
                                <button class="btn-visit-action btn-danger-icon btn-mat-del" data-id="${it.id}" data-tip="Smazat"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
                            </td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>
        </div>`;
    }
    if (!html) html = '<p class="mat-empty">Žádné materiály</p>';
    wrap.innerHTML = html;

    // Expand/collapse
    wrap.querySelectorAll('.mat-group-header').forEach(hdr => {
        hdr.addEventListener('click', () => {
            const group = hdr.closest('.mat-group');
            const body = group.querySelector('.mat-group-body');
            const chev = hdr.querySelector('.mat-chevron');
            const series = group.dataset.series;
            const isOpen = body.style.display !== 'none';
            body.style.display = isOpen ? 'none' : '';
            chev.classList.toggle('open', !isOpen);
            if (isOpen) matExpandedSeries.delete(series); else matExpandedSeries.add(series);
        });
    });

    // Edit product
    wrap.querySelectorAll('.btn-mat-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = +btn.dataset.id;
            let found = null;
            for (const items of Object.values(matGroupedData)) {
                found = items.find(i => i.id === id);
                if (found) break;
            }
            if (found) openMatModal(found);
        });
    });

    // Delete product
    wrap.querySelectorAll('.btn-mat-del').forEach(btn => {
        btn.addEventListener('click', async () => {
            const ok = await modalConfirm('Opravdu smazat tento materiál?', {
                title: 'Smazat materiál',
                okText: 'Smazat',
                okClass: 'btn btn-danger',
            });
            if (!ok) return;
            try {
                await api(`/products/delete/${btn.dataset.id}`, { method: 'POST' });
                toast('Materiál smazán');
                loadCodeList();
            } catch (err) {
                toast(err.message, 'error');
            }
        });
    });
}

// Material modal
const matModal = document.getElementById('mat-modal');
const matForm  = document.getElementById('mat-form');

function openMatModal(item) {
    document.getElementById('mat-modal-title').textContent = item ? 'Upravit materiál' : 'Přidat materiál';
    document.getElementById('matf-id').value = item?.id ?? '';
    document.getElementById('matf-series').value = item?.series ?? '';
    document.getElementById('matf-title').value = item?.title ?? '';
    document.getElementById('matf-category').value = item?.category ?? '';
    matModal.hidden = false;
    document.getElementById('matf-series').focus();
}

document.getElementById('btn-mat-cancel').addEventListener('click', () => { matModal.hidden = true; });
matModal.addEventListener('click', ev => { if (ev.target === matModal) matModal.hidden = true; });

matForm.addEventListener('submit', async ev => {
    ev.preventDefault();
    const id = document.getElementById('matf-id').value;
    const payload = {
        title:    document.getElementById('matf-title').value.trim(),
        series:   document.getElementById('matf-series').value.trim(),
        category: document.getElementById('matf-category').value.trim() || null,
    };
    if (!payload.title) { toast('Název je povinný', 'error'); return; }
    if (!payload.series) { toast('Řada je povinná', 'error'); return; }
    try {
        if (id) {
            await api(`/products/update/${id}`, { method: 'POST', body: JSON.stringify(payload) });
            toast('Materiál aktualizován');
        } else {
            await api('/products/store', { method: 'POST', body: JSON.stringify(payload) });
            toast('Materiál přidán');
        }
        matModal.hidden = true;
        loadCodeList();
    } catch (err) {
        toast(err.message, 'error');
    }
});

// ── Retail accordion (Produkty na doma) ───────────────────────────────────────

let retailExpandedSeries = new Set();

function renderRetailAccordion() {
    const wrap = document.getElementById('retail-accordion');
    const q = clSearchQuery;
    let html = '';
    const seriesNames = Object.keys(retailGroupedData).sort((a, b) => a.localeCompare(b, 'cs'));

    for (const series of seriesNames) {
        let items = retailGroupedData[series];
        if (q) {
            items = items.filter(it => it.title.toLowerCase().includes(q) || (it.category || '').toLowerCase().includes(q) || (it.volume || '').toLowerCase().includes(q));
            if (items.length === 0) continue;
        }
        const expanded = q || retailExpandedSeries.has(series);
        html += `<div class="mat-group" data-series="${e(series)}">
            <div class="mat-group-header" role="button">
                <svg class="mat-chevron ${expanded ? 'open' : ''}" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                <span class="mat-group-name">${e(series)}</span>
                <span class="mat-group-count">${items.length}</span>
            </div>
            <div class="mat-group-body" ${expanded ? '' : 'style="display:none"'}>
                <table class="cl-table mat-table">
                    <tbody>
                        ${items.map(it => `<tr data-id="${it.id}">
                            <td>${e(it.title)}</td>
                            <td class="mat-cat">${e(it.volume || '')}</td>
                            <td class="mat-cat">${e(it.category || '')}</td>
                            <td class="mat-cat">${it.default_price ? it.default_price + ' Kč' : ''}</td>
                            <td class="cl-actions">
                                <button class="btn-visit-action btn-ret-edit" data-id="${it.id}" data-tip="Upravit"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg></button>
                                <button class="btn-visit-action btn-danger-icon btn-ret-del" data-id="${it.id}" data-tip="Smazat"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
                            </td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>
        </div>`;
    }
    if (!html) html = '<p class="mat-empty">Žádné produkty na doma</p>';
    wrap.innerHTML = html;

    // Expand/collapse
    wrap.querySelectorAll('.mat-group-header').forEach(hdr => {
        hdr.addEventListener('click', () => {
            const group = hdr.closest('.mat-group');
            const body = group.querySelector('.mat-group-body');
            const chev = hdr.querySelector('.mat-chevron');
            const series = group.dataset.series;
            const isOpen = body.style.display !== 'none';
            body.style.display = isOpen ? 'none' : '';
            chev.classList.toggle('open', !isOpen);
            if (isOpen) retailExpandedSeries.delete(series); else retailExpandedSeries.add(series);
        });
    });

    // Edit retail product
    wrap.querySelectorAll('.btn-ret-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = +btn.dataset.id;
            let found = null;
            for (const items of Object.values(retailGroupedData)) {
                found = items.find(i => i.id === id);
                if (found) break;
            }
            if (found) openRetailModal(found);
        });
    });

    // Delete retail product
    wrap.querySelectorAll('.btn-ret-del').forEach(btn => {
        btn.addEventListener('click', async () => {
            const ok = await modalConfirm('Opravdu smazat tento produkt?', {
                title: 'Smazat produkt',
                okText: 'Smazat',
                okClass: 'btn btn-danger',
            });
            if (!ok) return;
            try {
                await api(`/products/delete/${btn.dataset.id}`, { method: 'POST' });
                toast('Produkt smazán');
                loadCodeList();
            } catch (err) {
                toast(err.message, 'error');
            }
        });
    });
}

// Retail modal
const retailModal = document.getElementById('retail-modal');
const retailForm  = document.getElementById('retail-form');

function openRetailModal(item) {
    document.getElementById('retail-modal-title').textContent = item ? 'Upravit produkt' : 'Přidat produkt';
    document.getElementById('retf-id').value = item?.id ?? '';
    document.getElementById('retf-series').value = item?.series ?? '';
    document.getElementById('retf-title').value = item?.title ?? '';
    document.getElementById('retf-volume').value = item?.volume ?? '';
    document.getElementById('retf-category').value = item?.category ?? '';
    document.getElementById('retf-price').value = item?.default_price ?? '';
    retailModal.hidden = false;
    document.getElementById('retf-series').focus();
}

document.getElementById('btn-retail-cancel').addEventListener('click', () => { retailModal.hidden = true; });
retailModal.addEventListener('click', ev => { if (ev.target === retailModal) retailModal.hidden = true; });

retailForm.addEventListener('submit', async ev => {
    ev.preventDefault();
    const id = document.getElementById('retf-id').value;
    const payload = {
        title:         document.getElementById('retf-title').value.trim(),
        series:        document.getElementById('retf-series').value.trim(),
        volume:        document.getElementById('retf-volume').value.trim() || null,
        category:      document.getElementById('retf-category').value.trim() || null,
        default_price: document.getElementById('retf-price').value ? +document.getElementById('retf-price').value : null,
        is_retail:     1,
    };
    if (!payload.title) { toast('Název je povinný', 'error'); return; }
    if (!payload.series) { toast('Řada je povinná', 'error'); return; }
    try {
        if (id) {
            await api(`/products/update/${id}`, { method: 'POST', body: JSON.stringify(payload) });
            toast('Produkt aktualizován');
        } else {
            await api('/products/store', { method: 'POST', body: JSON.stringify(payload) });
            toast('Produkt přidán');
        }
        retailModal.hidden = true;
        loadCodeList();
    } catch (err) {
        toast(err.message, 'error');
    }
});

// Code list modal
const clModal = document.getElementById('cl-modal');
const clForm  = document.getElementById('cl-form');

document.getElementById('btn-cl-add').addEventListener('click', () => {
    if (activeClType === 'material') {
        openMatModal(null);
    } else if (activeClType === 'retail') {
        openRetailModal(null);
    } else if (activeClType === 'tag') {
        openTagModal(null);
    } else {
        openClModal(null);
    }
});

function openClModal(item) {
    document.getElementById('cl-modal-title').textContent = item ? 'Upravit položku' : 'Přidat položku';
    document.getElementById('clf-id').value = item?.id ?? '';
    document.getElementById('clf-type').value = item?.type ?? activeClType;
    document.getElementById('clf-name').value = item?.name ?? '';
    document.getElementById('clf-icon').value = item?.icon ?? '';
    // Show icon field only for services
    document.getElementById('clf-icon-row').style.display = (item?.type ?? activeClType) === 'service' ? '' : 'none';
    clModal.hidden = false;
    document.getElementById('clf-name').focus();
}

document.getElementById('btn-cl-cancel').addEventListener('click', () => { clModal.hidden = true; });
clModal.addEventListener('click', ev => { if (ev.target === clModal) clModal.hidden = true; });

clForm.addEventListener('submit', async ev => {
    ev.preventDefault();
    const id = document.getElementById('clf-id').value;
    const payload = {
        type:       document.getElementById('clf-type').value,
        name:       document.getElementById('clf-name').value.trim(),
        icon:       document.getElementById('clf-icon').value.trim() || null,
        sort_order: 0,
    };
    if (!payload.name) { toast('Název je povinný', 'error'); return; }
    try {
        if (id) {
            await api(`/codelists/update/${id}`, { method: 'POST', body: JSON.stringify(payload) });
            toast('Položka aktualizována');
        } else {
            await api('/codelists/store', { method: 'POST', body: JSON.stringify(payload) });
            toast('Položka přidána');
        }
        clModal.hidden = true;
        loadCodeList();
        // Refresh cached data for editor
        refreshCodeListCache();
    } catch (err) {
        toast(err.message, 'error');
    }
});

// ── Accounting (Účetní přehledy) ──────────────────────────────────────────────

// ── Tag Management (Štítky) ──────────────────────────────────────────────────

let allTagItems = [];

async function loadTagList() {
    allTagItems = await api('/tags/index').catch(() => []);
    renderTagList();
}

function renderTagList() {
    const wrap = document.getElementById('tag-list-wrap');
    let items = allTagItems;
    if (clSearchQuery) {
        items = items.filter(t => t.name.toLowerCase().includes(clSearchQuery));
    }
    if (!items.length) {
        wrap.innerHTML = '<p style="text-align:center;color:var(--color-muted);padding:24px">Žádné štítky</p>';
        return;
    }
    wrap.innerHTML = `<table class="cl-table tag-table"><tbody>${items.map(t => `
        <tr data-id="${t.id}" draggable="true">
            <td class="tag-color-cell"><span class="tag-swatch" style="background:${e(t.color)}"></span></td>
            <td><span class="tag-pill" style="background:${hexBg(t.color)};color:${t.color}">${e(t.name)}</span></td>
            <td class="cl-actions">
                <button class="btn-visit-action btn-tag-edit" data-id="${t.id}" data-tip="Upravit"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg></button>
                <button class="btn-visit-action btn-danger-icon btn-tag-delete" data-id="${t.id}" data-tip="Smazat"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
            </td>
        </tr>`).join('')}</tbody></table>`;

    // Edit
    wrap.querySelectorAll('.btn-tag-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            const tag = allTagItems.find(t => t.id == btn.dataset.id);
            if (tag) openTagModal(tag);
        });
    });

    // Delete
    wrap.querySelectorAll('.btn-tag-delete').forEach(btn => {
        btn.addEventListener('click', async () => {
            const ok = await modalConfirm('Opravdu smazat tento štítek? Bude odebrán i ze všech klientů.', {
                title: 'Smazat štítek',
                okText: 'Smazat',
                okClass: 'btn btn-danger',
            });
            if (!ok) return;
            try {
                await api(`/tags/delete/${btn.dataset.id}`, { method: 'POST' });
                toast('Štítek smazán');
                loadTagList();
            } catch (err) {
                toast(err.message, 'error');
            }
        });
    });

    // Drag & drop reorder
    const tbody = wrap.querySelector('tbody');
    if (tbody && !clSearchQuery) {
        let dragRow = null;
        tbody.querySelectorAll('tr[draggable]').forEach(row => {
            row.addEventListener('dragstart', ev => {
                dragRow = row;
                row.classList.add('cl-dragging');
                ev.dataTransfer.effectAllowed = 'move';
            });
            row.addEventListener('dragend', () => {
                row.classList.remove('cl-dragging');
                tbody.querySelectorAll('tr').forEach(r => r.classList.remove('cl-drag-over'));
                dragRow = null;
            });
            row.addEventListener('dragover', ev => {
                ev.preventDefault();
                ev.dataTransfer.dropEffect = 'move';
                if (row !== dragRow) {
                    tbody.querySelectorAll('tr').forEach(r => r.classList.remove('cl-drag-over'));
                    row.classList.add('cl-drag-over');
                }
            });
            row.addEventListener('dragleave', () => row.classList.remove('cl-drag-over'));
            row.addEventListener('drop', async ev => {
                ev.preventDefault();
                row.classList.remove('cl-drag-over');
                if (!dragRow || dragRow === row) return;
                const rows = [...tbody.querySelectorAll('tr')];
                const fromIdx = rows.indexOf(dragRow);
                const toIdx = rows.indexOf(row);
                if (fromIdx < toIdx) row.after(dragRow); else row.before(dragRow);
                const ids = [...tbody.querySelectorAll('tr')].map(r => +r.dataset.id);
                try {
                    await api('/tags/reorder', { method: 'POST', body: JSON.stringify({ ids }) });
                } catch (err) {
                    toast(err.message, 'error');
                    loadTagList();
                }
            });
        });
    }
}

// Tag modal
const tagModal = document.getElementById('tag-modal');
const tagForm = document.getElementById('tag-form');
const tagColorInput = document.getElementById('tagf-color');

function updateTagPreview() {
    const name = document.getElementById('tagf-name').value.trim() || 'Náhled štítku';
    const color = tagColorInput.value;
    document.getElementById('tag-preview').innerHTML =
        `<span class="tag-pill" style="background:${hexBg(color)};color:${color}">${esc(name)}</span>`;
}

document.getElementById('tagf-name').addEventListener('input', updateTagPreview);
tagColorInput.addEventListener('input', updateTagPreview);

document.getElementById('tag-color-presets').addEventListener('click', ev => {
    const btn = ev.target.closest('.tag-color-btn');
    if (!btn) return;
    tagColorInput.value = btn.dataset.color;
    updateTagPreview();
});

function openTagModal(tag) {
    document.getElementById('tag-modal-title').textContent = tag ? 'Upravit štítek' : 'Přidat štítek';
    document.getElementById('tagf-id').value = tag?.id ?? '';
    document.getElementById('tagf-name').value = tag?.name ?? '';
    tagColorInput.value = tag?.color ?? '#a78bfa';
    updateTagPreview();
    tagModal.hidden = false;
    document.getElementById('tagf-name').focus();
}

document.getElementById('btn-tag-cancel').addEventListener('click', () => { tagModal.hidden = true; });
tagModal.addEventListener('click', ev => { if (ev.target === tagModal) tagModal.hidden = true; });

tagForm.addEventListener('submit', async ev => {
    ev.preventDefault();
    const id = document.getElementById('tagf-id').value;
    const payload = {
        name:  document.getElementById('tagf-name').value.trim(),
        color: tagColorInput.value,
    };
    if (!payload.name) { toast('Název je povinný', 'error'); return; }
    try {
        if (id) {
            await api(`/tags/update/${id}`, { method: 'POST', body: JSON.stringify(payload) });
            toast('Štítek uložen');
        } else {
            await api('/tags/store', { method: 'POST', body: JSON.stringify(payload) });
            toast('Štítek přidán');
        }
        tagModal.hidden = true;
        loadTagList();
    } catch (err) {
        toast(err.message, 'error');
    }
});

// ── Accounting (Účetní přehledy) ──────────────────────────────────────────────

const accMonthNames = ['','Leden','Únor','Březen','Duben','Květen','Červen',
                        'Červenec','Srpen','Září','Říjen','Listopad','Prosinec'];

function initAccYearSelect() {
    const sel = document.getElementById('acc-year');
    if (sel.options.length > 0) return;
    const cur = new Date().getFullYear();
    for (let y = cur; y >= cur - 5; y--) {
        const opt = document.createElement('option');
        opt.value = y;
        opt.textContent = y;
        sel.appendChild(opt);
    }
    sel.value = cur;
    sel.addEventListener('change', () => loadAccounting());
}

async function loadAccounting() {
    initAccYearSelect();
    const year = document.getElementById('acc-year').value;
    const data = await api(`/accounting/yearly?year=${year}`).catch(() => null);
    if (!data) return;

    const tbody = document.getElementById('acc-yearly-body');
    const fmt = n => Number(n).toLocaleString('cs-CZ') + ' Kč';

    tbody.innerHTML = data.months.map((m, i) => {
        const isEmpty = m.visits_count === 0 && m.sales_count === 0;
        return `<tr class="${isEmpty ? 'acc-row-empty' : ''}">
            <td>${accMonthNames[i + 1]}</td>
            <td class="num">${m.visits_count}</td>
            <td class="num">${fmt(m.services_total)}</td>
            <td class="num">${m.sales_count}</td>
            <td class="num">${fmt(m.products_total)}</td>
            <td class="num acc-highlight">${fmt(m.total)}</td>
        </tr>`;
    }).join('');

    document.getElementById('acc-yearly-foot').innerHTML = `<tr>
        <td>Celkem ${year}</td>
        <td class="num">${data.months.reduce((s, m) => s + m.visits_count, 0)}</td>
        <td class="num">${fmt(data.year_services)}</td>
        <td class="num">${data.months.reduce((s, m) => s + m.sales_count, 0)}</td>
        <td class="num">${fmt(data.year_products)}</td>
        <td class="num acc-highlight">${fmt(data.year_total)}</td>
    </tr>`;

    // Set daily date to today
    const dateInput = document.getElementById('acc-daily-date');
    if (!dateInput.value) dateInput.value = new Date().toISOString().split('T')[0];
    loadDailyClosing();
}

async function loadDailyClosing() {
    const date = document.getElementById('acc-daily-date').value;
    if (!date) return;
    const data = await api(`/accounting/daily?date=${date}`).catch(() => null);
    if (!data) return;

    const fmt = n => Number(n).toLocaleString('cs-CZ') + ' Kč';
    const container = document.getElementById('acc-daily-content');
    const isClosed = !!data.closing;

    container.innerHTML = `
        <div class="acc-daily-card">
            <span class="acc-daily-value">${data.visits_count}</span>
            <span class="acc-daily-label">Návštěv</span>
        </div>
        <div class="acc-daily-card">
            <span class="acc-daily-value">${fmt(data.services_total)}</span>
            <span class="acc-daily-label">Služby</span>
        </div>
        <div class="acc-daily-card">
            <span class="acc-daily-value">${fmt(data.products_total)}</span>
            <span class="acc-daily-label">Produkty (${data.sales_count} prodejů)</span>
        </div>
        <div class="acc-daily-card ${isClosed ? 'acc-closed' : ''}">
            <span class="acc-daily-value acc-highlight">${fmt(data.total)}</span>
            <span class="acc-daily-label">Celkem</span>
            ${isClosed ? '<span class="acc-closing-badge">✓ Uzavřeno</span>' : ''}
        </div>
    `;
}

document.getElementById('acc-daily-date').addEventListener('change', loadDailyClosing);

document.getElementById('acc-close-day').addEventListener('click', async () => {
    const date = document.getElementById('acc-daily-date').value;
    if (!date) return;

    // Load current day data first
    const dayData = await api(`/accounting/daily?date=${date}`).catch(() => null);
    if (!dayData) return;

    const fmt = n => Number(n).toLocaleString('cs-CZ') + ' Kč';
    const d = new Date(date + 'T00:00:00');
    const dateStr = d.toLocaleDateString('cs-CZ', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

    let warnings = '';
    if (dayData.unpaid_count > 0) {
        warnings += `<div class="modal-closing-warn warn-orange">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span>${dayData.unpaid_count} ${dayData.unpaid_count === 1 ? 'návštěva není vyúčtovaná' : dayData.unpaid_count < 5 ? 'návštěvy nejsou vyúčtované' : 'návštěv není vyúčtováno'} — nebudou zahrnuty v uzávěrce.</span>
        </div>`;
    }
    if (dayData.closing) {
        warnings += `<div class="modal-closing-warn warn-blue">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>Tento den již byl uzavřen — uzávěrka bude přepsána.</span>
        </div>`;
    }

    const html = `
        <div class="modal-closing-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div style="font-size:13px;color:var(--color-muted);margin-bottom:4px">${dateStr}</div>
        <dl class="modal-closing-stats">
            <dt>Vyúčtované návštěvy</dt><dd>${dayData.visits_count}</dd>
            <dt>Služby</dt><dd>${fmt(dayData.services_total)}</dd>
            <dt>Prodeje produktů</dt><dd>${dayData.sales_count}</dd>
            <dt>Produkty</dt><dd>${fmt(dayData.products_total)}</dd>
        </dl>
        <div class="modal-closing-total">
            <span>Celkem</span>
            <span class="total-value">${fmt(dayData.total)}</span>
        </div>
        ${warnings}
    `;

    const confirmed = await modalConfirm(html, {
        title: 'Denní uzávěrka',
        okText: 'Uzavřít den',
        okClass: 'btn btn-accent',
        html: true
    });
    if (!confirmed) return;

    const res = await api('/accounting/close-day', {
        method: 'POST',
        body: JSON.stringify({ date })
    }).catch(err => { toast(err.message, 'error'); return null; });
    if (res && !res.error) {
        toast('Denní uzávěrka uložena');
        loadDailyClosing();
        loadAccounting();
    }
});

document.getElementById('acc-export-csv').addEventListener('click', () => {
    const year = document.getElementById('acc-year').value;
    window.location.href = `/accounting/export-csv?year=${year}`;
});

// ── Code list cache for editor ────────────────────────────────────────────────

// ── Print Receipt ──────────────────────────────────────────────────────────────

let cachedSalonInfo = null;

async function getSalonInfo() {
    if (cachedSalonInfo) return cachedSalonInfo;
    cachedSalonInfo = await api('/settings/get-salon').catch(() => ({}));
    return cachedSalonInfo;
}

async function printReceipt(visitId) {
    const visit = await api(`/visits/show/${visitId}`).catch(() => null);
    if (!visit) return toast('Nepodařilo se načíst návštěvu', 'error');

    const sales = await api(`/sales/for-visit/${visitId}`).catch(() => []);
    const salon = await getSalonInfo();

    const serviceTotal = Number(visit.price) || 0;
    let productsTotal = 0;
    const productLines = [];
    if (sales && sales.length) {
        sales.forEach(s => {
            const items = typeof s.items === 'string' ? JSON.parse(s.items) : (s.items || []);
            items.forEach(item => {
                const price = Number(item.price) || 0;
                const qty = Number(item.qty) || 1;
                productsTotal += price * qty;
                productLines.push({ name: item.title || item.name || '?', qty, price: price * qty });
            });
        });
    }
    const total = serviceTotal + productsTotal;

    const date = new Date(visit.visit_date);
    const dateStr = date.toLocaleDateString('cs-CZ');

    const html = `
<div class="receipt-paper">
    <div class="receipt-center">
        <div class="receipt-salon-name">${esc(salon.salon_name || 'AURA')}</div>
        ${salon.salon_address ? `<div class="receipt-salon-detail">${esc(salon.salon_address)}</div>` : ''}
        ${salon.salon_phone ? `<div class="receipt-salon-detail">Tel: ${esc(salon.salon_phone)}</div>` : ''}
        ${salon.salon_ico ? `<div class="receipt-salon-detail">IČO: ${esc(salon.salon_ico)}</div>` : ''}
    </div>
    <hr class="receipt-hr">
    <div class="receipt-center receipt-meta">${dateStr}${visit.service_name ? ' · ' + esc(visit.service_name) : ''}</div>
    <div class="receipt-center receipt-meta">Klient: ${esc(state.activeClientName || '—')}</div>
    <hr class="receipt-hr">
    <div class="receipt-line"><span>Služby</span><span>${serviceTotal.toLocaleString('cs-CZ')} Kč</span></div>
    ${productLines.map(p => `<div class="receipt-line"><span>${esc(p.name)}${p.qty > 1 ? ' ×' + p.qty : ''}</span><span>${p.price.toLocaleString('cs-CZ')} Kč</span></div>`).join('')}
    ${productsTotal > 0 ? `<div class="receipt-line"><span><strong>Produkty celkem</strong></span><span><strong>${productsTotal.toLocaleString('cs-CZ')} Kč</strong></span></div>` : ''}
    <div class="receipt-line receipt-total"><span>CELKEM</span><span>${total.toLocaleString('cs-CZ')} Kč</span></div>
    ${visit.billing_amount ? `<hr class="receipt-hr"><div class="receipt-line"><span>Přijato</span><span>${Number(visit.billing_amount).toLocaleString('cs-CZ')} Kč</span></div>` : ''}
    ${visit.billing_change && Number(visit.billing_change) > 0 ? `<div class="receipt-line"><span>Vráceno</span><span>${Number(visit.billing_change).toLocaleString('cs-CZ')} Kč</span></div>` : ''}
    <hr class="receipt-hr">
    <div class="receipt-footer">
        ${salon.salon_note ? esc(salon.salon_note) + '<br>' : ''}
        Děkujeme za návštěvu
    </div>
</div>`;

    document.getElementById('receipt-content').innerHTML = html;
    const modal = document.getElementById('receipt-modal');
    modal.hidden = false;

    // Print handler
    document.getElementById('btn-receipt-print').onclick = () => {
        const printW = window.open('', '_blank', 'width=320,height=600');
        printW.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Účtenka</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Courier New',monospace;font-size:12px;padding:16px;max-width:280px;margin:0 auto;color:#000;background:#fff}
.receipt-center{text-align:center}
.receipt-salon-name{font-size:16px;font-weight:bold;margin-bottom:2px}
.receipt-salon-detail{font-size:11px;color:#555}
.receipt-hr{border:none;border-top:1px dashed #999;margin:10px 0}
.receipt-meta{font-size:11px;color:#555}
.receipt-line{display:flex;justify-content:space-between;padding:2px 0}
.receipt-total{font-weight:bold;font-size:14px;border-top:2px solid #000;padding-top:4px;margin-top:4px}
.receipt-footer{margin-top:12px;text-align:center;font-size:11px;color:#555}
</style></head><body>${document.getElementById('receipt-content').innerHTML}
<script>window.onload=function(){window.print()}<\/script>
</body></html>`);
        printW.document.close();
    };

    // Close handlers
    const close = () => { modal.hidden = true; };
    document.getElementById('btn-receipt-cancel').onclick = close;
    document.getElementById('btn-receipt-close').onclick = close;
    modal.addEventListener('click', ev => { if (ev.target === modal) close(); }, { once: true });
}

function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

/** Convert hex color to a light background version */
function hexBg(hex) {
    const r = parseInt(hex.slice(1,3), 16), g = parseInt(hex.slice(3,5), 16), b = parseInt(hex.slice(5,7), 16);
    return `rgba(${r},${g},${b},0.13)`;
}

// ── Tags ───────────────────────────────────────────────────────────────────────

function renderTagDots(tags) {
    if (!tags || !tags.length) return '';
    return tags.map(t => `<span class="tag-dot" style="background:${t.color}" title="${esc(t.name)}"></span>`).join('');
}

function renderTagPills(tags) {
    if (!tags || !tags.length) return '';
    return tags.map(t => `<span class="tag-pill" style="background:${hexBg(t.color)};color:${t.color}">${esc(t.name)}</span>`).join('');
}

// ── Poznámky klienta ──────────────────────────────────────────────────────────

let editingNoteId = null;

async function loadNotes(clientId) {
    const ul = document.getElementById('note-list');
    const notes = await api(`/notes/index/${clientId}`).catch(() => []);
    if (!notes.length) {
        ul.innerHTML = '<li class="empty-message">Zatím žádné poznámky</li>';
        return;
    }
    ul.innerHTML = notes.map(n => `
        <li class="note-item" data-id="${n.id}">
            <div class="note-content">${esc(n.content)}</div>
            <div class="note-meta">
                <span class="note-date">${fmtDateTime(n.created_at)}</span>
                <span class="note-actions">
                    <button class="btn-note-edit" data-id="${n.id}" title="Upravit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                    </button>
                    <button class="btn-note-delete" data-id="${n.id}" title="Smazat">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </span>
            </div>
        </li>
    `).join('');

    ul.querySelectorAll('.btn-note-edit').forEach(btn => {
        btn.addEventListener('click', () => editNote(+btn.dataset.id, notes));
    });
    ul.querySelectorAll('.btn-note-delete').forEach(btn => {
        btn.addEventListener('click', () => deleteNote(+btn.dataset.id));
    });
}

function fmtDateTime(dt) {
    if (!dt) return '';
    const d = new Date(dt);
    return d.toLocaleDateString('cs-CZ') + ' ' + d.toLocaleTimeString('cs-CZ', { hour: '2-digit', minute: '2-digit' });
}

function openNoteForm(content = '', noteId = null) {
    editingNoteId = noteId;
    const wrap = document.getElementById('note-form-wrap');
    const input = document.getElementById('note-input');
    input.value = content;
    wrap.hidden = false;
    input.focus();
}

function closeNoteForm() {
    editingNoteId = null;
    document.getElementById('note-form-wrap').hidden = true;
    document.getElementById('note-input').value = '';
}

document.getElementById('btn-add-note').addEventListener('click', () => openNoteForm());

document.getElementById('note-cancel').addEventListener('click', closeNoteForm);

document.getElementById('note-save').addEventListener('click', async () => {
    const content = document.getElementById('note-input').value.trim();
    if (!content) return;

    if (editingNoteId) {
        await api(`/notes/update/${editingNoteId}`, { method: 'POST', body: JSON.stringify({ content }) }).catch(err => {
            toast(err.message, 'error');
        });
    } else {
        await api('/notes/store', { method: 'POST', body: JSON.stringify({ client_id: state.activeClientId, content }) }).catch(err => {
            toast(err.message, 'error');
        });
    }
    closeNoteForm();
    loadNotes(state.activeClientId);
});

document.getElementById('note-input').addEventListener('keydown', ev => {
    if (ev.key === 'Enter' && (ev.metaKey || ev.ctrlKey)) {
        document.getElementById('note-save').click();
    }
});

function editNote(id, notes) {
    const note = notes.find(n => n.id === id);
    if (note) openNoteForm(note.content, id);
}

async function deleteNote(id) {
    const ok = await modalConfirm('Opravdu smazat tuto poznámku?', {
        title: 'Smazat poznámku',
        okText: 'Smazat',
    });
    if (!ok) return;
    await api(`/notes/delete/${id}`, { method: 'POST' }).catch(err => toast(err.message, 'error'));
    loadNotes(state.activeClientId);
}

// ── Salon Settings ─────────────────────────────────────────────────────────────

async function loadSalonSettings() {
    const data = await api('/settings/get-salon').catch(() => ({}));
    document.getElementById('salon-name').value = data.salon_name || '';
    document.getElementById('salon-address').value = data.salon_address || '';
    document.getElementById('salon-phone').value = data.salon_phone || '';
    document.getElementById('salon-ico').value = data.salon_ico || '';
    document.getElementById('salon-note').value = data.salon_note || '';
}

document.getElementById('salon-settings-form').addEventListener('submit', async ev => {
    ev.preventDefault();
    const msgEl = document.getElementById('salon-msg');
    msgEl.hidden = true;
    msgEl.className = 'settings-msg';

    const phone = document.getElementById('salon-phone').value.trim();
    const ico = document.getElementById('salon-ico').value.trim();

    // Validate phone: allow digits, spaces, +, -, ()
    if (phone && !/^[+\d][\d\s\-()]{5,}$/.test(phone)) {
        msgEl.textContent = 'Neplatný formát telefonu (např. +420 123 456 789)';
        msgEl.classList.add('msg-error');
        msgEl.hidden = false;
        return;
    }
    // Validate IČO: 8 digits (Czech standard)
    if (ico && !/^\d{8}$/.test(ico)) {
        msgEl.textContent = 'IČO musí mít přesně 8 číslic';
        msgEl.classList.add('msg-error');
        msgEl.hidden = false;
        return;
    }

    try {
        const res = await fetch('/settings/save-salon', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                salon_name: document.getElementById('salon-name').value.trim(),
                salon_address: document.getElementById('salon-address').value.trim(),
                salon_phone: document.getElementById('salon-phone').value.trim(),
                salon_ico: document.getElementById('salon-ico').value.trim(),
                salon_note: document.getElementById('salon-note').value.trim(),
            })
        });
        const data = await res.json();
        if (!res.ok) {
            msgEl.textContent = data.error || 'Chyba';
            msgEl.classList.add('msg-error');
        } else {
            msgEl.textContent = 'Údaje uloženy';
            msgEl.classList.add('msg-success');
            cachedSalonInfo = null; // reset cache
        }
        msgEl.hidden = false;
    } catch {
        msgEl.textContent = 'Chyba připojení';
        msgEl.classList.add('msg-error');
        msgEl.hidden = false;
    }
});

// ── Dark / Light Theme ─────────────────────────────────────────────────────────

function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    document.getElementById('theme-icon-moon').hidden = theme === 'light';
    document.getElementById('theme-icon-sun').hidden = theme !== 'light';
    localStorage.setItem('aura-theme', theme);
}

// Init theme from localStorage
(function() {
    const saved = localStorage.getItem('aura-theme') || 'dark';
    applyTheme(saved);
})();

document.getElementById('btn-theme-toggle').addEventListener('click', () => {
    const current = document.documentElement.getAttribute('data-theme') || 'dark';
    applyTheme(current === 'dark' ? 'light' : 'dark');
});

// ── Code list cache for editor ────────────────────────────────────────────────

async function refreshCodeListCache() {
    const all = await api('/codelists/index').catch(() => []);
    codeListCache.service = [];
    codeListCache.ratio = [];
    codeListCache.bowl = [];
    codeListCache.material = [];
    all.forEach(item => {
        if (codeListCache[item.type]) codeListCache[item.type].push(item);
    });
}

// Initial load
refreshCodeListCache();
renderClientList();
loadDashboard();

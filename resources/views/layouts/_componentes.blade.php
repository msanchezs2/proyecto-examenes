<style nonce="{{ Vite::cspNonce() }}">
    body {
        font-family: -apple-system, "Segoe UI", system-ui, sans-serif;
        color: var(--ink);
        background: var(--bg);
    }

    a { color: var(--accent); }

    .boton, button[type="submit"] {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font: inherit;
        font-weight: 600;
        font-size: 14px;
        padding: 10px 18px;
        border-radius: var(--radius-sm);
        border: 1px solid transparent;
        background: var(--accent);
        color: var(--accent-contrast);
        text-decoration: none;
        cursor: pointer;
        transition: background .15s ease, box-shadow .15s ease, transform .05s ease;
        box-shadow: var(--shadow-sm);
    }
    .boton:hover, button[type="submit"]:hover { background: var(--accent-strong); }
    .boton:active, button[type="submit"]:active { transform: translateY(1px); }
    .boton.secundario {
        background: var(--surface);
        color: var(--ink);
        border-color: var(--border-strong);
        box-shadow: none;
    }
    .boton.secundario:hover { background: var(--surface-alt); border-color: var(--muted-soft); }
    .boton:disabled, button[type="submit"]:disabled { opacity: .55; cursor: not-allowed; }

    a:focus-visible, button:focus-visible, input:focus-visible, textarea:focus-visible, select:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px var(--accent-soft), 0 0 0 1px var(--accent);
        border-radius: var(--radius-sm);
    }

    .aviso {
        background: var(--success-soft);
        border: 1px solid var(--success-border);
        color: var(--success);
        padding: 11px 15px;
        border-radius: var(--radius-sm);
        margin-bottom: 18px;
        font-size: 14px;
    }
    .errores {
        background: var(--danger-soft);
        border: 1px solid var(--danger-border);
        color: var(--danger);
        padding: 11px 15px;
        border-radius: var(--radius-sm);
        margin-bottom: 18px;
        font-size: 14px;
    }
    .errores ul { margin: 0; padding-left: 18px; }

    label { color: var(--ink); }
    input[type="text"],
    input[type="number"],
    input[type="email"],
    input[type="password"],
    input[type="datetime-local"],
    select,
    textarea {
        display: block;
        width: 100%;
        font: inherit;
        font-weight: 400;
        margin-top: 6px;
        padding: 10px 12px;
        border: 1px solid var(--border-strong);
        border-radius: var(--radius-sm);
        background: var(--surface);
        color: var(--ink);
        transition: border-color .15s, box-shadow .15s;
    }
    input:focus, textarea:focus, select:focus { border-color: var(--accent); }

    .card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 18px 20px;
        margin-bottom: 14px;
        box-shadow: var(--shadow-sm);
    }
</style>

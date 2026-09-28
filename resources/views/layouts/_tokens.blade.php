<style nonce="{{ Vite::cspNonce() }}">
    :root {
        color-scheme: light;

        --bg: #eef1f7;
        --surface: #ffffff;
        --surface-alt: #f8f9fc;
        --border: #e1e5ee;
        --border-strong: #cbd2e1;

        --ink: #161b28;
        --muted: #667085;
        --muted-soft: #98a2b3;

        --accent: #4152d6;
        --accent-strong: #323fb0;
        --accent-soft: #eef0fd;
        --accent-contrast: #ffffff;

        --success: #1a8a52;
        --success-soft: #e6f7ee;
        --success-border: #a9dfc2;

        --danger: #c0293e;
        --danger-soft: #fdecee;
        --danger-border: #f3b7bf;

        --warning: #b56a12;
        --warning-soft: #fdf1e0;
        --warning-border: #f0cf9c;

        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 18px;

        --shadow-sm: 0 1px 2px rgba(22, 27, 40, .06);
        --shadow-md: 0 10px 30px rgba(22, 27, 40, .10);
    }

    /* sigue la preferencia del sistema operativo, salvo que el usuario haya elegido explícitamente */
    @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]) {
            color-scheme: dark;

            --bg: #0e1015;
            --surface: #171a21;
            --surface-alt: #1d2129;
            --border: #2a2f3a;
            --border-strong: #3a4150;

            --ink: #e7e9ee;
            --muted: #9aa3b2;
            --muted-soft: #6b7280;

            --accent: #7c8cf0;
            --accent-strong: #97a4f4;
            --accent-soft: #232a4d;
            --accent-contrast: #10131a;

            --success: #4ade80;
            --success-soft: #133023;
            --success-border: #1f5c3f;

            --danger: #f87171;
            --danger-soft: #3a1720;
            --danger-border: #6b2531;

            --warning: #fbbf60;
            --warning-soft: #3a2a10;
            --warning-border: #6b4a1c;

            --shadow-sm: 0 1px 2px rgba(0, 0, 0, .4);
            --shadow-md: 0 10px 30px rgba(0, 0, 0, .5);
        }
    }

    /* elección explícita del usuario (botón de tema), gana en cualquier sentido */
    :root[data-theme="dark"] {
        color-scheme: dark;

        --bg: #0e1015;
        --surface: #171a21;
        --surface-alt: #1d2129;
        --border: #2a2f3a;
        --border-strong: #3a4150;

        --ink: #e7e9ee;
        --muted: #9aa3b2;
        --muted-soft: #6b7280;

        --accent: #7c8cf0;
        --accent-strong: #97a4f4;
        --accent-soft: #232a4d;
        --accent-contrast: #10131a;

        --success: #4ade80;
        --success-soft: #133023;
        --success-border: #1f5c3f;

        --danger: #f87171;
        --danger-soft: #3a1720;
        --danger-border: #6b2531;

        --warning: #fbbf60;
        --warning-soft: #3a2a10;
        --warning-border: #6b4a1c;

        --shadow-sm: 0 1px 2px rgba(0, 0, 0, .4);
        --shadow-md: 0 10px 30px rgba(0, 0, 0, .5);
    }

    * { box-sizing: border-box; }
</style>
<script nonce="{{ Vite::cspNonce() }}">
    // aplica el tema guardado ANTES de pintar la página, para que no haya parpadeo
    (function () {
        try {
            var guardado = localStorage.getItem('tema');
            if (guardado === 'light' || guardado === 'dark') {
                document.documentElement.setAttribute('data-theme', guardado);
            }
        } catch (e) {}
    })();
</script>

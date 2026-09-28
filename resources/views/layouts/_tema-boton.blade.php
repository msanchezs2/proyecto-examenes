<button type="button" id="boton-tema" class="boton secundario" aria-label="Cambiar a modo oscuro o claro">Oscuro</button>

<script nonce="{{ Vite::cspNonce() }}">
    (function () {
        var boton = document.getElementById('boton-tema');
        if (!boton) return;

        function temaEfectivo() {
            var explicito = document.documentElement.getAttribute('data-theme');
            if (explicito) return explicito;
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        function actualizarTexto() {
            boton.textContent = temaEfectivo() === 'dark' ? 'Modo claro' : 'Modo oscuro';
        }

        actualizarTexto();

        boton.addEventListener('click', function () {
            var nuevo = temaEfectivo() === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', nuevo);
            try { localStorage.setItem('tema', nuevo); } catch (e) {}
            actualizarTexto();
        });
    })();
</script>

<?php if(!defined('access') or !access) die(); ?>
<section class="panic-community" id="comunidad" aria-labelledby="community-title">
    <div class="shell community-layout">
        <div class="community-copy">
            <span class="eyebrow">COMUNIDAD / DISCORD DE MU PANIC</span>
            <h2 id="community-title">Jugá<br><em>acompañado.</em></h2>
            <p>Encontrá party, enterate de los eventos y pedí ayuda en nuestro Discord. Tu próxima aventura puede empezar con una charla.</p>
            <a class="button primary community-join" href="<?php echo htmlspecialchars($discordInvite, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Entrar al Discord <span aria-hidden="true">↗</span></a>
            <small>Se abre en una nueva pestaña. Podés usar Discord desde el navegador o su aplicación.</small>
        </div>
        <div class="community-features">
            <article><span class="community-icon" aria-hidden="true"><?php echo mupanicGlyph('party'); ?></span><div><h3>Buscá tu próxima party</h3><p>Conocé otros jugadores y organizá tu grupo para entrenar o enfrentar un boss.</p></div></article>
            <article><span class="community-icon" aria-hidden="true"><?php echo mupanicGlyph('gem'); ?></span><div><h3>Novedades y eventos</h3><p>Seguí los anuncios del servidor y enterate de lo que se viene.</p></div></article>
            <article><span class="community-icon" aria-hidden="true"><?php echo mupanicGlyph('wings'); ?></span><div><h3>Ayuda y soporte</h3><p>Consultá tus dudas y usá el sistema de tickets cuando necesites asistencia.</p></div></article>
        </div>
    </div>
</section>

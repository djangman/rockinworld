<?php ?>

<div class="accordion">

    <button class="accordion__trigger" aria-expanded="false" aria-controls="amazon-panel" id="amazon-trigger">
        <h2>Amazon $35 Shopping List</h2>
        <span class="accordion__icon" aria-hidden="true">
            <svg viewBox="0 0 16 16"><polyline points="2 5 8 11 14 5"/></svg>
        </span>
    </button>

    <div class="accordion__panel" id="amazon-panel" role="region" aria-labelledby="amazon-trigger">
        <div class="accordion__panel-inner">
            <ul class="amazon-list" id="amazon-list">
                <li class="loading">Loading…</li>
            </ul>
        </div>
    </div>

</div>

<script>
/* Accordion toggle — own behaviour, not part of EditableList */
(function () {
    const trigger = document.getElementById('amazon-trigger');
    const panel   = document.getElementById('amazon-panel');
    trigger.addEventListener('click', () => {
        const expanded = trigger.getAttribute('aria-expanded') === 'true';
        trigger.setAttribute('aria-expanded', String(!expanded));
        panel.classList.toggle('is-open', !expanded);
    });
})();
/* EditableList for books is initialised in index.html */
</script>
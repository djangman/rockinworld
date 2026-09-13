<?php ?>

<div class="accordion">

    <button class="accordion__trigger" aria-expanded="false" aria-controls="book-panel" id="book-trigger">
        <h2>Reading List</h2>
        <span class="accordion__icon" aria-hidden="true">
            <svg viewBox="0 0 16 16"><polyline points="2 5 8 11 14 5"/></svg>
        </span>
    </button>

    <div class="accordion__panel" id="book-panel" role="region" aria-labelledby="book-trigger">
        <div class="accordion__panel-inner">
            <ul class="book-list" id="book-list">
                <li class="loading">Loading…</li>
            </ul>
        </div>
    </div>

</div>

<script>
/* Accordion toggle — own behaviour, not part of EditableList */
(function () {
    const trigger = document.getElementById('book-trigger');
    const panel   = document.getElementById('book-panel');
    trigger.addEventListener('click', () => {
        const expanded = trigger.getAttribute('aria-expanded') === 'true';
        trigger.setAttribute('aria-expanded', String(!expanded));
        panel.classList.toggle('is-open', !expanded);
    });
})();
/* EditableList for books is initialised in index.html */
</script>
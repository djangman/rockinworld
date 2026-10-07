<?php
/**
 * todos.php - to-do list UI. Designed to be pulled into another page:
 *
 *     include("todos.php");
 *
 * It outputs an HTML fragment only (no <html>/<body>), with all CSS scoped to #td-app.
 * The ajax endpoint is relative to the *including* page by default. If backend.php lives
 * elsewhere, set this before the include:
 *
 *     $todos_backend_url = '/path/to/backend.php';
 *     include("todos.php");
 */
$todos_backend_url = $todos_backend_url ?? 'embed_todoapp/backend.php';
$todos_when_options = [
    'Today', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
    'Next Monday', 'Next Tuesday', 'Next Wednesday', 'Next Thursday',
    'Next Friday', 'Next Saturday', 'Next Sunday',
    'Next Weekend', 'Next Month',
];
// type value => section heading
$todos_types = [
    'normal'    => 'Normal',
    'errand'    => 'Errands',
    'repeating' => 'Repeating',
    'future'    => 'Future Tasks',
];

?>
<style>
#td-app {
    zoom: 0.75; /* Scales everything down to 80% of its original size */
}
#td-app { --td-accent:#3b6ef5; --td-border:#e3e6ec; --td-muted:#7a8394; --td-bg:#fff; --td-hover:#f6f8fc;
  max-width:800px; margin:1rem auto; font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; color:#1f2430; }
#td-app * { box-sizing:border-box; }
#td-app [hidden], #td-app .td-hide { display:none !important; }
#td-app h2 { margin:0 0 .75rem; font-size:1.3rem; }
#td-app .td-add { display:flex; gap:.5rem; margin-bottom:1rem; flex-wrap:wrap; }
#td-app .td-add input[type=text] { flex:1 1 220px; }
#td-app input[type=text], #td-app select { padding:.5rem .6rem; border:1px solid var(--td-border); border-radius:8px; font:inherit; background:var(--td-bg); }
#td-app input[type=text]:focus, #td-app select:focus { outline:2px solid #c9d6fb; border-color:var(--td-accent); }
#td-app button { font:inherit; cursor:pointer; border:0; border-radius:8px; padding:.5rem .9rem; }
#td-app .td-btn { background:var(--td-accent); color:#fff; font-weight:600; }
#td-app .td-btn:disabled { opacity:.6; cursor:wait; }
#td-app .td-loading { text-align:center; color:var(--td-muted); padding:1.5rem; border:1px solid var(--td-border); border-radius:10px; }
#td-app table { width:100%; border-collapse:separate; border-spacing:0; border:1px solid var(--td-border); border-radius:10px; overflow:hidden; }
#td-app thead th { text-align:left; font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; color:var(--td-muted); background:#fafbfd; padding:.55rem .6rem; border-bottom:1px solid var(--td-border); }
#td-app td { padding:.4rem .6rem; border-bottom:1px solid var(--td-border); vertical-align:middle; background:var(--td-bg); }
#td-app tbody:last-of-type tr:last-child td { border-bottom:0; }
#td-app tbody tr.td-row:hover td { background:var(--td-hover); }

/* section headers */
#td-app .td-sec-head th { --sec:#3b6ef5; text-align:left; padding:.7rem .8rem; font-size:.95rem; font-weight:700; letter-spacing:.02em;
  background:linear-gradient(90deg,color-mix(in srgb,var(--sec) 14%,#fff),#fff 70%); border-top:1px solid var(--td-border); border-bottom:1px solid var(--td-border); border-left:5px solid var(--sec); }
#td-app tbody[data-type=repeating] .td-sec-head th { --sec:#12a37f; }
#td-app tbody[data-type=future] .td-sec-head th { --sec:#8a5cf6; }
#td-app .td-sec-count { display:inline-block; margin-left:.5rem; min-width:1.5rem; padding:0 .45rem; border-radius:999px; background:var(--sec,#3b6ef5); color:#fff; font-size:.75rem; line-height:1.4rem; text-align:center; vertical-align:middle; }
#td-app .td-sec-empty td { text-align:center; color:var(--td-muted); font-style:italic; font-size:.9rem; padding:.9rem; }

#td-app .td-check { width:44px; text-align:center; }
#td-app .td-check input { width:18px; height:18px; cursor:pointer; accent-color:var(--td-accent); }
#td-app .td-name input { width:100%; border-color:transparent; background:transparent; }
#td-app .td-name input:hover { border-color:var(--td-border); }
#td-app tr.td-done .td-name input { text-decoration:line-through; color:var(--td-muted); }
#td-app .td-when select, #td-app .td-type select { width:100%; }
#td-app .td-type { width:130px; }
#td-app .td-del { width:40px; text-align:center; }
#td-app .td-del button { background:transparent; color:#b4bbc9; padding:.2rem .5rem; font-size:1rem; }
#td-app .td-del button:hover { color:#d64545; background:#fdeeee; }

/* error banner */
#td-app .td-error { display:flex; gap:.8rem; align-items:flex-start; margin:0 0 1rem; padding:.9rem 1rem; border-radius:12px;
  background:linear-gradient(135deg,#fff5f5,#ffeaea); border:1px solid #f5b8b8; border-left:5px solid #e04848;
  box-shadow:0 4px 14px rgba(224,72,72,.12); animation:td-in .2s ease-out; }
#td-app .td-error.td-warn { background:linear-gradient(135deg,#fffaf0,#fff3dc); border-color:#f3d79a; border-left-color:#e8a21b; box-shadow:0 4px 14px rgba(232,162,27,.12); }
#td-app .td-error-icon { font-size:1.4rem; line-height:1.2; }
#td-app .td-error-body { flex:1; }
#td-app .td-error-title { font-weight:700; margin-bottom:.15rem; }
#td-app .td-error-msg { font-size:.92rem; }
#td-app .td-error-hint { font-size:.84rem; color:var(--td-muted); margin-top:.35rem; }
#td-app .td-error-actions { display:flex; gap:.4rem; align-items:center; }
#td-app .td-error-actions button { background:#fff; border:1px solid #e3b4b4; color:#a33; padding:.3rem .7rem; font-size:.85rem; }
#td-app .td-error-actions .td-x { border:0; background:transparent; font-size:1.2rem; color:#a77; padding:.1rem .4rem; }

/* custom confirm dialog */
#td-app .td-modal-overlay { position:fixed; inset:0; z-index:99999; display:flex; align-items:center; justify-content:center; padding:1rem;
  background:rgba(20,26,40,.5); backdrop-filter:blur(2px); animation:td-fade .15s ease-out; }
#td-app .td-modal-overlay.td-out { opacity:0; transition:opacity .15s; }
#td-app .td-modal { width:100%; max-width:380px; background:#fff; border-radius:16px; padding:1.5rem 1.4rem 1.2rem; text-align:center;
  box-shadow:0 20px 50px rgba(20,26,40,.35); animation:td-pop .18s ease-out; }
#td-app .td-modal-icon { width:56px; height:56px; margin:0 auto .8rem; border-radius:50%; display:flex; align-items:center; justify-content:center;
  font-size:1.6rem; background:#fdeeee; }
#td-app .td-modal-title { font-size:1.15rem; font-weight:700; margin-bottom:.4rem; }
#td-app .td-modal-name { display:inline-block; max-width:100%; margin:.2rem 0 .4rem; padding:.25rem .6rem; border-radius:8px; background:#f2f4f9; font-weight:600;
  overflow:hidden; text-overflow:ellipsis; white-space:nowrap; vertical-align:bottom; }
#td-app .td-modal-msg { color:var(--td-muted); font-size:.92rem; }
#td-app .td-modal-actions { display:flex; gap:.6rem; margin-top:1.2rem; }
#td-app .td-modal-actions button { flex:1; padding:.65rem; font-weight:600; }
#td-app .td-modal-cancel { background:#eef1f7; color:#3a4357; }
#td-app .td-modal-cancel:hover { background:#e2e7f1; }
#td-app .td-modal-ok { background:#e04848; color:#fff; }
#td-app .td-modal-ok:hover { background:#c93a3a; }
#td-app .td-modal-actions button:focus-visible { outline:3px solid #c9d6fb; outline-offset:2px; }

@keyframes td-in { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }
@keyframes td-fade { from { opacity:0; } to { opacity:1; } }
@keyframes td-pop { from { opacity:0; transform:scale(.94) translateY(8px); } to { opacity:1; transform:none; } }
</style>

<div id="td-app" data-backend="<?= htmlspecialchars($todos_backend_url, ENT_QUOTES) ?>">
  <h2>To-Do List</h2>
  <div id="td-errors" aria-live="assertive"></div>

  <div class="td-add">
    <input type="text" id="td-new-name" placeholder="What needs doing?" maxlength="255" autocomplete="off">
    <select id="td-new-type" title="Type">
      <?php foreach ($todos_types as $val => $label): ?>
        <option value="<?= htmlspecialchars($val, ENT_QUOTES) ?>"><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>
    <select id="td-new-when" title="When">
      <option value="">— No day —</option>
      <?php foreach ($todos_when_options as $opt): ?>
        <option value="<?= htmlspecialchars($opt, ENT_QUOTES) ?>"><?= htmlspecialchars($opt) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" class="td-btn" id="td-add-btn">Add</button>
  </div>

  <div class="td-loading" id="td-loading">Loading…</div>

  <table id="td-table" hidden>
    <thead>
      <tr><th>Done</th><th>To-do</th><th>Type</th><th>When</th><th></th></tr>
    </thead>
    <?php foreach ($todos_types as $val => $label): ?>
    <tbody class="td-section" id="td-sec-<?= htmlspecialchars($val, ENT_QUOTES) ?>" data-type="<?= htmlspecialchars($val, ENT_QUOTES) ?>">
      <tr class="td-sec-head"><th colspan="5"><span class="td-sec-title"><?= htmlspecialchars($label) ?></span><span class="td-sec-count">0</span></th></tr>
      <tr class="td-sec-empty"><td colspan="5">Nothing here yet. Add one above.</td></tr>
    </tbody>
    <?php endforeach; ?>
  </table>
</div>

<script>
(function () {
  var root = document.getElementById('td-app');
  var BACKEND = root.getAttribute('data-backend');
  var WHEN = <?= json_encode($todos_when_options) ?>;
  var TYPES = <?= json_encode(array_keys($todos_types)) ?>;
  var TYPE_LABELS = <?= json_encode($todos_types) ?>;
  var table = document.getElementById('td-table');
  var loadingBox = document.getElementById('td-loading');
  var errBox = document.getElementById('td-errors');
  var newName = document.getElementById('td-new-name');
  var newType = document.getElementById('td-new-type');
  var newWhen = document.getElementById('td-new-when');
  var addBtn = document.getElementById('td-add-btn');
  var secs = {};
  TYPES.forEach(function (t) { secs[t] = document.getElementById('td-sec-' + t); });
  var todos = [];

  /* ---------- sort order: items are sorted by their "when" value ---------- */
  var todos_when_options_sort_order = {
    'Today':         -1,
    'Monday':         0,
    'Tuesday':        1,
    'Wednesday':      2,
    'Thursday':       3,
    'Friday':         4,
    'Saturday':       5,
    'Sunday':         6,
    'Next Monday':    7,
    'Next Tuesday':   8,
    'Next Wednesday': 9,
    'Next Thursday':  10,
    'Next Friday':    11,
    'Next Saturday':  12,
    'Next Sunday':    13,
    'Next Weekend':   14,
    'Next Month':     15,
    '— No day —':     16,
  };
  var NO_DAY_RANK = 99;   // items with no "when" sort last

  function whenRank(w) {
    return Object.prototype.hasOwnProperty.call(todos_when_options_sort_order, w)
      ? todos_when_options_sort_order[w] : NO_DAY_RANK;
  }
  // by "when", then by id (creation order) so ties stay stable
  function sortTodos() {
    todos.sort(function (a, b) { return (whenRank(a.todo_when) - whenRank(b.todo_when)) || (a.id - b.id); });
  }

  /* ---------- ajax helper (no page loads, ever) ---------- */
  function api(action, data) {
    var opts, url = BACKEND;
    if (action === 'list') {
      url += (BACKEND.indexOf('?') > -1 ? '&' : '?') + 'action=list&_=' + Date.now();
      opts = { headers: { 'Accept': 'application/json' } };
    } else {
      var p = new URLSearchParams(data || {});
      p.append('action', action);
      opts = { method: 'POST', body: p, headers: { 'Accept': 'application/json' } };
    }
    return fetch(url, opts).then(function (res) {
      return res.text().then(function (txt) {
        var json;
        try { json = JSON.parse(txt); } catch (e) {
          throw { type: 'server', title: 'Unexpected server response',
                  message: 'The server sent back something the to-do list could not understand.',
                  hint: 'Check that backend.php is reachable and has no syntax errors.' };
        }
        if (!json.ok) { throw json.error || { type: 'server', title: 'Something went wrong', message: 'The request failed.' }; }
        return json;
      });
    }, function () {
      throw { type: 'network', title: 'Connection problem',
              message: 'Could not reach the server. Your last change may not have been saved.',
              hint: 'Check your connection and try again.' };
    });
  }

  /* ---------- friendly error banner ---------- */
  function showError(err, retry) {
    err = err || {};
    var isDb = err.type === 'database';
    var box = document.createElement('div');
    box.className = 'td-error' + (isDb ? '' : ' td-warn');
    box.setAttribute('role', 'alert');

    var icon = document.createElement('div');
    icon.className = 'td-error-icon';
    icon.textContent = isDb ? '🛢️' : '⚠️';

    var main = document.createElement('div');
    main.className = 'td-error-body';
    var t = document.createElement('div'); t.className = 'td-error-title'; t.textContent = err.title || 'Something went wrong';
    var m = document.createElement('div'); m.className = 'td-error-msg'; m.textContent = err.message || '';
    main.appendChild(t); main.appendChild(m);
    if (err.hint) { var h = document.createElement('div'); h.className = 'td-error-hint'; h.textContent = err.hint; main.appendChild(h); }

    var act = document.createElement('div'); act.className = 'td-error-actions';
    if (retry) {
      var r = document.createElement('button'); r.type = 'button'; r.textContent = 'Retry';
      r.onclick = function () { box.remove(); retry(); };
      act.appendChild(r);
    }
    var x = document.createElement('button'); x.type = 'button'; x.className = 'td-x'; x.setAttribute('aria-label', 'Dismiss'); x.textContent = '×';
    x.onclick = function () { box.remove(); };
    act.appendChild(x);

    box.appendChild(icon); box.appendChild(main); box.appendChild(act);
    errBox.innerHTML = '';
    errBox.appendChild(box);
  }
  function clearErrors() { errBox.innerHTML = ''; }

  /* ---------- custom confirm dialog (replaces window.confirm) ---------- */
  function mk(tag, cls, text) {
    var e = document.createElement(tag);
    if (cls) { e.className = cls; }
    if (text !== undefined) { e.textContent = text; }
    return e;
  }

  function confirmDialog(opts) {
    return new Promise(function (resolve) {
      var prevFocus = document.activeElement;
      var overlay = mk('div', 'td-modal-overlay');
      var dlg = mk('div', 'td-modal');
      dlg.setAttribute('role', 'alertdialog');
      dlg.setAttribute('aria-modal', 'true');
      dlg.setAttribute('aria-labelledby', 'td-modal-title');

      var title = mk('div', 'td-modal-title', opts.title || 'Are you sure?');
      title.id = 'td-modal-title';
      dlg.appendChild(mk('div', 'td-modal-icon', opts.icon || '🗑️'));
      dlg.appendChild(title);
      if (opts.name) { dlg.appendChild(mk('div', 'td-modal-name', opts.name)); }
      if (opts.message) { dlg.appendChild(mk('div', 'td-modal-msg', opts.message)); }

      var actions = mk('div', 'td-modal-actions');
      var cancel = mk('button', 'td-modal-cancel', opts.cancelLabel || 'Cancel'); cancel.type = 'button';
      var ok = mk('button', 'td-modal-ok', opts.confirmLabel || 'Confirm'); ok.type = 'button';
      actions.appendChild(cancel); actions.appendChild(ok);
      dlg.appendChild(actions);
      overlay.appendChild(dlg);

      function close(result) {
        document.removeEventListener('keydown', onKey, true);
        overlay.classList.add('td-out');
        setTimeout(function () { overlay.remove(); }, 160);
        if (prevFocus && prevFocus.focus) { try { prevFocus.focus(); } catch (e) {} }
        resolve(result);
      }
      function onKey(e) {
        if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(false); }
        else if (e.key === 'Tab') {   // keep focus inside the dialog
          e.preventDefault();
          (document.activeElement === cancel ? ok : cancel).focus();
        }
      }
      cancel.addEventListener('click', function () { close(false); });
      ok.addEventListener('click', function () { close(true); });
      overlay.addEventListener('mousedown', function (e) { if (e.target === overlay) { close(false); } });
      document.addEventListener('keydown', onKey, true);

      root.appendChild(overlay);
      cancel.focus();   // safe default for a destructive action
    });
  }

  /* ---------- rendering ---------- */
  function buildRow(t) {
    var tr = document.createElement('tr');
    tr.className = 'td-row' + (t.done ? ' td-done' : '');
    tr.setAttribute('data-id', t.id);

    var c = document.createElement('td'); c.className = 'td-check';
    var cb = document.createElement('input'); cb.type = 'checkbox'; cb.checked = !!t.done;
    cb.addEventListener('change', function () {
      var want = cb.checked;
      api('toggle', { id: t.id, done: want ? 1 : 0 }).then(function () {
        t.done = want; tr.classList.toggle('td-done', want); clearErrors();
      }).catch(function (e) {
        cb.checked = !want; showError(e);
      });
    });
    c.appendChild(cb);

    var n = document.createElement('td'); n.className = 'td-name';
    var ni = document.createElement('input'); ni.type = 'text'; ni.value = t.todo_name; ni.maxLength = 255;
    ni.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { ni.blur(); }
      if (e.key === 'Escape') { ni.value = t.todo_name; ni.blur(); }
    });
    ni.addEventListener('change', function () {
      var v = ni.value.trim();
      if (!v) { ni.value = t.todo_name; return; }
      api('update', { id: t.id, todo_name: v }).then(function () {
        t.todo_name = v; ni.value = v; clearErrors();
      }).catch(function (e) { ni.value = t.todo_name; showError(e); });
    });
    n.appendChild(ni);

    var w = document.createElement('td'); w.className = 'td-when';
    var sel = document.createElement('select');
    [''].concat(WHEN).forEach(function (o) {
      var op = document.createElement('option'); op.value = o; op.textContent = o || '— No day —';
      if (o === t.todo_when) { op.selected = true; }
      sel.appendChild(op);
    });
    sel.addEventListener('change', function () {
      var v = sel.value;
      api('update', { id: t.id, todo_when: v }).then(function () {
        t.todo_when = v; clearErrors(); render();   // re-sort into place
      }).catch(function (e) { sel.value = t.todo_when; showError(e); });
    });
    w.appendChild(sel);

    var ty = document.createElement('td'); ty.className = 'td-type';
    var tsel = document.createElement('select');
    TYPES.forEach(function (k) {
      var op = document.createElement('option'); op.value = k; op.textContent = TYPE_LABELS[k];
      if (k === t.todo_type) { op.selected = true; }
      tsel.appendChild(op);
    });
    tsel.addEventListener('change', function () {
      var v = tsel.value;
      api('update', { id: t.id, todo_type: v }).then(function () {
        t.todo_type = v; clearErrors(); render();   // move to the matching section
      }).catch(function (e) { tsel.value = t.todo_type; showError(e); });
    });
    ty.appendChild(tsel);

    var d = document.createElement('td'); d.className = 'td-del';
    var db = document.createElement('button'); db.type = 'button'; db.title = 'Delete'; db.setAttribute('aria-label', 'Delete'); db.textContent = '✕';
    db.addEventListener('click', function () {
      confirmDialog({
        title: 'Delete this to-do?',
        name: t.todo_name,
        message: 'This can\'t be undone.',
        confirmLabel: 'Delete',
        cancelLabel: 'Keep it'
      }).then(function (yes) {
        if (!yes) { return; }
        api('delete', { id: t.id }).then(function () {
          todos = todos.filter(function (x) { return x.id !== t.id; });
          render(); clearErrors();
        }).catch(function (e) { showError(e); });
      });
    });
    d.appendChild(db);

    tr.appendChild(c); tr.appendChild(n); tr.appendChild(ty); tr.appendChild(w); tr.appendChild(d);

    return tr;
  }

  function refreshSections() {
    TYPES.forEach(function (type) {
      var sec = secs[type];
      var n = sec.querySelectorAll('tr.td-row').length;
      sec.querySelector('.td-sec-count').textContent = n;
      sec.querySelector('tr.td-sec-empty').classList.toggle('td-hide', n > 0);
    });
  }

  function render() {
    sortTodos();
    TYPES.forEach(function (type) {
      var sec = secs[type];
      Array.prototype.slice.call(sec.querySelectorAll('tr.td-row')).forEach(function (r) { r.remove(); });
      todos.forEach(function (t) { if (t.todo_type === type) { sec.appendChild(buildRow(t)); } });
    });
    refreshSections();
  }

  /* ---------- add ---------- */
  function addTodo() {
    var name = newName.value.trim();
    if (!name) { newName.focus(); return; }
    addBtn.disabled = true;
    api('add', { todo_name: name, todo_when: newWhen.value, todo_type: newType.value }).then(function (res) {
      todos.push(res.todo); render();
      newName.value = ''; newWhen.value = ''; newName.focus(); clearErrors();
    }).catch(function (e) { showError(e, addTodo); }).then(function () { addBtn.disabled = false; });
  }
  addBtn.addEventListener('click', addTodo);
  newName.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); addTodo(); } });

  /* ---------- initial load ---------- */
  function load() {
    loadingBox.textContent = 'Loading…';
    loadingBox.hidden = false;
    api('list').then(function (res) {
      todos = res.todos; render();
      loadingBox.hidden = true; table.hidden = false; clearErrors();
    }).catch(function (e) {
      loadingBox.textContent = 'Your list could not be loaded.';
      showError(e, load);
    });
  }
  load();
})();
</script>

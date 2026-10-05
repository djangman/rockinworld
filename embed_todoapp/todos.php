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
    'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
    'Next Monday', 'Next Tuesday', 'Next Wednesday', 'Next Thursday',
    'Next Friday', 'Next Saturday', 'Next Sunday',
    'Next Weekend', 'Next Month',
];
?>
<style>
#td-app { --td-accent:#3b6ef5; --td-border:#e3e6ec; --td-muted:#7a8394; --td-bg:#fff; --td-hover:#f6f8fc;
  max-width:760px; margin:1rem auto; font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; color:#1f2430; }
#td-app * { box-sizing:border-box; }
#td-app h2 { margin:0 0 .75rem; font-size:1.3rem; }
#td-app .td-add { display:flex; gap:.5rem; margin-bottom:1rem; flex-wrap:wrap; }
#td-app .td-add input[type=text] { flex:1 1 220px; }
#td-app input[type=text], #td-app select { padding:.5rem .6rem; border:1px solid var(--td-border); border-radius:8px; font:inherit; background:var(--td-bg); }
#td-app input[type=text]:focus, #td-app select:focus { outline:2px solid #c9d6fb; border-color:var(--td-accent); }
#td-app button { font:inherit; cursor:pointer; border:0; border-radius:8px; padding:.5rem .9rem; }
#td-app .td-btn { background:var(--td-accent); color:#fff; font-weight:600; }
#td-app .td-btn:disabled { opacity:.6; cursor:wait; }
#td-app table { width:100%; border-collapse:separate; border-spacing:0; border:1px solid var(--td-border); border-radius:10px; overflow:hidden; }
#td-app th { text-align:left; font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; color:var(--td-muted); background:#fafbfd; padding:.55rem .6rem; border-bottom:1px solid var(--td-border); }
#td-app td { padding:.4rem .6rem; border-bottom:1px solid var(--td-border); vertical-align:middle; background:var(--td-bg); }
#td-app tr:last-child td { border-bottom:0; }
#td-app tbody tr:hover td { background:var(--td-hover); }
#td-app .td-handle { width:28px; text-align:center; color:#a3abba; cursor:grab; user-select:none; font-size:1.1rem; }
#td-app .td-check { width:44px; text-align:center; }
#td-app .td-check input { width:18px; height:18px; cursor:pointer; accent-color:var(--td-accent); }
#td-app .td-name input { width:100%; border-color:transparent; background:transparent; }
#td-app .td-name input:hover { border-color:var(--td-border); }
#td-app tr.td-done .td-name input { text-decoration:line-through; color:var(--td-muted); }
#td-app .td-when select { width:100%; }
#td-app .td-del { width:40px; text-align:center; }
#td-app .td-del button { background:transparent; color:#b4bbc9; padding:.2rem .5rem; font-size:1rem; }
#td-app .td-del button:hover { color:#d64545; background:#fdeeee; }
#td-app tr.td-dragging td { opacity:.4; background:#eaf0ff; }
#td-app .td-empty { text-align:center; color:var(--td-muted); padding:1.5rem !important; }
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
@keyframes td-in { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }
</style>

<div id="td-app" data-backend="<?= htmlspecialchars($todos_backend_url, ENT_QUOTES) ?>">
  <h2>To-Do List</h2>
  <div id="td-errors" aria-live="assertive"></div>

  <div class="td-add">
    <input type="text" id="td-new-name" placeholder="What needs doing?" maxlength="255" autocomplete="off">
    <select id="td-new-when">
      <option value="">— No day —</option>
      <?php foreach ($todos_when_options as $opt): ?>
        <option value="<?= htmlspecialchars($opt, ENT_QUOTES) ?>"><?= htmlspecialchars($opt) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" class="td-btn" id="td-add-btn">Add</button>
  </div>

  <table>
    <thead>
      <tr><th></th><th>Done</th><th>To-do</th><th>When</th><th></th></tr>
    </thead>
    <tbody id="td-body">
      <tr><td colspan="5" class="td-empty">Loading…</td></tr>
    </tbody>
  </table>
</div>

<script>
(function () {
  var root = document.getElementById('td-app');
  var BACKEND = root.getAttribute('data-backend');
  var WHEN = <?= json_encode($todos_when_options) ?>;
  var body = document.getElementById('td-body');
  var errBox = document.getElementById('td-errors');
  var newName = document.getElementById('td-new-name');
  var newWhen = document.getElementById('td-new-when');
  var addBtn = document.getElementById('td-add-btn');
  var todos = [];

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

  /* ---------- rendering ---------- */
  function buildRow(t) {
    var tr = document.createElement('tr');
    tr.setAttribute('data-id', t.id);
    if (t.done) { tr.className = 'td-done'; }

    var h = document.createElement('td'); h.className = 'td-handle'; h.title = 'Drag to reorder'; h.textContent = '⋮⋮';
    h.addEventListener('mousedown', function () { tr.draggable = true; });
    h.addEventListener('mouseup', function () { tr.draggable = false; });

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
        t.todo_when = v; clearErrors();
      }).catch(function (e) { sel.value = t.todo_when; showError(e); });
    });
    w.appendChild(sel);

    var d = document.createElement('td'); d.className = 'td-del';
    var db = document.createElement('button'); db.type = 'button'; db.title = 'Delete'; db.textContent = '✕';
    db.addEventListener('click', function () {
      if (!confirm('Delete "' + t.todo_name + '"?')) { return; }
      api('delete', { id: t.id }).then(function () {
        todos = todos.filter(function (x) { return x.id !== t.id; });
        render(); clearErrors();
      }).catch(function (e) { showError(e); });
    });
    d.appendChild(db);

    tr.appendChild(h); tr.appendChild(c); tr.appendChild(n); tr.appendChild(w); tr.appendChild(d);

    /* drag events */
    tr.addEventListener('dragstart', function (e) {
      dragRow = tr; startOrder = currentOrder();
      tr.classList.add('td-dragging');
      e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain', String(t.id)); } catch (x) {}
    });
    tr.addEventListener('dragend', function () {
      tr.classList.remove('td-dragging'); tr.draggable = false;
      var after = currentOrder();
      dragRow = null;
      if (after.join(',') !== startOrder.join(',')) { saveOrder(after); }
    });
    return tr;
  }

  function render() {
    body.innerHTML = '';
    if (!todos.length) {
      var tr = document.createElement('tr'); var td = document.createElement('td');
      td.colSpan = 5; td.className = 'td-empty'; td.textContent = 'Nothing to do. Add your first item above.';
      tr.appendChild(td); body.appendChild(tr); return;
    }
    todos.forEach(function (t) { body.appendChild(buildRow(t)); });
  }

  /* ---------- drag-and-drop reordering (native HTML5, no library) ---------- */
  var dragRow = null, startOrder = [];

  function currentOrder() {
    return Array.prototype.map.call(body.querySelectorAll('tr[data-id]'), function (r) { return r.getAttribute('data-id'); });
  }

  body.addEventListener('dragover', function (e) {
    if (!dragRow) { return; }
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    var rows = Array.prototype.filter.call(body.querySelectorAll('tr[data-id]'), function (r) { return r !== dragRow; });
    var target = null;
    for (var i = 0; i < rows.length; i++) {
      var box = rows[i].getBoundingClientRect();
      if (e.clientY < box.top + box.height / 2) { target = rows[i]; break; }
    }
    if (target) { body.insertBefore(dragRow, target); } else { body.appendChild(dragRow); }
  });
  body.addEventListener('drop', function (e) { e.preventDefault(); });

  function saveOrder(ids) {
    var map = {};
    todos.forEach(function (t) { map[t.id] = t; });
    var prev = todos.slice();
    todos = ids.map(function (id) { return map[id]; });
    api('reorder', { ids: ids.join(',') }).then(clearErrors).catch(function (e) {
      todos = prev; render(); showError(e);
    });
  }

  /* ---------- add ---------- */
  function addTodo() {
    var name = newName.value.trim();
    if (!name) { newName.focus(); return; }
    addBtn.disabled = true;
    api('add', { todo_name: name, todo_when: newWhen.value }).then(function (res) {
      todos.push(res.todo); render();
      newName.value = ''; newWhen.value = ''; newName.focus(); clearErrors();
    }).catch(function (e) { showError(e, addTodo); }).then(function () { addBtn.disabled = false; });
  }
  addBtn.addEventListener('click', addTodo);
  newName.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); addTodo(); } });

  /* ---------- initial load ---------- */
  function load() {
    api('list').then(function (res) { todos = res.todos; render(); clearErrors(); })
      .catch(function (e) {
        body.innerHTML = '';
        var tr = document.createElement('tr'); var td = document.createElement('td');
        td.colSpan = 5; td.className = 'td-empty'; td.textContent = 'Your list could not be loaded.';
        tr.appendChild(td); body.appendChild(tr);
        showError(e, load);
      });
  }
  load();
})();
</script>

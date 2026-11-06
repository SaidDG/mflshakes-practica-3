/* Carrito de M*** Shakes: se guarda en el navegador y la compra se registra en comprar.php */
(function () {
  var KEY = 'mshakes_carrito';
  var items = [];
  try { items = JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { items = []; }

  var st = document.createElement('style');
  st.textContent = `
  #dlgCarrito{margin:auto;border:0;padding:0;width:min(560px,94vw);max-height:92vh;overflow:auto;font-family:Helvetica,Arial,sans-serif;font-size:14px;color:#111}
  #dlgCarrito::backdrop{background:rgba(0,0,0,.6)}
  .car{padding:28px}
  .car-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px}
  .car h2{font-size:26px;letter-spacing:-1px}
  .car .cerrar{background:none;border:0;font-size:26px;cursor:pointer;line-height:1}
  .car-vacio{color:#777;padding:18px 0}
  .car-item{display:grid;grid-template-columns:1fr auto;gap:6px 12px;padding:14px 0;border-bottom:1px solid #e5e5e5}
  .car-item b{text-transform:uppercase;font-size:13px;font-weight:normal}
  .car-item small{color:#777;display:block;margin-top:3px}
  .car-cant{display:flex;align-items:center;gap:8px}
  .car-cant button{width:26px;height:26px;border:1px solid #111;background:#fff;cursor:pointer}
  .car-item .quitar{background:none;border:0;text-decoration:underline;cursor:pointer;color:#777;font-size:12px}
  .car-total{display:flex;justify-content:space-between;font-size:18px;padding:18px 0}
  .car label{display:block;margin-bottom:14px;text-transform:uppercase;font-size:12px;letter-spacing:.5px}
  .car input,.car select{display:block;width:100%;margin-top:6px;padding:11px;border:1px solid #111;font-size:14px;background:#fff}
  .car .comprar{width:100%;background:#111;color:#fff;border:0;padding:14px;font-size:14px;text-transform:uppercase;cursor:pointer}
  .car .comprar:hover{background:#dc0a17}
  .car .comprar:disabled{opacity:.5;cursor:wait}
  .car-msg{display:none;padding:12px 14px;margin-bottom:16px;border-left:4px solid}
  .car-msg.ok{background:#eaf7ee;border-color:#1f8a43}
  .car-msg.error{background:#fdeaea;border-color:#dc0a17}`;
  document.head.appendChild(st);

  var w = document.createElement('div');
  w.innerHTML = `<dialog id="dlgCarrito"><div class="car">
    <div class="car-top"><h2>Tu carrito</h2><button type="button" class="cerrar" id="cerrarCarrito" aria-label="Cerrar">&times;</button></div>
    <div class="car-msg" id="carMsg" role="alert"></div>
    <div id="carLista"></div>
    <div id="carCompra">
      <div class="car-total"><span>Total</span><strong id="carTotal">$ 0.00</strong></div>
      <label>Correo de tu cuenta <input type="email" id="carEmail" placeholder="El que usaste al registrarte"></label>
      <label>Método de pago <select id="carPago"><option value="tarjeta">Tarjeta</option><option value="paypal">PayPal</option><option value="efectivo">Efectivo</option></select></label>
      <button type="button" class="comprar" id="carComprar">Comprar</button>
    </div></div></dialog>`;
  document.body.appendChild(w.firstChild);

  var dlg = document.getElementById('dlgCarrito');
  var lista = document.getElementById('carLista');
  var msg = document.getElementById('carMsg');
  var email = document.getElementById('carEmail');
  try { email.value = localStorage.getItem('mshakes_email') || ''; } catch (e) {}

  function money(n) { return '$ ' + n.toFixed(2); }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return '&#' + c.charCodeAt(0) + ';'; }); }
  function mostrar(tipo, texto) { msg.className = 'car-msg ' + tipo; msg.textContent = texto; msg.style.display = 'block'; }

  function guardar() {
    try { localStorage.setItem(KEY, JSON.stringify(items)); } catch (e) {}
    pintar();
  }

  function pintar() {
    var cant = 0, total = 0, h = '';
    items.forEach(function (it, i) {
      cant += it.cant; total += it.cant * it.precio;
      h += '<div class="car-item"><div><b>' + esc(it.nombre) + '</b><small>Talla: ' + esc(it.talla) + ' · ' + money(it.precio) + '</small></div>' +
        '<div style="text-align:right">' + money(it.cant * it.precio) + '</div>' +
        '<div class="car-cant"><button type="button" data-act="menos" data-i="' + i + '" aria-label="Quitar uno">−</button><span>' + it.cant +
        '</span><button type="button" data-act="mas" data-i="' + i + '" aria-label="Agregar uno">+</button></div>' +
        '<div style="text-align:right"><button type="button" class="quitar" data-act="quitar" data-i="' + i + '">Quitar</button></div></div>';
    });
    lista.innerHTML = h || '<p class="car-vacio">Tu carrito está vacío.</p>';
    document.getElementById('carTotal').textContent = money(total);
    document.getElementById('carCompra').style.display = items.length ? 'block' : 'none';
    var c = document.getElementById('cantCarrito');
    if (c) c.textContent = cant;
  }

  lista.addEventListener('click', function (e) {
    var b = e.target.closest('button[data-act]');
    if (!b) return;
    var i = +b.dataset.i;
    if (b.dataset.act === 'mas') items[i].cant++;
    else if (b.dataset.act === 'menos') { items[i].cant--; if (items[i].cant < 1) items.splice(i, 1); }
    else items.splice(i, 1);
    guardar();
  });

  document.addEventListener('click', function (e) {
    var b = e.target.closest('.agregar');
    if (!b) return;
    var card = b.closest('.producto');
    var sel = card.querySelector('.talla');
    var talla = sel ? sel.value : 'UNICA';
    var id = +b.dataset.id;
    var ya = items.filter(function (it) { return it.id === id && it.talla === talla; })[0];
    if (ya) ya.cant++;
    else items.push({ id: id, nombre: b.dataset.nombre, precio: +b.dataset.precio, talla: talla, cant: 1 });
    guardar();
    b.textContent = '¡Añadido!';
    setTimeout(function () { b.textContent = 'Añadir al carrito'; }, 1000);
  });

  var abrir = document.getElementById('abrirCarrito');
  if (abrir) abrir.addEventListener('click', function (e) {
    e.preventDefault();
    msg.style.display = 'none';
    dlg.showModal();
  });
  document.getElementById('cerrarCarrito').addEventListener('click', function () { dlg.close(); });
  dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });

  var btn = document.getElementById('carComprar');
  btn.addEventListener('click', function () {
    var correo = email.value.trim();
    if (!/^\S+@\S+\.\S+$/.test(correo)) { mostrar('error', 'Escribe el correo con el que creaste tu cuenta.'); return; }
    btn.disabled = true;
    fetch('comprar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: correo,
        metodo_pago: document.getElementById('carPago').value,
        items: items.map(function (it) { return { id: it.id, talla: it.talla, cantidad: it.cant }; })
      })
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.ok) {
          try { localStorage.setItem('mshakes_email', correo); } catch (e) {}
          items = [];       // la compra se registró: se vacía el carrito
          guardar();
          mostrar('ok', d.mensaje);
        } else {
          mostrar('error', d.mensaje);
        }
      })
      .catch(function () { mostrar('error', 'No se pudo conectar. Abre la página desde http://localhost/ y revisa que Apache esté encendido.'); })
      .then(function () { btn.disabled = false; });
  });

  pintar();
})();
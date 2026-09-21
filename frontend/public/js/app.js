const $ = (sel) => document.querySelector(sel);

const state = {
  layers: [],
  step: 0,
  selection: {},
  chef: localStorage.getItem('pizza.chef') || '',
};

const api = async (path, options = {}) => {
  const res = await fetch(`/api${path}`, {
    headers: { 'Content-Type': 'application/json' },
    ...options,
  });
  const data = await res.json().catch(() => ({}));

  return { ok: res.ok, status: res.status, data };
};

/* ---------- selection helpers ---------- */

const layer = () => state.layers[state.step];
const picked = (layerId) => state.selection[layerId] ?? [];
const optionById = (id) =>
  state.layers.flatMap((l) => l.options).find((o) => o.id === id);

function toggle(layerId, optionId) {
  const max = state.layers.find((l) => l.id === layerId).max;
  const current = picked(layerId);

  if (current.includes(optionId)) {
    state.selection[layerId] = current.filter((id) => id !== optionId);
  } else if (max === 1) {
    state.selection[layerId] = [optionId];
  } else if (current.length < max) {
    state.selection[layerId] = [...current, optionId];
  } else {
    return; // At the limit: ignore rather than silently swapping something out.
  }

  render();
}

const stepSatisfied = (index) => {
  const l = state.layers[index];

  return picked(l.id).length >= l.min;
};

const runningTotal = () =>
  Object.values(state.selection)
    .flat()
    .reduce((sum, id) => sum + (optionById(id)?.price ?? 0), 0);

/* ---------- rendering ---------- */

function renderSteps() {
  $('#steps').replaceChildren(
    ...state.layers.map((l, i) => {
      const chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'step-chip';
      if (i === state.step) chip.classList.add('active');
      else if (stepSatisfied(i) && picked(l.id).length > 0) chip.classList.add('done');
      chip.textContent = `${i + 1}. ${l.name}`;
      chip.onclick = () => {
        state.step = i;
        render();
      };

      return chip;
    }),
  );
}

function renderOptions() {
  const l = layer();
  const chosen = picked(l.id);
  const atMax = chosen.length >= l.max;

  $('#step-title').textContent = l.name;
  $('#step-prompt').textContent = l.prompt;
  $('#step-rule').textContent =
    l.max === 1
      ? 'Pick one.'
      : `${chosen.length} of ${l.max} picked${l.min > 0 ? ` · at least ${l.min}` : ' · optional'}`;

  $('#options').replaceChildren(
    ...l.options.map((o) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'option';
      btn.setAttribute('aria-pressed', String(chosen.includes(o.id)));
      if (chosen.includes(o.id)) btn.classList.add('selected');
      if (atMax) btn.classList.add('maxed');
      btn.innerHTML = `
        <span class="emoji">${o.emoji}</span>
        <span>
          <span class="name">${o.name} <span class="price">€${o.price.toFixed(2)}</span></span>
          <p class="note">${o.note}</p>
        </span>`;
      btn.onclick = () => toggle(l.id, o.id);

      return btn;
    }),
  );
}

/** Deterministic scatter so the same topping always lands in the same spots. */
function scatter(optionId, index, count) {
  const seed = [...optionId].reduce((a, c) => a + c.charCodeAt(0), index * 37);
  const bits = [];

  for (let i = 0; i < count; i += 1) {
    const angle = (seed + i * 137.508) * (Math.PI / 180);
    const radius = 14 + ((seed * (i + 3)) % 30);
    bits.push({
      x: 50 + Math.cos(angle) * radius,
      y: 50 + Math.sin(angle) * radius,
      rot: ((seed + i * 53) % 90) - 45,
    });
  }

  return bits;
}

function renderPizza() {
  const pizza = $('#pizza');
  const base = picked('base')[0] && optionById(picked('base')[0]);
  const sauce = picked('sauce')[0] && optionById(picked('sauce')[0]);
  const cheeses = picked('cheese').map(optionById);
  const toppings = picked('toppings').map(optionById);
  const finishes = picked('finish').map(optionById);

  if (!base) {
    pizza.replaceChildren(Object.assign(document.createElement('div'), { className: 'peel' }));
    pizza.setAttribute('aria-label', 'An empty pizza peel');

    return;
  }

  const nodes = [];
  const div = (cls, style) => {
    const el = document.createElement('div');
    el.className = cls;
    Object.assign(el.style, style);

    return el;
  };

  nodes.push(div('layer layer-base', { background: base.color }));

  if (sauce) {
    nodes.push(
      div('layer layer-sauce', {
        background: `radial-gradient(circle at 42% 38%, ${sauce.color}, ${sauce.color} 60%, rgba(0,0,0,.18))`,
      }),
    );
  }

  if (cheeses.length) {
    const blobs = cheeses
      .map((c, i) => `radial-gradient(circle at ${28 + i * 34}% ${34 + i * 22}%, ${c.color} 0 28%, transparent 52%)`)
      .join(',');
    nodes.push(
      div('layer layer-cheese', {
        background: `${blobs}, ${cheeses[0].color}`,
        opacity: 0.88,
      }),
    );
  }

  if (toppings.length) {
    const bitsLayer = div('bits');
    toppings.forEach((t, i) => {
      scatter(t.id, i, 7).forEach((b) => {
        const bit = document.createElement('span');
        bit.className = 'bit';
        bit.textContent = t.emoji;
        bit.style.left = `${b.x}%`;
        bit.style.top = `${b.y}%`;
        bit.style.setProperty('--rot', `${b.rot}deg`);
        bitsLayer.append(bit);
      });
    });
    nodes.push(bitsLayer);
  }

  if (finishes.length) {
    const drizzle = finishes
      .map(
        (f, i) =>
          `repeating-conic-gradient(from ${i * 40}deg, transparent 0 ${18 - i * 4}deg, ${f.color}55 ${18 - i * 4}deg ${22 - i * 4}deg)`,
      )
      .join(',');
    nodes.push(div('layer layer-finish', { background: drizzle, opacity: 0.75 }));
  }

  pizza.replaceChildren(...nodes);
  pizza.setAttribute(
    'aria-label',
    `Pizza with ${[base, sauce, ...cheeses, ...toppings, ...finishes].filter(Boolean).map((o) => o.name).join(', ')}`,
  );
}

function renderStack() {
  const items = state.layers.flatMap((l) =>
    picked(l.id).map((id) => {
      const o = optionById(id);
      const li = document.createElement('li');
      li.innerHTML = `${o.emoji} ${o.name} <span class="small">€${o.price.toFixed(2)}</span>`;

      return li;
    }),
  );

  $('#stack').replaceChildren(
    ...(items.length ? items : [Object.assign(document.createElement('li'), { textContent: 'Empty peel' })]),
  );

  const done = state.layers.filter((l, i) => stepSatisfied(i) && picked(l.id).length > 0).length;
  $('#layer-count').textContent = `${done} / ${state.layers.length}`;
  $('#total').textContent = `€${runningTotal().toFixed(2)}`;
}

function renderNav() {
  const last = state.step === state.layers.length - 1;
  $('#prev').disabled = state.step === 0;
  $('#next').hidden = last;
  $('#bake').hidden = !last;
  $('#next').disabled = !stepSatisfied(state.step);
  $('#bake').disabled = !state.layers.every((_, i) => stepSatisfied(i));
}

function render() {
  renderSteps();
  renderOptions();
  renderPizza();
  renderStack();
  renderNav();
}

/* ---------- actions ---------- */

async function bake() {
  $('#issues').hidden = true;
  $('#bake').disabled = true;
  $('#pizza').classList.add('baking');
  $('#oven-glow').classList.add('on');

  const { ok, data } = await api('/pizzas', {
    method: 'POST',
    body: JSON.stringify({ chef: state.chef, selection: state.selection }),
  });

  setTimeout(() => {
    $('#pizza').classList.remove('baking');
    $('#oven-glow').classList.remove('on');
  }, 1200);

  if (!ok) {
    const issues = data.issues?.join(' ') || data.detail || 'The oven refused this pizza.';
    $('#issues').textContent = issues;
    $('#issues').hidden = false;
    $('#bake').disabled = false;

    return;
  }

  showResult(data.pizza);
  loadRecent();
}

function showResult(pizza) {
  $('#result-name').textContent = pizza.name;
  $('#result-chef').textContent = `Built by ${pizza.chef}`;
  $('#result-score').textContent = pizza.verdict.score;
  $('.score-ring').style.setProperty('--pct', pizza.verdict.score);
  $('#result-grade').textContent = pizza.verdict.grade;
  $('#result-notes').replaceChildren(
    ...pizza.verdict.notes.map((n) => Object.assign(document.createElement('li'), { textContent: n })),
  );
  $('#result-total').textContent = `€${pizza.price.total.toFixed(2)} (incl. VAT)`;
  $('#result-oven').textContent = `${pizza.bake.temperatureC}°C · ${pizza.bake.minutes} min`;
  $('#result-after').textContent = pizza.bake.addAfterBake.join(', ') || 'Nothing — straight to the box';
  $('#result').hidden = false;
}

async function loadRecent() {
  const { ok, data } = await api('/pizzas');
  if (!ok) return;

  const items = (data.pizzas || []).map((p) => {
    const li = document.createElement('li');
    li.innerHTML = `
      <div class="recent-name">${p.name}</div>
      <div class="recent-meta">${p.chef} · ${p.verdict.score}/100 · €${p.price.total.toFixed(2)}</div>
      <div class="recent-meta">${p.ingredients.map((id) => optionById(id)?.emoji ?? '').join(' ')}</div>`;

    return li;
  });

  $('#recent-list').replaceChildren(
    ...(items.length ? items : [Object.assign(document.createElement('li'), { className: 'recent-empty', textContent: 'Nothing in the oven yet.' })]),
  );
}

function reset() {
  state.selection = {};
  state.step = 0;
  $('#issues').hidden = true;
  render();
}

/* ---------- boot ---------- */

async function boot() {
  const { ok, data } = await api('/ingredients');
  if (!ok) {
    $('#backend-status').textContent = 'oven offline — is the PHP API running?';

    return;
  }

  state.layers = data.layers;
  state.layers.forEach((l) => {
    state.selection[l.id] = [];
  });

  $('#chef').value = state.chef;
  $('#chef').oninput = (e) => {
    state.chef = e.target.value;
    localStorage.setItem('pizza.chef', state.chef);
  };

  $('#next').onclick = () => {
    state.step = Math.min(state.step + 1, state.layers.length - 1);
    render();
  };
  $('#prev').onclick = () => {
    state.step = Math.max(state.step - 1, 0);
    render();
  };
  $('#bake').onclick = bake;
  $('#reset').onclick = reset;
  $('#again').onclick = () => {
    $('#result').hidden = true;
    reset();
  };
  $('#close-result').onclick = () => {
    $('#result').hidden = true;
    $('#bake').disabled = false;
  };
  $('#surprise').onclick = async () => {
    const res = await api('/surprise');
    if (!res.ok) return;
    state.selection = res.data.selection;
    state.step = state.layers.length - 1;
    render();
  };

  const health = await api('/health');
  $('#backend-status').textContent = health.ok
    ? `oven hot · PHP ${health.data.php} · storage: ${health.data.storage}`
    : 'oven offline';

  render();
  loadRecent();
}

boot();

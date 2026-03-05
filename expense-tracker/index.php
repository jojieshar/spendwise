<?php
require_once 'db.php';
getDB(); // Ensure DB + schema are initialized

$currentMonth = date('Y-m');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Spendwisely — Expense Tracker</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
:root {
  --bg:          #0a0b0f;
  --surface:     #111318;
  --surface2:    #181c24;
  --border:      #1e2330;
  --border2:     #252b3b;
  --text:        #e8eaf0;
  --muted:       #5a6070;
  --accent:      #00e5a0;
  --accent-dim:  #00e5a015;
  --accent-glow: #00e5a030;
  --red:         #ff4757;
  --red-dim:     #ff475715;
  --yellow:      #ffd32a;
  --blue:        #3d8bff;
  --font-head:   'Syne', sans-serif;
  --font-mono:   'DM Mono', monospace;
  --radius:      14px;
  --radius-sm:   8px;
  --shadow:      0 4px 32px #00000060;
  --shadow-lg:   0 12px 64px #00000080;
  --transition:  all 0.22s cubic-bezier(0.4,0,0.2,1);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }

body {
  background: var(--bg);
  color: var(--text);
  font-family: var(--font-mono);
  font-size: 14px;
  line-height: 1.6;
  min-height: 100vh;
  overflow-x: hidden;
}

body::before {
  content: '';
  position: fixed;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
  pointer-events: none;
  z-index: 0;
}

.app {
  position: relative;
  z-index: 1;
  max-width: 1200px;
  margin: 0 auto;
  padding: 32px 24px 80px;
}

.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 40px;
  padding-bottom: 24px;
  border-bottom: 1px solid var(--border);
  animation: fadeDown 0.5s ease both;
}

.logo {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  object-fit: contain;
}

.logo-text {
  font-family: var(--font-head);
  font-size: 22px;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.logo-text span { color: var(--accent); }

.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.month-filter {
  background: var(--surface);
  border: 1px solid var(--border2);
  color: var(--text);
  font-family: var(--font-mono);
  font-size: 13px;
  padding: 8px 14px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: var(--transition);
  outline: none;
}
.month-filter:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-bottom: 28px;
}

@media (max-width: 700px) { .stats-grid { grid-template-columns: 1fr; } }

.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 22px 24px;
  position: relative;
  overflow: hidden;
  transition: var(--transition);
  animation: fadeUp 0.5s ease both;
}
.stat-card:nth-child(1) { animation-delay: 0.05s; }
.stat-card:nth-child(2) { animation-delay: 0.10s; }
.stat-card:nth-child(3) { animation-delay: 0.15s; }

.stat-card::after {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
  border-radius: var(--radius) var(--radius) 0 0;
}
.stat-card.balance::after  { background: var(--blue); }
.stat-card.income::after   { background: var(--accent); }
.stat-card.expense::after  { background: var(--red); }

.stat-card:hover { transform: translateY(-3px); border-color: var(--border2); box-shadow: var(--shadow); }

.stat-label {
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.stat-label .dot {
  width: 6px; height: 6px;
  border-radius: 50%;
}
.balance .dot  { background: var(--blue); }
.income .dot   { background: var(--accent); }
.expense .dot  { background: var(--red); }

.stat-value {
  font-family: var(--font-head);
  font-size: 30px;
  font-weight: 700;
  letter-spacing: -1px;
  line-height: 1;
}
.balance  .stat-value { color: var(--blue); }
.income   .stat-value { color: var(--accent); }
.expense  .stat-value { color: var(--red); }

.stat-sub {
  font-size: 11px;
  color: var(--muted);
  margin-top: 6px;
}

.main-grid {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 20px;
  align-items: start;
}

@media (max-width: 900px) { .main-grid { grid-template-columns: 1fr; } }

.panel {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  animation: fadeUp 0.5s ease both;
}
.panel:nth-child(1) { animation-delay: 0.2s; }
.panel:nth-child(2) { animation-delay: 0.25s; }

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 22px;
  border-bottom: 1px solid var(--border);
}

.panel-title {
  font-family: var(--font-head);
  font-size: 15px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
}

.panel-title .icon {
  width: 28px; height: 28px;
  background: var(--surface2);
  border-radius: 6px;
  display: grid;
  place-items: center;
  font-size: 14px;
}

.form-body { padding: 22px; }

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 12px;
}

.form-group { display: flex; flex-direction: column; gap: 5px; }
.form-group.full { grid-column: 1 / -1; }

label {
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--muted);
}

input, select, textarea {
  background: var(--surface2);
  border: 1px solid var(--border2);
  color: var(--text);
  font-family: var(--font-mono);
  font-size: 13px;
  padding: 10px 14px;
  border-radius: var(--radius-sm);
  outline: none;
  transition: var(--transition);
  width: 100%;
}
input:focus, select:focus, textarea:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-glow);
}
select option { background: var(--surface2); }

/* Type toggle */
.type-toggle {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.type-btn {
  padding: 10px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border2);
  background: var(--surface2);
  color: var(--muted);
  font-family: var(--font-mono);
  font-size: 13px;
  cursor: pointer;
  transition: var(--transition);
  text-align: center;
  font-weight: 500;
  letter-spacing: 0.05em;
}
.type-btn:hover { border-color: var(--border2); color: var(--text); }
.type-btn.active-income  { background: var(--accent-dim); border-color: var(--accent); color: var(--accent); }
.type-btn.active-expense { background: var(--red-dim);    border-color: var(--red);    color: var(--red); }

.btn-submit {
  width: 100%;
  margin-top: 8px;
  padding: 12px;
  background: var(--accent);
  color: #000;
  border: none;
  border-radius: var(--radius-sm);
  font-family: var(--font-head);
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 0.05em;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}
.btn-submit:hover { background: #00ffb3; transform: translateY(-1px); box-shadow: 0 6px 24px var(--accent-glow); }
.btn-submit:active { transform: translateY(0); }
.btn-submit:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

.filter-bar {
  padding: 14px 22px;
  border-bottom: 1px solid var(--border);
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}

.filter-bar select {
  width: auto;
  font-size: 12px;
  padding: 6px 10px;
}

.filter-label {
  font-size: 11px;
  color: var(--muted);
  letter-spacing: 0.08em;
  text-transform: uppercase;
  white-space: nowrap;
}

.tx-list {
  max-height: 520px;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: var(--border2) transparent;
}

.tx-list::-webkit-scrollbar { width: 4px; }
.tx-list::-webkit-scrollbar-track { background: transparent; }
.tx-list::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 4px; }

.tx-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 22px;
  border-bottom: 1px solid var(--border);
  transition: var(--transition);
  animation: slideIn 0.3s ease both;
}
.tx-item:last-child { border-bottom: none; }
.tx-item:hover { background: var(--surface2); }

.tx-icon {
  width: 38px; height: 38px;
  border-radius: 10px;
  display: grid;
  place-items: center;
  font-size: 16px;
  flex-shrink: 0;
}
.tx-item.income  .tx-icon { background: var(--accent-dim); }
.tx-item.expense .tx-icon { background: var(--red-dim); }

.tx-info { flex: 1; min-width: 0; }

.tx-desc {
  font-size: 13px;
  font-weight: 500;
  color: var(--text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tx-meta {
  font-size: 11px;
  color: var(--muted);
  margin-top: 2px;
  display: flex;
  gap: 8px;
}

.cat-badge {
  background: var(--surface2);
  border: 1px solid var(--border2);
  border-radius: 4px;
  padding: 1px 6px;
  font-size: 10px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.tx-amount {
  font-family: var(--font-head);
  font-size: 14px;
  font-weight: 700;
  letter-spacing: -0.3px;
  text-align: right;
}
.income  .tx-amount { color: var(--accent); }
.expense .tx-amount { color: var(--red); }

.tx-date { font-size: 10px; color: var(--muted); margin-top: 2px; text-align: right; }

.btn-delete {
  width: 28px; height: 28px;
  border-radius: 6px;
  border: 1px solid var(--border2);
  background: transparent;
  color: var(--muted);
  cursor: pointer;
  display: grid;
  place-items: center;
  font-size: 14px;
  transition: var(--transition);
  flex-shrink: 0;
}
.btn-delete:hover { background: var(--red-dim); border-color: var(--red); color: var(--red); }

.empty-state {
  padding: 60px 22px;
  text-align: center;
  color: var(--muted);
}
.empty-state .icon { font-size: 40px; margin-bottom: 12px; }
.empty-state p { font-size: 13px; }

.sidebar { display: flex; flex-direction: column; gap: 20px; }

.chart-wrap {
  padding: 22px;
  position: relative;
}
.chart-wrap canvas { max-height: 220px; }

.no-data-chart {
  height: 220px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--muted);
  gap: 8px;
  font-size: 13px;
}

/* Category breakdown */
.cat-list { padding: 0 22px 22px; }

.cat-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 0;
  border-bottom: 1px solid var(--border);
  animation: fadeUp 0.3s ease both;
}
.cat-row:last-child { border-bottom: none; }

.cat-color {
  width: 10px; height: 10px;
  border-radius: 3px;
  flex-shrink: 0;
}

.cat-name { flex: 1; font-size: 12px; }

.cat-bar-wrap {
  width: 80px;
  height: 4px;
  background: var(--border2);
  border-radius: 2px;
  overflow: hidden;
}

.cat-bar {
  height: 100%;
  border-radius: 2px;
  transition: width 0.6s cubic-bezier(0.4,0,0.2,1);
}

.cat-total {
  font-size: 12px;
  font-weight: 500;
  text-align: right;
  min-width: 70px;
  color: var(--red);
}

.toast {
  position: fixed;
  bottom: 28px;
  right: 28px;
  z-index: 1000;
  background: var(--surface2);
  border: 1px solid var(--border2);
  border-radius: var(--radius-sm);
  padding: 14px 18px;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 10px;
  box-shadow: var(--shadow-lg);
  transform: translateY(20px);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
  max-width: 320px;
}
.toast.show { transform: translateY(0); opacity: 1; }
.toast.success { border-left: 3px solid var(--accent); }
.toast.error   { border-left: 3px solid var(--red); }

.skeleton {
  background: linear-gradient(90deg, var(--surface2) 25%, var(--border2) 50%, var(--surface2) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
  height: 16px;
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeDown {
  from { opacity: 0; transform: translateY(-16px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes slideIn {
  from { opacity: 0; transform: translateX(-12px); }
  to   { opacity: 1; transform: translateX(0); }
}
@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
@keyframes pop {
  0%   { transform: scale(1); }
  50%  { transform: scale(1.12); }
  100% { transform: scale(1); }
}

.pop { animation: pop 0.25s ease; }
</style>
</head>
<body>
<div class="app">

  <header class="header">
    <div class="logo">
      <img src="images/ex-logo.png" alt="Spendwisely Logo" class="logo-icon">
      <div>
        <div class="logo-text">Spend<span>wisely</span></div>
        <div style="font-size:11px;color:var(--muted);letter-spacing:.08em;">EXPENSE TRACKER</div>
      </div>
    </div>
    <div class="header-right">
      <label for="monthFilter" style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;">Month</label>
      <input type="month" id="monthFilter" class="month-filter" value="<?= $currentMonth ?>">
    </div>
  </header>

  <div class="stats-grid">
    <div class="stat-card balance">
      <div class="stat-label"><span class="dot"></span>Net Balance</div>
      <div class="stat-value" id="statBalance">—</div>
      <div class="stat-sub">Income minus expenses</div>
    </div>
    <div class="stat-card income">
      <div class="stat-label"><span class="dot"></span>Total Income</div>
      <div class="stat-value" id="statIncome">—</div>
      <div class="stat-sub">This month</div>
    </div>
    <div class="stat-card expense">
      <div class="stat-label"><span class="dot"></span>Total Expenses</div>
      <div class="stat-value" id="statExpense">—</div>
      <div class="stat-sub">This month</div>
    </div>
  </div>

  <div class="main-grid">

    <!-- LEFT: Transactions -->
    <div>

      <!-- Add Transaction Panel -->
      <div class="panel" style="margin-bottom:20px;">
        <div class="panel-header">
          <div class="panel-title">
            <span class="icon">＋</span> Add Transaction
          </div>
        </div>
        <div class="form-body">
          <!-- Type Toggle -->
          <div class="form-group" style="margin-bottom:14px;">
            <label>Type</label>
            <div class="type-toggle">
              <button class="type-btn active-income" id="btnIncome"  onclick="setType('income')">▲ Income</button>
              <button class="type-btn"               id="btnExpense" onclick="setType('expense')">▼ Expense</button>
            </div>
          </div>
          <input type="hidden" id="txType" value="income">

          <div class="form-row">
            <div class="form-group">
              <label>Category</label>
              <select id="txCategory"></select>
            </div>
            <div class="form-group">
              <label>Amount (₱)</label>
              <input type="number" id="txAmount" placeholder="0.00" min="0.01" step="0.01">
            </div>
            <div class="form-group full">
              <label>Description</label>
              <input type="text" id="txDesc" placeholder="What was this for?">
            </div>
            <div class="form-group full">
              <label>Date</label>
              <input type="date" id="txDate" value="<?= date('Y-m-d') ?>">
            </div>
          </div>

          <button class="btn-submit" id="btnSubmit" onclick="addTransaction()">
            <span>＋</span> Add Transaction
          </button>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <div class="panel-title">
            <span class="icon">≡</span> Transactions
          </div>
          <span id="txCount" style="font-size:12px;color:var(--muted);">— records</span>
        </div>

        <!-- Filters -->
        <div class="filter-bar">
          <span class="filter-label">Filter:</span>
          <select id="filterType" onchange="loadTransactions()" style="font-size:12px;padding:6px 10px;width:auto;">
            <option value="">All Types</option>
            <option value="income">Income</option>
            <option value="expense">Expense</option>
          </select>
          <select id="filterCategory" onchange="loadTransactions()" style="font-size:12px;padding:6px 10px;width:auto;">
            <option value="">All Categories</option>
          </select>
        </div>

        <div class="tx-list" id="txList">
          <!-- Populated by JS -->
        </div>
      </div>
    </div>

    <div class="sidebar">

      <!-- Pie Chart -->
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title"><span class="icon">◕</span> Expenses by Category</div>
        </div>
        <div class="chart-wrap">
          <canvas id="pieChart"></canvas>
          <div class="no-data-chart" id="noDataPie" style="display:none;">
            <span style="font-size:32px;">📊</span>
            <span>No expense data yet</span>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <div class="panel-title"><span class="icon">↓</span> Category Breakdown</div>
        </div>
        <div class="cat-list" id="catList">
          <!-- Populated by JS -->
        </div>
      </div>

    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>

const CATEGORIES = {
  income:  ['Salary','Freelance','Investment','Business','Gift','Other'],
  expense: ['Food','Transport','Bills','Shopping','Health','Entertainment','Education','Other'],
};

const CAT_ICONS = {
  Salary:'💼', Freelance:'💻', Investment:'📈', Business:'🏢', Gift:'🎁',
  Food:'🍜', Transport:'🚗', Bills:'💡', Shopping:'🛍️', Health:'💊',
  Entertainment:'🎮', Education:'📚', Other:'📌',
};

const PIE_COLORS = [
  '#ff4757','#ffd32a','#00e5a0','#3d8bff','#ff6b81',
  '#eccc68','#7bed9f','#70a1ff','#ff6348','#a29bfe',
];

let pieChart = null;
let currentType = 'income';

document.addEventListener('DOMContentLoaded', () => {
  populateCategorySelects();
  document.getElementById('monthFilter').addEventListener('change', () => {
    loadAll();
  });
  loadAll();
});

function loadAll() {
  loadSummary();
  loadTransactions();
}

function setType(type) {
  currentType = type;
  document.getElementById('txType').value = type;
  document.getElementById('btnIncome').className  = 'type-btn' + (type === 'income'  ? ' active-income'  : '');
  document.getElementById('btnExpense').className = 'type-btn' + (type === 'expense' ? ' active-expense' : '');
  populateCategorySelects();
}

function populateCategorySelects() {
  // Form category select
  const sel = document.getElementById('txCategory');
  sel.innerHTML = CATEGORIES[currentType].map(c =>
    `<option value="${c}">${CAT_ICONS[c] || '📌'} ${c}</option>`
  ).join('');

  // Filter category select
  const allCats = [...new Set([...CATEGORIES.income, ...CATEGORIES.expense])];
  const fsel = document.getElementById('filterCategory');
  const current = fsel.value;
  fsel.innerHTML = `<option value="">All Categories</option>` +
    allCats.map(c => `<option value="${c}" ${c===current?'selected':''}>${CAT_ICONS[c]||'📌'} ${c}</option>`).join('');
}

async function loadSummary() {
  const month = document.getElementById('monthFilter').value;
  try {
    const res  = await fetch(`api.php?action=summary&month=${month}`);
    const json = await res.json();
    if (!json.success) return;

    const { summary, categories } = json;
    const income  = parseFloat(summary.total_income);
    const expense = parseFloat(summary.total_expense);
    const balance = income - expense;

    animateNumber('statIncome',  income);
    animateNumber('statExpense', expense);
    animateNumber('statBalance', balance);

    renderPieChart(categories);
    renderCatList(categories, expense);
  } catch(e) { console.error(e); }
}

async function loadTransactions() {
  const month    = document.getElementById('monthFilter').value;
  const type     = document.getElementById('filterType').value;
  const category = document.getElementById('filterCategory').value;

  let url = `api.php?action=list&month=${month}`;
  if (type)     url += `&type=${type}`;
  if (category) url += `&category=${encodeURIComponent(category)}`;

  const list = document.getElementById('txList');
  list.innerHTML = '<div style="padding:40px;text-align:center;color:var(--muted)"><div class="skeleton" style="margin:0 auto;width:60%;"></div></div>';

  try {
    const res  = await fetch(url);
    const json = await res.json();
    if (!json.success) return;

    const rows = json.data;
    document.getElementById('txCount').textContent = `${rows.length} record${rows.length!==1?'s':''}`;

    if (rows.length === 0) {
      list.innerHTML = `
        <div class="empty-state">
          <div class="icon">🔍</div>
          <p>No transactions found for this period.</p>
        </div>`;
      return;
    }

    list.innerHTML = rows.map((tx, i) => `
      <div class="tx-item ${tx.type}" style="animation-delay:${i*0.04}s">
        <div class="tx-icon">${CAT_ICONS[tx.category] || '📌'}</div>
        <div class="tx-info">
          <div class="tx-desc">${escHtml(tx.description)}</div>
          <div class="tx-meta">
            <span class="cat-badge">${escHtml(tx.category)}</span>
            <span>${tx.type === 'income' ? '▲ income' : '▼ expense'}</span>
          </div>
        </div>
        <div>
          <div class="tx-amount">${tx.type === 'income' ? '+' : '-'}₱${fmt(tx.amount)}</div>
          <div class="tx-date">${formatDate(tx.date)}</div>
        </div>
        <button class="btn-delete" onclick="deleteTransaction(${tx.id}, this)" title="Delete">✕</button>
      </div>
    `).join('');
  } catch(e) { console.error(e); }
}

async function addTransaction() {
  const btn = document.getElementById('btnSubmit');
  const payload = {
    type:        document.getElementById('txType').value,
    category:    document.getElementById('txCategory').value,
    description: document.getElementById('txDesc').value.trim(),
    amount:      parseFloat(document.getElementById('txAmount').value),
    date:        document.getElementById('txDate').value,
  };

  if (!payload.description) return toast('Enter a description', 'error');
  if (!payload.amount || payload.amount <= 0) return toast('Enter a valid amount', 'error');
  if (!payload.date) return toast('Select a date', 'error');

  btn.disabled = true;
  btn.textContent = 'Saving…';

  try {
    const res  = await fetch('api.php?action=add', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(payload),
    });
    const json = await res.json();

    if (json.success) {
      toast(`${payload.type === 'income' ? '▲ Income' : '▼ Expense'} added!`, 'success');
      document.getElementById('txDesc').value   = '';
      document.getElementById('txAmount').value = '';
      loadAll();
    } else {
      toast(json.error || 'Failed to add', 'error');
    }
  } catch(e) {
    toast('Network error', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span>＋</span> Add Transaction';
  }
}

async function deleteTransaction(id, btn) {
  btn.disabled = true;
  try {
    const res  = await fetch(`api.php?action=delete&id=${id}`, { method:'DELETE' });
    const json = await res.json();
    if (json.success) {
      const item = btn.closest('.tx-item');
      item.style.transition = 'all 0.25s ease';
      item.style.opacity = '0';
      item.style.transform = 'translateX(20px)';
      setTimeout(() => { item.remove(); loadAll(); }, 260);
      toast('Transaction removed', 'success');
    }
  } catch(e) { toast('Error deleting', 'error'); }
}

function renderPieChart(categories) {
  const canvas = document.getElementById('pieChart');
  const noData = document.getElementById('noDataPie');

  if (!categories.length) {
    canvas.style.display = 'none';
    noData.style.display = 'flex';
    if (pieChart) { pieChart.destroy(); pieChart = null; }
    return;
  }
  canvas.style.display = 'block';
  noData.style.display = 'none';

  const labels = categories.map(c => c.category);
  const data   = categories.map(c => parseFloat(c.total));
  const colors = categories.map((_, i) => PIE_COLORS[i % PIE_COLORS.length]);

  if (pieChart) pieChart.destroy();

  pieChart = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data,
        backgroundColor: colors,
        borderColor: '#111318',
        borderWidth: 3,
        hoverOffset: 8,
      }],
    },
    options: {
      cutout: '68%',
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#181c24',
          borderColor: '#252b3b',
          borderWidth: 1,
          titleColor: '#e8eaf0',
          bodyColor: '#5a6070',
          titleFont: { family: 'Syne', weight: '700' },
          callbacks: {
            label: ctx => ` ₱${fmt(ctx.parsed)} (${Math.round(ctx.parsed / data.reduce((a,b)=>a+b,0)*100)}%)`
          }
        }
      },
      animation: { animateRotate: true, duration: 600 },
    }
  });
}

function renderCatList(categories, totalExpense) {
  const el = document.getElementById('catList');
  if (!categories.length) {
    el.innerHTML = '<div style="padding:20px;text-align:center;color:var(--muted);font-size:12px;">No expenses this month</div>';
    return;
  }

  el.innerHTML = categories.map((cat, i) => {
    const pct  = totalExpense > 0 ? (cat.total / totalExpense * 100) : 0;
    const color = PIE_COLORS[i % PIE_COLORS.length];
    return `
      <div class="cat-row" style="animation-delay:${i*0.06}s">
        <div class="cat-color" style="background:${color}"></div>
        <div class="cat-name">${CAT_ICONS[cat.category]||'📌'} ${cat.category}</div>
        <div class="cat-bar-wrap">
          <div class="cat-bar" style="width:${pct}%;background:${color}"></div>
        </div>
        <div class="cat-total">₱${fmt(cat.total)}</div>
      </div>
    `;
  }).join('');
}

function fmt(n) {
  return parseFloat(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(d) {
  return new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month:'short', day:'numeric', year:'numeric' });
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function animateNumber(id, target) {
  const el = document.getElementById(id);
  const start = 0;
  const duration = 700;
  const startTime = performance.now();
  const isNeg = target < 0;
  const abs = Math.abs(target);

  el.classList.remove('pop');

  function step(now) {
    const elapsed = Math.min(now - startTime, duration);
    const progress = elapsed / duration;
    const ease = 1 - Math.pow(1 - progress, 3);
    const val = start + (abs - start) * ease;
    el.textContent = (isNeg ? '-' : '') + '₱' + fmt(val);
    if (elapsed < duration) requestAnimationFrame(step);
    else {
      el.textContent = (isNeg ? '-' : '') + '₱' + fmt(abs);
      el.classList.add('pop');
      setTimeout(() => el.classList.remove('pop'), 300);
    }
  }
  requestAnimationFrame(step);
}

function toast(msg, type = 'success') {
  const el = document.getElementById('toast');
  el.textContent = (type === 'success' ? '✓  ' : '✕  ') + msg;
  el.className = `toast ${type} show`;
  clearTimeout(el._timer);
  el._timer = setTimeout(() => { el.className = `toast ${type}`; }, 3000);
}
</script>
</body>
</html>

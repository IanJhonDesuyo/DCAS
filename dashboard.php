<?php
session_start();

/*
  Guest/demo mode since login was removed.
*/
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'Guest';
    $_SESSION['user_name'] = 'Guest';
    $_SESSION['role'] = 'guest';
}

require_once 'includes/db.php';
require_once 'includes/helpers.php';

// Enforce workflow: must upload, profile, and clean first
check_workflow_step($conn, 'dashboard');

// Get file ID from session
$id = (int)$_SESSION['file_id'];
$file = get_file_or_redirect($conn, $id);

$path = $file['cleaned_name']
  ? dcas_uploads_dir() . '/' . $file['cleaned_name']
  : dcas_uploads_dir() . '/' . $file['stored_name'];

[$headers, $rows] = read_csv($path);
$profile = profile_dataset($headers, $rows);

// Encode dataset for client-side charting
$payload = [
  'headers' => $headers,
  'rows' => array_slice($rows, 0, 5000),
  'types' => array_column($profile, 'type'),
];

$total_rows = count($rows);
$total_cols = count($headers);
$total_missing = array_sum(array_column($profile, 'missing'));
$dups = count_duplicate_rows($rows);

$downloadFile = $file['cleaned_name'] ?: $file['stored_name'];
$downloadName = $file['cleaned_name'] ? ('cleaned_' . $file['original_name']) : $file['original_name'];

require_once 'includes/header.php';
?>

<style>
  .dashboard-page{
    position:relative;
    isolation:isolate;
  }

  .dashboard-page::before{
    content:"";
    position:absolute;
    inset:-120px -40px auto -40px;
    height:520px;
    z-index:-1;
    background:
      radial-gradient(600px 300px at 15% 20%, rgba(16,185,129,.16), transparent 60%),
      radial-gradient(500px 260px at 85% 10%, rgba(20,184,166,.16), transparent 60%),
      radial-gradient(700px 320px at 50% 90%, rgba(110,231,183,.14), transparent 60%);
    filter:blur(2px);
  }

  .dashboard-hero{
    position:relative;
    border-radius:30px;
    padding:3rem;
    background:linear-gradient(135deg, rgba(255,255,255,.90), rgba(255,255,255,.66));
    border:1px solid rgba(15,23,42,.07);
    box-shadow:0 30px 80px -45px rgba(4,120,87,.45), 0 8px 24px -14px rgba(15,23,42,.08);
    backdrop-filter:blur(10px);
    overflow:hidden;
  }

  .dashboard-hero::after{
    content:"";
    position:absolute;
    right:-120px;
    top:-120px;
    width:340px;
    height:340px;
    border-radius:50%;
    background:conic-gradient(from 120deg, #047857, #6ee7b7, #14b8a6, #047857);
    filter:blur(60px);
    opacity:.35;
    z-index:0;
  }

  .dashboard-hero > *{
    position:relative;
    z-index:1;
  }

  .eyebrow{
    display:inline-flex;
    align-items:center;
    gap:.5rem;
    padding:.42rem .85rem;
    border-radius:999px;
    background:rgba(16,185,129,.10);
    color:#047857;
    font-weight:900;
    font-size:.78rem;
    letter-spacing:.06em;
    text-transform:uppercase;
    border:1px solid rgba(16,185,129,.18);
  }

  .dashboard-title{
    margin:.95rem 0 .55rem;
    font-size:clamp(2rem, 4vw, 3.1rem);
    line-height:1.05;
    font-weight:950;
    letter-spacing:-.045em;
    background:linear-gradient(120deg, #052e2b 0%, #047857 48%, #10b981 78%, #14b8a6 100%);
    -webkit-background-clip:text;
    background-clip:text;
    color:transparent;
  }

  .dashboard-subtitle{
    color:#475569;
    font-size:1.05rem;
    max-width:780px;
    margin:0;
  }

  .file-chip-main{
    display:inline-flex;
    align-items:center;
    gap:.6rem;
    padding:.75rem .95rem;
    border-radius:16px;
    background:#fff;
    border:1px solid rgba(15,23,42,.08);
    box-shadow:0 10px 28px -20px rgba(15,23,42,.25);
    color:#334155;
    font-weight:800;
    max-width:100%;
  }

  .file-chip-main span{
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    max-width:340px;
  }

  .btn-download-hero{
    display:inline-flex;
    align-items:center;
    gap:.5rem;
    padding:.75rem 1.1rem;
    border-radius:16px;
    background:linear-gradient(135deg, #047857, #10b981);
    border:none;
    color:#fff;
    font-weight:900;
    font-size:.9rem;
    white-space:nowrap;
    box-shadow:0 14px 34px -18px rgba(4,120,87,.70);
    transition:transform .15s ease, filter .15s ease;
    text-decoration:none;
  }

  .btn-download-hero:hover{
    transform:translateY(-2px);
    filter:brightness(1.06);
    color:#fff;
  }

  .stat-card{
    border:1px solid rgba(15,23,42,.07);
    border-radius:22px;
    background:linear-gradient(180deg, #fff, #fbfffd);
    transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    position:relative;
    overflow:hidden;
    box-shadow:0 18px 50px -36px rgba(4,120,87,.28);
    height:100%;
  }

  .stat-card::before{
    content:"";
    position:absolute;
    left:0;
    top:0;
    bottom:0;
    width:5px;
    background:var(--accent, #10b981);
  }

  .stat-card:hover{
    transform:translateY(-4px);
    box-shadow:0 24px 60px -36px rgba(4,120,87,.42);
    border-color:rgba(16,185,129,.22);
  }

  .stat-icon{
    width:54px;
    height:54px;
    border-radius:15px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:var(--icon-bg, rgba(16,185,129,.10));
    flex-shrink:0;
  }

  .stat-icon i{
    font-size:1.5rem;
    color:var(--accent, #10b981);
  }

  .stat-label{
    font-size:.76rem;
    font-weight:900;
    letter-spacing:.08em;
    text-transform:uppercase;
    color:#64748b;
  }

  .stat-value{
    font-size:2.1rem;
    font-weight:950;
    color:var(--accent, #10b981);
    line-height:1;
  }

  .stat-note{
    margin-top:.85rem;
    color:#64748b;
    font-size:.88rem;
  }

  .glass-panel{
    border:1px solid rgba(15,23,42,.07);
    border-radius:26px;
    background:rgba(255,255,255,.92);
    backdrop-filter:blur(10px);
    box-shadow:0 22px 60px -38px rgba(4,120,87,.38);
    overflow:hidden;
  }

  .panel-head{
    padding:1.25rem 1.45rem;
    border-bottom:1px solid rgba(15,23,42,.06);
    background:linear-gradient(180deg, #fff, #fbfffd);
  }

  .panel-title{
    margin:0;
    color:#0f172a;
    font-weight:950;
    display:flex;
    align-items:center;
    gap:.55rem;
  }

  .panel-subtitle{
    margin:.35rem 0 0;
    color:#64748b;
    font-size:.92rem;
  }

  .panel-body{
    padding:1.45rem;
  }

  .form-label{
    color:#334155;
    font-size:.83rem;
  }

  .form-select{
    min-height:48px;
    border-radius:14px;
    border:1px solid rgba(15,23,42,.12);
    background-color:#fff;
    color:#0f172a;
  }

  .form-select:focus{
    border-color:#10b981;
    box-shadow:0 0 0 4px rgba(16,185,129,.15);
  }

  .field-help{
    margin-top:.35rem;
    color:#64748b;
    font-size:.78rem;
  }

  .chart-card{
    border:1px solid rgba(15,23,42,.07);
    border-radius:24px;
    background:#fff;
    box-shadow:0 18px 50px -34px rgba(4,120,87,.28);
    overflow:hidden;
    height:100%;
  }

  .chart-head{
    padding:1.15rem 1.35rem;
    border-bottom:1px solid rgba(15,23,42,.06);
    background:linear-gradient(180deg, #fff, #fbfffd);
  }

  .chart-title{
    margin:0;
    font-weight:950;
    color:#0f172a;
    display:flex;
    align-items:center;
    gap:.55rem;
  }

  .chart-subtitle{
    margin:.35rem 0 0;
    color:#64748b;
    font-size:.88rem;
  }

  .chart-body{
    padding:1.35rem;
  }

  .chart-wrap{
    position:relative;
    width:100%;
    height:260px;
  }

  .chart-wrap-line{
    position:relative;
    width:100%;
    height:230px;
  }

  .simple-note{
    margin-top:1rem;
    padding:.9rem 1rem;
    border-radius:16px;
    background:rgba(16,185,129,.06);
    border:1px solid rgba(16,185,129,.10);
    color:#475569;
    font-size:.9rem;
    line-height:1.55;
  }

  .simple-note strong{
    color:#047857;
  }

  .insight-note{
    margin-top:1rem;
    padding:1rem 1.1rem;
    border-radius:18px;
    background:linear-gradient(135deg, rgba(236,253,245,.88), rgba(255,255,255,.94));
    border:1px solid rgba(16,185,129,.16);
    color:#334155;
    font-size:.92rem;
    line-height:1.6;
  }

  .insight-note .insight-title{
    display:flex;
    align-items:center;
    gap:.45rem;
    font-weight:950;
    color:#047857;
    margin-bottom:.35rem;
  }

  .insight-note p{
    margin:0;
  }

  .btn-soft{
    background:rgba(255,255,255,.74);
    border:1px solid rgba(15,23,42,.10);
    backdrop-filter:blur(8px);
    font-weight:800;
    border-radius:14px;
  }

  .btn-soft:hover{
    border-color:rgba(16,185,129,.25);
    box-shadow:0 12px 30px -24px rgba(4,120,87,.5);
  }

  @media (max-width: 991px){
    .dashboard-hero{
      padding:2rem;
    }

    .chart-wrap,
    .chart-wrap-line{
      height:230px;
    }
  }
</style>

<div class="dashboard-page">

  <div class="dashboard-hero mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
      <div>
        <span class="eyebrow">
          <i class="bi bi-pie-chart-fill"></i>
          Step 5 · Visualization
        </span>

        <h1 class="dashboard-title">
          Dashboard & Visualization
        </h1>

        <p class="dashboard-subtitle">
          Explore your dataset through clear charts and summary cards. Use the controls below to choose what information to compare and how it should be summarized.
        </p>
      </div>

      <div class="d-flex flex-column align-items-end gap-2">
        <div class="file-chip-main">
          <i class="bi bi-file-earmark-spreadsheet text-success"></i>
          <span><?= htmlspecialchars($file['original_name']) ?></span>
        </div>

        <a href="download.php?file=<?= rawurlencode($downloadFile) ?>&name=<?= rawurlencode($downloadName) ?>" class="btn-download-hero">
          <i class="bi bi-file-earmark-arrow-down"></i>
          Download CSV
        </a>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
      <div class="stat-card p-4" style="--accent:#047857; --icon-bg:rgba(16,185,129,.10);">
        <div class="d-flex align-items-center">
          <div class="stat-icon">
            <i class="bi bi-file-text"></i>
          </div>

          <div class="ms-3">
            <div class="stat-label">Records</div>
            <div class="stat-value"><?= number_format((int)$total_rows) ?></div>
          </div>
        </div>

        <div class="stat-note">
          Total rows included in the dataset.
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="stat-card p-4" style="--accent:#10b981; --icon-bg:rgba(16,185,129,.10);">
        <div class="d-flex align-items-center">
          <div class="stat-icon">
            <i class="bi bi-columns-gap"></i>
          </div>

          <div class="ms-3">
            <div class="stat-label">Fields</div>
            <div class="stat-value"><?= number_format((int)$total_cols) ?></div>
          </div>
        </div>

        <div class="stat-note">
          Total columns or variables detected.
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="stat-card p-4" style="--accent:#dc3545; --icon-bg:rgba(220,53,69,.10);">
        <div class="d-flex align-items-center">
          <div class="stat-icon">
            <i class="bi bi-question-circle"></i>
          </div>

          <div class="ms-3">
            <div class="stat-label">Missing</div>
            <div class="stat-value"><?= number_format((int)$total_missing) ?></div>
          </div>
        </div>

        <div class="stat-note">
          Blank or incomplete cells found.
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="stat-card p-4" style="--accent:#f59e0b; --icon-bg:rgba(245,158,11,.12);">
        <div class="d-flex align-items-center">
          <div class="stat-icon">
            <i class="bi bi-diagram-2"></i>
          </div>

          <div class="ms-3">
            <div class="stat-label">Duplicates</div>
            <div class="stat-value"><?= number_format((int)$dups) ?></div>
          </div>
        </div>

        <div class="stat-note">
          Repeated records detected in the file.
        </div>
      </div>
    </div>
  </div>

  <div class="glass-panel mb-4">
    <div class="panel-head">
      <h5 class="panel-title">
        <i class="bi bi-sliders text-success"></i>
        Chart Configuration
      </h5>

      <p class="panel-subtitle">
        Select the category, value, and calculation method you want to visualize.
      </p>
    </div>

    <div class="panel-body">
      <div class="row g-3 align-items-start">
        <div class="col-md-6 col-xl-3">
          <label class="form-label fw-semibold">
            <i class="bi bi-diagram-3 me-2"></i>
            Category (X-Axis)
          </label>
          <select id="xField" class="form-select"></select>
          <div class="field-help">This groups the records, such as by type, name, or location.</div>
        </div>

        <div class="col-md-6 col-xl-3">
          <label class="form-label fw-semibold">
            <i class="bi bi-calculator me-2"></i>
            Values (Y-Axis)
          </label>
          <select id="yField" class="form-select"></select>
          <div class="field-help">Choose a numeric column or count the rows.</div>
        </div>

        <div class="col-md-6 col-xl-3">
          <label class="form-label fw-semibold">
            <i class="bi bi-function me-2"></i>
            Aggregation
          </label>
          <select id="agg" class="form-select">
            <option value="sum">Sum</option>
            <option value="avg">Average</option>
            <option value="count">Count</option>
          </select>
          <div class="field-help">This decides how values are summarized.</div>
        </div>

        <div class="col-md-6 col-xl-3">
          <label class="form-label fw-semibold">
            <i class="bi bi-sort-down me-2"></i>
            Top N
          </label>
          <select id="topn" class="form-select">
            <option>5</option>
            <option selected>10</option>
            <option>20</option>
            <option value="0">All</option>
          </select>
          <div class="field-help">Limit the display to the highest results.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-lg-6">
      <div class="chart-card">
        <div class="chart-head">
          <h6 class="chart-title">
            <i class="bi bi-bar-chart-fill text-success"></i>
            Bar Chart
          </h6>

          <p class="chart-subtitle">
            Best for comparing values across categories.
          </p>
        </div>

        <div class="chart-body">
          <div class="chart-wrap">
            <canvas id="barChart"></canvas>
          </div>

          <div class="simple-note">
            <strong>Guide:</strong> Taller bars represent higher values based on your selected category and aggregation.
          </div>

          <div id="barInsight" class="insight-note">
            <div class="insight-title">
              <i class="bi bi-lightbulb-fill"></i>
              Bar Chart Insight
            </div>
            <p>The analysis will appear here after the chart loads.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="chart-card">
        <div class="chart-head">
          <h6 class="chart-title">
            <i class="bi bi-pie-chart-fill text-success"></i>
            Pie Chart
          </h6>

          <p class="chart-subtitle">
            Best for seeing each category's share of the total.
          </p>
        </div>

        <div class="chart-body">
          <div class="chart-wrap">
            <canvas id="pieChart"></canvas>
          </div>

          <div class="simple-note">
            <strong>Guide:</strong> Bigger slices mean that category has a larger share in the selected data.
          </div>

          <div id="pieInsight" class="insight-note">
            <div class="insight-title">
              <i class="bi bi-lightbulb-fill"></i>
              Pie Chart Insight
            </div>
            <p>The analysis will appear here after the chart loads.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="chart-card mb-4">
    <div class="chart-head">
      <h6 class="chart-title">
        <i class="bi bi-graph-up text-success"></i>
        Trend Line
      </h6>

      <p class="chart-subtitle">
        Uses a date column if available to show changes over time.
      </p>
    </div>

    <div class="chart-body">
      <div class="chart-wrap-line">
        <canvas id="lineChart"></canvas>
      </div>

      <div class="simple-note">
        <strong>Guide:</strong> Higher points show increased values or activity during a specific date.
      </div>

      <div id="lineInsight" class="insight-note">
        <div class="insight-title">
          <i class="bi bi-lightbulb-fill"></i>
          Trend Line Insight
        </div>
        <p>The analysis will appear here after the chart loads.</p>
      </div>
    </div>
  </div>

</div>

<script>
const DATA = <?= json_encode($payload) ?>;

function isNumericType(t){
  return t === 'integer' || t === 'float';
}

function isDateType(t){
  return t === 'date';
}

const xSel = document.getElementById('xField');
const ySel = document.getElementById('yField');
const aggSel = document.getElementById('agg');
const topnSel = document.getElementById('topn');

const barInsight = document.getElementById('barInsight');
const pieInsight = document.getElementById('pieInsight');
const lineInsight = document.getElementById('lineInsight');

DATA.headers.forEach((h, i) => {
  const o1 = new Option(h, i);
  xSel.add(o1);

  if (isNumericType(DATA.types[i])) {
    ySel.add(new Option(h, i));
  }
});

ySel.add(new Option('(count rows)', '__count__'));

let barChart;
let pieChart;
let lineChart;

function formatNumber(value){
  return Number(value).toLocaleString(undefined, {
    maximumFractionDigits: 2
  });
}

function getSelectedText(selectElement){
  return selectElement.options[selectElement.selectedIndex]?.text || '';
}

function getAggregationText(){
  const agg = aggSel.value;
  const yText = getSelectedText(ySel);

  if (agg === 'count' || ySel.value === '__count__') {
    return 'record count';
  }

  if (agg === 'sum') {
    return 'total ' + yText;
  }

  if (agg === 'avg') {
    return 'average ' + yText;
  }

  return yText;
}

function safeText(value){
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function aggregate(){
  const xi = +xSel.value;
  const yi = ySel.value;
  const agg = aggSel.value;
  const topn = +topnSel.value;
  const buckets = {};

  for (const r of DATA.rows){
    const k = (r[xi] ?? '').toString().trim() || '(empty)';

    if (!buckets[k]) {
      buckets[k] = {
        sum:0,
        count:0
      };
    }

    buckets[k].count++;

    if (yi !== '__count__') {
      const v = parseFloat(r[+yi]);

      if (!isNaN(v)) {
        buckets[k].sum += v;
      }
    }
  }

  let entries = Object.entries(buckets).map(([k, v]) => {
    let val = agg === 'count' || yi === '__count__'
      ? v.count
      : (agg === 'avg' ? (v.sum / (v.count || 1)) : v.sum);

    return [k, +val.toFixed(2)];
  });

  entries.sort((a, b) => b[1] - a[1]);

  if (topn > 0) {
    entries = entries.slice(0, topn);
  }

  return {
    labels: entries.map(e => e[0]),
    values: entries.map(e => e[1])
  };
}

function colorPalette(n){
  const base = [
    '#047857',
    '#10b981',
    '#14b8a6',
    '#6ee7b7',
    '#f59e0b',
    '#dc3545',
    '#0f766e',
    '#65a30d',
    '#0891b2',
    '#84cc16'
  ];

  return Array.from({ length:n }, (_, i) => base[i % base.length]);
}

function renderBarPie(){
  const { labels, values } = aggregate();
  const colors = colorPalette(labels.length);
  const aggregationText = getAggregationText();

  if (barChart) {
    barChart.destroy();
  }

  barChart = new Chart(document.getElementById('barChart'), {
    type:'bar',
    data:{
      labels,
      datasets:[{
        label:getSelectedText(ySel),
        data:values,
        backgroundColor:colors,
        borderRadius:8,
        maxBarThickness:42
      }]
    },
    options:{
      responsive:true,
      maintainAspectRatio:false,
      plugins:{
        legend:{
          display:false
        },
        tooltip:{
          callbacks:{
            label:function(context){
              return aggregationText + ': ' + formatNumber(context.raw);
            }
          }
        }
      },
      scales:{
        x:{
          grid:{
            display:false
          },
          ticks:{
            font:{
              size:11
            },
            maxRotation:45
          }
        },
        y:{
          beginAtZero:true,
          ticks:{
            font:{
              size:11
            }
          }
        }
      }
    }
  });

  if (pieChart) {
    pieChart.destroy();
  }

  pieChart = new Chart(document.getElementById('pieChart'), {
    type:'pie',
    data:{
      labels,
      datasets:[{
        data:values,
        backgroundColor:colors,
        borderColor:'#ffffff',
        borderWidth:3
      }]
    },
    options:{
      responsive:true,
      maintainAspectRatio:false,
      plugins:{
        legend:{
          position:'bottom',
          labels:{
            boxWidth:12,
            padding:12,
            font:{
              size:11
            }
          }
        },
        tooltip:{
          callbacks:{
            label:function(context){
              const total = values.reduce((sum, value) => sum + value, 0);
              const percentage = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
              return context.label + ': ' + formatNumber(context.raw) + ' (' + percentage + '%)';
            }
          }
        }
      }
    }
  });

  updateBarPieInsights(labels, values, aggregationText);
}

function updateBarPieInsights(labels, values, aggregationText){
  if (!labels.length || !values.length) {
    barInsight.innerHTML = `
      <div class="insight-title">
        <i class="bi bi-lightbulb-fill"></i>
        Bar Chart Insight
      </div>
      <p>No data is available for the selected chart setup. Try changing the category or value field.</p>
    `;

    pieInsight.innerHTML = `
      <div class="insight-title">
        <i class="bi bi-lightbulb-fill"></i>
        Pie Chart Insight
      </div>
      <p>No distribution data is available for the selected chart setup. Try changing the category or value field.</p>
    `;
    return;
  }

  const total = values.reduce((sum, value) => sum + value, 0);
  const highestLabel = labels[0];
  const highestValue = values[0];
  const highestPercent = total > 0 ? ((highestValue / total) * 100).toFixed(1) : 0;

  const lowestLabel = labels[labels.length - 1];
  const lowestValue = values[values.length - 1];

  barInsight.innerHTML = `
    <div class="insight-title">
      <i class="bi bi-lightbulb-fill"></i>
      Bar Chart Insight
    </div>
    <p>
      The highest displayed category is <strong>${safeText(highestLabel)}</strong> with
      <strong>${formatNumber(highestValue)}</strong> ${safeText(aggregationText)}.
      The lowest displayed category is <strong>${safeText(lowestLabel)}</strong> with
      <strong>${formatNumber(lowestValue)}</strong>.
    </p>
  `;

  pieInsight.innerHTML = `
    <div class="insight-title">
      <i class="bi bi-lightbulb-fill"></i>
      Pie Chart Insight
    </div>
    <p>
      <strong>${safeText(highestLabel)}</strong> has the largest share in the chart,
      representing approximately <strong>${highestPercent}%</strong> of the displayed data.
      Larger slices indicate categories that contribute more to the selected total.
    </p>
  `;
}

function renderLine(){
  const dateIdx = DATA.types.findIndex(isDateType);
  const numIdx = ySel.value === '__count__' ? -1 : +ySel.value;
  const buckets = {};

  if (dateIdx === -1) {
    if (lineChart) {
      lineChart.destroy();
    }

    lineInsight.innerHTML = `
      <div class="insight-title">
        <i class="bi bi-lightbulb-fill"></i>
        Trend Line Insight
      </div>
      <p>No date column was detected, so a trend line cannot be generated for this dataset.</p>
    `;
    return;
  }

  for (const r of DATA.rows){
    const d = (r[dateIdx] || '').toString().slice(0, 10);

    if (!d) {
      continue;
    }

    if (!buckets[d]) {
      buckets[d] = {
        sum:0,
        count:0
      };
    }

    buckets[d].count++;

    if (numIdx >= 0) {
      const v = parseFloat(r[numIdx]);

      if (!isNaN(v)) {
        buckets[d].sum += v;
      }
    }
  }

  const keys = Object.keys(buckets).sort();
  const values = keys.map(k => numIdx >= 0 ? buckets[k].sum : buckets[k].count);

  if (lineChart) {
    lineChart.destroy();
  }

  lineChart = new Chart(document.getElementById('lineChart'), {
    type:'line',
    data:{
      labels:keys,
      datasets:[{
        label:numIdx >= 0 ? 'Sum over time' : 'Count over time',
        data:values,
        borderColor:'#047857',
        backgroundColor:'rgba(16,185,129,.15)',
        pointBackgroundColor:'#047857',
        pointRadius:3,
        borderWidth:2.5,
        fill:true,
        tension:.28
      }]
    },
    options:{
      responsive:true,
      maintainAspectRatio:false,
      plugins:{
        legend:{
          labels:{
            font:{
              size:11
            }
          }
        }
      },
      scales:{
        x:{
          grid:{
            display:false
          },
          ticks:{
            font:{
              size:11
            },
            maxRotation:45
          }
        },
        y:{
          beginAtZero:true,
          ticks:{
            font:{
              size:11
            }
          }
        }
      }
    }
  });

  updateLineInsight(keys, values);
}

function updateLineInsight(keys, values){
  if (!keys.length || !values.length) {
    lineInsight.innerHTML = `
      <div class="insight-title">
        <i class="bi bi-lightbulb-fill"></i>
        Trend Line Insight
      </div>
      <p>No trend data is available. The date column may be empty or unreadable.</p>
    `;
    return;
  }

  let highestIndex = 0;
  let lowestIndex = 0;

  values.forEach((value, index) => {
    if (value > values[highestIndex]) {
      highestIndex = index;
    }

    if (value < values[lowestIndex]) {
      lowestIndex = index;
    }
  });

  const trendDirection = values[values.length - 1] > values[0]
    ? 'increased'
    : (values[values.length - 1] < values[0] ? 'decreased' : 'remained stable');

  lineInsight.innerHTML = `
    <div class="insight-title">
      <i class="bi bi-lightbulb-fill"></i>
      Trend Line Insight
    </div>
    <p>
      The highest point occurred on <strong>${safeText(keys[highestIndex])}</strong> with
      <strong>${formatNumber(values[highestIndex])}</strong>.
      The lowest point occurred on <strong>${safeText(keys[lowestIndex])}</strong> with
      <strong>${formatNumber(values[lowestIndex])}</strong>.
      Overall, the trend <strong>${trendDirection}</strong> from the first date to the last date shown.
    </p>
  `;
}

function renderAll(){
  renderBarPie();
  renderLine();
}

[xSel, ySel, aggSel, topnSel].forEach(s => s.addEventListener('change', renderAll));

renderAll();
</script>

<?php require_once 'includes/footer.php'; ?>
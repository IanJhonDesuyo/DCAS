<?php require_once 'includes/db.php'; require_once 'includes/header.php'; ?>
<h2 class="mb-4"><i class="bi bi-lightbulb me-2" style="color: #ffc107;"></i>System Logic Explanation</h2>
<p class="lead text-muted mb-4">A non-technical walkthrough you can use during presentation or defense.</p>

<div class="row g-4">
  <div class="col-md-6">
    <div class="card metric-card p-5 h-100" style="border-left: 4px solid #dc3545;">
      <h5 class="fw-bold mb-3"><i class="bi bi-exclamation-triangle me-2" style="color: #dc3545;"></i>Why is data cleaning needed?</h5>
      <p>Real-world datasets are messy: typos, blanks, duplicates, mixed formats, and errors. Analyzing dirty data leads to wrong conclusions and misleading insights.</p>
      <p class="text-muted small mb-0"><strong>Solution:</strong> Cleaning makes results trustworthy and actionable.</p>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card metric-card p-5 h-100" style="border-left: 4px solid #ffc107;">
      <h5 class="fw-bold mb-3"><i class="bi bi-question-circle me-2" style="color: #ffc107;"></i>Why handle missing values?</h5>
      <p>Blanks crash calculations or skew averages. You can either drop incomplete rows (when data is plentiful) or fill them with sensible defaults (column average for numbers, most frequent value for text).</p>
      <p class="text-muted small mb-0"><strong>Choice:</strong> Keep, remove, or intelligently fill.</p>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card metric-card p-5 h-100" style="border-left: 4px solid #198754;">
      <h5 class="fw-bold mb-3"><i class="bi bi-diagram-2 me-2" style="color: #198754;"></i>Why remove duplicates?</h5>
      <p>Duplicate rows inflate totals and counts (e.g., a sale counted twice). Removing them ensures every record represents a unique event or entity, making statistics accurate.</p>
      <p class="text-muted small mb-0"><strong>Result:</strong> Correct totals, averages, and trends.</p>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card metric-card p-5 h-100" style="border-left: 4px solid #0d6efd;">
      <h5 class="fw-bold mb-3"><i class="bi bi-shield-check me-2" style="color: #0d6efd;"></i>Why standardize formats?</h5>
      <p>Mixed text cases ("apple" vs "Apple"), date formats ("2023-01-15" vs "01/15/2023"), and stray spaces ("  John  ") cause grouping errors and break analysis. Standardization ensures consistency.</p>
      <p class="text-muted small mb-0"><strong>Example:</strong> "USA", "usa", " USA " now all group as one.</p>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card metric-card p-5 h-100" style="border-left: 4px solid #0dcaf0;">
      <h5 class="fw-bold mb-3"><i class="bi bi-search me-2" style="color: #0dcaf0;"></i>Why filter invalid data?</h5>
      <p>Negative quantities or unparsable dates usually indicate data-entry errors. Removing them prevents nonsense from entering analytics and distorting business decisions.</p>
      <p class="text-muted small mb-0"><strong>Protection:</strong> Only valid, sensible data moves forward.</p>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card metric-card p-5 h-100" style="border-left: 4px solid #198754;">
      <h5 class="fw-bold mb-3"><i class="bi bi-telescope me-2" style="color: #198754;"></i>How does profiling help?</h5>
      <p>Profiling gives an instant summary: How big is the dataset? What types of fields exist? What's missing? Users immediately understand quality and decide which cleaning actions to apply.</p>
      <p class="text-muted small mb-0"><strong>Insight:</strong> Information drives decisions.</p>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card metric-card p-5 h-100" style="border-left: 4px solid #0d6efd;">
      <h5 class="fw-bold mb-3"><i class="bi bi-graph-up me-2" style="color: #0d6efd;"></i>How do insights & charts help?</h5>
      <p>Raw numbers are hard to understand. Charts surface patterns instantly (top categories, trends over time) that would be invisible in a table. Visuals enable data-driven decisions.</p>
      <p class="text-muted small mb-0"><strong>Power:</strong> A picture is worth a thousand rows.</p>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card metric-card p-5 h-100" style="border-left: 4px solid #dc3545;">
      <h5 class="fw-bold mb-3"><i class="bi bi-lock me-2" style="color: #dc3545;"></i>Why track changes?</h5>
      <p>Every cleaning action is logged with details: what changed, how many rows affected. This transparency allows you to justify decisions and revert if needed.</p>
      <p class="text-muted small mb-0"><strong>Accountability:</strong> See exactly what happened.</p>
    </div>
  </div>
</div>

<div class="row g-4 mt-0">
  <div class="col-12">
    <div class="card metric-card p-5" style="background: linear-gradient(135deg, rgba(13, 110, 253, 0.05), rgba(11, 94, 215, 0.05)); border-left: 4px solid #0d6efd;">
      <h5 class="fw-bold mb-3"><i class="bi bi-diagram-3 me-2" style="color: #0d6efd;"></i>The Complete Workflow</h5>
      <p class="mb-3">This system implements a <strong>professional data pipeline</strong>:</p>
      <div class="row g-3">
        <div class="col-md-4">
          <div style="text-align: center;">
            <span class="badge" style="width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0d6efd, #0b5ed7); font-size: 1.2rem; margin-bottom: 0.5rem;">📤</span>
            <h6 class="fw-bold">Upload</h6>
            <p class="text-muted small">Raw, messy data enters here</p>
          </div>
        </div>
        <div class="col-md-4">
          <div style="text-align: center;">
            <span class="badge" style="width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0d6efd, #0b5ed7); font-size: 1.2rem; margin-bottom: 0.5rem;">🔍</span>
            <h6 class="fw-bold">Profile</h6>
            <p class="text-muted small">Understand structure and quality</p>
          </div>
        </div>
        <div class="col-md-4">
          <div style="text-align: center;">
            <span class="badge" style="width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0d6efd, #0b5ed7); font-size: 1.2rem; margin-bottom: 0.5rem;">🧹</span>
            <h6 class="fw-bold">Clean</h6>
            <p class="text-muted small">Apply fixes transparently</p>
          </div>
        </div>
        <div class="col-md-6">
          <div style="text-align: center;">
            <span class="badge" style="width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #198754, #1e7e34); font-size: 1.2rem; margin-bottom: 0.5rem;">📊</span>
            <h6 class="fw-bold">Analyze</h6>
            <p class="text-muted small">Compare, extract insights, measure impact</p>
          </div>
        </div>
        <div class="col-md-6">
          <div style="text-align: center;">
            <span class="badge" style="width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #198754, #1e7e34); font-size: 1.2rem; margin-bottom: 0.5rem;">📈</span>
            <h6 class="fw-bold">Visualize</h6>
            <p class="text-muted small">Interactive charts, dashboards, decisions</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
<?php require_once 'includes/footer.php'; ?>

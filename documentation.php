<?php require_once 'includes/db.php'; require_once 'includes/header.php'; ?>
<h2 class="mb-4"><i class="bi bi-book me-2" style="color: #0d6efd;"></i>Documentation</h2>

<div class="card metric-card p-5 mb-4">
  <h4 class="fw-bold mb-3"><i class="bi bi-gear me-2" style="color: #0d6efd;"></i>System Features</h4>
  <ul class="list-unstyled">
    <li class="mb-2"><i class="bi bi-check-circle me-2" style="color: #198754;"></i><strong>Upload CSV</strong> — Load datasets and built-in samples</li>
    <li class="mb-2"><i class="bi bi-check-circle me-2" style="color: #198754;"></i><strong>Auto-Profile</strong> — Analyze rows, columns, types, missing values, duplicates</li>
    <li class="mb-2"><i class="bi bi-check-circle me-2" style="color: #198754;"></i><strong>Smart Cleaning</strong> — Remove duplicates, fix missing values, standardize formats</li>
    <li class="mb-2"><i class="bi bi-check-circle me-2" style="color: #198754;"></i><strong>Compare Datasets</strong> — Side-by-side original vs cleaned with impact metrics</li>
    <li class="mb-2"><i class="bi bi-check-circle me-2" style="color: #198754;"></i><strong>Generate Insights</strong> — AI-powered summaries of discovered patterns</li>
    <li><i class="bi bi-check-circle me-2" style="color: #198754;"></i><strong>Interactive Visualizations</strong> — Charts.js dashboard with bar, pie, and trend lines</li>
  </ul>
</div>

<div class="card metric-card p-5 mb-4">
  <h4 class="fw-bold mb-3"><i class="bi bi-sparkles me-2" style="color: #ffc107;"></i>Cleaning Methods</h4>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead style="background: linear-gradient(135deg, #f1f5f9, #e9ecef);">
        <tr>
          <th><i class="bi bi-wrench me-2"></i>Method</th>
          <th><i class="bi bi-lightning me-2"></i>What it does</th>
          <th><i class="bi bi-lightbulb me-2"></i>Why it matters</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong><i class="bi bi-diagram-2 me-1"></i>Remove duplicates</strong></td>
          <td>Drops exact-duplicate rows</td>
          <td>Prevents inflated counts and biased statistics</td>
        </tr>
        <tr>
          <td><strong><i class="bi bi-funnel me-1"></i>Handle missing</strong></td>
          <td>Remove rows OR fill (avg/frequent)</td>
          <td>Avoids errors and skewed analysis</td>
        </tr>
        <tr>
          <td><strong><i class="bi bi-diagram-3 me-1"></i>Convert types</strong></td>
          <td>Normalize numeric columns</td>
          <td>Required for math and aggregations</td>
        </tr>
        <tr>
          <td><strong><i class="bi bi-scissors me-1"></i>Standardize text</strong></td>
          <td>Trim whitespace and capitalization</td>
          <td>Prevents "Apple" ≠ " apple " mismatches</td>
        </tr>
        <tr>
          <td><strong><i class="bi bi-calendar me-1"></i>Standardize dates</strong></td>
          <td>Reformat to YYYY-MM-DD</td>
          <td>Enables time-series analysis</td>
        </tr>
        <tr>
          <td><strong><i class="bi bi-exclamation-circle me-1"></i>Filter invalid</strong></td>
          <td>Remove negative amounts and bad dates</td>
          <td>Removes data-entry errors</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-md-6">
    <div class="card metric-card p-4">
      <h5 class="fw-bold mb-3"><i class="bi bi-arrow-repeat me-2" style="color: #0d6efd;"></i>Workflow Steps</h5>
      <ol class="mb-0">
        <li class="mb-3"><strong>Upload</strong><br><span class="text-muted small">Get raw data into the system</span></li>
        <li class="mb-3"><strong>Profile</strong><br><span class="text-muted small">Understand data structure and quality</span></li>
        <li class="mb-3"><strong>Clean</strong><br><span class="text-muted small">Apply transparent fixes transparently</span></li>
        <li class="mb-3"><strong>Analyze</strong><br><span class="text-muted small">Quantify impact and extract insights</span></li>
        <li><strong>Visualize</strong><br><span class="text-muted small">Communicate findings via charts</span></li>
      </ol>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card metric-card p-4">
      <h5 class="fw-bold mb-3"><i class="bi bi-stars me-2" style="color: #ffc107;"></i>Sample Dataset</h5>
      <p>The bundled <code style="background: #f0f0f0; padding: 0.2rem 0.5rem; border-radius: 4px;">sales_sample.csv</code> contains 20 sales records with intentional issues:</p>
      <ul class="mb-3">
        <li>Duplicate rows</li>
        <li>Missing values</li>
        <li>Mixed date formats</li>
        <li>Inconsistent capitalization</li>
        <li>Stray whitespace</li>
      </ul>
      <p class="mb-0 text-muted small">Use it to demonstrate every cleaning operation during your defense.</p>
    </div>
  </div>
</div>

<div class="card metric-card p-5">
  <h4 class="fw-bold mb-4"><i class="bi bi-mortarboard me-2" style="color: #0d6efd;"></i>Defense Walkthrough</h4>
  <div class="row g-3">
    <div class="col-md-6">
      <div style="background: linear-gradient(135deg, rgba(13, 110, 253, 0.05), rgba(11, 94, 215, 0.05)); border-left: 4px solid #0d6efd; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <h6 class="fw-bold mb-2"><span class="badge text-bg-primary">Step 1</span> Load Sample</h6>
        <p class="text-muted small mb-0">Go to Upload → Click "Use Sample Dataset" to load demo data</p>
      </div>
      <div style="background: linear-gradient(135deg, rgba(13, 110, 253, 0.05), rgba(11, 94, 215, 0.05)); border-left: 4px solid #0d6efd; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <h6 class="fw-bold mb-2"><span class="badge text-bg-primary">Step 2</span> Analyze Profile</h6>
        <p class="text-muted small mb-0">On Profile page, highlight detected types, missing values, duplicates</p>
      </div>
      <div style="background: linear-gradient(135deg, rgba(13, 110, 253, 0.05), rgba(11, 94, 215, 0.05)); border-left: 4px solid #0d6efd; padding: 1rem; border-radius: 8px;">
        <h6 class="fw-bold mb-2"><span class="badge text-bg-primary">Step 3</span> Apply Cleaning</h6>
        <p class="text-muted small mb-0">On Clean page, enable all options and apply. Show detailed logs</p>
      </div>
    </div>
    <div class="col-md-6">
      <div style="background: linear-gradient(135deg, rgba(25, 135, 84, 0.05), rgba(20, 108, 67, 0.05)); border-left: 4px solid #198754; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <h6 class="fw-bold mb-2"><span class="badge text-bg-success">Step 4</span> Show Insights</h6>
        <p class="text-muted small mb-0">On Analyze page, explain insights and side-by-side comparison</p>
      </div>
      <div style="background: linear-gradient(135deg, rgba(25, 135, 84, 0.05), rgba(20, 108, 67, 0.05)); border-left: 4px solid #198754; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <h6 class="fw-bold mb-2"><span class="badge text-bg-success">Step 5</span> Interactive Charts</h6>
        <p class="text-muted small mb-0">On Dashboard, change X/Y selectors to show interactive visualizations</p>
      </div>
      <div style="background: linear-gradient(135deg, rgba(25, 135, 84, 0.05), rgba(20, 108, 67, 0.05)); border-left: 4px solid #198754; padding: 1rem; border-radius: 8px;">
        <h6 class="fw-bold mb-2"><span class="badge text-bg-success">Outro</span> System Logic</h6>
        <p class="text-muted small mb-0">Link to "System Logic" for non-technical explanations</p>
      </div>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
<?php require_once 'includes/footer.php'; ?>

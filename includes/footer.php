  </main>
</div>

<footer class="cleansight-footer">
  <div class="footer-inner">
    <div>
      <strong>CleanSight</strong>
      <span>Data Cleaning and Analytics System</span>
    </div>

    <div>
      &copy; <?= date('Y') ?> Academic Project
    </div>
  </div>
</footer>

<style>
.cleansight-footer{
  border-top:1px solid rgba(15,23,42,.08);
  background:
    radial-gradient(420px 140px at 10% 0%, rgba(110,231,183,.14), transparent 60%),
    linear-gradient(180deg, rgba(255,255,255,.94), rgba(240,253,244,.96));
  color:#64748b;
  padding:1rem 1.5rem;
}

.footer-inner{
  max-width:1200px;
  margin:0 auto;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:1rem;
  font-size:.86rem;
}

.footer-inner strong{
  color:#047857;
  margin-right:.35rem;
}

.footer-inner span{
  color:#475569;
}

@media (max-width: 576px){
  .footer-inner{
    flex-direction:column;
    text-align:center;
  }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.alert.auto-dismiss').forEach(a => {
    setTimeout(() => {
      a.classList.remove('show');
      a.classList.add('fade');
    }, 5000);
  });
});
</script>

</body>
</html>
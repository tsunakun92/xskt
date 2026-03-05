const cron = require('node-cron');
const { spawn } = require('child_process');

// Entry: cấu hình cron để tự động crawl + reconcile 3 miền mỗi ngày sau giờ quay.
// Thời gian có thể chỉnh lại tùy ý:
// - XSMN: quay ~16h10 → chạy 16:40
// - XSMT: quay ~17h15 → chạy 17:45
// - XSMB: quay ~18h15 → chạy 18:45

console.log('[xsk-crawler] Scheduler khởi động...');

// cron format: 'm h dom mon dow'

function runJob(label, scriptPath) {
  console.log(`[xsk-crawler] Chạy job ${label} ...`);

  const child = spawn('node', [scriptPath], {
    stdio: 'inherit',
    shell: process.platform === 'win32'
  });

  child.on('exit', (code) => {
    console.log(`[xsk-crawler] Job ${label} kết thúc với exit code ${code}`);
  });
}

// 1) Miền Nam: crawl 3 nguồn + reconcile
cron.schedule('40 16 * * *', () => {
  // nguồn chính
  runJob('crawlXsmnJob (xoso)', 'src/jobs/crawlXsmnJob.js');
  // nguồn phụ
  runJob('crawlXsmnAz24Job', 'src/jobs/crawlXsmnAz24Job.js');
  runJob('crawlXsmnDaiPhatJob', 'src/jobs/crawlXsmnDaiPhatJob.js');
  // reconcile sau cùng
  runJob('reconcileXsmnJob', 'src/jobs/reconcileXsmnJob.js');
});

// 2) Miền Trung: crawl 3 nguồn + reconcile
cron.schedule('45 17 * * *', () => {
  runJob('crawlXsmtJob (xoso)', 'src/jobs/crawlXsmtJob.js');
  runJob('crawlXsmtAz24Job', 'src/jobs/crawlXsmtAz24Job.js');
  runJob('crawlXsmtDaiPhatJob', 'src/jobs/crawlXsmtDaiPhatJob.js');
  runJob('reconcileXsmtJob', 'src/jobs/reconcileXsmtJob.js');
});

// 3) Miền Bắc: crawl 3 nguồn + reconcile
cron.schedule('45 18 * * *', () => {
  runJob('crawlXsmbJob (xoso)', 'src/jobs/crawlXsmbJob.js');
  runJob('crawlXsmbAz24Job', 'src/jobs/crawlXsmbAz24Job.js');
  runJob('crawlXsmbDaiPhatJob', 'src/jobs/crawlXsmbDaiPhatJob.js');
  runJob('reconcileXsmbJob', 'src/jobs/reconcileXsmbJob.js');
});



const { spawn } = require('child_process');
const db = require('../config/db');

function formatYmd(date) {
  const yyyy = date.getFullYear();
  const mm = String(date.getMonth() + 1).padStart(2, '0');
  const dd = String(date.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}

async function getNextDateToBackfill() {
  const rows = await db.query('SELECT MIN(draw_date) AS min_date FROM draws');
  const today = new Date();

  if (!rows[0].min_date) {
    // Chưa có dữ liệu nào -> bắt đầu từ hôm nay
    return formatYmd(today);
  }

  const minDate = new Date(rows[0].min_date);
  minDate.setDate(minDate.getDate() - 1);
  return formatYmd(minDate);
}

function runJobWithDate(scriptPath, drawDate) {
  return new Promise((resolve) => {
    const env = { ...process.env, DRAW_DATE: drawDate };
    const child = spawn('node', [scriptPath], {
      // Chỉ giữ stderr, ẩn stdout để log gọn, tránh spam
      stdio: ['ignore', 'ignore', 'inherit'],
      shell: process.platform === 'win32',
      env
    });

    child.on('exit', (code) => resolve(code));
  });
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function loop() {
  const MIN_DATE = '2015-01-01';

  // chạy vô hạn tới khi bạn Ctrl+C
  // mỗi vòng xử lý 1 ngày (3 miền + reconcile)
  while (true) {
    const nextDate = await getNextDateToBackfill();

    if (nextDate < MIN_DATE) {
      console.log(`${nextDate} | DONE (đã chạm MIN_DATE ${MIN_DATE})`);
      process.exit(0);
    }

    let mnOk = true;
    let mtOk = true;
    let mbOk = true;

    // Miền Nam – thử tối đa 3 lần
    // Ưu tiên 1 nguồn chính (xosoComVn). Chỉ khi lỗi mới gọi thêm nguồn backup (az24, DaiPhat).
    for (let i = 0; i < 3; i++) {
      mnOk = true;
      const mainCode = await runJobWithDate('src/jobs/crawlXsmnJob.js', nextDate);

      if (mainCode !== 0) {
        // Thử nguồn backup 1: az24
        const azCode = await runJobWithDate('src/jobs/crawlXsmnAz24Job.js', nextDate);
        if (azCode !== 0) {
          // Thử thêm nguồn backup 2: DaiPhat (chỉ khi cả 2 nguồn trước đều lỗi)
          const dpCode = await runJobWithDate('src/jobs/crawlXsmnDaiPhatJob.js', nextDate);
          if (dpCode !== 0) mnOk = false;
        }
      }

      if ((await runJobWithDate('src/jobs/reconcileXsmnJob.js', nextDate)) !== 0) mnOk = false;
      if (mnOk) break;
    }

    // Miền Trung – thử tối đa 3 lần
    // Cũng ưu tiên nguồn chính (xosoComVn), chỉ fallback sang az24 / DaiPhat khi lỗi.
    for (let i = 0; i < 3; i++) {
      mtOk = true;
      const mainCode = await runJobWithDate('src/jobs/crawlXsmtJob.js', nextDate);

      if (mainCode !== 0) {
        const azCode = await runJobWithDate('src/jobs/crawlXsmtAz24Job.js', nextDate);
        if (azCode !== 0) {
          const dpCode = await runJobWithDate('src/jobs/crawlXsmtDaiPhatJob.js', nextDate);
          if (dpCode !== 0) mtOk = false;
        }
      }

      if ((await runJobWithDate('src/jobs/reconcileXsmtJob.js', nextDate)) !== 0) mtOk = false;
      if (mtOk) break;
    }

    // Miền Bắc – thử tối đa 3 lần
    // Tương tự: chỉ dùng az24 / DaiPhat khi nguồn chính lỗi.
    for (let i = 0; i < 3; i++) {
      mbOk = true;
      const mainCode = await runJobWithDate('src/jobs/crawlXsmbJob.js', nextDate);

      if (mainCode !== 0) {
        const azCode = await runJobWithDate('src/jobs/crawlXsmbAz24Job.js', nextDate);
        if (azCode !== 0) {
          const dpCode = await runJobWithDate('src/jobs/crawlXsmbDaiPhatJob.js', nextDate);
          if (dpCode !== 0) mbOk = false;
        }
      }

      if ((await runJobWithDate('src/jobs/reconcileXsmbJob.js', nextDate)) !== 0) mbOk = false;
      if (mbOk) break;
    }

    const fmt = (ok) => (ok ? 'OK ' : 'ERR');
    console.log(`${nextDate} | ${fmt(mbOk)}    | ${fmt(mtOk)}    | ${fmt(mnOk)}`);

    if (!mbOk || !mtOk || !mnOk) {
      console.error(
        `Lỗi backfill tại ngày ${nextDate}: MB=${fmt(mbOk).trim()}, MT=${fmt(mtOk).trim()}, MN=${fmt(mnOk).trim()}`
      );
      process.exit(1);
    }

    await sleep(2000);
  }
}

loop().catch((e) => {
  console.error('[crawlAuto] Lỗi không mong muốn:', e.message);
  process.exit(1);
});



const { spawn } = require('child_process');
const db = require('../config/db');

function runJobWithDate(scriptPath, drawDate) {
  return new Promise((resolve) => {
    const env = { ...process.env, DRAW_DATE: drawDate };
    const child = spawn('node', [scriptPath], {
      stdio: ['ignore', 'ignore', 'inherit'],
      shell: process.platform === 'win32',
      env
    });

    child.on('exit', (code) => resolve(code));
  });
}

async function retryForDraw(region, drawDate) {
  if (region === 'MN') {
    console.log(`[retryPendingDraws] Retry XSMN cho ngày ${drawDate}`);

    const mainCode = await runJobWithDate('src/jobs/crawlXsmnJob.js', drawDate);
    if (mainCode !== 0) {
      const azCode = await runJobWithDate('src/jobs/crawlXsmnAz24Job.js', drawDate);
      if (azCode !== 0) {
        const dpCode = await runJobWithDate('src/jobs/crawlXsmnDaiPhatJob.js', drawDate);
        if (dpCode !== 0) {
          console.error(
            `[retryPendingDraws] XSMN ${drawDate}: tất cả nguồn crawl đều lỗi (main/az24/daiphat).`
          );
        }
      }
    }
    await runJobWithDate('src/jobs/reconcileXsmnJob.js', drawDate);
    return;
  }

  if (region === 'MT') {
    console.log(`[retryPendingDraws] Retry XSMT cho ngày ${drawDate}`);

    const mainCode = await runJobWithDate('src/jobs/crawlXsmtJob.js', drawDate);
    if (mainCode !== 0) {
      const azCode = await runJobWithDate('src/jobs/crawlXsmtAz24Job.js', drawDate);
      if (azCode !== 0) {
        const dpCode = await runJobWithDate('src/jobs/crawlXsmtDaiPhatJob.js', drawDate);
        if (dpCode !== 0) {
          console.error(
            `[retryPendingDraws] XSMT ${drawDate}: tất cả nguồn crawl đều lỗi (main/az24/daiphat).`
          );
        }
      }
    }
    await runJobWithDate('src/jobs/reconcileXsmtJob.js', drawDate);
    return;
  }

  if (region === 'MB') {
    console.log(`[retryPendingDraws] Retry XSMB cho ngày ${drawDate}`);

    const mainCode = await runJobWithDate('src/jobs/crawlXsmbJob.js', drawDate);
    if (mainCode !== 0) {
      const azCode = await runJobWithDate('src/jobs/crawlXsmbAz24Job.js', drawDate);
      if (azCode !== 0) {
        const dpCode = await runJobWithDate('src/jobs/crawlXsmbDaiPhatJob.js', drawDate);
        if (dpCode !== 0) {
          console.error(
            `[retryPendingDraws] XSMB ${drawDate}: tất cả nguồn crawl đều lỗi (main/az24/daiphat).`
          );
        }
      }
    }
    await runJobWithDate('src/jobs/reconcileXsmbJob.js', drawDate);
  }
}

async function run() {
  // Lấy tất cả draws đang ở trạng thái collecting_live hoặc disputed
  const rows = await db.query(
    `SELECT id, region, draw_date
     FROM draws
     WHERE status IN ('collecting_live', 'disputed')
     ORDER BY draw_date, region`
  );

  if (!rows.length) {
    console.log('[retryPendingDraws] Không có draw nào ở trạng thái collecting_live/disputed.');
    process.exit(0);
  }

  console.log(
    `[retryPendingDraws] Tìm thấy ${rows.length} draw cần retry (status collecting_live/disputed).`
  );

  for (const row of rows) {
    const drawDate = row.draw_date.toISOString().slice(0, 10);
    try {
      await retryForDraw(row.region, drawDate);
    } catch (e) {
      console.error(
        `[retryPendingDraws] Lỗi khi retry region=${row.region}, date=${drawDate}:`,
        e.message
      );
    }
  }

  console.log('[retryPendingDraws] Hoàn tất.');
  process.exit(0);
}

run().catch((e) => {
  console.error('[retryPendingDraws] Lỗi không mong muốn:', e.message);
  process.exit(1);
});



const { fetchDaiPhatXsmbForDate } = require('../sources/xosodaiphat');
const { sanityCheckXsmb } = require('../core/sanityCheck');
const { findOrCreateXsmbDraw, saveSnapshot } = require('../core/reconciler');
const db = require('../config/db');

/**
 * Job: crawl XSMB từ xosodaiphat.com cho ngày hôm nay và lưu snapshot (không ghi vào results).
 */
async function run() {
  const overrideDate = process.env.DRAW_DATE;
  let drawDate;
  if (overrideDate) {
    drawDate = overrideDate;
  } else {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    drawDate = `${yyyy}-${mm}-${dd}`;
  }

  console.log(`[crawlXsmbDaiPhatJob] Bắt đầu crawl XSMB (Đại Phát) cho ngày ${drawDate}`);

  try {
    await db.query('SELECT 1');
  } catch (e) {
    console.error('[crawlXsmbDaiPhatJob] Lỗi kết nối MySQL:', e.message);
    process.exit(1);
  }

  const draw = await findOrCreateXsmbDraw(drawDate);

  try {
    const { entries, rawHtml } = await fetchDaiPhatXsmbForDate(drawDate);

    const sanityOk = sanityCheckXsmb(entries);
    console.log(`[crawlXsmbDaiPhatJob] sanityOk = ${sanityOk}, entries = ${entries.length}`);

    // CẦN chỉnh sourceId theo id thật của nguồn 'xosodaiphat_com' trong bảng sources
    const sourceId = 3;

    await saveSnapshot({
      drawId: draw.id,
      sourceId,
      rawPayload: rawHtml,
      parsedResult: entries,
      sanityOk
    });

    if (!sanityOk || entries.length === 0) {
      console.warn('[crawlXsmbDaiPhatJob] Dữ liệu chưa hợp lệ hoặc rỗng, chỉ lưu snapshot.');
    } else {
      console.log('[crawlXsmbDaiPhatJob] Đã lưu snapshot XSMB Đại Phát với dữ liệu hợp lệ (chưa ghi vào results).');
    }
  } catch (err) {
    console.error('[crawlXsmbDaiPhatJob] Lỗi trong quá trình crawl:', err.message);
  } finally {
    process.exit(0);
  }
}

run();



const { fetchDaiPhatXsmtForDate } = require('../sources/xosodaiphat');
const { sanityCheckXsmb } = require('../core/sanityCheck');
const { findOrCreateXsmtDraw, saveSnapshot } = require('../core/reconciler');
const db = require('../config/db');

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

  console.log(`[crawlXsmtDaiPhatJob] Bắt đầu crawl XSMT (Đại Phát) cho ngày ${drawDate}`);

  try {
    await db.query('SELECT 1');
  } catch (e) {
    console.error('[crawlXsmtDaiPhatJob] Lỗi kết nối MySQL:', e.message);
    process.exit(1);
  }

  const draw = await findOrCreateXsmtDraw(drawDate);

  try {
    const { entries, rawHtml } = await fetchDaiPhatXsmtForDate(drawDate);
    const sanityOk = sanityCheckXsmb(entries);
    console.log(`[crawlXsmtDaiPhatJob] sanityOk = ${sanityOk}, entries = ${entries.length}`);

    // chỉnh theo id thực của 'xosodaiphat_com' trong bảng sources
    const sourceId = 3;

    await saveSnapshot({
      drawId: draw.id,
      sourceId,
      rawPayload: rawHtml,
      parsedResult: entries,
      sanityOk
    });

    if (!sanityOk || entries.length === 0) {
      console.warn('[crawlXsmtDaiPhatJob] Dữ liệu chưa hợp lệ hoặc rỗng, chỉ lưu snapshot.');
    } else {
      console.log('[crawlXsmtDaiPhatJob] Đã lưu snapshot XSMT Đại Phát với dữ liệu hợp lệ (chưa ghi vào results).');
    }
  } catch (err) {
    console.error('[crawlXsmtDaiPhatJob] Lỗi trong quá trình crawl:', err.message);
  } finally {
    process.exit(0);
  }
}

run();



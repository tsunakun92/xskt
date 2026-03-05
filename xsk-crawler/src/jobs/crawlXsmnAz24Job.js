const { fetchAz24XsmnForDate } = require('../sources/az24');
const { sanityCheckXsmb } = require('../core/sanityCheck');
const { findOrCreateXsmnDraw, saveSnapshot } = require('../core/reconciler');
const db = require('../config/db');

/**
 * Job: crawl XSMN từ az24.vn cho ngày hôm nay và lưu snapshot (không ghi vào results).
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

  console.log(`[crawlXsmnAz24Job] Bắt đầu crawl XSMN (az24) cho ngày ${drawDate}`);

  try {
    await db.query('SELECT 1');
  } catch (e) {
    console.error('[crawlXsmnAz24Job] Lỗi kết nối MySQL:', e.message);
    process.exit(1);
  }

  const draw = await findOrCreateXsmnDraw(drawDate);

  try {
    const { entries, rawHtml } = await fetchAz24XsmnForDate(drawDate);

    const sanityOk = sanityCheckXsmb(entries);
    console.log(`[crawlXsmnAz24Job] sanityOk = ${sanityOk}, entries = ${entries.length}`);

    await saveSnapshot({
      drawId: draw.id,
      sourceId: 2, // giả sử az24_vn có id = 2 trong bảng sources
      rawPayload: rawHtml,
      parsedResult: entries,
      sanityOk
    });

    if (!sanityOk || entries.length === 0) {
      console.warn('[crawlXsmnAz24Job] Dữ liệu chưa hợp lệ hoặc rỗng, chỉ lưu snapshot.');
    } else {
      console.log('[crawlXsmnAz24Job] Đã lưu snapshot XSMN az24 với dữ liệu hợp lệ (chưa ghi vào results).');
    }
  } catch (err) {
    console.error('[crawlXsmnAz24Job] Lỗi trong quá trình crawl:', err.message);
  } finally {
    process.exit(0);
  }
}

run();



const { fetchXsmnForDate } = require('../sources/xosoComVn');
const { sanityCheckXsmb } = require('../core/sanityCheck');
const { findOrCreateXsmnDraw, saveSnapshot, saveXsmbResultsConfirmed } = require('../core/reconciler');
const db = require('../config/db');

/**
 * Job: crawl XSMN cho ngày hôm nay từ xoso.com.vn và lưu vào DB.
 * Tạm thời gom thành 1 draw chung (region MN, station XSMN).
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

  console.log(`[crawlXsmnJob] Bắt đầu crawl XSMN cho ngày ${drawDate}`);

  try {
    await db.query('SELECT 1');
  } catch (e) {
    console.error('[crawlXsmnJob] Lỗi kết nối MySQL:', e.message);
    process.exit(1);
  }

  const draw = await findOrCreateXsmnDraw(drawDate);

  try {
    const { entries, rawHtml } = await fetchXsmnForDate(drawDate);

    const sanityOk = sanityCheckXsmb(entries);
    console.log(`[crawlXsmnJob] sanityOk = ${sanityOk}, entries = ${entries.length}`);

    await saveSnapshot({
      drawId: draw.id,
      sourceId: 1, // xoso_com_vn
      rawPayload: rawHtml,
      parsedResult: entries,
      sanityOk
    });

    if (sanityOk && entries.length > 0) {
      await saveXsmbResultsConfirmed(draw.id, entries);
      console.log('[crawlXsmnJob] Đã lưu kết quả XSMN (single_source_weighted).');
    } else {
      console.warn('[crawlXsmnJob] Dữ liệu chưa hợp lệ, chỉ lưu snapshot.');
    }
  } catch (err) {
    console.error('[crawlXsmnJob] Lỗi trong quá trình crawl:', err.message);
  } finally {
    process.exit(0);
  }
}

run();



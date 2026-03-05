const { fetchXsmtForDate } = require('../sources/xosoComVn');
const { sanityCheckXsmb } = require('../core/sanityCheck');
const { findOrCreateXsmtDraw, saveSnapshot, saveXsmbResultsConfirmed } = require('../core/reconciler');
const db = require('../config/db');

/**
 * Job đơn giản: crawl XSMT cho ngày hôm nay từ xoso.com.vn và lưu vào DB.
 * Tạm thời:
 *  - Gom kết quả miền Trung thành 1 draw chung (region MT, station XSMT).
 *  - Dùng chung sanityCheck với XSMB (định dạng giải tương tự).
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

  console.log(`[crawlXsmtJob] Bắt đầu crawl XSMT cho ngày ${drawDate}`);

  try {
    await db.query('SELECT 1');
  } catch (e) {
    console.error('[crawlXsmtJob] Lỗi kết nối MySQL:', e.message);
    process.exit(1);
  }

  const draw = await findOrCreateXsmtDraw(drawDate);

  try {
    const { entries, rawHtml } = await fetchXsmtForDate(drawDate);

    const sanityOk = sanityCheckXsmb(entries);
    console.log(`[crawlXsmtJob] sanityOk = ${sanityOk}, entries = ${entries.length}`);

    await saveSnapshot({
      drawId: draw.id,
      sourceId: 1, // xoso_com_vn
      rawPayload: rawHtml,
      parsedResult: entries,
      sanityOk
    });

    if (sanityOk && entries.length > 0) {
      await saveXsmbResultsConfirmed(draw.id, entries);
      console.log('[crawlXsmtJob] Đã lưu kết quả XSMT (single_source_weighted).');
    } else {
      console.warn('[crawlXsmtJob] Dữ liệu chưa hợp lệ, chỉ lưu snapshot.');
    }
  } catch (err) {
    console.error('[crawlXsmtJob] Lỗi trong quá trình crawl:', err.message);
  } finally {
    process.exit(0);
  }
}

run();



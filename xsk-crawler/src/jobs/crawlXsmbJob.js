const { fetchXsmbForDate } = require('../sources/xosoComVn');
const { sanityCheckXsmb } = require('../core/sanityCheck');
const { findOrCreateXsmbDraw, saveSnapshot, saveXsmbResultsConfirmed } = require('../core/reconciler');
const db = require('../config/db');

/**
 * Job đơn giản: crawl XSMB cho ngày hôm nay từ xoso.com.vn và lưu vào DB.
 * Hiện tại:
 *  - Gọi HTTP lấy HTML
 *  - (chưa) parse entries thực sự – bạn cần hoàn thiện trong xosoComVn.fetchXsmbForDate
 *  - Chạy sanity check
 *  - Lưu snapshot + nếu hợp lệ thì lưu results dạng "confirmed" đơn nguồn
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

  console.log(`[crawlXsmbJob] Bắt đầu crawl XSMB cho ngày ${drawDate}`);

  let connectionOk = false;
  try {
    await db.query('SELECT 1');
    connectionOk = true;
  } catch (e) {
    console.error('[crawlXsmbJob] Lỗi kết nối MySQL:', e.message);
    process.exit(1);
  }
  if (!connectionOk) return;

  const draw = await findOrCreateXsmbDraw(drawDate);

  try {
    const { entries, rawHtml } = await fetchXsmbForDate(drawDate);

    const sanityOk = sanityCheckXsmb(entries);
    console.log(`[crawlXsmbJob] sanityOk = ${sanityOk}, entries = ${entries.length}`);

    // Tạm thời lưu raw HTML (có thể rất lớn – dùng MEDIUMTEXT)
    await saveSnapshot({
      drawId: draw.id,
      sourceId: 1, // id nguồn xoso_com_vn trong bảng sources (insert trong schema.sql)
      rawPayload: rawHtml,
      parsedResult: entries,
      sanityOk
    });

    if (sanityOk && entries.length > 0) {
      await saveXsmbResultsConfirmed(draw.id, entries);
      console.log('[crawlXsmbJob] Đã lưu kết quả XSMB (single_source_weighted).');
    } else {
      console.warn('[crawlXsmbJob] Dữ liệu chưa hợp lệ, chỉ lưu snapshot.');
    }
  } catch (err) {
    console.error('[crawlXsmbJob] Lỗi trong quá trình crawl:', err.message);
  } finally {
    process.exit(0);
  }
}

run();



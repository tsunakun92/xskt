const { fetchMinhNgocXsmtForDate } = require('../sources/minhngoc');
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

  console.log(`[crawlXsmtMinhNgocJob] Bắt đầu crawl XSMT (MinhNgoc) cho ngày ${drawDate}`);

  try {
    await db.query('SELECT 1');
  } catch (e) {
    console.error('[crawlXsmtMinhNgocJob] Lỗi kết nối MySQL:', e.message);
    process.exit(1);
  }

  const draw = await findOrCreateXsmtDraw(drawDate);

  try {
    const { entries, rawHtml } = await fetchMinhNgocXsmtForDate(drawDate);

    const sanityOk = sanityCheckXsmb(entries);
    console.log(
      `[crawlXsmtMinhNgocJob] sanityOk = ${sanityOk}, entries = ${entries.length}`
    );

    await saveSnapshot({
      drawId: draw.id,
      sourceId: 4, // minh_ngoc_net dự kiến id=4
      rawPayload: rawHtml,
      parsedResult: entries,
      sanityOk
    });

    if (!sanityOk || entries.length === 0) {
      console.warn(
        '[crawlXsmtMinhNgocJob] Dữ liệu chưa hợp lệ hoặc rỗng, chỉ lưu snapshot.'
      );
    } else {
      console.log(
        '[crawlXsmtMinhNgocJob] Đã lưu snapshot XSMT MinhNgoc với dữ liệu hợp lệ (chưa ghi vào results).'
      );
    }
  } catch (err) {
    console.error('[crawlXsmtMinhNgocJob] Lỗi trong quá trình crawl:', err.message);
  } finally {
    process.exit(0);
  }
}

run();



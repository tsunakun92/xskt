const axios = require('axios');
const cheerio = require('cheerio');
const { REGIONS } = require('../core/constants');

function buildDateSlug(drawDate) {
  // drawDate dạng 'YYYY-MM-DD'
  const [yyyy, mm, dd] = drawDate.split('-');
  return `${dd}-${mm}-${yyyy}`;
}

/**
 * Parse bảng kết quả XSMB trên xoso.com.vn.
 *
 * Lưu ý:
 * - HTML thực tế có thể thay đổi, bạn cần điều chỉnh selector cho đúng.
 * - Dữ liệu ví dụ trong websearch cho thấy cấu trúc dạng bảng có hàng ĐB, 1,2,3,...
 *
 * Trả về mảng entries:
 * [{ region, provinceCode, stationCode, drawDate, prizeCode, indexInPrize, number }, ...]
 */
async function fetchXsmbForDate(drawDate) {
  const slug = buildDateSlug(drawDate);
  // Ví dụ: https://xoso.com.vn/xsmb-04-03-2026.html
  const url = `https://xoso.com.vn/xsmb-${slug}.html`;

  const resp = await axios.get(url, {
    headers: {
      'User-Agent':
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122 Safari/537.36'
    },
    timeout: 15000
  });

  const html = resp.data;
  const $ = cheerio.load(html);

  const entries = [];

  // TÌM BẢNG KẾT QUẢ XSMB:
  // Chiến lược: duyệt tất cả <table>, tìm table nào có 1 hàng
  // mà ô đầu tiên (th hoặc td) là "ĐB" (giải đặc biệt). Đó sẽ là bảng kết quả chính.
  let resultTable = null;

  $('table').each((i, tableEl) => {
    if (resultTable) return;
    const table = $(tableEl);
    const hasDbRow = table
      .find('tr')
      .toArray()
      .some((tr) => {
        const firstCellText = $(tr).find('th,td').first().text().trim();
        return firstCellText === 'ĐB';
      });

    if (hasDbRow) {
      resultTable = table;
    }
  });

  if (resultTable) {
    resultTable.find('tr').each((i, tr) => {
      const tds = $(tr).find('td');
      if (tds.length < 1) return;

      const rawLabel =
        $(tr).find('th').first().text().trim() ||
        $(tr).find('td').first().text().trim();
      const prizeCode = mapPrizeLabelToCode(rawLabel);
      if (!prizeCode) return;

      const numbersText = $(tds[0]).text().trim();
      if (!numbersText) return;

      const numbers = numbersText
        .split(/[\s,]+/)
        .map((n) => n.trim())
        .filter(Boolean);

      numbers.forEach((num, idx) => {
        if (!/^\d+$/.test(num)) return;

        entries.push({
          region: REGIONS.MB,
          provinceCode: 'MB',
          stationCode: 'XSMB',
          drawDate,
          prizeCode,
          indexInPrize: idx,
          number: num
        });
      });
    });
  }

  return { entries, rawHtml: html };
}

/**
 * Parse bảng kết quả XSMT trên xoso.com.vn.
 * Lưu ý: hiện tại gom kết quả miền Trung vào 1 draw chung (region MT, station XSMT).
 * Sau này nếu cần chi tiết từng tỉnh có thể mở rộng thêm.
 */
async function fetchXsmtForDate(drawDate) {
  const slug = buildDateSlug(drawDate);
  // Ví dụ: https://xoso.com.vn/xsmt-04-03-2026.html
  const url = `https://xoso.com.vn/xsmt-${slug}.html`;

  const resp = await axios.get(url, {
    headers: {
      'User-Agent':
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122 Safari/537.36'
    },
    timeout: 15000
  });

  const html = resp.data;
  const $ = cheerio.load(html);

  const entries = [];

  // TÌM BẢNG CHÍNH CỦA XSMT:
  // Dựa trên div id="mt_kqngay_YYYYMMDD_kq" trên trang theo ngày.
  const container = $('div[id^="mt_kqngay_"][id$="_kq"]').first();
  const resultTable = container.find('table.table-result.table-xsmn').first();

  if (resultTable && resultTable.length) {
    resultTable.find('tr').each((i, tr) => {
      const tds = $(tr).find('td');
      if (tds.length < 1) return;

      const rawLabel =
        $(tr).find('th').first().text().trim() ||
        $(tr).find('td').first().text().trim();
      const prizeCode = mapPrizeLabelToCode(rawLabel);
      if (!prizeCode) return;

      // Gom tất cả số trong các cột (các tỉnh) vào chung 1 draw miền Trung
      let idx = 0;
      tds.each((_, td) => {
        const numbersText = $(td).text().trim();
        if (!numbersText) return;

        const numbers = numbersText
          .split(/[\s,]+/)
          .map((n) => n.trim())
          .filter(Boolean);

        numbers.forEach((num) => {
          if (!/^\d+$/.test(num)) return;

          entries.push({
            region: REGIONS.MT,
            provinceCode: 'MT',
            stationCode: 'XSMT',
            drawDate,
            prizeCode,
            indexInPrize: idx++,
            number: num
          });
        });
      });
    });
  }

  return { entries, rawHtml: html };
}

/**
 * Parse bảng kết quả XSMN trên xoso.com.vn (gom thành 1 draw chung).
 */
async function fetchXsmnForDate(drawDate) {
  const slug = buildDateSlug(drawDate);
  // Ví dụ: https://xoso.com.vn/xsmn-04-03-2026.html
  const url = `https://xoso.com.vn/xsmn-${slug}.html`;

  const resp = await axios.get(url, {
    headers: {
      'User-Agent':
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122 Safari/537.36'
    },
    timeout: 15000
  });

  const html = resp.data;
  const $ = cheerio.load(html);

  const entries = [];

  // Dựa trên div id="mn_kqngay_YYYYMMDD_kq" trên trang theo ngày.
  const container = $('div[id^="mn_kqngay_"][id$="_kq"]').first();
  const resultTable = container.find('table.table-result.table-xsmn').first();

  if (resultTable && resultTable.length) {
    resultTable.find('tr').each((i, tr) => {
      const tds = $(tr).find('td');
      if (tds.length < 1) return;

      const rawLabel =
        $(tr).find('th').first().text().trim() ||
        $(tr).find('td').first().text().trim();
      const prizeCode = mapPrizeLabelToCode(rawLabel);
      if (!prizeCode) return;

      // Gom tất cả số trong các cột (các tỉnh) vào chung 1 draw miền Nam
      let idx = 0;
      tds.each((_, td) => {
        const numbersText = $(td).text().trim();
        if (!numbersText) return;

        const numbers = numbersText
          .split(/[\s,]+/)
          .map((n) => n.trim())
          .filter(Boolean);

        numbers.forEach((num) => {
          if (!/^\d+$/.test(num)) return;

          entries.push({
            region: REGIONS.MN,
            provinceCode: 'MN',
            stationCode: 'XSMN',
            drawDate,
            prizeCode,
            indexInPrize: idx++,
            number: num
          });
        });
      });
    });
  }

  return { entries, rawHtml: html };
}

function mapPrizeLabelToCode(label) {
  const normalized = label.replace(/\s+/g, '').toUpperCase();
  if (normalized === 'ĐB' || normalized === 'DB') return 'DB';
  if (normalized === '1') return 'G1';
  if (normalized === '2') return 'G2';
  if (normalized === '3') return 'G3';
  if (normalized === '4') return 'G4';
  if (normalized === '5') return 'G5';
  if (normalized === '6') return 'G6';
  if (normalized === '7') return 'G7';
  return normalized;
}

module.exports = {
  fetchXsmbForDate,
  fetchXsmtForDate,
  fetchXsmnForDate
};



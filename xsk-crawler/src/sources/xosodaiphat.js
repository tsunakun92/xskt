const axios = require('axios');
const cheerio = require('cheerio');
const { REGIONS } = require('../core/constants');

function buildDateSlugDaiPhat(drawDate) {
  // drawDate dạng 'YYYY-MM-DD' -> 'DD-MM-YYYY' (pad 0) theo pattern xosodaiphat.com/xsmb-04-03-2026.html
  const [yyyy, mm, dd] = drawDate.split('-');
  return `${dd}-${mm}-${yyyy}`;
}

/**
 * Crawl XSMB theo ngày từ xosodaiphat.com, ví dụ:
 * https://xosodaiphat.com/xsmb-04-03-2026.html
 */
async function fetchDaiPhatXsmbForDate(drawDate) {
  const slug = buildDateSlugDaiPhat(drawDate);
  const url = `https://xosodaiphat.com/xsmb-${slug}.html`;

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

  // Bảng có header "Mã ĐB" và các dòng G.ĐB, G.1, G.2,...
  const table = $('table')
    .filter((i, el) => {
      const firstCell = $(el).find('tr').first().find('th,td').first().text().trim();
      return firstCell === 'Mã ĐB';
    })
    .first();

  if (!table || !table.length) {
    return { entries, rawHtml: html };
  }

  table.find('tr').each((rowIdx, tr) => {
    if (rowIdx === 0) return; // bỏ hàng Mã ĐB
    const cells = $(tr).find('td,th');
    if (cells.length < 2) return;

    const rawLabel = $(cells[0]).text().trim(); // G.ĐB, G.1, G.2,...
    const prizeCode = mapDaiPhatPrizeLabelToCode(rawLabel);
    if (!prizeCode) return;

    const numbersText = $(cells[1]).text().trim();
    if (!numbersText) return;

    const tokens = numbersText.split(/\s+/).filter(Boolean);

    tokens.forEach((num, idx) => {
      const clean = num.replace(/\D/g, '');
      if (!/^\d{2,6}$/.test(clean)) return;

      entries.push({
        region: REGIONS.MB,
        provinceCode: 'MB',
        stationCode: 'XSMB',
        drawDate,
        prizeCode,
        indexInPrize: idx,
        number: clean
      });
    });
  });

  return { entries, rawHtml: html };
}

/**
 * Crawl XSMT theo ngày từ xosodaiphat.com, ví dụ:
 * https://xosodaiphat.com/xsmt-04-03-2026.html
 *
 * Bảng kết quả:
 * <table class="table table-bordered table-xsmn livetn2">
 *   <tr><th>Giải</th><th>Đà Nẵng</th><th>Khánh Hòa</th></tr>
 *   <tr><td>G.8</td><td><span>96</span></td><td><span>88</span></td></tr>
 *   ...
 *   <tr><td>G.ĐB</td><td><span>018361</span></td><td><span>971174</span></td></tr>
 * </table>
 *
 * Hiện tại gom tất cả tỉnh vào 1 draw chung region MT, stationCode 'XSMT'.
 */
async function fetchDaiPhatXsmtForDate(drawDate) {
  const slug = buildDateSlugDaiPhat(drawDate);
  const url = `https://xosodaiphat.com/xsmt-${slug}.html`;

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

  // Gần đây site đổi class (có thể là livetn2, livetn3, livetn4, thêm table-cityX,...)
  // nên chỉ cần bám vào table-xsmn là đủ phân biệt với bảng loto.
  const table = $('table.table-xsmn').first();
  if (!table || !table.length) {
    return { entries, rawHtml: html };
  }

  table.find('tr').each((rowIdx, tr) => {
    const tds = $(tr).find('td');
    if (tds.length < 2) return; // bỏ header Giải / tỉnh

    const rawLabel = $(tds[0]).text().trim(); // G.8, G.7, ..., G.ĐB
    const prizeCode = mapDaiPhatPrizeLabelToCode(rawLabel);
    if (!prizeCode) return;

    let idx = 0;
    tds.slice(1).each((_, td) => {
      $(td)
        .find('span')
        .each((__, span) => {
          const num = $(span).text().replace(/\D/g, '').trim();
          if (!num) return;
          if (!/^\d{2,6}$/.test(num)) return;

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

  return { entries, rawHtml: html };
}

/**
 * Crawl XSMN theo ngày từ xosodaiphat.com, ví dụ:
 * https://xosodaiphat.com/xsmn-04-03-2026.html
 *
 * Bảng kết quả:
 * <table class="table table-bordered table-striped table-xsmn livetn3 table-city3">
 *   <tr><th>Giải</th><th>Đồng Nai</th><th>Cần Thơ</th><th>Sóc Trăng</th></tr>
 *   ...
 * </table>
 *
 * Gom tất cả tỉnh vào 1 draw chung region MN, stationCode 'XSMN'.
 */
async function fetchDaiPhatXsmnForDate(drawDate) {
  const slug = buildDateSlugDaiPhat(drawDate);
  const url = `https://xosodaiphat.com/xsmn-${slug}.html`;

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

  // HTML hiện tại có thể dùng livetn3, livetn4 (3 hoặc 4 đài), cộng thêm table-cityX.
  // Chỉ cần chọn table-xsmn đầu tiên là bảng kết quả chính.
  const table = $('table.table-xsmn').first();
  if (!table || !table.length) {
    return { entries, rawHtml: html };
  }

  table.find('tr').each((rowIdx, tr) => {
    const tds = $(tr).find('td');
    if (tds.length < 2) return;

    const rawLabel = $(tds[0]).text().trim(); // G.8, G.7, ..., G.ĐB
    const prizeCode = mapDaiPhatPrizeLabelToCode(rawLabel);
    if (!prizeCode) return;

    let idx = 0;
    tds.slice(1).each((_, td) => {
      $(td)
        .find('span')
        .each((__, span) => {
          const num = $(span).text().replace(/\D/g, '').trim();
          if (!num) return;
          if (!/^\d{2,6}$/.test(num)) return;

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

  return { entries, rawHtml: html };
}

function mapDaiPhatPrizeLabelToCode(label) {
  const normalized = label.replace(/\s+/g, '').toUpperCase();
  if (normalized === 'G.ĐB' || normalized === 'G.DB' || normalized === 'GĐB' || normalized === 'DB') return 'DB';
  if (normalized === 'G.1' || normalized === 'G1') return 'G1';
  if (normalized === 'G.2' || normalized === 'G2') return 'G2';
  if (normalized === 'G.3' || normalized === 'G3') return 'G3';
  if (normalized === 'G.4' || normalized === 'G4') return 'G4';
  if (normalized === 'G.5' || normalized === 'G5') return 'G5';
  if (normalized === 'G.6' || normalized === 'G6') return 'G6';
  if (normalized === 'G.7' || normalized === 'G7') return 'G7';
  if (normalized === 'G.8' || normalized === 'G8') return 'G8';
  return null;
}

module.exports = {
  fetchDaiPhatXsmbForDate,
  fetchDaiPhatXsmtForDate,
  fetchDaiPhatXsmnForDate
};



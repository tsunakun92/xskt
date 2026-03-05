const axios = require('axios');
const cheerio = require('cheerio');
const { REGIONS } = require('../core/constants');

function buildDateSlugMinhNgoc(drawDate) {
  // drawDate dạng 'YYYY-MM-DD' -> 'DD-MM-YYYY' (pad 0)
  const [yyyy, mm, dd] = drawDate.split('-');
  return `${dd}-${mm}-${yyyy}`;
}

function mapPrizeClassToCode(cls) {
  // cls có dạng giai8, giai7, ..., giaidb
  if (!cls) return null;
  const normalized = cls.toLowerCase();
  if (normalized === 'giaidb') return 'DB';
  const m = normalized.match(/^giai(\d)$/);
  if (m) {
    return `G${m[1]}`;
  }
  return null;
}

function mapMienBacLabelToPrizeCode(labelClass) {
  if (!labelClass) return null;
  const n = labelClass.toLowerCase();
  if (n === 'giaidbl') return 'DB';
  const m = n.match(/^giai(\d)l$/);
  if (m) {
    return `G${m[1]}`;
  }
  return null;
}

/**
 * Parse box_kqxs Miền Nam / Miền Trung (cấu trúc bảng tỉnh rightcl giống nhau).
 * Trả về entries gom chung theo miền (provinceCode = 'MN' / 'MT').
 */
function parseMinhNgocMultiProvince($root, box, region, provinceCode, stationCode, drawDate) {
  const entries = [];

  const mainTable = box.find('table.bkqmiennam').first();
  if (!mainTable || !mainTable.length) {
    return entries;
  }

  // Mỗi province là 1 table.rightcl
  mainTable.find('table.rightcl').each((_, provinceTable) => {
    const $pt = cheerio.load($root(provinceTable).html() || '');

    $pt('tr').each((__, tr) => {
      const td = $pt(tr).find('td').first();
      if (!td || !td.length) return;

      const classAttr = (td.attr('class') || '').trim();
      const prizeCode = mapPrizeClassToCode(classAttr);
      if (!prizeCode) return;

      let idx = 0;
      td.find('div').each((___, div) => {
        const num = $pt(div).text().replace(/\D/g, '').trim();
        if (!num) return;
        if (!/^\d{2,6}$/.test(num)) return;

        entries.push({
          region,
          provinceCode,
          stationCode,
          drawDate,
          prizeCode,
          indexInPrize: idx++,
          number: num
        });
      });
    });
  });

  return entries;
}

/**
 * Parse box_kqxs Miền Bắc.
 */
function parseMinhNgocMienBac($root, box, drawDate) {
  const entries = [];

  const table = box.find('table.bkqtinhmienbac').first();
  if (!table || !table.length) {
    return entries;
  }

  table.find('tr').each((_, tr) => {
    const tds = $root(tr).find('td');
    if (tds.length < 2) return;

    const labelTd = tds.eq(0);
    const numsTd = tds.eq(1);

    const labelClass = (labelTd.attr('class') || '').trim();
    const prizeCode = mapMienBacLabelToPrizeCode(labelClass);
    if (!prizeCode) return;

    let idx = 0;
    numsTd.find('div').each((__, div) => {
      const num = $root(div).text().replace(/\D/g, '').trim();
      if (!num) return;
      if (!/^\d{2,6}$/.test(num)) return;

      entries.push({
        region: REGIONS.MB,
        provinceCode: 'MB',
        stationCode: 'XSMB',
        drawDate,
        prizeCode,
        indexInPrize: idx++,
        number: num
      });
    });
  });

  return entries;
}

async function fetchMinhNgocXsmnForDate(drawDate) {
  const slug = buildDateSlugMinhNgoc(drawDate);
  const url = `https://www.minhngoc.net/ket-qua-xo-so/mien-nam/${slug}.html`;

  const resp = await axios.get(url, {
    headers: {
      'User-Agent':
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122 Safari/537.36'
    },
    timeout: 15000
  });

  const html = resp.data;
  const $ = cheerio.load(html);

  // box_kqxs đầu tiên là ngày tương ứng trong URL
  const box = $('div.box_kqxs').first();
  if (!box || !box.length) {
    return { entries: [], rawHtml: html };
  }

  const entries = parseMinhNgocMultiProvince($, box, REGIONS.MN, 'MN', 'XSMN', drawDate);
  return { entries, rawHtml: html };
}

async function fetchMinhNgocXsmtForDate(drawDate) {
  const slug = buildDateSlugMinhNgoc(drawDate);
  const url = `https://www.minhngoc.net/ket-qua-xo-so/mien-trung/${slug}.html`;

  const resp = await axios.get(url, {
    headers: {
      'User-Agent':
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122 Safari/537.36'
    },
    timeout: 15000
  });

  const html = resp.data;
  const $ = cheerio.load(html);

  const box = $('div.box_kqxs').first();
  if (!box || !box.length) {
    return { entries: [], rawHtml: html };
  }

  const entries = parseMinhNgocMultiProvince($, box, REGIONS.MT, 'MT', 'XSMT', drawDate);
  return { entries, rawHtml: html };
}

async function fetchMinhNgocXsmbForDate(drawDate) {
  const slug = buildDateSlugMinhNgoc(drawDate);
  const url = `https://www.minhngoc.net/ket-qua-xo-so/mien-bac/${slug}.html`;

  const resp = await axios.get(url, {
    headers: {
      'User-Agent':
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122 Safari/537.36'
    },
    timeout: 15000
  });

  const html = resp.data;
  const $ = cheerio.load(html);

  const box = $('div.box_kqxs').first();
  if (!box || !box.length) {
    return { entries: [], rawHtml: html };
  }

  const entries = parseMinhNgocMienBac($, box, drawDate);
  return { entries, rawHtml: html };
}

module.exports = {
  fetchMinhNgocXsmbForDate,
  fetchMinhNgocXsmtForDate,
  fetchMinhNgocXsmnForDate
};



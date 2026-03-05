const axios = require('axios');
const cheerio = require('cheerio');
const { REGIONS } = require('../core/constants');

function buildDateSlugAz24(drawDate) {
    // drawDate dạng 'YYYY-MM-DD' -> 'D-M-YYYY' (không pad 0)
    const [yyyy, mm, dd] = drawDate.split('-');
    return `${Number(dd)}-${Number(mm)}-${yyyy}`;
}

/**
 * Crawl XSMB theo ngày từ az24.vn, ví dụ:
 * https://az24.vn/xsmb-4-3-2026.html
 */
async function fetchAz24XsmbForDate(drawDate) {
    const slug = buildDateSlugAz24(drawDate);
    const url = `https://az24.vn/xsmb-${slug}.html`;

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

    // Tìm bảng có header chứa "Mã ĐB" và cột thứ 2 là chuỗi giải.
    const table = $('table')
        .filter((i, el) => {
            const firstTh = $(el).find('tr').first().find('th,td').first().text().trim();
            return firstTh === 'Mã ĐB';
        })
        .first();

    if (!table || !table.length) {
        return { entries, rawHtml: html };
    }

    table.find('tr').each((i, tr) => {
        if (i === 0) return; // bỏ hàng tiêu đề Mã ĐB
        const cells = $(tr).find('td,th');
        if (cells.length < 2) return;

        const label = $(cells[0]).text().trim(); // ĐB, G1, G2, ...
        const rawNumbers = $(cells[1]).text().trim().replace(/\s+/g, '');
        if (!rawNumbers) return;

        const prizeCode = mapAz24PrizeLabelToCode(label);
        if (!prizeCode) return;

        // Các giải đều là số 5 chữ số ghép lại, cắt thành từng block 5 số.
        const chunkSize = 5;
        for (let i = 0; i + chunkSize <= rawNumbers.length; i += chunkSize) {
            const num = rawNumbers.slice(i, i + chunkSize);
            if (!/^\d{5}$/.test(num)) continue;
            const indexInPrize = i / chunkSize;

            entries.push({
                region: REGIONS.MB,
                provinceCode: 'MB',
                stationCode: 'XSMB',
                drawDate,
                prizeCode,
                indexInPrize,
                number: num
            });
        }
    });

    return { entries, rawHtml: html };
}

function mapAz24PrizeLabelToCode(label) {
    const normalized = label.replace(/\s+/g, '').toUpperCase();
    if (normalized === 'ĐB' || normalized === 'DB') return 'DB';
    if (normalized === 'G1') return 'G1';
    if (normalized === 'G2') return 'G2';
    if (normalized === 'G3') return 'G3';
    if (normalized === 'G4') return 'G4';
    if (normalized === 'G5') return 'G5';
    if (normalized === 'G6') return 'G6';
    if (normalized === 'G7') return 'G7';
    return null;
}

/**
 * Crawl XSMT theo ngày từ az24.vn, ví dụ:
 * https://az24.vn/xsmt-4-3-2026.html
 *
 * Cấu trúc:
 * <div class="two-city">
 *   <table class="coltwocity colgiai extendable">
 *     <tr class="gr-yellow">
 *       <th></th><th>Đà Nẵng</th><th>Khánh Hòa</th>
 *     </tr>
 *     <tr class="g8"><td>G8</td><td><div class="v-g8">96</div></td><td>...</td></tr>
 *     ...
 *   </table>
 * </div>
 *
 * Hiện tại ta gom cả các tỉnh vào một draw chung (region MT, station XSMT).
 */
async function fetchAz24XsmtForDate(drawDate) {
    const slug = buildDateSlugAz24(drawDate);
    const url = `https://az24.vn/xsmt-${slug}.html`;

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

    const table = $('div.two-city table.coltwocity.colgiai').first();

    if (!table || !table.length) {
        return { entries, rawHtml: html };
    }

    table.find('tr').each((rowIdx, tr) => {
        const tds = $(tr).find('td');
        if (tds.length < 2) return; // bỏ header

        const label = $(tds[0]).text().trim(); // G8, G7, G6, ...
        const prizeCode = mapAz24PrizeLabelToCode(label);
        if (!prizeCode) return;

        let idx = 0;
        // Duyệt qua tất cả cột tỉnh (Đà Nẵng, Khánh Hòa, ...)
        tds.slice(1).each((_, td) => {
            $(td)
                .find('div')
                .each((__, divEl) => {
                    const num = $(divEl).text().replace(/\D/g, '').trim();
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
 * Crawl XSMN theo ngày từ az24.vn, ví dụ:
 * https://az24.vn/xsmn-4-3-2026.html
 *
 * Cấu trúc:
 * <div class="three-city">
 *   <table class="colthreecity colgiai extendable">
 *     <tr class="gr-yellow">
 *       <th></th><th>Đồng Nai</th><th>Cần Thơ</th><th>Sóc Trăng</th>
 *     </tr>
 *     <tr class="g8"><td>G8</td><td><div class="v-g8">35</div></td>...</tr>
 *     ...
 *   </table>
 * </div>
 *
 * Ta cũng gom các tỉnh vào một draw chung (region MN, station XSMN).
 */
async function fetchAz24XsmnForDate(drawDate) {
    const slug = buildDateSlugAz24(drawDate);
    const url = `https://az24.vn/xsmn-${slug}.html`;

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

    // Ngày có 3 đài: three-city/colthreecity, ngày có 4 đài: four-city/colfourcity.
    let table = $('div.three-city table.colthreecity.colgiai').first();
    if (!table || !table.length) {
        table = $('div.four-city table.colfourcity.colgiai').first();
    }

    if (!table || !table.length) {
        return { entries, rawHtml: html };
    }

    table.find('tr').each((rowIdx, tr) => {
        const tds = $(tr).find('td');
        if (tds.length < 2) return; // bỏ header

        const label = $(tds[0]).text().trim();
        const prizeCode = mapAz24PrizeLabelToCode(label);
        if (!prizeCode) return;

        let idx = 0;
        // Duyệt qua tất cả cột tỉnh (Đồng Nai, Cần Thơ, Sóc Trăng, ...)
        tds.slice(1).each((_, td) => {
            $(td)
                .find('div')
                .each((__, divEl) => {
                    const num = $(divEl).text().replace(/\D/g, '').trim();
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

module.exports = {
    fetchAz24XsmbForDate,
    fetchAz24XsmtForDate,
    fetchAz24XsmnForDate
};



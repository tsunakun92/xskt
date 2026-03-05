const db = require('../config/db');

/**
 * Lưu snapshot thô + kết quả parse cho 1 nguồn.
 * parsedResult sẽ được lưu dạng JSON (chuỗi hóa).
 */
async function saveSnapshot({ drawId, sourceId, rawPayload, parsedResult, sanityOk }) {
  const parsedJson = parsedResult && parsedResult.length > 0 ? JSON.stringify(parsedResult) : null;

  await db.query(
    `INSERT INTO raw_snapshots (draw_id, source_id, snapshot_time, raw_payload, parsed_ok, sanity_ok, parsed_result)
     VALUES (?, ?, NOW(), ?, ?, ?, ?)`,
    [drawId, sourceId, rawPayload, parsedResult ? 1 : 0, sanityOk ? 1 : 0, parsedJson]
  );
}

/**
 * Tìm hoặc tạo draw cho XSMB (miền Bắc 1 đài / ngày).
 */
async function findOrCreateXsmbDraw(drawDate) {
  const region = 'MB';
  const provinceCode = 'MB';
  const stationCode = 'XSMB';

  const existing = await db.query(
    `SELECT * FROM draws WHERE region = ? AND province_code = ? AND station_code = ? AND draw_date = ? LIMIT 1`,
    [region, provinceCode, stationCode, drawDate]
  );

  if (existing.length > 0) {
    return existing[0];
  }

  const result = await db.query(
    `INSERT INTO draws (region, province_code, station_code, draw_date, status)
     VALUES (?, ?, ?, ?, 'collecting_live')`,
    [region, provinceCode, stationCode, drawDate]
  );

  return {
    id: result.insertId,
    region,
    province_code: provinceCode,
    station_code: stationCode,
    draw_date: drawDate
  };
}

/**
 * Tìm hoặc tạo draw cho XSMT (tạm thời gom thành 1 đài chung XSMT).
 * Sau này nếu cần chi tiết từng tỉnh, ta sẽ mở rộng thêm province_code/station_code thực tế.
 */
async function findOrCreateXsmtDraw(drawDate) {
  const region = 'MT';
  const provinceCode = 'MT';
  const stationCode = 'XSMT';

  const existing = await db.query(
    `SELECT * FROM draws WHERE region = ? AND province_code = ? AND station_code = ? AND draw_date = ? LIMIT 1`,
    [region, provinceCode, stationCode, drawDate]
  );

  if (existing.length > 0) {
    return existing[0];
  }

  const result = await db.query(
    `INSERT INTO draws (region, province_code, station_code, draw_date, status)
     VALUES (?, ?, ?, ?, 'collecting_live')`,
    [region, provinceCode, stationCode, drawDate]
  );

  return {
    id: result.insertId,
    region,
    province_code: provinceCode,
    station_code: stationCode,
    draw_date: drawDate
  };
}

/**
 * Tìm hoặc tạo draw cho XSMN (tạm thời gom thành 1 đài chung XSMN).
 */
async function findOrCreateXsmnDraw(drawDate) {
  const region = 'MN';
  const provinceCode = 'MN';
  const stationCode = 'XSMN';

  const existing = await db.query(
    `SELECT * FROM draws WHERE region = ? AND province_code = ? AND station_code = ? AND draw_date = ? LIMIT 1`,
    [region, provinceCode, stationCode, drawDate]
  );

  if (existing.length > 0) {
    return existing[0];
  }

  const result = await db.query(
    `INSERT INTO draws (region, province_code, station_code, draw_date, status)
     VALUES (?, ?, ?, ?, 'collecting_live')`,
    [region, provinceCode, stationCode, drawDate]
  );

  return {
    id: result.insertId,
    region,
    province_code: provinceCode,
    station_code: stationCode,
    draw_date: drawDate
  };
}

/**
 * Tối giản: hiện tại chỉ lưu entries vào results, chưa làm vote đa nguồn.
 * Sau này có nhiều nguồn, ta sẽ gom và áp dụng majority vote.
 */
async function saveXsmbResultsConfirmed(drawId, entries) {
  if (!entries || entries.length === 0) return;

  const values = [];
  for (const e of entries) {
    values.push(
      drawId,
      e.prizeCode,
      e.indexInPrize || 0,
      String(e.number),
      'single_source_weighted'
    );
  }

  const placeholders = entries
    .map(() => '(?,?,?,?,?)')
    .join(',');

  await db.query(
    `DELETE FROM results WHERE draw_id = ?`,
    [drawId]
  );

  await db.query(
    `INSERT INTO results (draw_id, prize_code, index_in_prize, number, confirmed_by_rule)
     VALUES ${placeholders}`,
    values
  );

  await db.query(
    `UPDATE draws SET status = 'confirmed', confirmed_at = NOW() WHERE id = ?`,
    [drawId]
  );
}

/**
 * Gom và confirm kết quả cho 1 draw dựa trên nhiều snapshot (multi-source vote).
 * region/provinceCode/stationCode/drawDate phải match 1 record trong draws.
 */
async function reconcileDraw({ region, provinceCode, stationCode, drawDate }) {
  // 1. Tìm draw
  const draws = await db.query(
    `SELECT * FROM draws
     WHERE region = ? AND province_code = ? AND station_code = ? AND draw_date = ?
     LIMIT 1`,
    [region, provinceCode, stationCode, drawDate]
  );

  if (draws.length === 0) {
    throw new Error(`No draw found for ${region}-${provinceCode}-${stationCode} ${drawDate}`);
  }

  const draw = draws[0];

  // 2. Lấy snapshot + thông tin source
  const snapshots = await db.query(
    `SELECT rs.*, s.code as source_code, s.weight
     FROM raw_snapshots rs
     JOIN sources s ON rs.source_id = s.id
     WHERE rs.draw_id = ? AND rs.parsed_ok = 1`,
    [draw.id]
  );

  if (snapshots.length === 0) {
    throw new Error(`No parsed snapshots for draw_id=${draw.id}`);
  }

  // 3. Build map key -> valueStats
  // key: prizeCode:indexInPrize
  // valueStats: { [number]: { weightSum, sources: [] } }
  const keyMap = Object.create(null);

  for (const snap of snapshots) {
    const weight = snap.weight || 1;
    if (!snap.parsed_result) continue;

    let parsed;
    try {
      parsed = typeof snap.parsed_result === 'string'
        ? JSON.parse(snap.parsed_result)
        : snap.parsed_result;
    } catch (e) {
      // bỏ qua snapshot parse JSON lỗi
      continue;
    }

    if (!Array.isArray(parsed)) continue;

    for (const e of parsed) {
      if (!e || !e.prizeCode || e.number == null) continue;
      const key = `${e.prizeCode}:${e.indexInPrize || 0}`;
      const num = String(e.number);

      if (!keyMap[key]) {
        keyMap[key] = {};
      }
      if (!keyMap[key][num]) {
        keyMap[key][num] = { weightSum: 0, sources: [] };
      }
      keyMap[key][num].weightSum += weight;
      keyMap[key][num].sources.push(snap.source_code);
    }
  }

  const confirmedEntries = [];
  const conflicts = [];

  // 4. Chọn value cho từng key theo majority / weight
  for (const key of Object.keys(keyMap)) {
    const valueStats = keyMap[key];
    const candidates = Object.keys(valueStats);

    if (candidates.length === 1) {
      // mọi nguồn đồng ý
      const num = candidates[0];
      const [prizeCode, idxStr] = key.split(':');
      confirmedEntries.push({
        prizeCode,
        indexInPrize: parseInt(idxStr, 10) || 0,
        number: num,
        confirmedByRule: 'majority'
      });
      continue;
    }

    // nhiều giá trị: chọn theo weight cao nhất, nếu vẫn tie thì coi là conflict
    let bestNum = null;
    let bestWeight = -1;
    let tie = false;

    for (const num of candidates) {
      const w = valueStats[num].weightSum;
      if (w > bestWeight) {
        bestWeight = w;
        bestNum = num;
        tie = false;
      } else if (w === bestWeight) {
        tie = true;
      }
    }

    const [prizeCode, idxStr] = key.split(':');
    if (!tie && bestNum != null) {
      confirmedEntries.push({
        prizeCode,
        indexInPrize: parseInt(idxStr, 10) || 0,
        number: bestNum,
        confirmedByRule: 'majority'
      });
    } else {
      conflicts.push({
        key,
        candidates: Object.fromEntries(
          candidates.map((num) => [num, valueStats[num]])
        )
      });
    }
  }

  // 5. Ghi results (override toàn bộ)
  if (confirmedEntries.length > 0) {
    const values = [];
    for (const e of confirmedEntries) {
      values.push(
        draw.id,
        e.prizeCode,
        e.indexInPrize,
        e.number,
        e.confirmedByRule || 'majority'
      );
    }
    const placeholders = confirmedEntries.map(() => '(?,?,?,?,?)').join(',');

    await db.query(`DELETE FROM results WHERE draw_id = ?`, [draw.id]);
    await db.query(
      `INSERT INTO results (draw_id, prize_code, index_in_prize, number, confirmed_by_rule)
       VALUES ${placeholders}`,
      values
    );
  }

  // 6. Nếu có conflict, ghi anomalies
  if (conflicts.length > 0) {
    await db.query(
      `INSERT INTO anomalies (draw_id, type, details)
       VALUES (?, 'reconcile_conflict', ?)`,
      [draw.id, JSON.stringify({ conflicts })]
    );
  }

  // 7. Update status draw
  const newStatus =
    confirmedEntries.length > 0
      ? (conflicts.length > 0 ? 'confirmed_with_conflict' : 'confirmed')
      : 'disputed';

  await db.query(
    `UPDATE draws
     SET status = ?, confirmed_at = CASE WHEN ? IN ('confirmed','confirmed_with_conflict') THEN NOW() ELSE confirmed_at END
     WHERE id = ?`,
    [newStatus, newStatus, draw.id]
  );

  return {
    drawId: draw.id,
    confirmedCount: confirmedEntries.length,
    conflictCount: conflicts.length,
    status: newStatus
  };
}

module.exports = {
  saveSnapshot,
  findOrCreateXsmbDraw,
  findOrCreateXsmtDraw,
  findOrCreateXsmnDraw,
  saveXsmbResultsConfirmed,
  reconcileDraw
};



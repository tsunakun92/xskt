/**
 * Kiểm tra nhanh dữ liệu đã parse có hợp lý với cơ cấu giải (dùng chung cho 3 miền).
 * entries: mảng object { prizeCode, indexInPrize, number }
 */
function sanityCheckXsmb(entries) {
  if (!Array.isArray(entries) || entries.length === 0) {
    return false;
  }

  for (const e of entries) {
    // number phải là chuỗi chỉ chứa số, độ dài 2–6 (thực tế thường 5–6 chữ số)
    if (!/^\d{2,6}$/.test(String(e.number))) {
      return false;
    }
  }

  return true;
}

module.exports = {
  sanityCheckXsmb
};



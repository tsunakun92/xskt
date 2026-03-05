const { reconcileDraw } = require('../core/reconciler');

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

    console.log(`[reconcileXsmtJob] Bắt đầu reconcile XSMT cho ngày ${drawDate}`);

    try {
        const result = await reconcileDraw({
            region: 'MT',
            provinceCode: 'MT',
            stationCode: 'XSMT',
            drawDate
        });

        console.log(
            `[reconcileXsmtJob] Hoàn tất. drawId=${result.drawId}, confirmed=${result.confirmedCount}, conflicts=${result.conflictCount}, status=${result.status}`
        );
    } catch (err) {
        console.error('[reconcileXsmtJob] Lỗi khi reconcile:', err.message);
    } finally {
        process.exit(0);
    }
}

run();



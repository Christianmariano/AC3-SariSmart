  // Export table with styles (bold headers, borders, alignment)
const exportBtn = document.getElementById('exportBtn');

exportBtn.addEventListener('click', () => {
    const table = document.getElementById('recordsTable');

    // Convert table to worksheet
    const ws = XLSX.utils.table_to_sheet(table, { raw: false });

    // Decode table range
    const range = XLSX.utils.decode_range(ws['!ref']);

    for (let R = range.s.r; R <= range.e.r; ++R) {
        for (let C = range.s.c; C <= range.e.c; ++C) {
            const cellAddress = XLSX.utils.encode_cell({ r: R, c: C });
            if (!ws[cellAddress]) ws[cellAddress] = { t: 's', v: '' };

            // Apply styling
            ws[cellAddress].s = {
                font: { name: "Arial", sz: 11, bold: R < 2 }, // Bold for header rows
                alignment: { vertical: "center", horizontal: "center" }, // center alignment
                border: {
                    top: { style: "thin", color: { rgb: "000000" } },
                    bottom: { style: "thin", color: { rgb: "000000" } },
                    left: { style: "thin", color: { rgb: "000000" } },
                    right: { style: "thin", color: { rgb: "000000" } }
                },
                fill: R < 2 ? { fgColor: { rgb: "D9D9D9" } } : undefined // light grey background for headers
            };
        }
    }

    // Create workbook and append worksheet
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Daily Income");

    // Column widths
    ws['!cols'] = [
        { wch: 15 }, // Date
        { wch: 12 }, // Income - Store
        { wch: 18 }, // Income - School Service
        { wch: 12 }, // Expenses - Store
        { wch: 18 }  // Expenses - School Service
    ];

    // Export
    XLSX.writeFile(wb, `daily_income_${new Date().toISOString().slice(0,10)}.xlsx`);
});
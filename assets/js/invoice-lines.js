function addLine() {
    const tbody = document.getElementById('lines-body');
    const tr = document.createElement('tr');
    tr.className = 'line-row';
    tr.innerHTML = `
        <td><input type="text" name="description[]" class="form-control" placeholder="Description de la prestation" oninput="recalculate()" required></td>
        <td><input type="number" name="quantity[]" class="form-control line-qty" value="1" min="0.01" step="0.01" oninput="recalculate()" style="text-align:right;"></td>
        <td><input type="number" name="unit_price[]" class="form-control line-price" value="0.00" min="0" step="0.01" oninput="recalculate()" style="text-align:right;"></td>
        <td><input type="number" name="tva_rate[]" class="form-control line-tva" value="20" min="0" max="100" step="0.1" oninput="recalculate()" style="text-align:right;"></td>
        <td class="line-total-cell"><span class="line-total">0,00 €</span></td>
        <td><button type="button" onclick="removeLine(this)" class="btn-remove-line">✕</button></td>
    `;
    tbody.appendChild(tr);
    recalculate();
}

function removeLine(btn) {
    const rows = document.querySelectorAll('.line-row');
    if (rows.length <= 1) return;
    btn.closest('tr').remove();
    recalculate();
}

function recalculate() {
    let totalHt = 0, totalTva = 0;
    document.querySelectorAll('.line-row').forEach(row => {
        const qty    = parseFloat(row.querySelector('.line-qty').value)   || 0;
        const price  = parseFloat(row.querySelector('.line-price').value) || 0;
        const tva    = parseFloat(row.querySelector('.line-tva').value)   || 0;
        const ht     = qty * price;
        const tvaAmt = ht * tva / 100;
        totalHt  += ht;
        totalTva += tvaAmt;
        row.querySelector('.line-total').textContent = fmt(ht);
    });
    document.getElementById('total-ht').textContent  = fmt(totalHt);
    document.getElementById('total-tva').textContent = fmt(totalTva);
    document.getElementById('total-ttc').textContent = fmt(totalHt + totalTva);
}

const CURRENCY_SYMBOLS = { EUR: '€', USD: '$', GBP: '£', CHF: 'CHF', CAD: '$' };

function getCurrencySymbol() {
    const sel = document.getElementById('currency-select');
    return sel ? (CURRENCY_SYMBOLS[sel.value] || '€') : '€';
}

function updateCurrencySymbol() {
    recalculate();
}

function fmt(n) {
    const sym = getCurrencySymbol();
    const num = n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return sym === 'CHF' ? num + ' CHF' : num + ' ' + sym;
}

recalculate();

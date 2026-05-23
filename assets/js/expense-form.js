function recalc() {
    const ttc     = parseFloat(document.getElementById('amount-ttc').value) || 0;
    const tvaRate = parseFloat(document.getElementById('tva-rate').value)   || 0;
    const ht      = tvaRate > 0 ? ttc / (1 + tvaRate / 100) : ttc;
    const tva     = ttc - ht;

    document.getElementById('display-ht').textContent  = fmt(ht);
    document.getElementById('display-tva').textContent = fmt(tva);
    document.getElementById('display-ttc').textContent = fmt(ttc);
}

function fmt(n) {
    return n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
}

recalc();

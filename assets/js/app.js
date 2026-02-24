
// assets/js/app.js
(function(){
  function rupiah(n){
    n = Number(n || 0);
    return 'Rp.' + n.toLocaleString('id-ID', {maximumFractionDigits:0});
  }

  function toDate(v){
    if(!v) return null;
    const d = new Date(v + 'T00:00:00');
    return isNaN(d.getTime()) ? null : d;
  }

  function daysBetween(a,b){
    if(!a || !b) return 1;
    const ms = b.getTime() - a.getTime();
    const d = Math.ceil(ms / (1000*60*60*24));
    return Math.max(1, d);
  }

  function numVal(el){
    if(!el) return 0;
    const s = (el.value || '').toString().replace(/[^\d]/g,'');
    return Number(s || 0);
  }

  function setIfEmpty(el, val){
    if(!el) return;
    if(!el.value) el.value = val;
  }

  function updateTx(){
    const room = document.getElementById('room_number');
    const price = document.getElementById('price_per_day');
    const checkIn = document.getElementById('check_in');
    const checkOut = document.getElementById('check_out');
    const dp = document.getElementById('down_payment');
    const paid = document.getElementById('paid_amount');

    const daysInput = document.getElementById('total_days');

    if(!room || !price) return;

    // auto fill price by room number
    if(window.ROOM_PRICE_MAP && room.value){
      const p = window.ROOM_PRICE_MAP[String(room.value).trim()];
      if(typeof p !== 'undefined'){
        setIfEmpty(price, String(p));
      }
    }

    const d1 = toDate(checkIn?.value);
    const d2 = toDate(checkOut?.value);
    const days = daysBetween(d1,d2);
    if(daysInput) daysInput.value = String(days);

    const priceN = numVal(price);
    const total = days * priceN;

    const dpN = numVal(dp);
    const due = Math.max(total - dpN, 0);
    const paidN = numVal(paid);

    const remaining = Math.max(due - paidN, 0);
    const change = Math.max(paidN - due, 0);

    const elTotal = document.getElementById('tx_total');
    const elRemaining = document.getElementById('tx_remaining');
    const elChange = document.getElementById('tx_change');

    if(elTotal) elTotal.textContent = rupiah(total);
    if(elRemaining) elRemaining.textContent = rupiah(remaining);
    if(elChange) elChange.textContent = rupiah(change);
  }

  document.addEventListener('input', function(e){
    if(e.target && e.target.closest('.tx-card')){
      updateTx();
    }
  });

  document.addEventListener('change', function(e){
    if(e.target && e.target.closest('.tx-card')){
      updateTx();
    }
  });

  document.addEventListener('DOMContentLoaded', function(){
    updateTx();
  });
})();

// Small UI script: click source then target, populate hidden inputs and mark squares.
document.addEventListener('DOMContentLoaded', () => {
    const boardEl = document.querySelector('.board');
    if (!boardEl) return;
    let fromEl = null;
    let toEl = null;
    const inputFrom = document.getElementById('from');
    const inputTo = document.getElementById('to');

    boardEl.addEventListener('click', (e) => {
        const sq = e.target.closest('.square');
        if (!sq) return;
        const coord = sq.getAttribute('data-square');
        // If no source selected -> pick source
        if (!fromEl) {
            // ensure there is a piece in the square
            if (sq.querySelector('.piece')) {
                fromEl = sq;
                sq.classList.add('selected');
                inputFrom.value = coord;
            }
            return;
        }
        // If clicked the same -> deselect
        if (fromEl === sq) {
            fromEl.classList.remove('selected');
            fromEl = null;
            inputFrom.value = '';
            inputTo.value = '';
            if (toEl) { toEl.classList.remove('target'); toEl = null; }
            return;
        }
        // Set destination
        if (toEl) toEl.classList.remove('target');
        toEl = sq;
        toEl.classList.add('target');
        inputTo.value = coord;
    });

    // Optional: auto submit when both selected
    document.getElementById('moveForm').addEventListener('submit', (ev) => {
        if (!inputFrom.value || !inputTo.value) {
            ev.preventDefault();
            alert('Select both source and destination squares.');
        }
    });
});
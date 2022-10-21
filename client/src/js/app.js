const linkableRows = document.querySelectorAll('.xd-dashboard-panel tr[data-link]');
for (const row of linkableRows) {
    row.addEventListener('click', function(e) {
        clickRow(e, row);
    });
}

function clickRow(e, row) {
    // console.log('clickRow',  row);
    const link = row.getAttribute('data-link');
    window.location.href = link;
}

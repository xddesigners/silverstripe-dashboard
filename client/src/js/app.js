import { watchElement } from './utils';

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

const callback = (mutationList, observer) => {
    for (const mutation of mutationList) {
        if (mutation.type === 'childList') {
            console.log('A child node has been added or removed.', mutation);
        }
    }
};

// needed to make sure chart is mounted on dynamic content loaded
watchElement('.chart__holder', (el) => {
    setTimeout(() => drawCharts());
});

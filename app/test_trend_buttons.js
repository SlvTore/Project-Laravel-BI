// Test script to check if trend analysis buttons are working
// This should be run in browser console on the edit page

console.log('Testing trend analysis buttons...');

// Check if buttons exist
const buttons = document.querySelectorAll('.chart-period');
console.log('Found buttons:', buttons.length);

// Check if event listeners are attached
buttons.forEach((btn, index) => {
    console.log(`Button ${index}: data-period=${btn.getAttribute('data-period')}, text=${btn.textContent}`);
});

// Test click event simulation
if (buttons.length > 0) {
    console.log('Testing 7-day button click...');
    const sevenDayBtn = document.querySelector('.chart-period[data-period="7"]');
    if (sevenDayBtn) {
        sevenDayBtn.click();
        console.log('7-day button clicked');
    }

    setTimeout(() => {
        console.log('Testing 90-day button click...');
        const ninetyDayBtn = document.querySelector('.chart-period[data-period="90"]');
        if (ninetyDayBtn) {
            ninetyDayBtn.click();
            console.log('90-day button clicked');
        }
    }, 2000);
}

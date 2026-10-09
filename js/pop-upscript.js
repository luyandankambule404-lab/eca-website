// Show popup after the page loads
window.addEventListener('load', () => {
  // Simulate delay after loader if needed
  setTimeout(() => {
    document.getElementById('popup').style.display = 'flex';
  }, 1000); // adjust as needed
});

// Close popup function
function closePopup() {
  document.getElementById('popup').style.display = 'none';
}
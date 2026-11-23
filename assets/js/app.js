// assets/js/app.js

document.addEventListener('DOMContentLoaded', () => {
  // ---------------------------------------------------------
  // 1. GREETING QUOTE ROTATOR (Matches Expo GreetingSection.tsx)
  // ---------------------------------------------------------
  const quoteElement = document.getElementById('greetingQuote');
  
  if (quoteElement && quoteElement.dataset.quotes) {
    try {
      const quotes = JSON.parse(quoteElement.dataset.quotes);
      let currentIndex = 0;

      // Rotate quote every 10 seconds (10000ms) just like the React Native app
      setInterval(() => {
        // 1. Fade out
        quoteElement.style.opacity = '0';
        quoteElement.style.transition = 'opacity 0.5s ease-in-out';

        // 2. Wait for fade out, then change text and fade back in
        setTimeout(() => {
          currentIndex = (currentIndex + 1) % quotes.length;
          quoteElement.textContent = quotes[currentIndex];
          quoteElement.style.opacity = '1';
        }, 500); // 500ms matches your app's ANIMATION_SPEED
      }, 10000);
      
    } catch (e) {
      console.error('Error parsing quotes JSON:', e);
    }
  }

  // ---------------------------------------------------------
  // 2. AUTO-DISMISS FLASH MESSAGES
  // ---------------------------------------------------------
  // Makes success/error alerts fade away automatically after 4 seconds
  const flashMessages = document.querySelectorAll('.flash');
  flashMessages.forEach((msg) => {
    setTimeout(() => {
      msg.style.transition = 'opacity 0.5s ease';
      msg.style.opacity = '0';
      // Remove the element entirely after fading out
      setTimeout(() => msg.remove(), 500);
    }, 4000); 
  });

  // ---------------------------------------------------------
  // 3. PASSWORD VISIBILITY TOGGLE (Optional Utility)
  // ---------------------------------------------------------
  // If you add an eye icon to your password fields later, this handles the toggle
  const toggleButtons = document.querySelectorAll('.toggle-password');
  toggleButtons.forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const input = btn.previousElementSibling;
      if (input && input.type === 'password') {
        input.type = 'text';
        btn.textContent = 'Hide';
      } else if (input) {
        input.type = 'password';
        btn.textContent = 'Show';
      }
    });
  });
});
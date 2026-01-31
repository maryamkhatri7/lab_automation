
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('user-form');
  const formContainer = document.getElementById('form-container');
  const mainContent = document.getElementById('main-site');
  const topBar = document.getElementById('top-bar');
  const navbar = document.querySelector('.navbar');
  const footer = document.querySelector('footer');

  function showMain(firstName, userType, isReturning = false) {
    formContainer.style.display = 'none';

    // ✅ only show these after form is done
    if (topBar) topBar.style.display = 'flex';
    if (navbar) navbar.style.display = 'block';
    if (footer) footer.style.display = 'block';

    mainContent.style.display = 'block';
    mainContent.style.opacity = '1';

    const heroTitle = document.querySelector('.home-hero-title');
    if (heroTitle) {
      heroTitle.innerHTML = `${isReturning ? 'Welcome back' : 'Welcome'}, ${firstName}! <br>
        <small>We're glad to have our favorite ${userType} here</small>`;
    }

    const userNameDisplay = document.getElementById('user-name-display');
    if (userNameDisplay) userNameDisplay.textContent = firstName;
  }

  // Check localStorage
  const storedName = localStorage.getItem('userFirstName');
  const storedType = localStorage.getItem('userType');
  if (storedName && storedType) {
    showMain(storedName, storedType, true);
  } else {
    // ✅ hide nav + footer until user submits
    if (topBar) topBar.style.display = 'none';
    if (navbar) navbar.style.display = 'none';
    if (footer) footer.style.display = 'none';
  }

  // On form submit
  form.addEventListener('submit', e => {
    e.preventDefault();
    const firstName = document.getElementById('firstName').value;
    const userType = document.querySelector('input[name="userType"]:checked').value;

    localStorage.setItem('userFirstName', firstName);
    localStorage.setItem('userType', userType);

    formContainer.style.opacity = '0';
    formContainer.style.transform = 'translateY(-20px)';
    setTimeout(() => showMain(firstName, userType), 500);
  });
});

  // CLOCK
  function updateClock() {
    const now = new Date();
    const clockEl = document.getElementById("clock");
    if (clockEl) {
      clockEl.textContent = now.toLocaleTimeString();
    }
  }
  setInterval(updateClock, 1000);
  updateClock();

  // GEOLOCATION
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const locEl = document.getElementById("location");
        if (locEl) {
          locEl.textContent = `Lat: ${pos.coords.latitude.toFixed(2)}, Long: ${pos.coords.longitude.toFixed(2)}`;
        }
      },
      () => {
        const locEl = document.getElementById("location");
        if (locEl) {
          locEl.textContent = "Location not available";
        }
      }
    );
  }
// Track number of visits
let visitCount = localStorage.getItem("visitCount") || 0;
visitCount = parseInt(visitCount) + 1;
localStorage.setItem("visitCount", visitCount);

// After incrementing visitCount
const visitEl = document.getElementById("visit-count");
if (visitEl) visitEl.textContent = visitCount;

  AOS.init({
    once: false,
    duration: 800,
  });

//   <!-- for nav active -->
document.addEventListener('DOMContentLoaded', () => {
    const currentPage = window.location.pathname.split("/").pop();
    document.querySelectorAll(".navbar-nav .nav-link").forEach(link => {
        if(link.getAttribute("href") === currentPage){
            link.classList.add("active");
        }
    });
});


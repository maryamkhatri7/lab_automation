<footer>
  <div class="footer-container">

    <div class="footer-top">

      <!-- About -->
      <div class="footer-col footer-left">
        <h5 style="color:var(--accent);">Lab Automation System</h5>
        <p>Reliable testing & compliance solutions for electrical & industrial sectors.</p>
      </div>

         <div class="footer-col footer-center">
        <h6 style="color:var(--accent);">Quick Links</h6>
        <div class="footer-links-grid">
          <ul>
            <li><a href="#hero">Home</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#engineers">Engineers</a></li>
          </ul>
          <ul>
            <li><a href="#products">Lab Scope</a></li>
            <li><a href="#process">Process</a></li>
            <li><a href="#contact">Contact</a></li>
          </ul>
        </div>
      </div>

      <!-- Contact & Social -->
      <div class="footer-col footer-right">
        <h6 style="color:var(--accent);">Contact</h6>
        <p>Email: <a href="mailto:info@labautomation.com" style="color: white;" class="e">info@labautomation.com</a></p>
        <p>Phone: 
          <a href="https://api.whatsapp.com/send?phone=923001234567&text=Lab%20Automation" target="_blank" style="color: white;" class="e">
            +92 300 1234567
          </a>
        </p>
        <div class="footer-social">
          <a href="https://facebook.com/yourpage" target="_blank"><i class="bi bi-facebook"></i></a>
          <a href="https://twitter.com/yourhandle" target="_blank"><i class="bi bi-twitter"></i></a>
          <a href="https://linkedin.com/company/yourcompany" target="_blank"><i class="bi bi-linkedin"></i></a>
          <a href="https://instagram.com/yourhandle" target="_blank"><i class="bi bi-instagram"></i></a>
        </div>
      </div>

    </div>

    <!-- Bottom copyright -->
    <div class="footer-bottom">
      <p>© 2026 Lab Automation System. All rights reserved.</p>
    </div>

  </div>
</footer>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/aos.js"></script>
<script>
AOS.init({duration:800,once:true});
const links=document.querySelectorAll(".nav-link");
window.addEventListener("scroll",()=>{
let fromTop=window.scrollY+130;
links.forEach(link=>{
let sec=document.querySelector(link.getAttribute("href"));
if(sec && sec.offsetTop<=fromTop && sec.offsetTop+sec.offsetHeight>fromTop){
links.forEach(l=>l.classList.remove("active"));
link.classList.add("active");
}
});
});
</script>
 <script>
  AOS.init({
    once: false,
    duration: 800,
  });
</script>
</body>
</html>

class MobileMenu {
  constructor() {
    this.menuBtn = document.getElementById('menu-btn');
    if (!this.menuBtn) return;
    this.mobileMenu = document.getElementById('mobile-menu');
    this.menuOpen = false;
  }

  init() {
    if (!this.menuBtn) return;
    this.menuBtn.addEventListener('click', () => this.toggleMenu());
    window.addEventListener('resize', () => this.handleResize());
  }

  toggleMenu() {
    this.menuOpen = !this.menuOpen;

    if (this.menuOpen) {
      this.mobileMenu.classList.remove('hidden');
      setTimeout(() => {
        this.mobileMenu.classList.remove('opacity-0', 'translate-y-[-10px]');
        this.mobileMenu.classList.add('opacity-100', 'translate-y-0');
      }, 10);
    } else {
      this.mobileMenu.classList.add('opacity-0', 'translate-y-[-10px]');
      this.mobileMenu.classList.remove('opacity-100', 'translate-y-0');
      setTimeout(() => this.mobileMenu.classList.add('hidden'), 300);
    }

    // Animate the hamburger into an "X"
    this.menuBtn.classList.toggle('open');
    const spans = this.menuBtn.querySelectorAll('span');
    if (this.menuBtn.classList.contains('open')) {
      spans[0].style.transform = 'rotate(45deg) translateY(7px)';
      spans[1].style.opacity = '0';
      spans[2].style.transform = 'rotate(-45deg) translateY(-7px)';
    } else {
      spans[0].style.transform = '';
      spans[1].style.opacity = '';
      spans[2].style.transform = '';
    }
  }

  handleResize() {
    if (window.innerWidth >= 640) { // sm breakpoint
      this.mobileMenu.classList.add('hidden', 'opacity-0', 'translate-y-[-10px]');
      this.mobileMenu.classList.remove('opacity-100', 'translate-y-0');
      this.menuOpen = false;
    }
  }
}

class Carousel {
  constructor() {
    this.carousel = document.getElementById('carousel');
    if (!this.carousel) return;
    this.slides = this.carousel.children.length;
    this.indicators = document.querySelectorAll('[data-slide]');
    this.index = 0;
  }

  init() {
    if (!this.carousel) return;
    this.indicators.forEach(dot => {
      dot.addEventListener('click', () => this.showSlide(Number(dot.dataset.slide)));
    });
    this.showSlide(this.index);
    setInterval(() => {
      this.index = (this.index + 1) % this.slides;
      this.showSlide(this.index);
    }, 10000); // 10 seconds per slide
  }

  showSlide(i) {
    this.index = i;
    this.carousel.style.transform = `translateX(-${this.index * 100}%)`;
    this.indicators.forEach((dot, idx) => {
      dot.classList.toggle('opacity-100', idx === this.index);
      dot.classList.toggle('opacity-50', idx !== this.index);
    });
  }
}

class ScrollAnimations {
  constructor() {
    this.observerOptions = {
      threshold: 0,
      rootMargin: '0px 0px 100px 0px'
    };
    this.observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.remove('fade-out');
          entry.target.classList.add('animate');
        } else {
          entry.target.classList.remove('animate');
          entry.target.classList.add('fade-out');
        }
      });
    }, this.observerOptions);
  }

  init() {
    document.querySelectorAll('.fade-in-up').forEach(el => {
      if (el) this.observer.observe(el);
    });

    // Observe vision container for animations
    document.querySelectorAll('.vision-container').forEach(el => {
      if (el) this.observer.observe(el);
    });

    // Observe mission container for animations
    document.querySelectorAll('.mission-container').forEach(el => {
      if (el) this.observer.observe(el);
    });

    // Observe agencies container for animations
    document.querySelectorAll('.agencies-container').forEach(el => {
      if (el) this.observer.observe(el);
    });

    // Observe news cards for animations
    document.querySelectorAll('.news-card').forEach((el, index) => {
      if (el) {
        el.style.transitionDelay = `${index * 0.2}s`; // Stagger the animations
        this.observer.observe(el);
      }
    });

    // Observe advisory cards for animations
    document.querySelectorAll('.advisory-card').forEach((el, index) => {
      if (el) {
        el.style.transitionDelay = `${index * 0.2}s`; // Stagger the animations
        this.observer.observe(el);
      }
    });
  }
}

class StatsAnimations {
  constructor() {
    this.layers = document.querySelectorAll('#stats-section [data-speed]');
    this.positions = Array.from(this.layers).map(() => 0);
    this.maxOffset = window.innerWidth * 0.3;
    this.globalSpeedMultiplier = 7;
    this.statsSection = document.getElementById('stats-section');
    if (!this.statsSection) return;
    this.statCards = this.statsSection.querySelectorAll('.stat-card');
    this.statsObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            this.statsSection.classList.remove('opacity-0', 'translate-y-10');
            this.statsSection.classList.add('opacity-100', 'translate-y-0');

            this.statCards.forEach((card) => {
              card.classList.remove('opacity-0', 'translate-y-10');
              card.classList.add('opacity-100', 'translate-y-0');
            });
          }
        });
      },
      { threshold: 0.3 }
    );
  }

  init() {
    if (!this.statsSection) return;
    this.animateLayers();
    this.statsObserver.observe(this.statsSection);
  }

  animateLayers() {
    this.layers.forEach((layer, i) => {
      const speed = parseFloat(layer.dataset.speed);
      const direction = layer.dataset.direction;

      if (direction === 'right') {
        this.positions[i] += speed * this.globalSpeedMultiplier;
        if (this.positions[i] > this.maxOffset) layer.dataset.direction = 'left';
      } else {
        this.positions[i] -= speed * this.globalSpeedMultiplier;
        if (this.positions[i] < -this.maxOffset) layer.dataset.direction = 'right';
      }

      layer.style.transform = `translateX(${this.positions[i]}px) rotate(-25deg)`;
    });

    requestAnimationFrame(() => this.animateLayers());
  }
}

class Accordion {
  /**
   * selector: parent accordion container selector (eg '#mandate-accordion')
   * options: { allowMultiple: boolean } - whether more than one panel can be open
   */
  constructor(selector = '#mandate-accordion', options = {}) {
    this.container = document.querySelector(selector);
    if (!this.container) return;
    this.items = Array.from(this.container.querySelectorAll('.accordion-item'));
    this.options = Object.assign({ allowMultiple: false }, options);
  }

  init() {
    if (!this.container) return;
    // Initialize each item
    this.items.forEach((item, index) => {
      const btn = item.querySelector('.accordion-btn');
      const content = item.querySelector('.accordion-content');

      // set initial ARIA / styles
      btn.setAttribute('aria-controls', `accordion-content-${index}`);
      btn.setAttribute('aria-expanded', 'false');
      content.id = `accordion-content-${index}`;
      content.style.maxHeight = '0px';
      content.style.opacity = '0';

      // event
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        this.toggle(item, content, btn);
      });
    });

    // Close on outside click (optional)
    document.addEventListener('click', (e) => {
      if (!this.container.contains(e.target)) {
        // close all
        this.closeAll();
      }
    });
  }

  open(item, content, btn) {
    content.style.display = 'block';
    const fullHeight = content.scrollHeight + 'px';
    content.style.maxHeight = fullHeight;
    content.style.opacity = '1';
    btn.setAttribute('aria-expanded', 'true');

    const icon = btn.querySelector('.accordion-icon');
    if (icon) icon.style.transform = 'rotate(180deg)';
  }


  close(item, content, btn) {
    content.style.maxHeight = '0px';
    content.style.opacity = '0';
    btn.setAttribute('aria-expanded', 'false');
    const icon = btn.querySelector('.accordion-icon');
    if (icon) icon.style.transform = '';
    // optional: hide after transition to avoid tab stops
    setTimeout(() => {
      if (content.style.maxHeight === '0px') content.style.display = '';
    }, 300);
  }

  toggle(item, content, btn) {
    const isOpen = btn.getAttribute('aria-expanded') === 'true';
    if (isOpen) {
      this.close(item, content, btn);
    } else {
      this.open(item, content, btn);
    }
  }

  closeAll() {
    this.items.forEach(item => this._collapseItem(item));
  }

  _collapseItem(item) {
    const btn = item.querySelector('.accordion-btn');
    const content = item.querySelector('.accordion-content');
    if (!btn || !content) return;
    btn.setAttribute('aria-expanded', 'false');
    content.style.maxHeight = '0px';
    content.style.opacity = '0';
    const icon = btn.querySelector('.accordion-icon');
    if (icon) icon.style.transform = '';
    setTimeout(() => {
      content.style.display = '';
    }, 300);
  }
}


// News data array
const newsData = [
  {
    image: '../images/News/news-img.png',
    title: 'News Headline 1',
    summary: 'Lorem Ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    fullContent: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
  },
  {
    image: '../images/News/news-img.png',
    title: 'News Headline 2',
    summary: 'Lorem Ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    fullContent: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
  },
  {
    image: '../images/News/news-img.png',
    title: 'News Headline 3',
    summary: 'Lorem Ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    fullContent: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
  },
  {
    image: '../images/News/news-img.png',
    title: 'News Headline 4',
    summary: 'Lorem Ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    fullContent: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
  },
  {
    image: '../images/News/news-img.png',
    title: 'News Headline 5',
    summary: 'Lorem Ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    fullContent: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
  },
  {
    image: '../images/News/news-img.png',
    title: 'News Headline 6',
    summary: 'Lorem Ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    fullContent: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
  }
];

// Advisory data array
const advisoryData = [
  {
    image: '../images/News/advisory-img.png',
    title: 'Advisory Headline 1',
    summary: 'Lorem Ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    fullContent: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
  },
  {
    image: '../images/News/advisory-img.png',
    title: 'Advisory Headline 2',
    summary: 'Lorem Ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    fullContent: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
  },
  {
    image: '../images/News/advisory-img.png',
    title: 'Advisory Headline 3',
    summary: 'Lorem Ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    fullContent: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
  },
  {
    image: '../images/News/advisory-img.png',
    title: 'Advisory Headline 4',
    summary: 'Lorem Ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    fullContent: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
  },
  {
    image: '../images/News/advisory-img.png',
    title: 'Advisory Headline 5',
    summary: 'Lorem Ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    fullContent: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
  },
  {
    image: '../images/News/advisory-img.png',
    title: 'Advisory Headline 6',
    summary: 'Lorem Ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    fullContent: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
  }
];

// Function to generate news cards
function generateNewsCards() {
  const container = document.getElementById('news-cards-container');
  if (!container) return;

  newsData.forEach((news, index) => {
    const card = document.createElement('div');
    card.className = 'min-w-[300px] bg-white rounded-[15px] shadow-[0_6px_20px_rgba(0,0,0,0.25)] flex flex-col news-card';
    card.innerHTML = `
      <div class="relative bg-[#003673] rounded-t-[15px] pb-10 pt-4 pl-4 pr-4">
        <img src="${news.image}" alt="${news.title}" class="w-full h-[200px] object-cover rounded-t-[15px]" />
        <div class="absolute -bottom-[12px] mr-[35px] w-full flex justify-center z-10">
          <div class="bg-[#B0D1F2] px-5 py-1 rounded-md shadow-md">
            <p class="text-[#003673] font-[Oswald] text-[15px] font-semibold text-center">
              5th Fighter Wing News
            </p>
          </div>
        </div>
      </div>
      <div class="flex flex-col justify-between flex-grow px-4 py-5 mt-10">
        <div>
          <h3 class="text-[18px] font-bold text-black mb-1">${news.title}</h3>
          <p class="text-gray-700 text-sm">${news.summary}</p>
        </div>
        <div class="mt-4 text-right">
          <a href="#" class="view-article-link text-[12px] text-[#003673] font-semibold hover:underline" data-index="${index}">View the full article</a>
        </div>
      </div>
    `;
    container.appendChild(card);
  });
}

// Function to generate advisory cards
function generateAdvisoryCards() {
  const container = document.getElementById('advisory-cards-container');
  if (!container) return;

  advisoryData.forEach((advisory, index) => {
    const card = document.createElement('div');
    card.className = 'min-w-[300px] bg-white rounded-[15px] shadow-[0_6px_20px_rgba(0,0,0,0.25)] flex flex-col advisory-card';
    card.innerHTML = `
      <div class="relative bg-[#003673] rounded-t-[15px] pb-10 pt-4 pl-4 pr-4">
        <img src="${advisory.image}" alt="${advisory.title}" class="w-full h-[200px] object-cover rounded-t-[15px]" />
        <div class="absolute -bottom-[12px] justify-center content-center w-full flex z-10">
          <div class="bg-[#B0D1F2] mr-[25px] px-5 py-1 rounded-md shadow-md">
            <p class="text-[#003673] font-[Oswald] text-[15px] font-semibold text-center">
              5th Fighter Wing Advisory
            </p>
          </div>
        </div>
      </div>
      <div class="flex flex-col justify-between flex-grow px-4 py-5 mt-10">
        <div>
          <h3 class="text-[18px] font-bold text-black mb-1">${advisory.title}</h3>
          <p class="text-gray-700 text-sm">${advisory.summary}</p>
        </div>
        <div class="mt-4 text-right">
          <a href="#" class="view-advisory-link text-[12px] text-[#003673] font-semibold hover:underline" data-index="${index}">View the full advisory</a>
        </div>
      </div>
    `;
    container.appendChild(card);
  });
}

// Modal functionality for news
function initModal() {
  const modal = document.getElementById('article-modal');
  const modalTitle = document.getElementById('modal-title');
  const modalContent = document.getElementById('modal-content');
  const closeModalBtn = document.getElementById('close-modal');

  if (!modal || !modalTitle || !modalContent || !closeModalBtn) return;

  // Event listeners for view article links
  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('view-article-link')) {
      e.preventDefault();
      const index = parseInt(e.target.dataset.index);
      const news = newsData[index];
      modalTitle.textContent = news.title;
      modalContent.innerHTML = `<p>${news.fullContent}</p>`;
      modal.classList.remove('hidden');
    }
  });

  // Close modal
  closeModalBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
  });

  // Close modal on outside click
  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.classList.add('hidden');
    }
  });
}

// Modal functionality for advisory
function initAdvisoryModal() {
  const modal = document.getElementById('advisory-modal');
  const modalTitle = document.getElementById('advisory-modal-title');
  const modalContent = document.getElementById('advisory-modal-content');
  const closeModalBtn = document.getElementById('close-advisory-modal');

  if (!modal || !modalTitle || !modalContent || !closeModalBtn) return;

  // Event listeners for view advisory links
  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('view-advisory-link')) {
      e.preventDefault();
      const index = parseInt(e.target.dataset.index);
      const advisory = advisoryData[index];
      modalTitle.textContent = advisory.title;
      modalContent.innerHTML = `<p>${advisory.fullContent}</p>`;
      modal.classList.remove('hidden');
    }
  });

  // Close modal
  closeModalBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
  });

  // Close modal on outside click
  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.classList.add('hidden');
    }
  });
}

// Initialize news functionality
function initNews() {
  generateNewsCards();
  initModal();
}

// Initialize advisory functionality
function initAdvisory() {
  generateAdvisoryCards();
  initAdvisoryModal();
}

// Call initNews immediately
initNews();

// Call initAdvisory immediately
initAdvisory();

// Instantiate and initialize classes
const mobileMenu = new MobileMenu();
mobileMenu.init();

const carousel = new Carousel();
carousel.init();

const scrollAnimations = new ScrollAnimations();
scrollAnimations.init();

const statsAnimations = new StatsAnimations();
statsAnimations.init();

const mandateAccordion = new Accordion('#mandate-accordion', { allowMultiple: false });
mandateAccordion.init();

// Vehicle fields toggle functionality
const vehicleRadios = document.querySelectorAll('input[name="has_vehicle"]');
const vehicleFields = document.getElementById('vehicle-fields');

if (vehicleRadios.length > 0 && vehicleFields) {
  vehicleRadios.forEach(radio => {
    radio.addEventListener('change', () => {
      if (radio.value === 'yes') {
        vehicleFields.classList.remove('hidden');
      } else if (radio.value === 'no') {
        vehicleFields.classList.add('hidden');
      }
    });
  });
}

// Flatpickr calendar for visit date
const visitDateInput = document.getElementById('visit-date');
if (visitDateInput) {
  // Mockup available dates: next 10 days starting from tomorrow
  const availableDates = [];
  const today = new Date();
  for (let i = 1; i <= 10; i++) {
    const date = new Date(today);
    date.setDate(today.getDate() + i);
    availableDates.push(date.toISOString().split('T')[0]); // YYYY-MM-DD format
  }

  flatpickr(visitDateInput, {
    enable: availableDates,
    dateFormat: "Y-m-d",
    minDate: "today",
    onDayCreate: function(dObj, dStr, fp, dayElem) {
      // Add green highlight for available dates
      const dateStr = dayElem.dateObj.toISOString().split('T')[0];
      if (availableDates.includes(dateStr)) {
        dayElem.style.backgroundColor = '#10B981'; // Green color
        dayElem.style.color = 'white';
      }
    }
  });
}

// Flatpickr time picker for visit time
const visitTimeInput = document.getElementById('visit-time');
if (visitTimeInput) {
  flatpickr(visitTimeInput, {
    enableTime: true,
    noCalendar: true,
    dateFormat: "H:i",
    minTime: "07:00",
    maxTime: "19:00",
    time_24hr: false
  });
}

// Facial Scanning Modal Functionality
const facialScanBtn = document.getElementById('facial-scan-btn');
const facialScanModal = document.getElementById('facial-scan-modal');
const closeFacialModal = document.getElementById('close-facial-modal');
const cancelFacialScan = document.getElementById('cancel-facial-scan');
const completeFacialScan = document.getElementById('complete-facial-scan');
const facialPhotosInput = document.getElementById('facial-photos');

if (facialScanBtn && facialScanModal) {
  // Open modal
  facialScanBtn.addEventListener('click', () => {
    facialScanModal.classList.remove('hidden');
  });

  // Close modal functions
  const closeModal = () => {
    facialScanModal.classList.add('hidden');
  };

  if (closeFacialModal) closeFacialModal.addEventListener('click', closeModal);
  if (cancelFacialScan) cancelFacialScan.addEventListener('click', closeModal);

  // Close modal on outside click
  facialScanModal.addEventListener('click', (e) => {
    if (e.target === facialScanModal) {
      closeModal();
    }
  });

  // Complete scan (placeholder for now)
  if (completeFacialScan) {
    completeFacialScan.addEventListener('click', () => {
      // Here you would integrate with your Python facial scanning program
      // For now, we'll just close the modal and set a placeholder value
      if (facialPhotosInput) {
        facialPhotosInput.value = 'facial_scan_completed'; // This will be replaced with actual photo paths
      }
      closeModal();
    });
  }
}



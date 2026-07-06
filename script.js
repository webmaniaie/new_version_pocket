const intro = document.getElementById("intro");
let introDismissed = false;
const INTRO_KEY = "pocketIntroSeen";
const isSameOriginReferrer = (() => {
  try {
    return (
      document.referrer &&
      new URL(document.referrer).origin === window.location.origin
    );
  } catch (error) {
    return false;
  }
})();
const navigationEntry =
  typeof performance !== "undefined" &&
  performance.getEntriesByType &&
  performance.getEntriesByType("navigation")[0];
const navigationType = navigationEntry ? navigationEntry.type : "navigate";

if ("scrollRestoration" in history) {
  history.scrollRestoration = "manual";
}

window.addEventListener("load", () => {
  window.scrollTo({ top: 0, left: 0, behavior: "auto" });
});

const workPage = document.body.classList.contains("work-page");
const workMobileQuery = window.matchMedia("(max-width: 900px)");
if (workPage) {
  const setWorkNavState = () => {
    const scrolled = window.scrollY > 20;
    document.body.classList.toggle("work-nav-scrolled", scrolled);
  };
  const onScroll = () => {
    requestAnimationFrame(setWorkNavState);
  };
  if (workMobileQuery.addEventListener) {
    workMobileQuery.addEventListener("change", setWorkNavState);
  } else {
    workMobileQuery.addListener(setWorkNavState);
  }
  window.addEventListener("scroll", onScroll, { passive: true });
  setWorkNavState();
}

const homePage = document.body.classList.contains("home-page");
const homeMobileQuery = window.matchMedia("(max-width: 900px)");
if (homePage) {
  const setHomeNavState = () => {
    const scrolled = window.scrollY > 20;
    document.body.classList.toggle("home-nav-scrolled", scrolled);
  };
  const onScroll = () => {
    requestAnimationFrame(setHomeNavState);
  };
  if (homeMobileQuery.addEventListener) {
    homeMobileQuery.addEventListener("change", setHomeNavState);
  } else {
    homeMobileQuery.addListener(setHomeNavState);
  }
  window.addEventListener("scroll", onScroll, { passive: true });
  setHomeNavState();
}

const mobileProjectModal = document.querySelector(".mobile-project-modal");
if (mobileProjectModal) {
  const mobileOnlyQuery = window.matchMedia("(max-width: 900px)");
  const unlockScroll = () => {
    document.body.classList.remove("modal-open");
    document.documentElement.classList.remove("modal-open");
    document.body.style.overflow = "";
    document.documentElement.style.overflow = "";
    document.documentElement.style.touchAction = "";
  };
  const showMobileProjectModal = () => {
    if (!mobileOnlyQuery.matches) return;
    mobileProjectModal.classList.add("is-open");
    mobileProjectModal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
    document.documentElement.classList.add("modal-open");
  };
  const closeMobileProjectModal = () => {
    mobileProjectModal.classList.remove("is-open");
    mobileProjectModal.setAttribute("aria-hidden", "true");
    unlockScroll();
  };
  const overlay = mobileProjectModal.querySelector(".contact-modal-overlay");
  const closeBtn = mobileProjectModal.querySelector(".contact-modal-close");
  if (overlay) overlay.addEventListener("click", closeMobileProjectModal);
  if (closeBtn) closeBtn.addEventListener("click", closeMobileProjectModal);
  window.addEventListener("load", showMobileProjectModal);
}

function dismissIntro() {
  if (!intro || introDismissed) {
    return;
  }
  introDismissed = true;
  intro.classList.add("exit");
  document.body.classList.remove("intro-locked");
  window.scrollTo({ top: 0, left: 0, behavior: "auto" });
  try {
    sessionStorage.setItem(INTRO_KEY, "true");
  } catch (error) {
    try {
      localStorage.setItem(INTRO_KEY, "true");
    } catch (innerError) {
      // Ignore storage errors in restricted environments.
    }
  }
}

if (intro) {
  let introSeen = false;
  try {
    introSeen = sessionStorage.getItem(INTRO_KEY) === "true";
  } catch (error) {
    try {
      introSeen = localStorage.getItem(INTRO_KEY) === "true";
    } catch (innerError) {
      introSeen = false;
    }
  }

  // If user navigates from another page on this same site, never show intro again.
  if (isSameOriginReferrer) {
    introSeen = true;
    try {
      sessionStorage.setItem(INTRO_KEY, "true");
    } catch (error) {
      try {
        localStorage.setItem(INTRO_KEY, "true");
      } catch (innerError) {
        // Ignore storage errors in restricted environments.
      }
    }
  }

  const shouldShowIntro = navigationType === "reload" || !introSeen;

  if (!shouldShowIntro) {
    intro.classList.add("exit");
    document.body.classList.remove("intro-locked");
  } else {
    const unlock = () => dismissIntro();
    intro.addEventListener("click", unlock, { once: true, passive: true });
    window.addEventListener("keydown", unlock, { once: true });
    window.addEventListener("touchend", unlock, { once: true, passive: true });
  }
}

const menuToggle = document.querySelector(".menu-toggle");
const menuClose = document.querySelector(".menu-close");
const mobileMenu = document.querySelector(".mobile-menu");
const mobileLinks = document.querySelectorAll(".mobile-menu-links a");

function openMenu() {
  if (!mobileMenu) {
    return;
  }
  document.body.classList.add("menu-open");
  document.documentElement.classList.add("menu-open");
  mobileMenu.setAttribute("aria-hidden", "false");
}

function closeMenu() {
  if (!mobileMenu) {
    return;
  }
  document.body.classList.remove("menu-open");
  document.documentElement.classList.remove("menu-open");
  mobileMenu.setAttribute("aria-hidden", "true");
}

if (menuToggle) {
  menuToggle.addEventListener("click", () => {
    if (document.body.classList.contains("menu-open")) {
      closeMenu();
    } else {
      openMenu();
    }
  });
}

if (menuClose) {
  menuClose.addEventListener("click", closeMenu);
}

mobileLinks.forEach((link) => {
  link.addEventListener("click", () => {
    closeMenu();
  });
});

const usPopup = document.querySelector(".us-popup");
const usPopupClose = document.querySelector(".us-popup-close");
if (usPopup && usPopupClose) {
  usPopupClose.addEventListener("click", (event) => {
    event.stopPropagation();
    usPopup.classList.add("is-hidden");
    usPopup.setAttribute("aria-hidden", "true");
  });

  usPopupClose.addEventListener(
    "pointerdown",
    (event) => {
      event.preventDefault();
      event.stopPropagation();
      if (event.stopImmediatePropagation) {
        event.stopImmediatePropagation();
      }
      usPopup.classList.add("is-hidden");
      usPopup.setAttribute("aria-hidden", "true");
    },
    true
  );
}

// Us page mobile sub-nav: toggle spirit/roster sections via hash
const usPage = document.body.classList.contains("us-page");
const usMobileQuery = window.matchMedia("(max-width: 900px)");
function setUsMobileSection() {
  if (!usPage || !usMobileQuery.matches) {
    document.body.classList.remove("us-mobile-spirit", "us-mobile-roster");
    return;
  }
  const hash = window.location.hash.toLowerCase();
  const spiritLink = document.querySelector(".sub-nav a[href$=\"#spirit\"]");
  const rosterLink = document.querySelector(".sub-nav a[href$=\"#roster\"]");
  if (hash === "#roster") {
    document.body.classList.add("us-mobile-roster");
    document.body.classList.remove("us-mobile-spirit");
    if (rosterLink) rosterLink.classList.add("active");
    if (spiritLink) spiritLink.classList.remove("active");
  } else {
    document.body.classList.add("us-mobile-spirit");
    document.body.classList.remove("us-mobile-roster");
    if (spiritLink) spiritLink.classList.add("active");
    if (rosterLink) rosterLink.classList.remove("active");
  }
}

if (usPage) {
  window.addEventListener("hashchange", setUsMobileSection);
  if (usMobileQuery.addEventListener) {
    usMobileQuery.addEventListener("change", setUsMobileSection);
  } else {
    usMobileQuery.addListener(setUsMobileSection);
  }
  setUsMobileSection();
}

if (usPopup && usMobileQuery.matches) {
  usPopup.removeAttribute("data-draggable");
}

if (usPopup && !usMobileQuery.matches) {
  usPopup.style.transform = "";
  usPopup.style.right = "10px";
  usPopup.style.bottom = "-110px";
  usPopup.style.setProperty("--drag-base-x", "0");
  usPopup.style.setProperty("--drag-base-y", "0");
  usPopup.dataset.dragX = "0";
  usPopup.dataset.dragY = "0";
}

// Us page mobile roster: autoplay videos when centered in view
if (usPage) {
  const usVideos = document.querySelectorAll(".us-hero-video video");
  const rosterObserver =
    "IntersectionObserver" in window
      ? new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              const video = entry.target;
              if (!usMobileQuery.matches) return;
              const onRoster =
                document.body.classList.contains("us-mobile-roster");
              if (!onRoster) return;
              if (entry.isIntersecting) {
                video.play().catch(() => {});
              } else {
                video.pause();
                video.currentTime = 0;
              }
            });
          },
          { threshold: 0.6 }
        )
      : null;

  usVideos.forEach((video) => {
    video.preload = "auto";
    video.muted = true;
    video.playsInline = true;
    if (rosterObserver) rosterObserver.observe(video);
    video.addEventListener("click", () => {
      if (!usMobileQuery.matches) return;
      const onRoster = document.body.classList.contains("us-mobile-roster");
      if (!onRoster) return;
      if (video.paused) {
        video.play().catch(() => {});
      } else {
        video.pause();
      }
    });
  });
}

const magneticButtons = document.querySelectorAll(".magnetic");
magneticButtons.forEach((button) => {
  button.addEventListener("mousemove", (event) => {
    const rect = button.getBoundingClientRect();
    const x = event.clientX - rect.left - rect.width / 2;
    const y = event.clientY - rect.top - rect.height / 2;
    button.style.transform = `translate(${x * 0.15}px, ${y * 0.2}px)`;
  });

  button.addEventListener("mouseleave", () => {
    button.style.transform = "translate(0px, 0px)";
  });
});

let isResizing = false;

// Drag-to-move for elements with data-draggable (skips while resizing)
  const draggable = document.querySelectorAll("[data-draggable]");
const isMobileViewport =
  window.matchMedia("(max-width: 900px)").matches ||
  window.matchMedia("(hover: none)").matches ||
  navigator.maxTouchPoints > 0;

  if (isMobileViewport) {
    // Keep draggable on mobile for services items
    draggable.forEach((el) => {
      if (!el.classList.contains("smm-card")) {
        el.removeAttribute("data-draggable");
      }
    });
  }

  draggable.forEach((element) => {
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let initialX = 0;
    let initialY = 0;
    let baseX = 0;
    let baseY = 0;
    let draggingId = null;

    element.addEventListener("pointerdown", (event) => {
      const isTouch = event.pointerType === "touch";
      if (event.target.closest(".us-popup-close")) {
        return;
      }
      if (isTouch && !element.classList.contains("smm-card")) return;
      if (element.classList.contains("us-popup")) {
        event.preventDefault();
      }
      if (isResizing || event.target.closest(".resizer-handle")) {
        return;
      }
      if (!element.dataset.baseX && !element.dataset.baseY) {
        const styles = getComputedStyle(element);
        const baseXVar = styles.getPropertyValue("--drag-base-x").trim();
        const baseYVar = styles.getPropertyValue("--drag-base-y").trim();
        if (baseXVar || baseYVar) {
          element.dataset.baseX = baseXVar || "0";
          element.dataset.baseY = baseYVar || "0";
        }
      }
      isDragging = true;
      draggingId = event.pointerId;
      element.classList.add("is-dragging");
      if (element.classList.contains("us-popup")) {
        document.body.classList.add("dragging-no-select");
      }
      if (!element.hasPointerCapture(event.pointerId)) {
        element.setPointerCapture(event.pointerId);
      }
      startX = event.clientX;
      startY = event.clientY;
      initialX = parseFloat(element.dataset.dragX || "0");
      initialY = parseFloat(element.dataset.dragY || "0");
      baseX = parseFloat(element.dataset.baseX || "0");
      baseY = parseFloat(element.dataset.baseY || "0");
    });

    let dragFrame = null;
    let pendingEvent = null;

    element.addEventListener("pointermove", (event) => {
      if (!isDragging || draggingId !== event.pointerId) return;
      pendingEvent = event;
      if (dragFrame) return;
      dragFrame = requestAnimationFrame(() => {
        dragFrame = null;
        if (!pendingEvent) return;
        const deltaX = pendingEvent.clientX - startX;
        const deltaY = pendingEvent.clientY - startY;
        const nextX = initialX + deltaX;
        const nextY = initialY + deltaY;
        element.style.transform = `translate(${baseX + nextX}px, ${baseY + nextY}px)`;
        element.dataset.dragX = `${nextX}`;
        element.dataset.dragY = `${nextY}`;
        pendingEvent = null;
      });
    });

    element.addEventListener("pointerup", (event) => {
      if (draggingId !== event.pointerId) return;
      isDragging = false;
      draggingId = null;
      element.classList.remove("is-dragging");
      document.body.classList.remove("dragging-no-select");
    });

    element.addEventListener("pointercancel", (event) => {
      if (draggingId !== event.pointerId) return;
      isDragging = false;
      draggingId = null;
      element.classList.remove("is-dragging");
      document.body.classList.remove("dragging-no-select");
    });
  });

  // Dedicated drag for Services cards (more reliable than generic draggable)
  // Services page: drag disabled for all elements

  // Services mobile: tap SMM image opens contact modal
  let servicesModal = document.querySelector(
    ".contact-modal:not(.mobile-project-modal)"
  );
  let servicesOverlay = servicesModal
    ? servicesModal.querySelector(".contact-modal-overlay")
    : null;
  let servicesClose = servicesModal
    ? servicesModal.querySelector(".contact-modal-close")
    : null;
  const navCta = document.querySelector(".nav-cta");
  const contactLinks = document.querySelectorAll('[data-contact]');
  const servicesSmm = document.querySelector(
    ".services-showcase .smm-card:not(.freelance-card)"
  );

  const projectModal = document.querySelector(".project-modal");
  const projectOverlay = document.querySelector(".project-modal-overlay");
  const projectClose = document.querySelector(".project-modal-close");
  const servicesProject = document.querySelector(
    ".services-showcase .smm-card.freelance-card"
  );
  const projectImageButton = document.querySelector(".project-modal-image-link");
  const imageViewer = document.querySelector(".image-viewer");
  const imageViewerOverlay = document.querySelector(".image-viewer-overlay");
  const imageViewerBack = document.querySelector(".image-viewer-back");
  const imageViewerImg = document.querySelector(".image-viewer img");
  const zoomIn = document.querySelector(".image-viewer-zoom-in");
  const zoomOut = document.querySelector(".image-viewer-zoom-out");
  const zoomReset = document.querySelector(".image-viewer-zoom-reset");
  let zoomLevel = 1;

  function ensureServicesModal() {
    if (servicesModal) return;
    servicesModal = document.querySelector(
      ".contact-modal:not(.mobile-project-modal)"
    );
    servicesOverlay = servicesModal
      ? servicesModal.querySelector(".contact-modal-overlay")
      : null;
    servicesClose = servicesModal
      ? servicesModal.querySelector(".contact-modal-close")
      : null;
    if (servicesOverlay) {
      servicesOverlay.addEventListener("click", closeServicesModal);
    }
    if (servicesClose) {
      servicesClose.addEventListener("click", closeServicesModal);
    }
  }

  function openServicesModal() {
    ensureServicesModal();
    if (!servicesModal) return;
    servicesModal.classList.add("is-open");
    servicesModal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
  }

  function closeServicesModal() {
    if (!servicesModal) return;
    servicesModal.classList.remove("is-open");
    servicesModal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("modal-open");
    document.documentElement.classList.remove("modal-open");
  }

  function openProjectModal() {
    if (!projectModal) return;
    projectModal.classList.add("is-open");
    projectModal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
    document.documentElement.classList.add("modal-open");
  }

  function closeProjectModal() {
    if (!projectModal) return;
    projectModal.classList.remove("is-open");
    projectModal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("modal-open");
    document.documentElement.classList.remove("modal-open");
  }

  function openImageViewer() {
    if (!imageViewer) return;
    imageViewer.classList.add("is-open");
    imageViewer.setAttribute("aria-hidden", "false");
    zoomLevel = 1;
    if (imageViewerImg) {
      imageViewerImg.style.setProperty("--zoom", zoomLevel);
    }
  }

  function closeImageViewer() {
    if (!imageViewer) return;
    imageViewer.classList.remove("is-open");
    imageViewer.setAttribute("aria-hidden", "true");
  }

  if (servicesSmm) {
    servicesSmm.addEventListener("pointerup", (event) => {
      if (servicesSmm.dataset.dragMoved === "1") return;
      if (event.pointerType === "mouse" && event.button !== 0) return;
      openServicesModal();
    });
  }

if (navCta) {
  navCta.addEventListener("click", () => {
    openServicesModal();
  });
}

contactLinks.forEach((link) => {
  link.addEventListener("click", (event) => {
    event.preventDefault();
    closeMenu();
    openServicesModal();
  });
});

const homeBannerCta = document.querySelector(".home-banner-cta");
if (homeBannerCta) {
  homeBannerCta.addEventListener("click", () => {
    openServicesModal();
  });
}

if (servicesOverlay) {
  servicesOverlay.addEventListener("click", closeServicesModal);
}

if (servicesClose) {
  servicesClose.addEventListener("click", closeServicesModal);
}

  if (servicesProject) {
    servicesProject.addEventListener("pointerup", (event) => {
      if (servicesProject.dataset.dragMoved === "1") return;
      if (event.pointerType === "mouse" && event.button !== 0) return;
      openProjectModal();
    });
  }

  if (projectImageButton) {
    projectImageButton.addEventListener("click", (event) => {
      event.preventDefault();
      openImageViewer();
    });
  }

  if (servicesOverlay) {
    servicesOverlay.addEventListener("click", closeServicesModal);
  }

  if (servicesClose) {
    servicesClose.addEventListener("click", closeServicesModal);
  }

  if (projectOverlay) {
    projectOverlay.addEventListener("click", closeProjectModal);
  }

  if (projectClose) {
    projectClose.addEventListener("click", closeProjectModal);
  }

  if (imageViewerOverlay) {
    imageViewerOverlay.addEventListener("click", closeImageViewer);
  }

  if (imageViewerBack) {
    imageViewerBack.addEventListener("click", closeImageViewer);
  }

  function setZoom(next) {
    zoomLevel = Math.min(3, Math.max(1, next));
    if (imageViewerImg) {
      imageViewerImg.style.setProperty("--zoom", zoomLevel);
    }
  }

  if (zoomIn) {
    zoomIn.addEventListener("click", () => setZoom(zoomLevel + 0.2));
  }

  if (zoomOut) {
    zoomOut.addEventListener("click", () => setZoom(zoomLevel - 0.2));
  }

  if (zoomReset) {
    zoomReset.addEventListener("click", () => setZoom(1));
  }

// Resizing removed

const workTrack = document.querySelector("[data-track]");
if (workTrack) {
  let isDown = false;
  let startX = 0;
  let scrollLeft = 0;

  workTrack.addEventListener("pointerdown", (event) => {
    isDown = true;
    workTrack.setPointerCapture(event.pointerId);
    startX = event.clientX;
    scrollLeft = workTrack.scrollLeft;
  });

  workTrack.addEventListener("pointermove", (event) => {
    if (!isDown) {
      return;
    }
    const walk = (event.clientX - startX) * 1.2;
    workTrack.scrollLeft = scrollLeft - walk;
  });

  workTrack.addEventListener("pointerup", () => {
    isDown = false;
  });
}

const sbCarousels = document.querySelectorAll(".sb-carousel");
sbCarousels.forEach((carousel) => {
  const track = carousel.querySelector(".sb-carousel-track");
  const prev = carousel.querySelector("[data-carousel=\"prev\"]");
  const next = carousel.querySelector("[data-carousel=\"next\"]");
  if (!track) return;

  const getStep = () => {
    const width = track.getBoundingClientRect().width;
    return width || 280;
  };

  if (prev) {
    prev.addEventListener("click", () => {
      track.scrollBy({ left: -getStep(), behavior: "smooth" });
    });
  }

  if (next) {
    next.addEventListener("click", () => {
      track.scrollBy({ left: getStep(), behavior: "smooth" });
    });
  }
});

if (
  document.body.classList.contains("sb-page") &&
  window.matchMedia("(max-width: 900px)").matches
) {
  const sbMobileVideos = document.querySelectorAll(".sb-page video");
  const playAllSbMobileVideos = () => {
    sbMobileVideos.forEach((video) => {
      video.muted = true;
      video.loop = true;
      video.playsInline = true;
      video.setAttribute("muted", "");
      video.setAttribute("loop", "");
      video.setAttribute("playsinline", "");
      video.setAttribute("autoplay", "");
      const attempt = video.play();
      if (attempt && typeof attempt.catch === "function") {
        attempt.catch(() => {});
      }
    });
  };

  window.addEventListener("load", playAllSbMobileVideos);
  window.addEventListener("pageshow", playAllSbMobileVideos);
  window.addEventListener("focus", playAllSbMobileVideos);
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden) playAllSbMobileVideos();
  });
  document.addEventListener(
    "touchstart",
    () => {
      playAllSbMobileVideos();
    },
    { passive: true }
  );
  playAllSbMobileVideos();
}

const sbDesktopStats = document.querySelector(".sb-desktop-stats");
if (
  sbDesktopStats &&
  document.body.classList.contains("sb-page") &&
  window.matchMedia("(min-width: 901px)").matches &&
  "IntersectionObserver" in window
) {
  const sbStatsObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        sbDesktopStats.classList.toggle("is-visible", entry.isIntersecting);
      });
    },
    { threshold: 0.35 }
  );
  sbStatsObserver.observe(sbDesktopStats);
}

const sbMobileStats = document.querySelector(".sb-mobile-stats");
if (
  sbMobileStats &&
  document.body.classList.contains("sb-page") &&
  window.matchMedia("(max-width: 900px)").matches &&
  "IntersectionObserver" in window
) {
  const sbMobileStatsObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        sbMobileStats.classList.toggle("is-visible", entry.isIntersecting);
      });
    },
    { threshold: 0.35 }
  );
  sbMobileStatsObserver.observe(sbMobileStats);
}

const revealElements = document.querySelectorAll(".reveal");
const revealObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("visible");
      }
    });
  },
  { threshold: 0.2 }
);

revealElements.forEach((el) => revealObserver.observe(el));

const parallaxElements = document.querySelectorAll("[data-parallax]");
function handleParallax() {
  const scrollY = window.scrollY;
  parallaxElements.forEach((el) => {
    const factor = parseFloat(el.dataset.parallax || "0.2");
    el.style.transform = `translateY(${scrollY * factor * -0.1}px)`;
  });
}

window.addEventListener("scroll", handleParallax, { passive: true });
handleParallax();

  const tiltCards = document.querySelectorAll(".tilt");
  tiltCards.forEach((card) => {
    card.addEventListener("mousemove", (event) => {
      const rect = card.getBoundingClientRect();
      const x = event.clientX - rect.left - rect.width / 2;
      const y = event.clientY - rect.top - rect.height / 2;
    card.style.transform = `rotateX(${(y / rect.height) * -6}deg) rotateY(${
      (x / rect.width) * 6
    }deg)`;
  });
    card.addEventListener("mouseleave", () => {
      card.style.transform = "rotateX(0deg) rotateY(0deg)";
    });
  });

  // Make mobile nav transparent while "Platon style video" is in view (SB page)
  const platonVideo = document.querySelector(".sb-cover-video");
  if (platonVideo && document.body.classList.contains("sb-page")) {
    const toggleNav = () => {
      const rect = platonVideo.getBoundingClientRect();
      const viewportH = window.innerHeight || 0;
      const inView = rect.top < viewportH && rect.bottom > 0;
      document.body.classList.toggle("nav-transparent", inView);
      document.documentElement.classList.toggle("nav-transparent", inView);
    };

    window.addEventListener("scroll", toggleNav, { passive: true });
    window.addEventListener("touchmove", toggleNav, { passive: true });
    window.addEventListener("resize", toggleNav);
    toggleNav();
  }

  // Hover-to-play videos (reset when leaving)
const hoverVideos = document.querySelectorAll(".hover-play");
hoverVideos.forEach((video) => {
  video.muted = true;
  video.playsInline = true;
  const playVideo = () => {
    video.play().catch(() => {});
  };
  const stopVideo = () => {
    video.pause();
    video.currentTime = 0;
  };
  video.addEventListener("mouseenter", playVideo);
  video.addEventListener("mouseleave", stopVideo);
  video.addEventListener("pointerenter", playVideo);
  video.addEventListener("pointerleave", stopVideo);
});

const usVideoCards = document.querySelectorAll(".us-video-card");
usVideoCards.forEach((card) => {
  const video = card.querySelector("video");
  if (!video) return;
  video.muted = true;
  video.playsInline = true;
  video.preload = "auto";
  video.load();
  const isDesktopUsPage =
    document.body.classList.contains("us-page") &&
    window.matchMedia("(min-width: 901px)").matches;

  if (isDesktopUsPage) {
    let restartTimer = null;
    const playCycle = () => {
      video.currentTime = 0;
      const attempt = video.play();
      if (attempt && typeof attempt.catch === "function") {
        attempt.catch(() => {});
      }
    };
    video.loop = false;
    video.addEventListener("ended", () => {
      video.pause();
      restartTimer = window.setTimeout(playCycle, 3000);
    });
    window.addEventListener("beforeunload", () => {
      if (restartTimer) {
        window.clearTimeout(restartTimer);
      }
    });
    playCycle();
    return;
  }

  const playVideo = () => {
    video.muted = true;
    const attempt = video.play();
    if (attempt && typeof attempt.catch === "function") {
      attempt.catch(() => {});
    }
  };
  const stopVideo = () => {
    video.pause();
    video.currentTime = 0;
  };
  card.addEventListener("mouseenter", playVideo);
  card.addEventListener("mouseleave", stopVideo);
  card.addEventListener("pointerenter", playVideo);
  card.addEventListener("pointerleave", stopVideo);
  card.addEventListener("click", playVideo);
  video.addEventListener("mouseenter", playVideo);
  video.addEventListener("pointerenter", playVideo);
  video.addEventListener("pointermove", playVideo);
  video.addEventListener("click", playVideo);
});

const g4Video = document.querySelector(".g4-video");
if (g4Video) {
  const desktopSrc = g4Video.dataset.desktop;
  const mobileSrc = g4Video.dataset.mobile;
  const mediaQuery = window.matchMedia("(max-width: 820px)");

  function updateG4Video() {
    const nextSrc = mediaQuery.matches ? mobileSrc : desktopSrc;
    if (g4Video.getAttribute("src") === nextSrc || !nextSrc) {
      return;
    }
    g4Video.setAttribute("src", nextSrc);
    g4Video.load();
    const playPromise = g4Video.play();
    if (playPromise && typeof playPromise.catch === "function") {
      playPromise.catch(() => {});
    }
  }

  updateG4Video();
  if (mediaQuery.addEventListener) {
    mediaQuery.addEventListener("change", updateG4Video);
  } else {
    mediaQuery.addListener(updateG4Video);
  }
}

const cubeButtons = document.querySelectorAll(".cube-cta");
if (cubeButtons.length) {
  setInterval(() => {
    cubeButtons.forEach((button) => {
      button.classList.toggle("cube-purple");
    });
  }, 1000);
}

const workMobileMedia = document.querySelectorAll(".work-mobile-media");
workMobileMedia.forEach((media) => {
  const img = media.querySelector("img");
  const video = media.querySelector("video");
  const markLoaded = () => media.classList.add("is-loaded");
  if (img) {
    if (img.complete) {
      markLoaded();
    } else {
      img.addEventListener("load", markLoaded, { once: true });
      img.addEventListener("error", markLoaded, { once: true });
    }
  }
  if (video) {
    if (video.readyState >= 2) {
      markLoaded();
    } else {
      video.addEventListener("loadeddata", markLoaded, { once: true });
      video.addEventListener("error", markLoaded, { once: true });
    }
  }
});

const homeMobileVideoWrap = document.querySelector(".home-mobile-video-wrap");
if (homeMobileVideoWrap) {
  const homeMobileVideo = homeMobileVideoWrap.querySelector(".mobile-video");
  const markLoaded = () => homeMobileVideoWrap.classList.add("is-loaded");
  if (homeMobileVideo) {
    if (homeMobileVideo.readyState >= 2) {
      markLoaded();
    } else {
      homeMobileVideo.addEventListener("loadeddata", markLoaded, { once: true });
      homeMobileVideo.addEventListener("error", markLoaded, { once: true });
    }
  }
}

const pocketToTop = document.querySelectorAll(".pocket-to-top");
pocketToTop.forEach((button) => {
  button.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
});

const pocketYear = document.querySelector("[data-year]");
if (pocketYear) {
  pocketYear.textContent = new Date().getFullYear();
}

if (document.body.classList.contains("sb-page")) {
  const updateSbFoxScrollState = () => {
    document.body.classList.toggle("sb-fox-shrunk", window.scrollY > 0);
  };

  window.addEventListener("scroll", updateSbFoxScrollState, { passive: true });
  updateSbFoxScrollState();
}

const workCanvas = document.querySelector(".work-canvas");
const workSquares = Array.from(document.querySelectorAll("[data-square]"));
const initWorkCanvas = () => {
  if (workCanvas?.dataset.initialized === "1") {
    return;
  }
  if (workCanvas) {
    workCanvas.dataset.initialized = "1";
  }
  if (!workCanvas || !workSquares.length) {
    return;
  }
  const state = workSquares.map(() => ({
    x: 0,
    y: 0,
    vx: (Math.random() - 0.5) * 0.385,
    vy: (Math.random() - 0.5) * 0.385,
    boostUntil: 0,
    pinned: false,
  }));
  let hoveredIndex = null;
  let pointer = { x: 0, y: 0, active: false };
  let draggingIndex = null;
  let dragOffset = { x: 0, y: 0 };
  let dragStart = { x: 0, y: 0 };
  let dragMoved = false;
  const dragThreshold = 6;
  let topZ = 2;

  workSquares.forEach((square, index) => {
    square.addEventListener("mouseenter", () => {
      hoveredIndex = index;
    });
    square.addEventListener("mouseleave", () => {
      hoveredIndex = null;
    });

    square.addEventListener("pointerdown", (event) => {
      if (event.pointerType === "mouse" && event.button !== 0) {
        return;
      }
      event.preventDefault();
      draggingIndex = index;
      dragMoved = false;
      state[index].pinned = false;
      workCanvas.classList.add("is-dragging");
      square.classList.add("is-dragging");
      square.style.zIndex = `${topZ + 1}`;
      square.setPointerCapture(event.pointerId);
      const rect = workCanvas.getBoundingClientRect();
      dragOffset = {
        x: event.clientX - rect.left - state[index].x,
        y: event.clientY - rect.top - state[index].y,
      };
      dragStart = { x: event.clientX, y: event.clientY };
      state[index].vx = 0;
      state[index].vy = 0;
    });

    square.addEventListener("click", (event) => {
      if (dragMoved) {
        event.preventDefault();
      }
    });
  });

  workCanvas.addEventListener("mousemove", (event) => {
    const rect = workCanvas.getBoundingClientRect();
    pointer = {
      x: event.clientX - rect.left,
      y: event.clientY - rect.top,
      active: true,
    };
  });

  workCanvas.addEventListener("mouseleave", () => {
    pointer.active = false;
  });

  function handleDragMove(event) {
    if (draggingIndex === null) {
      return;
    }
    event.preventDefault();
    const rect = workCanvas.getBoundingClientRect();
    const sizeX = workSquares[0].offsetWidth;
    const sizeY = workSquares[0].offsetHeight;
    const nav = document.querySelector(".site-nav");
    const safeTop = Math.max((nav ? nav.offsetHeight : 0) + 24 - 200 + 150, 0);
    const sideInset = rect.width * 0.08;
    const bottomInset = 165;
    const areaWidth = rect.width - sideInset * 2;
    const areaHeight = rect.height - safeTop - bottomInset;
    const originX = sideInset;
    const originY = safeTop;
    const padding = 10;
    const x = event.clientX - rect.left - dragOffset.x;
    const y = event.clientY - rect.top - dragOffset.y;
    if (!dragMoved) {
      const distance = Math.hypot(
        event.clientX - dragStart.x,
        event.clientY - dragStart.y
      );
      if (distance > dragThreshold) {
        dragMoved = true;
      }
    }
    state[draggingIndex].x = Math.max(
      originX + padding,
      Math.min(x, originX + areaWidth - sizeX - padding)
    );
    state[draggingIndex].y = Math.max(
      originY + padding,
      Math.min(y, originY + areaHeight - sizeY - padding)
    );
    workSquares[draggingIndex].style.setProperty(
      "--x",
      `${state[draggingIndex].x}px`
    );
    workSquares[draggingIndex].style.setProperty(
      "--y",
      `${state[draggingIndex].y}px`
    );
    workSquares[draggingIndex].style.transform = `translate(${state[draggingIndex].x}px, ${state[draggingIndex].y}px)`;
    dragMoved = true;
  }

  function handleDragEnd() {
    if (draggingIndex === null) {
      return;
    }
    const index = draggingIndex;
    workCanvas.classList.remove("is-dragging");
    workSquares[index].classList.remove("is-dragging");
    topZ += 1;
    workSquares[index].style.zIndex = `${topZ}`;
    state[index].pinned = false;
    state[index].vx = (Math.random() - 0.5) * 0.3;
    state[index].vy = (Math.random() - 0.5) * 0.3;
    draggingIndex = null;
  }

  workSquares.forEach((square) => {
    square.addEventListener("pointermove", handleDragMove);
    square.addEventListener("pointerup", handleDragEnd);
    square.addEventListener("pointercancel", handleDragEnd);
  });

  function layoutSquares() {
    const rect = workCanvas.getBoundingClientRect();
    if (rect.width === 0 || rect.height === 0) {
      return;
    }
    const sizeX = workSquares[0].offsetWidth;
    const sizeY = workSquares[0].offsetHeight;
    const nav = document.querySelector(".site-nav");
    const safeTop = Math.max((nav ? nav.offsetHeight : 0) + 24 - 200 + 150, 0);
    const sideInset = rect.width * 0.08;
    const bottomInset = 165;
    const areaWidth = rect.width - sideInset * 2;
    const areaHeight = rect.height - safeTop - bottomInset;
    const originX = sideInset;
    const originY = safeTop;
    const centerX = originX + areaWidth / 2;
    const centerY = originY + areaHeight / 2;
    const spacingX = 200;
    state.forEach((item, index) => {
      const offsetX =
        (index - (state.length - 1) / 2) * spacingX;
      const square = workSquares[index];
      const href = square ? square.getAttribute("href") || "" : "";
      const offsetY =
        href.includes("robotics") || href.includes("fastiv") ? -30 : 30;
      item.x = Math.max(
        originX,
        Math.min(centerX + offsetX - sizeX / 2, originX + areaWidth - sizeX)
      );
      item.y = Math.max(
        originY,
        Math.min(centerY + offsetY - sizeY / 2, originY + areaHeight - sizeY)
      );
      workSquares[index].style.setProperty("--x", `${item.x}px`);
      workSquares[index].style.setProperty("--y", `${item.y}px`);
      workSquares[index].style.transform = `translate(${item.x}px, ${item.y}px)`;
    });
  }

  function step() {
    const now = performance.now();
    const rect = workCanvas.getBoundingClientRect();
    if (rect.width === 0 || rect.height === 0) {
      requestAnimationFrame(step);
      return;
    }
    const sizeX = workSquares[0].offsetWidth;
    const sizeY = workSquares[0].offsetHeight;
    const nav = document.querySelector(".site-nav");
    const safeTop = Math.max((nav ? nav.offsetHeight : 0) + 24 - 200 + 150, 0);
    const sideInset = rect.width * 0.08;
    const bottomInset = 165;
    const areaWidth = rect.width - sideInset * 2;
    const areaHeight = rect.height - safeTop - bottomInset;
    const originX = sideInset;
    const originY = safeTop;
    const padding = 10;

    state.forEach((item, index) => {
      if (item.pinned || hoveredIndex === index || draggingIndex === index) {
        return;
      }
      const speedBoost = item.boostUntil > now ? 2 : 1;
      item.x += item.vx * speedBoost;
      item.y += item.vy * speedBoost;

      if (
        item.x <= originX + padding ||
        item.x >= originX + areaWidth - sizeX - padding
      ) {
        item.vx = item.vx === 0 ? (Math.random() - 0.5) * 0.6 : -item.vx;
        item.boostUntil = now + 500;
      }
      if (
        item.y <= originY + padding ||
        item.y >= originY + areaHeight - sizeY - padding
      ) {
        item.vy = item.vy === 0 ? (Math.random() - 0.5) * 0.6 : -item.vy;
        item.boostUntil = now + 500;
      }

      state.forEach((other, otherIndex) => {
        if (index === otherIndex) {
          return;
        }
        const dx = item.x - other.x;
        const dy = item.y - other.y;
        const distance = Math.sqrt(dx * dx + dy * dy);
        const minDistance = Math.max(sizeX, sizeY) * 0.95;
        if (distance < minDistance && distance > 0) {
          const push = (minDistance - distance) / minDistance;
          item.x += (dx / distance) * push;
          item.y += (dy / distance) * push;
        }
      });

      if (hoveredIndex !== null) {
        const target = state[hoveredIndex];
        const dx = item.x - target.x;
        const dy = item.y - target.y;
        const distance = Math.sqrt(dx * dx + dy * dy) || 1;
        const repel = 0.6;
        item.x += (dx / distance) * repel;
        item.y += (dy / distance) * repel;
      }

      if (pointer.active) {
        const dx = item.x - pointer.x;
        const dy = item.y - pointer.y;
        const distance = Math.sqrt(dx * dx + dy * dy) || 1;
        const repel = 0.35;
        item.x += (dx / distance) * repel;
        item.y += (dy / distance) * repel;
      }

      item.x = Math.max(
        originX + padding,
        Math.min(item.x, originX + areaWidth - sizeX - padding)
      );
      item.y = Math.max(
        originY + padding,
        Math.min(item.y, originY + areaHeight - sizeY - padding)
      );

      if (
        item.x === originX + padding ||
        item.x === originX + areaWidth - sizeX - padding
      ) {
        item.vx = item.vx === 0 ? (Math.random() - 0.5) * 0.6 : item.vx;
      }
      if (
        item.y === originY + padding ||
        item.y === originY + areaHeight - sizeY - padding
      ) {
        item.vy = item.vy === 0 ? (Math.random() - 0.5) * 0.6 : item.vy;
      }

      workSquares[index].style.setProperty("--x", `${item.x}px`);
      workSquares[index].style.setProperty("--y", `${item.y}px`);
      workSquares[index].style.transform = `translate(${item.x}px, ${item.y}px)`;
    });

    requestAnimationFrame(step);
  }

  layoutSquares();
  workCanvas.classList.add("is-ready");
  window.addEventListener("resize", layoutSquares);
  requestAnimationFrame(step);
};

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initWorkCanvas, { once: true });
} else {
  initWorkCanvas();
}

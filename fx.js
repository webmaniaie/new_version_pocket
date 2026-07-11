/* ============================================================
   POCKET FX — interaction layer (runs after script.js).
   Requires (CDN, deferred): GSAP 3 + ScrollTrigger; Three.js is
   optional and only loaded on index/work.
   Additive only: no layout or placement changes.
   ============================================================ */
(function () {
  "use strict";

  var prefersReduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (prefersReduced) return;

  var isTouch = window.matchMedia("(hover: none), (pointer: coarse)").matches;
  var hasGSAP = typeof window.gsap !== "undefined";
  var hasST = hasGSAP && typeof window.ScrollTrigger !== "undefined";
  var hasThree = typeof window.THREE !== "undefined";
  if (hasST) gsap.registerPlugin(ScrollTrigger);

  /* ---------- AI-version cursor ---------- */
  if (!isTouch) {
    var cursorDot = document.createElement("div");
    var cursorRing = document.createElement("div");
    var cursorLabel = document.createElement("span");
    cursorDot.className = "cursor-dot";
    cursorRing.className = "cursor-ring";
    cursorLabel.className = "cursor-label";
    cursorRing.appendChild(cursorLabel);
    document.body.appendChild(cursorDot);
    document.body.appendChild(cursorRing);

    var mx = window.innerWidth / 2;
    var my = window.innerHeight / 2;
    var rx = mx;
    var ry = my;
    var dx = mx;
    var dy = my;

    document.addEventListener("mousemove", function (e) {
      mx = e.clientX;
      my = e.clientY;

      var draggable = e.target.closest("[data-draggable]");
      var labelled = e.target.closest("[data-cursor]");
      var hoverable = e.target.closest("a, button, [data-hover]");
      if (draggable) {
        cursorLabel.textContent = "Drag";
        cursorRing.classList.add("is-label", "is-drag");
        cursorRing.classList.remove("is-hover");
      } else if (labelled) {
        cursorLabel.textContent = labelled.getAttribute("data-cursor") || "";
        cursorRing.classList.add("is-label");
        cursorRing.classList.remove("is-drag");
        cursorRing.classList.remove("is-hover");
      } else {
        cursorLabel.textContent = "";
        cursorRing.classList.remove("is-label", "is-drag");
        cursorRing.classList.toggle("is-hover", !!hoverable);
      }
    }, { passive: true });

    function cursorLoop() {
      rx += (mx - rx) * 0.18;
      ry += (my - ry) * 0.18;
      dx += (mx - dx) * 0.45;
      dy += (my - dy) * 0.45;
      cursorRing.style.transform = "translate3d(" + rx + "px," + ry + "px,0) translate(-50%, -50%)";
      cursorDot.style.transform = "translate3d(" + dx + "px," + dy + "px,0) translate(-50%, -50%)";
      requestAnimationFrame(cursorLoop);
    }
    cursorLoop();
  }

  /* ---------- scroll progress bar ---------- */
  var prog = document.createElement("div");
  prog.className = "fx-progress";
  prog.setAttribute("aria-hidden", "true");
  prog.innerHTML = "<span></span>";
  document.body.appendChild(prog);
  var progBar = prog.firstChild;
  function updateBar() {
    var max = (document.documentElement.scrollHeight - window.innerHeight) || 1;
    var p = Math.min(1, Math.max(0, (window.scrollY || 0) / max));
    progBar.style.transform = "scaleX(" + p + ")";
  }
  window.addEventListener("scroll", updateBar, { passive: true });
  window.addEventListener("resize", updateBar);
  updateBar();

  /* ---------- nav entrance (opacity only — nav is centered via transform) ---------- */
  var nav = document.querySelector(".site-nav");
  if (nav && hasGSAP) {
    var navIn = function () {
      gsap.fromTo(nav, { opacity: 0 }, { opacity: 1, duration: 0.9, ease: "power2.out", delay: 0.35, overwrite: true });
    };
    if (document.body.classList.contains("intro-locked")) {
      // wait for the intro to be dismissed — an early inline opacity would
      // defeat the .intro-locked nav hide and float the nav over the intro
      var introEl = document.getElementById("intro");
      var armed = false;
      var onDismiss = function () {
        if (armed) return;
        armed = true;
        navIn();
      };
      if (introEl) introEl.addEventListener("click", onDismiss, { once: true });
      window.addEventListener("keydown", onDismiss, { once: true });
      window.addEventListener("touchend", onDismiss, { once: true });
    } else {
      navIn();
    }
  }

  /* ---------- rolling letters on the existing buttons ---------- */
  function buildFlip(el) {
    if (el.dataset.fxFlip) return;
    var text = el.textContent.replace(/\s+/g, " ").trim();
    if (!text) return;
    el.dataset.fxFlip = "1";
    if (!el.getAttribute("aria-label")) el.setAttribute("aria-label", text);
    el.textContent = "";
    var host = document.createElement("span");
    host.className = "fx-flip";
    host.setAttribute("aria-hidden", "true");
    el.appendChild(host);
    text.split("").forEach(function (chr, i) {
      var c = chr === " " ? " " : chr;
      var fl = document.createElement("span");
      fl.className = "fl";
      fl.style.setProperty("--i", i);
      var top = document.createElement("i");
      top.textContent = c;
      var bottom = document.createElement("i");
      bottom.textContent = c;
      fl.appendChild(top);
      fl.appendChild(bottom);
      host.appendChild(fl);
    });
  }
  if (!isTouch) {
    document.querySelectorAll(".nav-links a, .btn.nav-cta, .home-banner-cta").forEach(buildFlip);
  }

  /* ---------- char + word split helpers ---------- */
  function splitChars(node) {
    Array.prototype.slice.call(node.childNodes).forEach(function (n) {
      if (n.nodeType === 3) {
        var frag = document.createDocumentFragment();
        n.nodeValue.split(/(\s+)/).forEach(function (part) {
          if (!part) return;
          if (/^\s+$/.test(part)) {
            frag.appendChild(document.createTextNode(" "));
            return;
          }
          var word = document.createElement("span");
          word.className = "fx-w";
          part.split("").forEach(function (chr) {
            var s = document.createElement("span");
            s.className = "fx-ch";
            s.textContent = chr;
            word.appendChild(s);
          });
          frag.appendChild(word);
        });
        node.replaceChild(frag, n);
      } else if (n.nodeType === 1) {
        splitChars(n);
      }
    });
  }
  function wrapWords(root) {
    Array.prototype.slice.call(root.childNodes).forEach(function (n) {
      if (n.nodeType === 3) {
        var frag = document.createDocumentFragment();
        n.nodeValue.split(/(\s+)/).forEach(function (part) {
          if (!part) return;
          if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(part)); return; }
          var w = document.createElement("span");
          w.className = "fx-w";
          w.textContent = part;
          frag.appendChild(w);
        });
        root.replaceChild(frag, n);
      } else if (n.nodeType === 1 && n.tagName !== "BR") {
        wrapWords(n);
      }
    });
    return root.querySelectorAll(".fx-w");
  }

  function collectFlipWords(root) {
    var words = [];
    root.querySelectorAll(".fx-line-in").forEach(function (line) {
      var wrappedWords = line.querySelectorAll(".fx-w");
      if (wrappedWords.length) {
        wrappedWords.forEach(function (word) {
          var chars = Array.prototype.slice.call(word.querySelectorAll(".fx-ch"));
          if (chars.length) words.push(chars);
        });
        return;
      }
      var current = [];
      Array.prototype.slice.call(line.childNodes).forEach(function (node) {
        if (node.nodeType === 1 && node.classList.contains("fx-ch")) {
          current.push(node);
          return;
        }
        if (current.length) {
          words.push(current);
          current = [];
        }
      });
      if (current.length) words.push(current);
    });
    return words;
  }

  function flipWord(chars) {
    chars.forEach(function (ch, i) {
      setTimeout(function () {
        ch.classList.add("is-flip");
        setTimeout(function () { ch.classList.remove("is-flip"); }, 560);
      }, i * 48);
    });
  }

  function startAutoWordFlip(root) {
    var words = collectFlipWords(root);
    if (!words.length) return;

    var index = 0;
    var timer = null;
    var started = false;
    function tick() {
      flipWord(words[index]);
      index = (index + 1) % words.length;
    }
    function start() {
      if (timer) return;
      if (!started) {
        started = true;
        setTimeout(tick, 180);
      } else {
        tick();
      }
      timer = setInterval(tick, 1000);
    }
    function stop() {
      if (!timer) return;
      clearInterval(timer);
      timer = null;
    }

    if ("IntersectionObserver" in window) {
      new IntersectionObserver(function (entries) {
        if (entries[0].isIntersecting) start();
        else stop();
      }, { threshold: 0.18 }).observe(root);
      var box = root.getBoundingClientRect();
      if (box.top < window.innerHeight && box.bottom > 0) start();
    } else {
      start();
    }
  }

  /* ---------- home banner: masked line reveal + letter flips ---------- */
  function setupBannerCopy(banner) {
    var isHeroDisplay = banner.classList.contains("fx-hero-display");
    var useAuthoredLines =
      banner.classList.contains("home-who-copy") ||
      isHeroDisplay;
    var authoredLines = useAuthoredLines
      ? Array.prototype.slice
          .call(banner.querySelectorAll(":scope > span"))
          .map(function (span) {
            return {
              text: span.textContent.replace(/\s+/g, " ").trim(),
              serif: span.hasAttribute("data-fx-serif") || span.classList.contains("fx-serif-source")
            };
          })
          .filter(function (line) { return line.text; })
      : [];

    if (!authoredLines.length) {
      var bSpan = banner.querySelector("span");
      var line1 = "";
      Array.prototype.slice.call(banner.childNodes).forEach(function (n) {
        if (n.nodeType === 3) line1 += n.nodeValue;
      });
      line1 = line1.replace(/\s+/g, " ").trim();
      var line2 = bSpan ? bSpan.textContent.replace(/\s+/g, " ").trim() : "";
      authoredLines = [
        { text: line1, serif: false },
        { text: line2, serif: true }
      ].filter(function (line) { return line.text; });
    }

    banner.setAttribute(
      "aria-label",
      authoredLines.map(function (line) { return line.text; }).join(" ")
    );
    banner.innerHTML = "";

    function mkLine(txt, serif) {
      var line = document.createElement("span");
      line.className = "fx-line";
      line.setAttribute("aria-hidden", "true");
      var inner = document.createElement("span");
      inner.className = "fx-line-in" + (serif ? " fx-serif" : "");
      inner.textContent = txt;
      line.appendChild(inner);
      banner.appendChild(line);
      return inner;
    }
    var inners = authoredLines.map(function (line) {
      return mkLine(line.text, line.serif);
    });
    if (hasGSAP) {
      gsap.set(inners, { yPercent: 110 });
      if (isHeroDisplay) {
        var heroSafety = setTimeout(function () {
          gsap.set(inners, { yPercent: 0, clearProps: "transform" });
        }, 1700);
        gsap.to(inners, {
          yPercent: 0,
          duration: 1.2,
          stagger: 0.09,
          ease: "power4.out",
          delay: 0.18,
          clearProps: "transform",
          onComplete: function () {
            clearTimeout(heroSafety);
            gsap.set(inners, { yPercent: 0, clearProps: "transform" });
          }
        });
      } else if (hasST) {
        gsap.to(inners, {
          yPercent: 0, duration: 1.1, stagger: 0.12, ease: "power4.out",
          scrollTrigger: { trigger: banner, start: "top 92%" }
        });
      }
    }
    inners.forEach(splitChars);
    setTimeout(function () { startAutoWordFlip(banner); }, isHeroDisplay ? 1600 : 700);
    if (!isTouch) {
      banner.addEventListener("mouseover", function (e) {
        var ch = e.target.closest(".fx-ch");
        if (!ch || ch.classList.contains("is-flip")) return;
        ch.classList.add("is-flip");
        setTimeout(function () { ch.classList.remove("is-flip"); }, 520);
      });
    }
  }
  document.querySelectorAll(".home-banner-text, .home-who-copy, .fx-hero-display").forEach(setupBannerCopy);

  /* ---------- hero headline: word-by-word scrub reveal ---------- */
  var headline = document.querySelector(".hero-headline");
  if (headline && hasGSAP && hasST) {
    var words = wrapWords(headline);
    if (words.length) {
      gsap.fromTo(words, { opacity: 0.15 }, {
        opacity: 1, stagger: 0.05, ease: "none",
        scrollTrigger: { trigger: headline, start: "top 85%", end: "bottom 55%", scrub: true }
      });
    }
  }

  /* ---------- scroll reveals (cards keep their places, just enter nicely) ---------- */
  if (hasGSAP && hasST) {
    document.querySelectorAll(".us-video-card, .us-profile-card, .home-media").forEach(function (el, i) {
      gsap.fromTo(el, { opacity: 0, y: 40 }, {
        opacity: 1, y: 0, duration: 1, ease: "power3.out", delay: (i % 2) * 0.1,
        scrollTrigger: { trigger: el, start: "top 90%" }
      });
    });
    document.querySelectorAll(".us-top-images img").forEach(function (el, i) {
      gsap.fromTo(el, { opacity: 0, y: 30 }, {
        opacity: 1, y: 0, duration: 1, ease: "power3.out", delay: i * 0.12,
        scrollTrigger: { trigger: el, start: "top 92%" }
      });
    });
    // note keeps its CSS translateX — fade opacity only
    var note = document.querySelector(".work-desktop-note");
    if (note) {
      gsap.fromTo(note, { opacity: 0 }, {
        opacity: 1, duration: 1.2, ease: "power2.out", delay: 0.3
      });
    }
  }

  /* ---------- team number chips ---------- */
  var teamCards = document.querySelectorAll(".us-video-card, .us-profile-card");
  teamCards.forEach(function (card, i) {
    var num = document.createElement("span");
    num.className = "fx-num";
    num.textContent = "/ 0" + (i + 1);
    card.appendChild(num);
  });

  /* ---------- Three.js particles behind hero openings ---------- */
  function particles(holderParent, opts) {
    if (!holderParent) return;
    var holder = document.createElement("div");
    holder.className = "fx-webgl";
    holder.setAttribute("aria-hidden", "true");
    holderParent.insertBefore(holder, holderParent.firstChild);

    function canvasFallback() {
      holder.classList.add("fx-webgl-fallback");
      holder.innerHTML = "";
      var canvas = document.createElement("canvas");
      var ctx = canvas.getContext("2d");
      if (!ctx) return;
      holder.appendChild(canvas);
      var dpr = Math.min(window.devicePixelRatio || 1, 2);
      var W = 0;
      var H = 0;
      var px = [];
      var amount = window.innerWidth < 900 ? 120 : 260;
      var targetX = 0;
      var targetY = 0;
      var currentX = 0;
      var currentY = 0;
      var color = opts.fallbackColor || "rgba(11, 11, 11, 0.28)";
      var wire = opts.fallbackWireColor || "rgba(11, 11, 11, 0.22)";

      function resizeFallback() {
        W = holder.clientWidth || holderParent.clientWidth || window.innerWidth;
        H = holder.clientHeight || holderParent.clientHeight || 520;
        canvas.width = Math.max(1, Math.floor(W * dpr));
        canvas.height = Math.max(1, Math.floor(H * dpr));
        canvas.style.width = W + "px";
        canvas.style.height = H + "px";
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        px = [];
        for (var i = 0; i < amount; i++) {
          px.push({
            x: Math.random() * W,
            y: Math.random() * H,
            z: 0.35 + Math.random() * 0.9,
            r: 0.7 + Math.random() * 1.5
          });
        }
      }

      document.addEventListener("mousemove", function (e) {
        targetX = (e.clientX / window.innerWidth - 0.5) * 2;
        targetY = (e.clientY / window.innerHeight - 0.5) * 2;
      }, { passive: true });

      function drawFallback() {
        currentX += (targetX - currentX) * 0.04;
        currentY += (targetY - currentY) * 0.04;
        ctx.clearRect(0, 0, W, H);
        ctx.save();
        ctx.translate(currentX * 26, currentY * 18);
        ctx.fillStyle = color;
        for (var i = 0; i < px.length; i++) {
          var p = px[i];
          ctx.globalAlpha = 0.35 + p.z * 0.45;
          ctx.beginPath();
          ctx.arc(p.x, p.y, p.r * p.z, 0, Math.PI * 2);
          ctx.fill();
        }
        ctx.globalAlpha = 1;
        ctx.strokeStyle = wire;
        ctx.lineWidth = 1;
        var cx = W * 0.68 + currentX * 42;
        var cy = H * 0.38 + currentY * 34;
        var radius = Math.min(W, H) * 0.11;
        for (var j = 0; j < 3; j++) {
          ctx.beginPath();
          ctx.ellipse(cx, cy, radius, radius * (0.34 + j * 0.16), (j * Math.PI) / 3 + currentX * 0.35, 0, Math.PI * 2);
          ctx.stroke();
        }
        ctx.beginPath();
        ctx.arc(cx, cy, radius, 0, Math.PI * 2);
        ctx.stroke();
        ctx.restore();
        requestAnimationFrame(drawFallback);
      }

      resizeFallback();
      drawFallback();
      window.addEventListener("resize", resizeFallback);
    }

    if (!hasThree) { canvasFallback(); return; }

    var renderer;
    try {
      renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true, powerPreference: "low-power" });
    } catch (e) { canvasFallback(); return; }
    var W = holder.clientWidth || holderParent.clientWidth;
    var H = holder.clientHeight || holderParent.clientHeight;
    if (!W || !H) { canvasFallback(); return; }
    renderer.setSize(W, H);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    holder.appendChild(renderer.domElement);

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(60, W / H, 0.1, 100);
    camera.position.z = 9;

    var COUNT = opts.count || (window.innerWidth < 900 ? 900 : 2200);
    var spreadX = opts.spreadX || 24;
    var spreadY = opts.spreadY || 14;
    var geo = new THREE.BufferGeometry();
    var pos = new Float32Array(COUNT * 3);
    for (var i = 0; i < COUNT; i++) {
      pos[i * 3] = (Math.random() - 0.5) * spreadX;
      pos[i * 3 + 1] = (Math.random() - 0.5) * spreadY;
      pos[i * 3 + 2] = (Math.random() - 0.5) * 10;
    }
    geo.setAttribute("position", new THREE.BufferAttribute(pos, 3));
    var mat = new THREE.PointsMaterial({
      color: opts.color, size: opts.size || 0.045,
      transparent: true, opacity: opts.opacity, depthWrite: false,
      blending: opts.additive === false ? THREE.NormalBlending : THREE.AdditiveBlending
    });
    var points = new THREE.Points(geo, mat);
    scene.add(points);

    var wire = null;
    if (opts.wire) {
      wire = new THREE.Mesh(
        new THREE.IcosahedronGeometry(2.6, 1),
        new THREE.MeshBasicMaterial({
          color: opts.wireColor || opts.color,
          wireframe: true,
          transparent: true,
          opacity: opts.wireOpacity == null ? 0.12 : opts.wireOpacity
        })
      );
      var wp = opts.wirePos || [4.5, 0.6, -2];
      wire.position.set(wp[0], wp[1], wp[2]);
      scene.add(wire);
    }

    var tx = 0, ty = 0, cx = 0, cy = 0;
    document.addEventListener("mousemove", function (e) {
      tx = (e.clientX / window.innerWidth - 0.5) * 2;
      ty = (e.clientY / window.innerHeight - 0.5) * 2;
    });

    var clock = new THREE.Clock();
    var running = true;
    function animate() {
      if (!running) return;
      requestAnimationFrame(animate);
      var t = clock.getElapsedTime();
      cx += (tx - cx) * 0.03;
      cy += (ty - cy) * 0.03;
      points.rotation.y = t * 0.03 + cx * 0.15;
      points.rotation.x = cy * 0.1;
      points.position.y = Math.sin(t * 0.4) * 0.25;
      if (wire) {
        wire.rotation.x = t * 0.12;
        wire.rotation.y = t * 0.16;
      }
      renderer.render(scene, camera);
    }
    animate();

    if ("IntersectionObserver" in window) {
      new IntersectionObserver(function (entries) {
        var vis = entries[0].isIntersecting;
        if (vis && !running) { running = true; animate(); }
        else if (!vis) running = false;
      }, { threshold: 0 }).observe(holder);
    }

    window.addEventListener("resize", function () {
      W = holder.clientWidth; H = holder.clientHeight;
      if (!W || !H) return;
      camera.aspect = W / H;
      camera.updateProjectionMatrix();
      renderer.setSize(W, H);
    });
  }

  var hasWideCanvas = window.matchMedia("(min-width: 901px)").matches;
  if (hasWideCanvas) {
    // spread matches the banner's wide aspect so the field fills the whole strip
    particles(document.querySelector(".home-page .home-video-banner"), {
      color: 0x00b80d, opacity: 0.6, size: 0.07, wire: true, count: 2600,
      spreadX: 64, spreadY: 12, wirePos: [9, 0.2, -2]
    });
  }
  particles(document.querySelector(".work-refresh-hero"), {
    color: 0x0b0b0b,
    opacity: 0.22,
    size: 0.04,
    wire: true,
    wireOpacity: 0.18,
    additive: false
  });
  particles(document.querySelector(".us-refresh-hero"), {
    color: 0x0b0b0b,
    opacity: 0.22,
    size: 0.04,
    wire: true,
    wireOpacity: 0.18,
    additive: false
  });
  particles(document.querySelector(".services-hero"), {
    color: 0x0b0b0b,
    opacity: 0.22,
    size: 0.04,
    wire: true,
    wireOpacity: 0.18,
    additive: false
  });

  /* ---------- intro: sparkles falling top -> bottom (desktop + mobile) ---------- */
  (function introSparkles() {
    var intro = document.getElementById("intro");
    if (!intro || !hasThree) return;
    if (intro.classList.contains("exit")) return;

    var holder = document.createElement("div");
    holder.className = "fx-webgl intro-webgl";
    holder.setAttribute("aria-hidden", "true");
    intro.insertBefore(holder, intro.firstChild);

    var renderer;
    try {
      renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true, powerPreference: "low-power" });
    } catch (e) { return; }
    var W = window.innerWidth, H = window.innerHeight;
    renderer.setSize(W, H);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    holder.appendChild(renderer.domElement);

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(60, W / H, 0.1, 100);
    camera.position.z = 9;

    var visH = 2 * 9 * Math.tan(Math.PI / 6); // visible world height at z=0
    var visW = visH * (W / H);
    var COUNT = W < 900 ? 300 : 560;
    var geo = new THREE.BufferGeometry();
    var pos = new Float32Array(COUNT * 3);
    var vel = new Float32Array(COUNT);
    var sway = new Float32Array(COUNT);
    var phase = new Float32Array(COUNT);
    for (var i = 0; i < COUNT; i++) {
      pos[i * 3] = (Math.random() - 0.5) * visW * 1.15;
      pos[i * 3 + 1] = (Math.random() - 0.5) * visH * 1.2;
      pos[i * 3 + 2] = (Math.random() - 0.5) * 4;
      vel[i] = 0.014 + Math.random() * 0.034;
      sway[i] = 0.2 + Math.random() * 0.6;
      phase[i] = Math.random() * Math.PI * 2;
    }
    geo.setAttribute("position", new THREE.BufferAttribute(pos, 3));
    var mat = new THREE.PointsMaterial({
      color: 0xffffff, size: 0.08, transparent: true, opacity: 0.85,
      depthWrite: false, blending: THREE.AdditiveBlending
    });
    scene.add(new THREE.Points(geo, mat));

    var clock = new THREE.Clock();
    var alive = true;
    function tick() {
      if (!alive) return;
      requestAnimationFrame(tick);
      var t = clock.getElapsedTime();
      var arr = geo.attributes.position.array;
      var top = visH * 0.62;
      for (var i = 0; i < COUNT; i++) {
        arr[i * 3 + 1] -= vel[i];
        arr[i * 3] += Math.sin(t * sway[i] + phase[i]) * 0.004;
        if (arr[i * 3 + 1] < -top) {
          arr[i * 3 + 1] = top;
          arr[i * 3] = (Math.random() - 0.5) * visW * 1.15;
        }
      }
      geo.attributes.position.needsUpdate = true;
      mat.opacity = 0.72 + Math.sin(t * 2.1) * 0.16;
      renderer.render(scene, camera);
    }
    tick();

    // let the exit transition finish, then free the GPU
    function stop() { setTimeout(function () { alive = false; }, 1400); }
    intro.addEventListener("click", stop, { once: true });
    window.addEventListener("keydown", stop, { once: true });
    window.addEventListener("touchend", stop, { once: true });

    window.addEventListener("resize", function () {
      W = window.innerWidth; H = window.innerHeight;
      visW = visH * (W / H);
      camera.aspect = W / H;
      camera.updateProjectionMatrix();
      renderer.setSize(W, H);
    });
  })();

  /* keyboard access for the services cards (script.js listens for pointerup) */
  document.querySelectorAll(".svc-card[role='button']").forEach(function (card) {
    card.addEventListener("keydown", function (e) {
      if (e.key !== "Enter" && e.key !== " ") return;
      e.preventDefault();
      var up;
      try {
        up = new PointerEvent("pointerup", { bubbles: true });
      } catch (err) {
        up = document.createEvent("Event");
        up.initEvent("pointerup", true, true);
      }
      card.dispatchEvent(up);
    });
  });
})();

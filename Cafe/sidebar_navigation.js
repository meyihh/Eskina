  // Sidebar logic
  document.querySelectorAll('.sidebar .section-header').forEach(h => {
    h.addEventListener('click', () => {
      const parent = h.parentElement;
      const targetList = parent.querySelector('.section-list');
      document.querySelectorAll('.sidebar .section-list').forEach(l => {
        if (l !== targetList) l.classList.remove('expanded');
      });
      targetList.classList.toggle('expanded');
    });
  });

  function scrollToSection(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    updateActiveSidebar(id);
  }

document.querySelectorAll('[data-target]').forEach(link => {
  link.addEventListener('click', () => {
    const targetId = link.dataset.target;
    const container = sectionMap[targetId];
    if (!container) return;

    // Hide all section titles and grids
    Object.keys(sectionMap).forEach(id => {
      const title = document.getElementById(id);
      const grid = sectionMap[id];
      if (title) title.style.display = 'none';
      if (grid) grid.style.display = 'none';
    });

    // Show the selected section title and grid
    const selectedTitle = document.getElementById(targetId);
    if (selectedTitle) selectedTitle.style.display = 'block';
    if (container) container.style.display = 'grid';

    // Load products only if not already loaded
    if (container.children.length === 0) {
      const sectionProducts = products.filter(p => p.sectionId === targetId);
      sectionProducts.forEach(p => container.appendChild(createProductCard(p)));
    }

    // Scroll to view
    selectedTitle.scrollIntoView({ behavior: 'smooth' });

    lastActiveSectionId = targetId;
  });
});

  function updateActiveSidebar(targetId) {
    document.querySelectorAll('.sidebar .section-list li').forEach(li => li.classList.remove('active'));
    const matched = document.querySelectorAll(`.sidebar a[data-target="${targetId}"]`);
    matched.forEach(a => a.parentElement.classList.add('active'));
  }

  document.querySelectorAll('.sidebar .section-list li a').forEach(a => {
    a.addEventListener('click', function(e) {
      e.preventDefault();
      const target = this.dataset.target;
      if (target) {
        scrollToSection(target);
        document.getElementById('globalSearch').value = this.textContent.trim();
      }
    });
  });

    const sections = [
      'classics-section','specials-section','iceblendedcoffee',
      'iceblendedcream','tea','refreshers','anticoffee','extras',
      'ricebowls','munchies','pasta','wraps'
    ].map(id => document.getElementById(id)).filter(Boolean);

  window.addEventListener('scroll', () => {
    const fromTop = window.scrollY + 120;
    let current = sections[0]?.id;
    sections.forEach(sec => {
      if (sec.offsetTop <= fromTop) current = sec.id;
    });
    if (current) updateActiveSidebar(current);
  });
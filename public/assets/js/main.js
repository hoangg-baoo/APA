document.addEventListener("DOMContentLoaded", () => {
  setupLoginForm();
  setupRegisterForm();
  setupForgotForm();
  setupProfileForm();
  setupChangePasswordForm();
});

function isValidEmail(email) {
  return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email);
}

/* ========== LOGIN VALIDATION ========== */

function setupLoginForm() {
  const form = document.getElementById("form-login");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    const emailInput = form.querySelector('input[name="email"]');
    const passwordInput = form.querySelector('input[name="password"]');

    let valid = true;

    emailInput.setCustomValidity("");
    passwordInput.setCustomValidity("");

    const email = emailInput.value.trim();
    const password = passwordInput.value.trim();

    if (!email) {
      emailInput.setCustomValidity("Email is required.");
      valid = false;
    } else if (!isValidEmail(email)) {
      emailInput.setCustomValidity("Please enter a valid email address.");
      valid = false;
    }

    if (!password) {
      passwordInput.setCustomValidity("Password is required.");
      valid = false;
    } else if (password.length < 8) {
      passwordInput.setCustomValidity("Password must be at least 8 characters.");
      valid = false;
    }

    if (!valid) {
      e.preventDefault();
      // Show the first invalid field's message
      if (emailInput.validationMessage) {
        emailInput.reportValidity();
      } else if (passwordInput.validationMessage) {
        passwordInput.reportValidity();
      }
    }
  });
}

/* ========== REGISTER VALIDATION ========== */

function setupRegisterForm() {
  const form = document.getElementById("form-register");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    const nameInput = form.querySelector('input[name="name"]');
    const emailInput = form.querySelector('input[name="email"]');
    const passwordInput = form.querySelector('input[name="password"]');
    const confirmInput = form.querySelector('input[name="password_confirmation"]');

    let valid = true;

    [nameInput, emailInput, passwordInput, confirmInput].forEach((el) =>
      el.setCustomValidity("")
    );

    const name = nameInput.value.trim();
    const email = emailInput.value.trim();
    const password = passwordInput.value.trim();
    const confirm = confirmInput.value.trim();

    if (!name) {
      nameInput.setCustomValidity("Full name is required.");
      valid = false;
    }

    if (!email) {
      emailInput.setCustomValidity("Email is required.");
      valid = false;
    } else if (!isValidEmail(email)) {
      emailInput.setCustomValidity("Please enter a valid email address.");
      valid = false;
    }

    if (!password) {
      passwordInput.setCustomValidity("Password is required.");
      valid = false;
    } else if (password.length < 8) {
      passwordInput.setCustomValidity("Password must be at least 8 characters.");
      valid = false;
    }

    if (!confirm) {
      confirmInput.setCustomValidity("Please confirm your password.");
      valid = false;
    } else if (password && confirm && password !== confirm) {
      confirmInput.setCustomValidity("Passwords do not match.");
      valid = false;
    }

    if (!valid) {
      e.preventDefault();
      // Report the first invalid
      for (const el of [nameInput, emailInput, passwordInput, confirmInput]) {
        if (el.validationMessage) {
          el.reportValidity();
          break;
        }
      }
    }
  });
}

/* ========== FORGOT PASSWORD VALIDATION ========== */

function setupForgotForm() {
  const form = document.getElementById("form-forgot");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    const emailInput = form.querySelector('input[name="email"]');
    let valid = true;
    emailInput.setCustomValidity("");
    const email = emailInput.value.trim();

    if (!email) {
      emailInput.setCustomValidity("Email is required.");
      valid = false;
    } else if (!isValidEmail(email)) {
      emailInput.setCustomValidity("Please enter a valid email address.");
      valid = false;
    }

    if (!valid) {
      e.preventDefault();
      emailInput.reportValidity();
    }
  });
}

/* ========== PROFILE FORM VALIDATION (simple) ========== */

function setupProfileForm() {
  const form = document.getElementById("form-profile");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    const nameInput = form.querySelector('input[name="name"]');
    nameInput.setCustomValidity("");

    if (!nameInput.value.trim()) {
      e.preventDefault();
      nameInput.setCustomValidity("Full name is required.");
      nameInput.reportValidity();
    }
  });
}

/* ========== CHANGE PASSWORD FORM VALIDATION ========== */

function setupChangePasswordForm() {
  const form = document.getElementById("form-change-password");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    const currentInput = form.querySelector('input[name="current_password"]');
    const newInput = form.querySelector('input[name="new_password"]');
    const confirmInput = form.querySelector(
      'input[name="new_password_confirmation"]'
    );

    [currentInput, newInput, confirmInput].forEach((el) =>
      el.setCustomValidity("")
    );

    let valid = true;

    if (!currentInput.value.trim()) {
      currentInput.setCustomValidity("Current password is required.");
      valid = false;
    }

    const newPass = newInput.value.trim();
    const confirm = confirmInput.value.trim();

    if (!newPass) {
      newInput.setCustomValidity("New password is required.");
      valid = false;
    } else if (newPass.length < 8) {
      newInput.setCustomValidity("New password must be at least 8 characters.");
      valid = false;
    }

    if (!confirm) {
      confirmInput.setCustomValidity("Please confirm your new password.");
      valid = false;
    } else if (newPass && confirm && newPass !== confirm) {
      confirmInput.setCustomValidity("New passwords do not match.");
      valid = false;
    }

    if (!valid) {
      e.preventDefault();
      for (const el of [currentInput, newInput, confirmInput]) {
        if (el.validationMessage) {
          el.reportValidity();
          break;
        }
      }
    }
  });
}




document.addEventListener("DOMContentLoaded", () => {
  initTankSearch();
  initDeleteTankButtons();
  initTankTabs();
  initPlantSearch();
  initSelectPlant();
  initCreateTankButton();
  initWaterLogPage();
  initQAPages();
  initCommunityPages();
  initImageAdminPages();

});

/* ========== NAV HELPERS (dùng demo) ========== */

function goToTankDetail(id) {
  // sau này dùng query param / route thực tế, tạm demo:
  window.location.href = "tank_detail.html";
}

function goToTankFormEdit(id) {
  window.location.href = "tank_form.html";
}

function goToAddPlant(tankId) {
  window.location.href = "add_plant_to_tank.html";
}

function goToWaterLogForm(tankId) {
  // ví dụ sau sẽ link sang màn monitoring riêng
  alert("In the real app this will open the Add Water Log form for tank id " + tankId);
}

/* ========== MY_TANKS: SEARCH + DELETE CONFIRM ========== */

function initTankSearch() {
  const input = document.getElementById("tank-search");
  const table = document.getElementById("tanks-table");
  if (!input || !table) return;

  const rows = Array.from(table.querySelectorAll("tbody tr"));

  input.addEventListener("input", () => {
    const term = input.value.toLowerCase().trim();
    rows.forEach((row) => {
      const nameCell = row.querySelector("td");
      if (!nameCell) return;
      const text = nameCell.textContent.toLowerCase();
      row.style.display = text.includes(term) ? "" : "none";
    });
  });
}

function initDeleteTankButtons() {
  const buttons = document.querySelectorAll(".btn-delete-tank");
  if (!buttons.length) return;

  buttons.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const id = btn.getAttribute("data-id");
      const ok = confirm("Are you sure you want to delete this tank?");
      if (!ok) {
        e.preventDefault();
        return;
      }
      // Prototype: chỉ alert, sau này sẽ gọi API / submit form.
      alert("Tank " + id + " deleted (demo only).");
    });
  });
}

/* ========== TANK DETAIL: TAB SWITCHING ========== */

function initTankTabs() {
  const tabsContainer = document.querySelector('[data-tabs="tank-detail-tabs"]');
  if (!tabsContainer) return;

  const buttons = tabsContainer.querySelectorAll(".tab-link");
  const panels = document.querySelectorAll(".tab-panel");

  buttons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const targetId = btn.getAttribute("data-tab-target");

      // remove active on all
      buttons.forEach((b) => b.classList.remove("active"));
      panels.forEach((p) => p.classList.remove("active"));

      // active current
      btn.classList.add("active");
      const panel = document.getElementById(targetId);
      if (panel) panel.classList.add("active");
    });
  });
}

/* ========== ADD PLANT: SEARCH FILTER ========== */

function initPlantSearch() {
  const input = document.getElementById("plant-search");
  const table = document.getElementById("plants-table");
  if (!input || !table) return;

  const rows = Array.from(table.querySelectorAll("tbody tr"));

  input.addEventListener("input", () => {
    const term = input.value.toLowerCase().trim();
    rows.forEach((row) => {
      const nameCell = row.querySelector("td");
      if (!nameCell) return;
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(term) ? "" : "none";
    });
  });
}

/* ========== ADD PLANT: SELECT PLANT + VALIDATE FORM ========== */

function initSelectPlant() {
  const table = document.getElementById("plants-table");
  const form = document.getElementById("form-add-plant");
  if (!table || !form) return;

  const selectedIdInput = document.getElementById("selected-plant-id");
  const selectedDisplay = document.getElementById("selected-plant-display");

  table.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-select-plant");
    if (!btn) return;

    const row = btn.closest("tr");
    const plantId = row.getAttribute("data-plant-id");
    const plantName = row.querySelector("td").textContent.trim();

    selectedIdInput.value = plantId;
    selectedDisplay.textContent = plantName;
    selectedDisplay.style.color = "#111827";
  });

  form.addEventListener("submit", (e) => {
    if (!selectedIdInput.value) {
      e.preventDefault();
      alert("Please select a plant from the list before adding it to the tank.");
      return;
    }
    // Sau này sẽ submit thật. Prototype chỉ alert:
    e.preventDefault();
    alert(
      "Plant " +
        selectedDisplay.textContent +
        " has been added to this tank (demo)."
    );
  });
}

// ===== TV4 - Monitoring: Water Logs & Chart =====
function initWaterLogPage() {
  const chartCanvas = document.getElementById("waterlogChart");
  const form = document.getElementById("form-water-log");

  // không ở trang water logs => thôi
  if (!chartCanvas && !form) return;

  // ✅ nếu page dùng API thật thì bỏ qua demo
  if ((chartCanvas && chartCanvas.dataset.live === "1") || (form && form.dataset.live === "1")) {
    return;
  }

  // (giữ demo nếu bạn muốn ở page demo cũ)
  if (typeof Chart !== "undefined" && chartCanvas) {
    const ctx = chartCanvas.getContext("2d");
    new Chart(ctx, {
      type: "line",
      data: {
        labels: ["2025-02-02", "2025-02-05", "2025-02-08"],
        datasets: [
          { label: "pH", data: [6.7, 6.5, 6.6], borderWidth: 2, tension: 0.35 },
          { label: "Temp (°C)", data: [23.9, 24.1, 24.3], borderWidth: 2, tension: 0.35 },
          { label: "NO₃ (ppm)", data: [9, 10, 12], borderWidth: 2, tension: 0.35 }
        ]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });
  }

  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      alert("Demo only: in real app this will call API to save water log and get advisory.");
    });
  }
}



// ===== TV5 - Q&A UI Helpers =====
function initQAPages() {
  // 1. Filter chip behaviour on questions_list.html
  const chips = document.querySelectorAll(".chip-filter");
  const rows = document.querySelectorAll("#qaQuestionsTable tbody tr");
  if (chips.length && rows.length) {
    chips.forEach(chip => {
      chip.addEventListener("click", () => {
        const status = chip.getAttribute("data-status");

        chips.forEach(c => c.classList.remove("active"));
        chip.classList.add("active");

        rows.forEach(row => {
          const rowStatus = row.getAttribute("data-status");
          const show = status === "all" || status === rowStatus;
          row.style.display = show ? "" : "none";
        });
      });
    });
  }

  // 2. Demo: prevent real submit on ask_question.html & answer form
  const askForm = document.getElementById("form-ask-question");
  if (askForm) {
    askForm.addEventListener("submit", e => {
      e.preventDefault();
      alert("Demo only: in real app this will call the API to create a question.");
    });
  }

  const answerForm = document.getElementById("form-answer");
  if (answerForm) {
    answerForm.addEventListener("submit", e => {
      e.preventDefault();
      alert("Demo only: in real app this will post your answer and refresh the list.");
      answerForm.reset();
      // scroll lên answers sau khi gửi giả lập
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }
}


// ===== TV6 - COMMUNITY & PLANT LOG =====
function initCommunityPages() {
  // Category filter on posts_list.html
  const categoryButtons = document.querySelectorAll(".com-category");
  const postCards = document.querySelectorAll("#postsGrid .post-card");

  if (categoryButtons.length && postCards.length) {
    categoryButtons.forEach(btn => {
      btn.addEventListener("click", () => {
        const cat = btn.getAttribute("data-category");

        categoryButtons.forEach(b => b.classList.remove("active"));
        btn.classList.add("active");

        postCards.forEach(card => {
          const cardCat = card.getAttribute("data-category");
          const show = cat === "all" || cat === cardCat;
          card.style.display = show ? "" : "none";
        });
      });
    });
  }

  // Demo submit: create_post.html
  const createPostForm = document.getElementById("form-create-post");
  if (createPostForm) {
    createPostForm.addEventListener("submit", e => {
      e.preventDefault();
      alert("Demo only: this will send API request to create a post.");
    });
  }

  // Demo comment: post_detail.html
  const commentForm = document.getElementById("form-comment");
  if (commentForm) {
    commentForm.addEventListener("submit", e => {
      e.preventDefault();
      alert("Demo only: your comment would be saved and shown in the list.");
      commentForm.reset();
    });
  }

  // Demo plant log form
  const plantLogForm = document.getElementById("form-plant-log");
  if (plantLogForm) {
    plantLogForm.addEventListener("submit", e => {
      e.preventDefault();
      alert("Demo only: this will create a plant log entry.");
      plantLogForm.reset();
    });
  }
}


// ===== TV7 – IMAGE RETRIEVAL & ADMIN =====
function initImageAdminPages() {
  /* ---- Identify Plant: preview + fake result ---- */
  const plantFileInput = document.getElementById("plant_image");
  const previewImg = document.getElementById("irPreviewImg");
  const previewText = document.getElementById("irPreviewText");
  const formIdentify = document.getElementById("form-identify-plant");

  if (plantFileInput && previewImg && previewText) {
    plantFileInput.addEventListener("change", () => {
      const file = plantFileInput.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = e => {
        previewImg.src = e.target.result;
      };
      reader.readAsDataURL(file);

      previewText.textContent = `Selected file: ${file.name} (${Math.round(
        file.size / 1024
      )} KB).`;
    });
  }

  if (formIdentify) {
    formIdentify.addEventListener("submit", e => {
      e.preventDefault();
      alert(
        "Demo only: in the real system this will upload the image, call ImageRetrievalService, and refresh the suggestions."
      );
    });
  }

  /* ---- Admin: plant list filter ---- */
  const plantSearchInput = document.getElementById("plantSearchInput");
  const filterDifficulty = document.getElementById("filterDifficulty");
  const filterLight = document.getElementById("filterLight");
  const plantTable = document.getElementById("plantTable");

  if (plantTable) {
    const rows = plantTable.querySelectorAll("tbody tr");

    function applyPlantFilters() {
      const text = (plantSearchInput?.value || "").toLowerCase();
      const diff = filterDifficulty?.value || "";
      const light = filterLight?.value || "";

      rows.forEach(row => {
        const name = row.children[0].textContent.toLowerCase();
        const rd = row.getAttribute("data-difficulty");
        const rl = row.getAttribute("data-light");

        const matchText = !text || name.includes(text);
        const matchDiff = !diff || rd === diff;
        const matchLight = !light || rl === light;

        row.style.display = matchText && matchDiff && matchLight ? "" : "none";
      });
    }

    plantSearchInput?.addEventListener("input", applyPlantFilters);
    filterDifficulty?.addEventListener("change", applyPlantFilters);
    filterLight?.addEventListener("change", applyPlantFilters);
  }

  /* ---- Admin: user list filter ---- */
  const usersTable = document.getElementById("usersTable");
  const userSearchInput = document.getElementById("userSearchInput");
  const filterRole = document.getElementById("filterRole");

  if (usersTable) {
    const rows = usersTable.querySelectorAll("tbody tr");

    function applyUserFilters() {
      const text = (userSearchInput?.value || "").toLowerCase();
      const role = filterRole?.value || "";

      rows.forEach(row => {
        const name = row.children[0].textContent.toLowerCase();
        const email = row.children[1].textContent.toLowerCase();
        const rowRole = row.getAttribute("data-role");

        const matchText = !text || name.includes(text) || email.includes(text);
        const matchRole = !role || rowRole === role;

        row.style.display = matchText && matchRole ? "" : "none";
      });
    }

    userSearchInput?.addEventListener("input", applyUserFilters);
    filterRole?.addEventListener("change", applyUserFilters);
  }

  /* ---- Admin: Edit roles modal ---- */
  const modal = document.getElementById("editRolesModal");
  if (modal) {
    const editButtons = document.querySelectorAll(".edit-roles-btn");
    const userNameLabel = document.getElementById("editRolesUserName");
    const btnClose = document.getElementById("closeRolesModal");
    const btnCancel = document.getElementById("cancelRolesBtn");
    const btnSave = document.getElementById("saveRolesBtn");

    function closeModal() {
      modal.classList.remove("show");
    }

    editButtons.forEach(btn => {
      btn.addEventListener("click", () => {
        const userName = btn.getAttribute("data-user");
        userNameLabel.textContent = userName;
        modal.classList.add("show");
      });
    });

    btnClose?.addEventListener("click", closeModal);
    btnCancel?.addEventListener("click", closeModal);

    btnSave?.addEventListener("click", () => {
      alert("Demo only: roles would be updated via API in the real system.");
      closeModal();
    });

    // Click outside to close
    modal.addEventListener("click", e => {
      if (e.target === modal) closeModal();
    });
  }

  /* ---- Admin: plant form submit demo ---- */
//   const plantForm = document.getElementById("plantForm");
//   if (plantForm) {
//     plantForm.addEventListener("submit", e => {
//       e.preventDefault();
//       alert("Demo only: this will call the API to create/update a plant.");
//     });
//   }
// }
}

function initCreateTankButton() {
  const btn = document.getElementById("btn-create-tank");
  if (!btn) return;

  btn.addEventListener("click", () => {
    // vì my_tanks.html và tank_form.html cùng folder /pages/tanks/
    window.location.href = "tank_form.html";
  });
}

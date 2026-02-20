// ─── SCROLL TO TOP ON LOAD ───
window.addEventListener('load', function() {
  setTimeout(() => {
    window.scrollTo(0, 0);
  }, 0);
  
  // Check for errors
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('error') === 'duplicate') {
    setTimeout(() => {
      showToast("⚠️ This phone number or email is already registered!", "error");
    }, 500);
  } else if (urlParams.get('error') === 'invalid_phone') {
    setTimeout(() => {
      showToast("⚠️ Invalid phone number! Must be 10 digits starting with 6-9.", "error");
    }, 500);
  } else if (urlParams.get('error') === 'invalid_email') {
    setTimeout(() => {
      showToast("⚠️ Invalid email address! Please enter a valid email.", "error");
    }, 500);
  }

  // Enable smooth scroll after everything loads
  setTimeout(() => {
    document.documentElement.classList.add('loaded');
  }, 100);
});

// Immediate scroll on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
  window.scrollTo(0, 0);
});

// ─── DYNAMIC PARTICIPANT FIELDS ───
function updateParticipantFields() {
  const teamSize = document.getElementById("team_size").value;
  const optionalMembers = document.querySelectorAll(".optional-member");
  
  optionalMembers.forEach(member => {
    member.style.display = "none";
    // Clear optional fields
    member.querySelectorAll("input, select").forEach(field => {
      if (!field.hasAttribute("required")) {
        field.value = "";
      }
    });
  });
  
  // Show selected number of participants
  if (teamSize >= 2) optionalMembers[0].style.display = "block";
  if (teamSize >= 3) optionalMembers[1].style.display = "block";
  if (teamSize >= 4) optionalMembers[2].style.display = "block";
}

// ─── FORM VALIDATION ───
document.addEventListener('DOMContentLoaded', function() {
  const hackForm = document.getElementById("hackForm");
  if (hackForm) {
    hackForm.addEventListener("submit", function (e) {
      const teamSize = document.getElementById("team_size").value;
      
      // Validate team size selection
      if (!teamSize) {
        e.preventDefault();
        showToast("Please select your team size", "error");
        document.getElementById("team_size").focus();
        return;
      }
      
      // Validate team leader phone
      const p1_phone = document.querySelector("[name='p1_phone']").value.trim();
      if (!validateIndianPhone(p1_phone)) {
        e.preventDefault();
        showToast("Team Leader: Please enter a valid 10-digit mobile number starting with 6-9", "error");
        document.querySelector("[name='p1_phone']").focus();
        return;
      }

      // Validate team leader food preference
      const p1_food = document.querySelector("[name='p1_food']").value.trim();
      if (!p1_food) {
        e.preventDefault();
        showToast("Team Leader: Please select a food preference", "error");
        document.querySelector("[name='p1_food']").focus();
        return;
      }

      // Validate optional members if they have partial data
      if (teamSize >= 2) {
        validateOptionalMember(2, e);
      }
      if (teamSize >= 3) {
        validateOptionalMember(3, e);
      }
      if (teamSize >= 4) {
        validateOptionalMember(4, e);
      }
    });
  }
});

// ─── PHONE VALIDATION HELPER ───
function validateIndianPhone(phone) {
  const phoneRegex = /^[6-9]\d{9}$/;
  return phoneRegex.test(phone);
}

// ─── VALIDATE OPTIONAL MEMBER ───
function validateOptionalMember(memberNum, event) {
  const name = document.querySelector(`[name='p${memberNum}']`).value.trim();
  
  // If member has a name, all fields must be filled
  if (name) {
    const phone = document.querySelector(`[name='p${memberNum}_phone']`).value.trim();
    const email = document.querySelector(`[name='p${memberNum}_email']`).value.trim();
    const food = document.querySelector(`[name='p${memberNum}_food']`).value.trim();
    
    if (!phone) {
      event.preventDefault();
      showToast(`Member ${memberNum}: Please enter mobile number`, "error");
      document.querySelector(`[name='p${memberNum}_phone']`).focus();
      return;
    }
    
    if (!validateIndianPhone(phone)) {
      event.preventDefault();
      showToast(`Member ${memberNum}: Invalid phone number (must start with 6-9 and be 10 digits)`, "error");
      document.querySelector(`[name='p${memberNum}_phone']`).focus();
      return;
    }
    
    if (!email || !email.includes("@")) {
      event.preventDefault();
      showToast(`Member ${memberNum}: Please enter a valid email address`, "error");
      document.querySelector(`[name='p${memberNum}_email']`).focus();
      return;
    }
    
    if (!food) {
      event.preventDefault();
      showToast(`Member ${memberNum}: Please select food preference`, "error");
      document.querySelector(`[name='p${memberNum}_food']`).focus();
      return;
    }
  }
}

// ─── TOAST NOTIFICATION ───
function showToast(message, type = "info") {
  const existing = document.querySelector(".toast");
  if (existing) existing.remove();

  const toast = document.createElement("div");
  toast.className = "toast";
  toast.textContent = message;
  
  const bgColor = type === "error" ? "#ef5350" : type === "success" ? "#66bb6a" : "#29b6f6";
  
  toast.style.cssText = `
    position: fixed;
    bottom: 28px;
    right: 28px;
    padding: 16px 24px;
    background: ${bgColor};
    color: #ffffff;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 500;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    z-index: 9999;
    animation: slideInToast 0.3s ease forwards;
    max-width: 400px;
  `;

  const style = document.createElement("style");
  style.textContent = `
    @keyframes slideInToast {
      from { opacity: 0; transform: translateY(10px) scale(0.95); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes slideOutToast {
      to { opacity: 0; transform: translateY(10px) scale(0.95); }
    }
  `;
  document.head.appendChild(style);
  document.body.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = "slideOutToast 0.3s ease forwards";
    setTimeout(() => toast.remove(), 300);
  }, 4000);
}

// ─── ACTIVE STEP HIGHLIGHT ON SCROLL / FOCUS ───
const sections = document.querySelectorAll(".form-section");
const steps = document.querySelectorAll(".reg-step");

if (sections.length && steps.length) {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const idx = Array.from(sections).indexOf(entry.target);
          steps.forEach((s) => s.classList.remove("active"));
          if (steps[idx]) steps[idx].classList.add("active");
        }
      });
    },
    { threshold: 0.5 }
  );
  sections.forEach((s) => observer.observe(s));
}

// ─── INPUT ANIMATIONS & INTERACTIONS ───
document.querySelectorAll("input, textarea, select").forEach((el) => {
  el.addEventListener("focus", () => {
    const card = el.closest(".participant-card");
    if (card) {
      card.classList.add("focused");
    }
  });
  
  el.addEventListener("blur", () => {
    const card = el.closest(".participant-card");
    if (card) {
      card.classList.remove("focused");
    }
  });
  
  // Phone number formatting for Indian numbers
  if (el.type === "tel") {
    el.addEventListener("input", (e) => {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length > 10) {
        value = value.slice(0, 10);
      }
      e.target.value = value;
    });
  }
});

// ─── SMOOTH FORM SECTION TRANSITIONS ───
document.addEventListener("DOMContentLoaded", () => {
  const formSections = document.querySelectorAll(".form-section");
  formSections.forEach((section, index) => {
    section.style.opacity = "0";
    section.style.animation = `fadeUp 0.5s ease forwards`;
    section.style.animationDelay = `${index * 0.1}s`;
  });
});

// ─── INITIALIZE ───
document.addEventListener("DOMContentLoaded", () => {
  updateParticipantFields();
});
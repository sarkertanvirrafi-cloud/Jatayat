const people = {
  customer: { title: "Rider Info", otherLabel: "Rider", name: "Afroza", department: "BBA", studentId: "0112310151", phone: "01XXXXXXXXX" },
  rider: { title: "Customer Info", otherLabel: "Customer", name: "Sakib Rahman", department: "CSE", studentId: "011221045", phone: "01XXXXXXXXX" }
};

function detectProjectBase() {
  const path = window.location.pathname.replace(/\\/g, "/");
  for (const marker of ["/features/", "/api/", "/shared/"]) {
    const index = path.indexOf(marker);
    if (index >= 0) return path.slice(0, index);
  }
  if (path.endsWith("/index.html")) return path.slice(0, -11).replace(/\/$/, "");
  return path.replace(/\/$/, "");
}
const PROJECT_BASE = detectProjectBase();
function projectUrl(path) {
  const cleanPath = path.startsWith("/") ? path : `/${path}`;
  return `${PROJECT_BASE}${cleanPath}`;
}
const API_BASE = projectUrl("/api");
const AUTH_TOKEN_KEY = "jatraAuthToken";
let cameraStream = null;


function fixProjectLinks() {
  document.querySelectorAll('a[href^="/features/"]').forEach(link => {
    link.setAttribute("href", projectUrl(link.getAttribute("href")));
  });
  document.querySelectorAll('img[src^="/shared/"]').forEach(image => {
    image.setAttribute("src", projectUrl(image.getAttribute("src")));
  });
}

function getRole() {
  const params = new URLSearchParams(window.location.search);
  return params.get("role") === "rider" ? "rider" : "customer";
}
function roleQuery() { return `?role=${getRole()}`; }
function goTo(path) { window.location.href = `${projectUrl(path)}${roleQuery()}`; }
function setText(id, value) { const el = document.getElementById(id); if (el) el.textContent = value; }
function token() { return localStorage.getItem(AUTH_TOKEN_KEY) || ""; }
function validUiUEmail(email) { return /^[^@\s]+@bscse\.uiu\.ac\.bd$/i.test(String(email || "").trim()); }

async function apiFetch(path, options = {}) {
  const headers = { "Content-Type": "application/json", ...(options.headers || {}) };
  if (token()) { headers.Authorization = `Bearer ${token()}`; headers["X-Auth-Token"] = token(); }
  const response = await fetch(`${API_BASE}/${path}`, { ...options, headers });
  let data = {};
  try { data = await response.json(); } catch { data = {}; }
  if (!response.ok || data.ok === false) throw new Error(data.message || "Request failed");
  return data;
}

function infoRow(label, value) {
  return `<div class="info-row"><span>${escapeHtml(label)}</span><strong>${escapeHtml(String(value ?? ""))}</strong></div>`;
}
function escapeHtml(value) {
  return String(value).replace(/[&<>'"]/g, ch => ({"&":"&amp;","<":"&lt;",">":"&gt;","'":"&#39;",'"':"&quot;"}[ch]));
}
function destinationsText(destinations) {
  if (Array.isArray(destinations)) return destinations.join(" / ");
  if (typeof destinations === "string") {
    try { const parsed = JSON.parse(destinations); return Array.isArray(parsed) ? parsed.join(" / ") : destinations; } catch { return destinations; }
  }
  return "";
}
function applyPersonData(prefix = "") {
  const person = people[getRole()];
  if (prefix === "active") {
    setText("activePersonInfoTitle", person.title); setText("activePersonName", person.name); setText("activePersonDepartment", person.department); setText("activePersonStudentId", person.studentId); setText("activePersonPhone", person.phone); return;
  }
  setText("personInfoTitle", person.title); setText("personName", person.name); setText("personDepartment", person.department); setText("personStudentId", person.studentId); setText("personPhone", person.phone);
}
function showToast(message) {
  const toast = document.getElementById("toast");
  if (!toast) { alert(message); return; }
  toast.textContent = message; toast.classList.remove("hidden"); window.setTimeout(() => toast.classList.add("hidden"), 1800);
}

function initAuth() {
  const loginForm = document.getElementById("loginForm");
  const signupForm = document.getElementById("signupForm");
  const showLogin = document.getElementById("showLogin");
  const showSignup = document.getElementById("showSignup");

  showLogin?.addEventListener("click", () => {
    loginForm?.classList.remove("hidden"); signupForm?.classList.add("hidden");
    showLogin.classList.add("active"); showSignup?.classList.remove("active");
  });
  showSignup?.addEventListener("click", () => {
    signupForm?.classList.remove("hidden"); loginForm?.classList.add("hidden");
    showSignup.classList.add("active"); showLogin?.classList.remove("active");
  });

  loginForm?.addEventListener("submit", async event => {
    event.preventDefault();
    const email = document.getElementById("loginEmail")?.value.trim() || "";
    if (!validUiUEmail(email)) return alert("Email must end with @bscse.uiu.ac.bd");
    try {
      const data = await apiFetch("auth/login.php", { method: "POST", body: JSON.stringify({ student_id: document.getElementById("loginStudentId")?.value.trim(), email, password: document.getElementById("loginPassword")?.value || "" }) });
      localStorage.setItem(AUTH_TOKEN_KEY, data.token);
      window.location.href = projectUrl("/features/view-selection/index.html");
    } catch (error) { alert(error.message); }
  });

  signupForm?.addEventListener("submit", async event => {
    event.preventDefault();
    const email = document.getElementById("signupEmail")?.value.trim() || "";
    if (!validUiUEmail(email)) return alert("Email must end with @bscse.uiu.ac.bd");
    try {
      const data = await apiFetch("auth/signup.php", { method: "POST", body: JSON.stringify({ name: document.getElementById("signupName")?.value.trim(), student_id: document.getElementById("signupStudentId")?.value.trim(), email, password: document.getElementById("signupPassword")?.value || "" }) });
      localStorage.setItem(AUTH_TOKEN_KEY, data.token);
      window.location.href = projectUrl("/features/view-selection/index.html");
    } catch (error) { alert(error.message); }
  });
}

function initViewSelection() {
  document.querySelectorAll("[data-view]").forEach(button => button.addEventListener("click", () => {
    window.location.href = button.dataset.view === "rider" ? projectUrl("/features/rider-dashboard/index.html") : projectUrl("/features/customer-dashboard/index.html");
  }));
}
function addDestination(groupId, addButtonId) {
  const group = document.getElementById(groupId), addButton = document.getElementById(addButtonId);
  if (!group || !addButton) return;
  addButton.addEventListener("click", () => {
    const label = document.createElement("label"); label.className = "form-field";
    label.innerHTML = `<span>Destination</span><span class="destination-line"><input class="destination-input" type="text" required><button class="destination-remove" type="button">−</button></span>`;
    group.appendChild(label); label.querySelector(".destination-remove")?.addEventListener("click", () => label.remove());
  });
}
function getDestinations(containerId) {
  return [...document.querySelectorAll(`#${containerId} .destination-input`)].map(input => input.value.trim()).filter(Boolean);
}

function initRiderCreateSchedule() {
  addDestination("riderDestinations", "addRiderDestination");
  document.getElementById("riderScheduleForm")?.addEventListener("submit", async event => {
    event.preventDefault();
    try {
      await apiFetch("schedule-ride/index.php", { method: "POST", body: JSON.stringify({ role: "rider", pickup: document.getElementById("riderPickup")?.value.trim(), destinations: getDestinations("riderDestinations"), available_seat: Number(document.getElementById("riderSeat")?.value), fare: Number(document.getElementById("riderFare")?.value), ride_date: document.getElementById("riderDate")?.value, pickup_time: document.getElementById("riderPickupTime")?.value, drop_time: document.getElementById("riderDropTime")?.value }) });
      window.location.href = projectUrl("/features/schedule-ride/rider-my-active.html");
    } catch (error) { alert(error.message); }
  });
}

function initCustomerRideRequest() {
  addDestination("customerRequestDestinations", "addCustomerRequestDestination");
  document.getElementById("customerRideRequestForm")?.addEventListener("submit", async event => {
    event.preventDefault();
    try {
      await apiFetch("ride-request/index.php", { method: "POST", body: JSON.stringify({ pickup: document.getElementById("customerRequestPickup")?.value.trim(), destinations: getDestinations("customerRequestDestinations"), fare: Number(document.getElementById("customerRequestFare")?.value) }) });
      window.location.href = projectUrl("/features/customer-dashboard/index.html");
    } catch (error) { alert(error.message); }
  });
}

function initCustomerCreateSchedule() {
  document.getElementById("customerScheduleForm")?.addEventListener("submit", async event => {
    event.preventDefault();
    try {
      await apiFetch("schedule-ride/index.php", { method: "POST", body: JSON.stringify({ role: "customer", pickup: document.getElementById("customerSchedulePickup")?.value.trim(), destinations: [document.getElementById("customerScheduleDestination")?.value.trim()], fare: Number(document.getElementById("customerScheduleFare")?.value), ride_date: document.getElementById("customerScheduleDate")?.value, pickup_time: document.getElementById("customerSchedulePickupTime")?.value, drop_time: document.getElementById("customerScheduleDropTime")?.value }) });
      window.location.href = projectUrl("/features/schedule-ride/customer-my-active.html");
    } catch (error) { alert(error.message); }
  });
}

function scheduleRows(schedule, role) {
  let html = infoRow("Pick Up Location", schedule.pickup) + infoRow("Destination", destinationsText(schedule.destinations)) + infoRow("Date", schedule.ride_date) + infoRow("Pick Up Time", schedule.pickup_time) + infoRow("Drop Time", schedule.drop_time);
  if (role === "rider") html += infoRow("Available Seat", schedule.available_seat || "") + infoRow("Per Seat Fare", `৳ ${schedule.fare}`);
  else html += infoRow("Fare", `৳ ${schedule.fare}`);
  return html;
}
function scheduleCard(schedule, role, actions = false) {
  return `<article class="schedule-card" data-schedule-card="${escapeHtml(schedule.id)}"><h2 class="schedule-card-title">Schedule Ride</h2>${scheduleRows(schedule, role)}${actions ? `<div class="card-actions"><button class="request-button decline-button" data-schedule-action="decline" data-schedule-id="${escapeHtml(schedule.id)}" type="button">Decline</button><button class="request-button accept-button" data-schedule-action="accept" data-schedule-id="${escapeHtml(schedule.id)}" type="button">Accept</button></div>` : ""}</article>`;
}

async function renderAvailableRequests() {
  const container = document.getElementById("availableRequestList"); if (!container) return;
  try {
    const data = await apiFetch("ride-request/index.php");
    container.innerHTML = data.requests.map(request => `<article class="request-card" data-request-card="${escapeHtml(request.id)}"><h2 class="request-card-title">Ride Request</h2>${infoRow("Pick Up Location", request.pickup)}${infoRow("Destination", destinationsText(request.destinations))}${infoRow("Seat Fare", `৳ ${request.fare}`)}<div class="card-actions"><button class="request-button decline-button" data-ride-action="decline" data-request-id="${escapeHtml(request.id)}" type="button">Decline</button><button class="request-button accept-button" data-ride-action="accept" data-request-id="${escapeHtml(request.id)}" type="button">Accept</button></div></article>`).join("");
    container.querySelectorAll("[data-ride-action]").forEach(button => button.addEventListener("click", async () => {
      try {
        await apiFetch("ride-request/action.php", { method: "POST", body: JSON.stringify({ request_id: button.dataset.requestId, action: button.dataset.rideAction }) });
        if (button.dataset.rideAction === "accept") window.location.href = projectUrl("/features/active-ride/index.html?role=rider");
        else button.closest("[data-request-card]")?.remove();
      } catch (error) { alert(error.message); }
    }));
  } catch (error) { alert(error.message); }
}

async function renderSchedules(containerId, scope, originRole, cardRole, actions = false, actionRole = null) {
  const container = document.getElementById(containerId); if (!container) return;
  try {
    const q = new URLSearchParams({ scope }); if (originRole) q.set("origin_role", originRole);
    const data = await apiFetch(`schedule-ride/index.php?${q}`);
    container.innerHTML = data.schedules.map(schedule => scheduleCard(schedule, cardRole, actions)).join("");
    if (actions) {
      container.querySelectorAll("[data-schedule-action]").forEach(button => button.addEventListener("click", async () => {
        try {
          await apiFetch("schedule-ride/action.php", { method: "POST", body: JSON.stringify({ schedule_id: button.dataset.scheduleId, action: button.dataset.scheduleAction, view_role: actionRole || getRole() }) });
          if (button.dataset.scheduleAction === "accept") window.location.href = projectUrl(`/features/active-ride/index.html?role=${actionRole || getRole()}`);
          else button.closest("[data-schedule-card]")?.remove();
        } catch (error) { alert(error.message); }
      }));
    }
  } catch (error) { alert(error.message); }
}
function renderRiderMySchedules() { return renderSchedules("riderMyScheduleList", "mine", "rider", "rider", false); }
function renderCustomerActiveSchedules() { return renderSchedules("customerActiveScheduleList", "available", "customer", "customer", true, "rider"); }
function renderCustomerMySchedules() { return renderSchedules("customerMyScheduleList", "mine", "customer", "customer", false); }
function renderRiderAvailableSchedules() { return renderSchedules("riderAvailableScheduleList", "available", "rider", "rider", true, "customer"); }

async function initRequestHistory() {
  const role = getRole();
  const back = document.getElementById("historyBackLink"); if (back) back.href = role === "rider" ? projectUrl("/features/rider-dashboard/index.html") : projectUrl("/features/customer-dashboard/index.html");
  const container = document.getElementById("requestHistoryList"); if (!container) return;
  try {
    const data = await apiFetch(`request-history/index.php?role=${role}`);
    container.innerHTML = data.history.map(item => `<article class="request-card"><h2 class="request-card-title">${escapeHtml(item.title)}</h2>${infoRow("Action", item.action)}${infoRow("Date", item.created_at)}${item.pickup ? infoRow("Pick Up Location", item.pickup) : ""}${item.destinations ? infoRow("Destination", destinationsText(item.destinations)) : ""}</article>`).join("");
  } catch (error) { alert(error.message); }
}

function initRideDetails() {
  const person = people[getRole()]; applyPersonData(""); setText("mapOtherLabel", person.otherLabel);
  document.getElementById("declineRequest")?.addEventListener("click", () => { window.location.href = getRole() === "rider" ? projectUrl("/features/ride-request/rider-available.html") : projectUrl("/features/customer-dashboard/index.html"); });
  document.getElementById("acceptRequest")?.addEventListener("click", () => goTo("/features/active-ride/index.html"));
}
function initActiveRide() {
  const person = people[getRole()]; applyPersonData("active"); setText("activeMapOtherLabel", person.otherLabel); setText("callActionLabel", `Call ${person.otherLabel}`); setText("chatTitle", person.otherLabel); setText("modalPhone", person.phone);
  const dial = document.getElementById("dialPhone"); if (dial) dial.href = `tel:${person.phone.replace(/[^0-9+]/g, "")}`;
  const back = document.getElementById("activeBackLink"); if (back) back.href = getRole() === "rider" ? projectUrl("/features/ride-request/rider-available.html") : projectUrl(`/features/active-ride/details.html${roleQuery()}`);
  document.getElementById("callAction")?.addEventListener("click", () => document.getElementById("callModal")?.classList.remove("hidden"));
  document.querySelector("[data-close-modal]")?.addEventListener("click", () => document.getElementById("callModal")?.classList.add("hidden"));
  document.getElementById("callModal")?.addEventListener("click", event => { if (event.target.id === "callModal") event.currentTarget.classList.add("hidden"); });
  document.getElementById("copyPhone")?.addEventListener("click", async () => { try { await navigator.clipboard.writeText(person.phone); showToast("Number copied"); } catch { showToast(person.phone); } });
  document.getElementById("messageAction")?.addEventListener("click", () => document.getElementById("chatPanel")?.classList.remove("hidden"));
  document.querySelector("[data-close-chat]")?.addEventListener("click", () => document.getElementById("chatPanel")?.classList.add("hidden"));
  document.getElementById("chatForm")?.addEventListener("submit", event => { event.preventDefault(); const input = document.getElementById("chatInput"), value = input?.value.trim(); if (!value) return; const message = document.createElement("div"); message.className = "chat-message sent"; message.textContent = value; document.getElementById("chatMessages")?.appendChild(message); input.value = ""; });
  document.getElementById("statusAction")?.addEventListener("click", () => document.getElementById("rideStatusArea")?.scrollIntoView({ block: "center" }));
  document.getElementById("rideStageButton")?.addEventListener("click", event => { if (event.currentTarget.dataset.completed === "true") { goTo("/features/payment/index.html"); return; } event.currentTarget.textContent = "Ride Completed"; event.currentTarget.dataset.completed = "true"; });
}
function initPayment() {
  const role = getRole(); const back = document.getElementById("paymentBackLink"); if (back) back.href = projectUrl(`/features/active-ride/index.html${roleQuery()}`);
  document.querySelectorAll("[data-payment]").forEach(button => button.addEventListener("click", () => { document.querySelectorAll("[data-payment]").forEach(item => item.classList.remove("selected")); button.classList.add("selected"); renderPayment(button.dataset.payment, role); }));
}
function renderPayment(method, role) {
  stopCamera(); const result = document.getElementById("paymentResult"); if (!result) return; result.classList.remove("hidden");
  if (method === "cash") {
    if (role === "customer") { result.innerHTML = `<h3>Enter 4 Digit OTP</h3><div class="otp-inputs"><input class="otp-input" maxlength="1" inputmode="numeric"><input class="otp-input" maxlength="1" inputmode="numeric"><input class="otp-input" maxlength="1" inputmode="numeric"><input class="otp-input" maxlength="1" inputmode="numeric"></div><button class="payment-action-button" id="confirmCash">Confirm</button>`; setupOtpInputs(); document.getElementById("confirmCash")?.addEventListener("click", () => showToast("Payment confirmed")); }
    else result.innerHTML = `<h3>Cash OTP</h3><div class="otp-display"><span class="otp-digit">6</span><span class="otp-digit">2</span><span class="otp-digit">4</span><span class="otp-digit">8</span></div>`;
    return;
  }
  const methodName = method === "bkash" ? "bKash" : method.charAt(0).toUpperCase() + method.slice(1);
  if (role === "customer") { result.innerHTML = `<h3>Scan ${methodName} QR</h3><div class="camera-box"><video id="paymentCamera" autoplay muted playsinline></video></div><button class="payment-action-button" id="enableCamera">Enable Camera</button>`; document.getElementById("enableCamera")?.addEventListener("click", startCamera); }
  else result.innerHTML = `<h3>${methodName} QR</h3><img class="qr-image" src="${projectUrl("/shared/assets/images/payment-qr.png")}" alt="Payment QR">`;
}
function setupOtpInputs() { const inputs = [...document.querySelectorAll(".otp-input")]; inputs.forEach((input,index) => input.addEventListener("input", () => { input.value = input.value.replace(/\D/g, "").slice(0,1); if (input.value && inputs[index+1]) inputs[index+1].focus(); })); }
async function startCamera() { const video = document.getElementById("paymentCamera"); if (!video) return; try { cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" }, audio: false }); video.srcObject = cameraStream; showToast("Camera enabled"); } catch { showToast("Camera permission unavailable"); } }
function stopCamera() { if (!cameraStream) return; cameraStream.getTracks().forEach(track => track.stop()); cameraStream = null; }
window.addEventListener("beforeunload", stopCamera);

document.addEventListener("DOMContentLoaded", () => {
  fixProjectLinks();
  const page = document.body.dataset.page;
  if (page === "auth") initAuth();
  if (page === "view-selection") initViewSelection();
  if (page === "rider-create-schedule") initRiderCreateSchedule();
  if (page === "customer-ride-request") initCustomerRideRequest();
  if (page === "customer-create-schedule") initCustomerCreateSchedule();
  if (page === "rider-available-request") renderAvailableRequests();
  if (page === "rider-my-active-schedule") renderRiderMySchedules();
  if (page === "rider-customers-active-schedule") renderCustomerActiveSchedules();
  if (page === "customer-my-active-schedule") renderCustomerMySchedules();
  if (page === "customer-available-schedule") renderRiderAvailableSchedules();
  if (page === "request-history") initRequestHistory();
  if (page === "ride-details") initRideDetails();
  if (page === "ride-active") initActiveRide();
  if (page === "payment") initPayment();
});

document.addEventListener("DOMContentLoaded", function () {
    const API_ENDPOINT = "assets/api/catalog-api.php";
    const AUTH_API = "assets/api/";

    const vehicleTypeSelect = document.getElementById("vehicle_type");
    const vehicleTypesContainer = document.getElementById("vehicleTypes");
    const brandSelect = document.getElementById("brand_id");
    const modelSelect = document.getElementById("model_id");
    const bookBtn = document.getElementById("bookBtn");
    const servicesSection = document.getElementById("servicesSection");
    const servicesContainer = document.getElementById("servicesContainer");

    const authModal = document.getElementById("authModal");
    const closeAuthModal = document.getElementById("closeAuthModal");
    const tabLogin = document.getElementById("tabLoginBtn");
    const tabReg = document.getElementById("tabRegisterBtn");
    const formLogin = document.getElementById("quickLoginForm");
    const formReg = document.getElementById("quickRegisterForm");

    const state = {
        vehicleTypeId: null,
        brandId: null,
        modelId: null,
        serviceId: null,
        price: null
    };

    async function fetchJSON(url, options = {}) {
        const response = await fetch(url, options);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const result = await response.json();
        if (!result.success) throw new Error(result.message || "API Error");
        return result.data;
    }

    async function checkUserAuth() {
        try {
            const res = await fetch(AUTH_API + "auth-check.php");
            const json = await res.json();
            return Boolean(json.logged_in);
        } catch (e) {
            return false;
        }
    }

    function openAuthGate() {
        if (authModal) authModal.style.display = "flex";
    }

    if (closeAuthModal && authModal) {
        closeAuthModal.addEventListener("click", () => authModal.style.display = "none");
    }

    if (tabLogin && tabReg && formLogin && formReg) {
        tabLogin.addEventListener("click", () => {
            tabLogin.classList.add("active");
            tabReg.classList.remove("active");
            formLogin.style.display = "block";
            formReg.style.display = "none";
        });
        tabReg.addEventListener("click", () => {
            tabReg.classList.add("active");
            tabLogin.classList.remove("active");
            formReg.style.display = "block";
            formLogin.style.display = "none";
        });
    }

    function fillSelect(selectEl, items, placeholder, emptyMessage) {
        if (!selectEl) return;
        selectEl.innerHTML = "";
        if (!items || items.length === 0) {
            selectEl.appendChild(new Option(emptyMessage, ""));
            selectEl.disabled = true;
            return;
        }
        selectEl.appendChild(new Option(placeholder, ""));
        items.forEach(item => selectEl.appendChild(new Option(item.name, item.id)));
        selectEl.disabled = false;
    }

    function resetSelect(selectEl, message) {
        if (!selectEl) return;
        selectEl.innerHTML = "";
        selectEl.appendChild(new Option(message, ""));
        selectEl.disabled = true;
    }

    function updateBookButton() {
        if (!bookBtn) return;
        bookBtn.disabled = !(state.vehicleTypeId && state.brandId && state.modelId && state.serviceId && state.price !== null);
    }

    function hideServices() {
        if (servicesSection) servicesSection.style.display = "none";
        if (servicesContainer) servicesContainer.innerHTML = "";
        state.serviceId = null;
        state.price = null;
        updateBookButton();
    }

    // 1. Initial Load: Types
    fetchJSON(`${API_ENDPOINT}?action=types`)
        .then(types => {
            if (vehicleTypeSelect) fillSelect(vehicleTypeSelect, types, "Select vehicle type", "No vehicle types");

            if (vehicleTypesContainer) {
                vehicleTypesContainer.innerHTML = "";
                types.forEach(vehicle => {
                    const card = document.createElement("div");
                    card.className = "vehicle-card";
                    card.dataset.id = vehicle.id;

                    const icon = vehicle.name.toLowerCase().includes("bike") ? "bx-cycling" : "bx-car";
                    card.innerHTML = `<i class="bx ${icon}"></i><h2>${vehicle.name}</h2>`;

                    card.addEventListener("click", function () {
                        document.querySelectorAll(".vehicle-card").forEach(c => c.classList.remove("active"));
                        card.classList.add("active");

                        state.vehicleTypeId = vehicle.id;
                        if (vehicleTypeSelect) vehicleTypeSelect.value = vehicle.id;
                        handleVehicleTypeChange(vehicle.id);
                    });

                    vehicleTypesContainer.appendChild(card);
                });
            }
        })
        .catch(err => console.error("Types error:", err));

    // 2. Type Change -> Fetch Brands
    function handleVehicleTypeChange(typeId) {
        state.vehicleTypeId = typeId;
        state.brandId = null;
        state.modelId = null;

        resetSelect(brandSelect, "Loading brands...");
        resetSelect(modelSelect, "Select brand first");
        hideServices();

        if (!typeId) return;

        fetchJSON(`${API_ENDPOINT}?action=brands&vehicle_type_id=${encodeURIComponent(typeId)}`)
            .then(brands => fillSelect(brandSelect, brands, "Select a brand", "No brands found"))
            .catch(() => resetSelect(brandSelect, "Error loading brands"));
    }

    // 3. Brand Change -> Fetch Models
    if (brandSelect) {
        brandSelect.addEventListener("change", function () {
            state.brandId = this.value;
            state.modelId = null;

            resetSelect(modelSelect, "Loading models...");
            hideServices();

            if (!this.value) return;

            fetchJSON(`${API_ENDPOINT}?action=models&brand_id=${encodeURIComponent(this.value)}`)
                .then(models => fillSelect(modelSelect, models, "Select a model", "No models found"))
                .catch(() => resetSelect(modelSelect, "Error loading models"));
        });
    }

    // 4. Model Change -> Fetch Model Services
    if (modelSelect) {
        modelSelect.addEventListener("change", function () {
            state.modelId = this.value;
            hideServices();

            if (!this.value) return;
            loadModelServices(this.value);
        });
    }

    // 5. Render Service Packages
    function loadModelServices(modelId) {
        if (!servicesContainer || !servicesSection) return;

        servicesContainer.innerHTML = `<div class="loading-services">Loading packages...</div>`;
        servicesSection.style.display = "block";

        fetchJSON(`${API_ENDPOINT}?action=services&model_id=${encodeURIComponent(modelId)}`)
            .then(services => {
                if (!services || services.length === 0) {
                    servicesContainer.innerHTML = `<div class="no-services"><p>No packages found for this model.</p></div>`;
                    return;
                }

                servicesContainer.innerHTML = services.map(pkg => {
                    const isRec = Number(pkg.is_recommended) === 1;
                    const price = parseFloat(pkg.price) || 0;
                    return `
                        <div class="service-package-card ${isRec ? 'recommended' : ''}" data-service-id="${pkg.service_id}">
                            <div class="package-header">
                                <h3>${pkg.service_name} ${isRec ? '<span class="badge-recommended">Recommended</span>' : ''}</h3>
                                <div class="package-price">₹${price.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</div>
                            </div>
                            <div class="package-duration"><i class='bx bx-time-five'></i> ${pkg.duration || '2 Hours'}</div>
                            <p class="package-desc">${pkg.description || ''}</p>
                            <button type="button" class="select-service-btn" data-service-id="${pkg.service_id}" data-price="${price}">
                                Select Package
                            </button>
                        </div>
                    `;
                }).join('');

                document.querySelectorAll(".select-service-btn").forEach(btn => {
                    btn.addEventListener("click", function () {
                        document.querySelectorAll(".service-package-card").forEach(c => c.classList.remove("selected-package"));
                        document.querySelectorAll(".select-service-btn").forEach(b => b.textContent = "Select Package");

                        const activeCard = this.closest(".service-package-card");
                        if (activeCard) activeCard.classList.add("selected-package");
                        this.textContent = "Selected ✓";

                        state.serviceId = this.dataset.serviceId;
                        state.price = this.dataset.price;
                        updateBookButton();

                        if (bookBtn) bookBtn.scrollIntoView({ behavior: "smooth", block: "center" });
                    });
                });
            })
            .catch(() => {
                servicesContainer.innerHTML = `<div class="no-services error"><p>Error retrieving packages.</p></div>`;
            });
    }

    // 6. Proceed to Checkout
    async function proceedToCheckout() {
        if (!state.vehicleTypeId || !state.brandId || !state.modelId || !state.serviceId) {
            alert("Please select vehicle and package first.");
            return;
        }

        const isLoggedIn = await checkUserAuth();
        if (!isLoggedIn) {
            openAuthGate();
        } else {
            const queryParams = new URLSearchParams({
                model_id: state.modelId,
                service_id: state.serviceId,
                amount: state.price
            }).toString();

            window.location.href = `checkout.php?${queryParams}`;
        }
    }

    if (bookBtn) {
        bookBtn.addEventListener("click", (e) => {
            e.preventDefault();
            proceedToCheckout();
        });
    }

    // 7. Modal Form Submissions
    if (formLogin) {
        formLogin.addEventListener("submit", async function (e) {
            e.preventDefault();
            const res = await fetch(AUTH_API + "auth-handler.php?action=login", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    phone: document.getElementById("login_phone").value,
                    password: document.getElementById("login_pass").value
                })
            });
            const data = await res.json();
            if (data.success) {
                if (authModal) authModal.style.display = "none";
                proceedToCheckout();
            } else {
                alert(data.message || "Invalid credentials");
            }
        });
    }

    if (formReg) {
        formReg.addEventListener("submit", async function (e) {
            e.preventDefault();
            const res = await fetch(AUTH_API + "auth-handler.php?action=register", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    name: document.getElementById("reg_name").value,
                    phone: document.getElementById("reg_phone").value,
                    email: document.getElementById("reg_email").value,
                    password: document.getElementById("reg_pass").value
                })
            });
            const data = await res.json();
            if (data.success) {
                if (authModal) authModal.style.display = "none";
                proceedToCheckout();
            } else {
                alert(data.message || "Registration failed");
            }
        });
    }
});
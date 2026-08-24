const numberPattern = /^-?\d+(\.\d+)?$/;

function showFieldState(input, errorElement, isValid, message) {
    input.classList.toggle("valid", isValid);
    input.classList.toggle("invalid", !isValid);
    input.setAttribute("aria-invalid", String(!isValid));

    if (errorElement) {
        errorElement.textContent = message;
    }
}

function clearFieldState(input, errorElement) {
    input.classList.remove("valid", "invalid");
    input.removeAttribute("aria-invalid");

    if (errorElement) {
        errorElement.textContent = "";
    }
}

function validateNumberInput(input) {
    const value = input.value.trim();
    const errorElement = input.parentElement.querySelector(".error-message");
    let message = "";

    if (value === "") {
        message = "This value is required.";
    } else if (!numberPattern.test(value)) {
        message = "Enter a valid number.";
    } else if (Number(value) < 0) {
        message = "Value cannot be negative.";
    }

    showFieldState(input, errorElement, message === "", message);
    return message === "";
}

const predictionForm = document.getElementById("predictionForm");

if (predictionForm) {
    const predictionInputs = Array.from(
        predictionForm.querySelectorAll('input[type="number"]')
    );
    const sampleDataButton = document.getElementById("sampleDataBtn");

    predictionInputs.forEach(function (input) {
        input.addEventListener("input", function () {
            validateNumberInput(input);
        });
    });

    predictionForm.addEventListener("submit", function (event) {
        let firstInvalidInput = null;

        predictionInputs.forEach(function (input) {
            const isValid = validateNumberInput(input);

            if (!isValid && !firstInvalidInput) {
                firstInvalidInput = input;
            }
        });

        if (firstInvalidInput) {
            event.preventDefault();
            firstInvalidInput.focus();
        }
    });

    if (sampleDataButton) {
        const sampleData = {
            fixed_acidity: 7.4,
            volatile_acidity: 0.7,
            citric_acid: 0,
            residual_sugar: 1.9,
            chlorides: 0.076,
            free_sulfur_dioxide: 11,
            total_sulfur_dioxide: 34,
            density: 0.9978,
            ph: 3.51,
            sulphates: 0.56,
            alcohol: 9.4
        };

        sampleDataButton.addEventListener("click", function () {
            predictionInputs.forEach(function (input) {
                input.value = sampleData[input.id];
                validateNumberInput(input);
            });
        });
    }

    predictionForm.addEventListener("reset", function () {
        predictionInputs.forEach(function (input) {
            const errorElement = input.parentElement.querySelector(".error-message");
            clearFieldState(input, errorElement);
        });
    });
}

const registrationForm = document.getElementById("registrationForm");

if (registrationForm) {
    const fullNameInput = registrationForm.querySelector("#full_name");
    const emailInput = registrationForm.querySelector("#email");
    const passwordInput = registrationForm.querySelector("#password");
    const fullNameError = document.getElementById("fullNameError");
    const emailError = document.getElementById("emailError");
    const passwordError = document.getElementById("passwordError");
    const strengthBar = document.getElementById("passwordStrengthBar");
    const strengthLabel = document.getElementById("passwordStrengthLabel");
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function validateFullName() {
        const value = fullNameInput.value.trim();
        let message = "";

        if (value === "") {
            message = "Full name is required.";
        } else if (value.length < 2) {
            message = "Use at least 2 characters.";
        }

        showFieldState(fullNameInput, fullNameError, message === "", message);
        return message === "";
    }

    function validateEmail() {
        const value = emailInput.value.trim();
        let message = "";

        if (value === "") {
            message = "Email is required.";
        } else if (!emailPattern.test(value)) {
            message = "Enter a valid email address.";
        }

        showFieldState(emailInput, emailError, message === "", message);
        return message === "";
    }

    function getPasswordScore(password) {
        let score = 0;

        if (password.length >= 8) score += 1;
        if (/[A-Z]/.test(password)) score += 1;
        if (/[a-z]/.test(password)) score += 1;
        if (/\d/.test(password)) score += 1;
        if (/[^A-Za-z0-9]/.test(password)) score += 1;

        return score;
    }

    function updatePasswordStrength(password) {
        const score = getPasswordScore(password);
        let strength = "empty";
        let width = 0;

        if (password !== "" && score <= 2) {
            strength = "weak";
            width = 40;
        } else if (score <= 4 && password !== "") {
            strength = "moderate";
            width = 70;
        } else if (score === 5) {
            strength = "strong";
            width = 100;
        }

        strengthBar.className = "password-strength-bar " + strength;
        strengthBar.style.width = width + "%";
        strengthLabel.textContent =
            "Strength: " + strength.charAt(0).toUpperCase() + strength.slice(1);
    }

    function validatePassword() {
        const value = passwordInput.value;
        const score = getPasswordScore(value);
        let message = "";

        if (value === "") {
            message = "Password is required.";
        } else if (value.length < 8) {
            message = "Use at least 8 characters.";
        } else if (!/[A-Z]/.test(value) || !/[a-z]/.test(value) || !/\d/.test(value)) {
            message = "Add uppercase, lowercase, and a number.";
        }

        updatePasswordStrength(value);
        showFieldState(passwordInput, passwordError, message === "", message);
        return message === "";
    }

    fullNameInput.addEventListener("input", validateFullName);
    emailInput.addEventListener("input", validateEmail);
    passwordInput.addEventListener("input", validatePassword);

    const confirmPasswordInput = registrationForm.querySelector("#confirm_password");

    registrationForm.addEventListener("submit", function (event) {
        const isNameValid = validateFullName();
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        const isConfirmValid = !confirmPasswordInput || (confirmPasswordInput.value !== "" && confirmPasswordInput.value === passwordInput.value);

        if (confirmPasswordInput) {
            confirmPasswordInput.classList.toggle("invalid", !isConfirmValid);
            confirmPasswordInput.classList.toggle("valid", isConfirmValid);
        }

        if (!isNameValid || !isEmailValid || !isPasswordValid || !isConfirmValid) {
            event.preventDefault();

            if (!isNameValid) {
                fullNameInput.focus();
            } else if (!isEmailValid) {
                emailInput.focus();
            } else {
                passwordInput.focus();
            }
        }
    });
}

const systemStats = [
    { title: "Model Type", value: "LogisticRegressionCV" },
    { title: "Input Features", value: "Physicochemical Values" },
    { title: "Output", value: "Quality Class" }
];

function createStatCard(item) {
    const card = document.createElement("article");
    const title = document.createElement("h3");
    const value = document.createElement("p");

    card.className = "stat-card rounded-xl border bg-white p-6 text-center shadow-sm";
    title.textContent = item.title;
    value.textContent = item.value;
    card.append(title, value);

    return card;
}

const statsContainer = document.getElementById("statsContainer");

if (statsContainer) {
    systemStats.forEach(function (item) {
        statsContainer.appendChild(createStatCard(item));
    });
}

const menuToggle = document.getElementById("menuToggle");
const appNavigation = document.getElementById("appNav");

if (menuToggle && appNavigation) {
    menuToggle.addEventListener("click", function () {
        const isOpen = appNavigation.classList.toggle("active");
        menuToggle.setAttribute("aria-expanded", String(isOpen));
    });
}

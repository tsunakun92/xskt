/**
 * Role/User Permission Management
 * Handles module tabs, permission checkboxes, and counters
 */

const SELECTORS = {
    TAB_MODULE_BUTTONS: ".tab-module-button",
    CONTENTS: "#permission-tab",
    CHECKBOXES: 'input[type="checkbox"].permission-checkbox',
};

const CLASSES = {
    ACTIVE_TAB: "active",
    HIDDEN: "hidden",
    UPDATED_COUNTER: "updated",
};

const CONFIG = {
    COUNTER_ANIMATION_DURATION: 500,
};

let formChanged = false;

document.addEventListener("DOMContentLoaded", initializePermissionManagement);

function initializePermissionManagement() {
    initializeModuleTabs();
    initializeCounters();
    initializeModuleActions();
    updateAllCounters();
}

function initializeModuleTabs() {
    const tabButtons = document.querySelectorAll(SELECTORS.TAB_MODULE_BUTTONS);

    tabButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const targetModule = button.dataset.module;
            switchModuleTab(targetModule);
        });
    });
}

function initializeModuleActions() {
    document.querySelectorAll(SELECTORS.CONTENTS).forEach((content) => {
        const currentModule = content.getAttribute("data-current-module") || "all";
        const moduleActions = content.querySelectorAll(".module-actions");
        moduleActions.forEach((action) => {
            const actionModule = action.dataset.module || "all";
            if (actionModule === currentModule) {
                action.classList.remove(CLASSES.HIDDEN);
            } else {
                action.classList.add(CLASSES.HIDDEN);
            }
        });
    });
}

function switchModuleTab(targetModule) {
    const tabButtons = document.querySelectorAll(SELECTORS.TAB_MODULE_BUTTONS);

    tabButtons.forEach((btn) => {
        btn.classList.remove(CLASSES.ACTIVE_TAB);
    });

    const currentButton = document.querySelector(`[data-module="${targetModule}"]`);
    if (currentButton) {
        currentButton.classList.add(CLASSES.ACTIVE_TAB);
    }

    const content = document.getElementById("permission-tab");
    if (content) {
        const normalizedModule = targetModule === "" || !targetModule ? "all" : targetModule;
        content.setAttribute("data-current-module", normalizedModule);

        // Show/hide module action buttons
        const moduleActions = content.querySelectorAll(".module-actions");
        moduleActions.forEach((action) => {
            const actionModule = action.dataset.module || "all";
            if (actionModule === normalizedModule) {
                action.classList.remove(CLASSES.HIDDEN);
            } else {
                action.classList.add(CLASSES.HIDDEN);
            }
        });

        // Show/hide permission group wrappers based on module
        const wrappers = content.querySelectorAll(".permission-group-wrapper");
        wrappers.forEach((wrapper) => {
            const wrapperModule = wrapper.dataset.module || "all";
            const shouldShow = normalizedModule === "all" || wrapperModule === normalizedModule;
            wrapper.style.display = shouldShow ? "" : "none";
        });
    }

    updateAllCounters();
}

function initializeCounters() {
    document.querySelectorAll(SELECTORS.CHECKBOXES).forEach((checkbox) => {
        checkbox.addEventListener("change", () => {
            formChanged = true;
            updateAllCounters();
        });
    });
}

function getChecked(moduleKey = null) {
    const container = document.getElementById("permission-tab");
    if (!container) return 0;

    let selector = `${SELECTORS.CHECKBOXES}:checked`;
    let checkboxes = Array.from(container.querySelectorAll(selector));

    if (moduleKey && moduleKey !== "all") {
        checkboxes = checkboxes.filter((cb) => (cb.dataset.module || "all") === moduleKey);
    } else {
        // only visible wrappers for all
        checkboxes = checkboxes.filter((cb) => {
            const wrapper = cb.closest(".permission-group-wrapper");
            return !wrapper || wrapper.style.display !== "none";
        });
    }

    return checkboxes.length;
}

function getTotal(moduleKey = null) {
    const container = document.getElementById("permission-tab");
    if (!container) return 0;

    let checkboxes = Array.from(container.querySelectorAll(SELECTORS.CHECKBOXES));

    if (moduleKey && moduleKey !== "all") {
        checkboxes = checkboxes.filter((cb) => (cb.dataset.module || "all") === moduleKey);
    } else {
        checkboxes = checkboxes.filter((cb) => {
            const wrapper = cb.closest(".permission-group-wrapper");
            return !wrapper || wrapper.style.display !== "none";
        });
    }

    return checkboxes.length;
}

function getCheckedInGroup(moduleKey, groupIndex) {
    const container = document.getElementById("permission-tab");
    if (!container) return 0;

    const group = container.querySelector(`.group-${groupIndex}`);
    if (!group) return 0;

    const selector = `${SELECTORS.CHECKBOXES}:checked`;
    const checkboxes = group.querySelectorAll(selector);
    return checkboxes.length;
}

function getTotalInGroup(moduleKey, groupIndex) {
    const container = document.getElementById("permission-tab");
    if (!container) return 0;

    const group = container.querySelector(`.group-${groupIndex}`);
    if (!group) return 0;

    const checkboxes = group.querySelectorAll(SELECTORS.CHECKBOXES);
    return checkboxes.length;
}

function updateAllCounters() {
    updateModuleCounters();
    updateGroupCounters();
}

function updateModuleCounters() {
    document.querySelectorAll('[id^="module-counter-"]').forEach((counter) => {
        const moduleKey = counter.id.replace("module-counter-", "");
        const normalized = moduleKey === "" ? "all" : moduleKey;

        const checked = getChecked(normalized === "all" ? null : normalized);
        const total = getTotal(normalized === "all" ? null : normalized);
        const newCount = `${checked}/${total}`;

        if (counter.textContent !== newCount) {
            updateCounterWithAnimation(counter, newCount);
        }
    });
}

function updateGroupCounters() {
    document.querySelectorAll('[id^="group-counter-"]').forEach((counter) => {
        const parts = counter.id.replace("group-counter-", "").split("-");
        const moduleKey = parts[0] || "all";
        const groupIndex = parts[parts.length - 1];

        const checked = getCheckedInGroup(moduleKey, groupIndex);
        const total = getTotalInGroup(moduleKey, groupIndex);
        const newCount = `${checked}/${total}`;

        if (counter.textContent !== newCount) {
            updateCounterWithAnimation(counter, newCount);
        }
    });
}

function updateCounterWithAnimation(counter, newValue) {
    counter.textContent = newValue;
    counter.classList.add(CLASSES.UPDATED_COUNTER);
    setTimeout(() => {
        counter.classList.remove(CLASSES.UPDATED_COUNTER);
    }, CONFIG.COUNTER_ANIMATION_DURATION);
}

function checkAll(moduleKey = null) {
    const container = document.getElementById("permission-tab");
    if (!container) return;

    const normalized = moduleKey && moduleKey !== "all" ? moduleKey : (container.getAttribute("data-current-module") || "all");

    let checkboxes = Array.from(container.querySelectorAll(SELECTORS.CHECKBOXES));

    if (normalized && normalized !== "all") {
        checkboxes = checkboxes.filter((cb) => (cb.dataset.module || "all") === normalized);
    } else {
        checkboxes = checkboxes.filter((cb) => {
            const wrapper = cb.closest(".permission-group-wrapper");
            return !wrapper || wrapper.style.display !== "none";
        });
    }

    checkboxes.forEach((checkbox) => {
        checkbox.checked = true;
    });
    updateAllCounters();
    formChanged = true;
}

function uncheckAll(moduleKey = null) {
    const container = document.getElementById("permission-tab");
    if (!container) return;

    const normalized = moduleKey && moduleKey !== "all" ? moduleKey : (container.getAttribute("data-current-module") || "all");

    let checkboxes = Array.from(container.querySelectorAll(SELECTORS.CHECKBOXES));

    if (normalized && normalized !== "all") {
        checkboxes = checkboxes.filter((cb) => (cb.dataset.module || "all") === normalized);
    } else {
        checkboxes = checkboxes.filter((cb) => {
            const wrapper = cb.closest(".permission-group-wrapper");
            return !wrapper || wrapper.style.display !== "none";
        });
    }

    checkboxes.forEach((checkbox) => {
        checkbox.checked = false;
    });
    updateAllCounters();
    formChanged = true;
}

function checkAllInGroup(moduleKey, groupIndex) {
    const container = document.getElementById("permission-tab");
    if (!container) return;

    const group = container.querySelector(`.group-${groupIndex}`);
    if (!group) return;

    const checkboxes = group.querySelectorAll(SELECTORS.CHECKBOXES);
    checkboxes.forEach((checkbox) => {
        checkbox.checked = true;
    });

    updateAllCounters();
    formChanged = true;
}

function uncheckAllInGroup(moduleKey, groupIndex) {
    const container = document.getElementById("permission-tab");
    if (!container) return;

    const group = container.querySelector(`.group-${groupIndex}`);
    if (!group) return;

    const checkboxes = group.querySelectorAll(SELECTORS.CHECKBOXES);
    checkboxes.forEach((checkbox) => {
        checkbox.checked = false;
    });

    updateAllCounters();
    formChanged = true;
}

function toggleCheckbox(checkboxId) {
    const checkbox = document.getElementById(checkboxId);
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        updateAllCounters();
        formChanged = true;
    }
}

window.checkAll = checkAll;
window.uncheckAll = uncheckAll;
window.checkAllInGroup = checkAllInGroup;
window.uncheckAllInGroup = uncheckAllInGroup;
window.toggleCheckbox = toggleCheckbox;

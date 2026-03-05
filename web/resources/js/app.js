import Alpine from "alpinejs";
import $ from "jquery";

window.$ = window.jQuery = $;

// Prevent Alpine.js multiple initialization conflicts
if (!window.Alpine) {
    window.Alpine = Alpine;
    Alpine.start();
} else if (!window.Alpine.version && !window.Alpine.$version) {
    window.Alpine = Alpine;
    Alpine.start();
}

/* Import Flowbite */
import "flowbite";

/* Import Custom Scripts */
import "./custom";

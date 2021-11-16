define(['jquery', 'core/notification', 'core/custom_interaction_events', 'core/modal', 'core/modal_registry'],
        function($, Notification, CustomEvents, Modal, ModalRegistry) {

    var registered = false;
    /**
     * Constructor for the Modal.
     *
     * @param {object} root The root jQuery element for the modal
     */
    var ModalReachedLocation = function(root) {
        Modal.call(this, root);
    };

    ModalReachedLocation.TYPE = 'block_gps-reachedlocation';
    ModalReachedLocation.prototype = Object.create(Modal.prototype);
    ModalReachedLocation.prototype.constructor = ModalReachedLocation;

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    ModalReachedLocation.prototype.registerEventListeners = function() {
        // Apply parent event listeners.
        Modal.prototype.registerEventListeners.call(this);
    };

    // Automatically register with the modal registry the first time this module is imported so that you can create modals
    // of this type using the modal factory.
    if (!registered) {
        ModalRegistry.register(ModalReachedLocation.TYPE, ModalReachedLocation, 'block_gps/modal_reachedlocation');
        registered = true;
    }

    return ModalReachedLocation;
});

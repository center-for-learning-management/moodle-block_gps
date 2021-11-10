define(['jquery', 'core/modal_factory'], function($, ModalFactory) {
    return {
        modal: function(title,text) {
            ModalFactory.create({
                title: title,
                body: text,
            }).done(function(modal) {
                modal.show();
            });
        }
    };
});

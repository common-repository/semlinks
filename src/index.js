import {SettingsClient} from "./admin/js/clients/settings-client";

/**
 * Entrypoint for all the js scripts enqueued in the admin
 */
import AdminSettings from './admin/js/settings/admin-settings';
import Toolbar from "./admin/js/gutenberg/components/toolbar";
import {LinkingSelector} from "./admin/js/gutenberg/components/sidebar";
import RelatedPostsMetaBox from "./admin/js/meta-box/related-posts/meta-box";

SettingsClient.isApiKeyValid().then((isApiKeyValid) => {
    if (isApiKeyValid) {
        wp.domReady(() => {
            const featureAllowedPromises = [];
            featureAllowedPromises.push(SettingsClient.isFeatureAllowed("NER"));
            featureAllowedPromises.push(SettingsClient.isFeatureAllowed("LOOKALIKE"));

            Promise.all(featureAllowedPromises).then(([isNerAllowed, isLookalikeAllowed]) => {
                if (isLookalikeAllowed) {
                    Toolbar.register();
                }

                if (isNerAllowed && isLookalikeAllowed) {
                    // Sidebar
                    LinkingSelector.register();
                }
            });
        });
    }
});

window.addEventListener('load', () => {
    AdminSettings.bindEvents();
    RelatedPostsMetaBox.bindEvents();
});

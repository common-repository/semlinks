import apiFetch from '@wordpress/api-fetch';

const {__} = wp.i18n;
const $ = jQuery;

const AdminSettings = {
    bindEvents: () => {
        const button = document.querySelector('#semlinks-plugin-start-sync-button');
        if (button) {
            button.addEventListener('click', AdminSettings.clickHandle)
        }

        const progressHiddenInput = document.querySelector('#semlinks-plugin_sync_posts_progress');
        if (progressHiddenInput) {
            setInterval(() => {
                AdminSettings.followSynchronization();
            }, 10000);
        }
    },
    followSynchronization: () => {
        apiFetch({
            path: '/semlinks-plugin/v1/related-posts/nb-already-synced',
            method: 'GET',
        }).then((response) => {
            document.querySelector('#semlinks-plugin_sync_posts_progress').setAttribute("value", response.nbPostSynced);
            const totalPosts = document.querySelector('#semlinks-plugin_sync_posts_total').getAttribute("value");
            const totalTags = document.querySelector('#semlinks-plugin_sync_tags_total').getAttribute("value");
            const total = parseInt(totalPosts) + parseInt(totalTags);
            if (total === (response.nbPostSynced + response.nbTagSynced)) {
                AdminSettings.markSyncAsDone();
            }
        }).catch((err) => {
            console.error(err);
        });
    },
    markSyncAsDone: () => {
        const doneText = __('Done', 'semlinks');
        $('#semlinks-plugin-start-sync-button')
            .html(doneText)
            .attr('disabled', true)
            .attr('style', 'background-color: #4CAF50 !important; color: white !important');
    },
    clickHandle: (e) => {
        e.preventDefault();

        apiFetch({
            path: '/semlinks-plugin/v1/initial-sync/start-sync',
            method: 'POST',
        })
            .then(response => {
                if (!response.success) {
                    alert(__('An error occurred and the synchronization could not start', 'semlinks'))
                    throw new Error(`Response content : ${response.data}`);
                }

                return response.synchId;
            })
            .then(() => {
                document.querySelector('#semlinks-plugin-sync-info').parentNode.removeChild(document.querySelector('#semlinks-plugin-sync-info'));
                const ongoingText = __('Loading...', 'semlinks');
                $('#semlinks-plugin-start-sync-button')
                    .html(ongoingText)
                    .attr('disabled', true);
            })
            .catch(error => console.error(error));
    }
};

export default AdminSettings;

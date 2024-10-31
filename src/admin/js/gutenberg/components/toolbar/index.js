import {registerFormatType} from '@wordpress/rich-text';
import {ToolbarCustomButton} from "./toolbar-custom-button";

const Toolbar = {
    register: () => {
        registerFormatType('semlinks-plugin/link-to-related-post', {
            title: 'CSP Related post',
            tagName: 'a',
            className: 'semlinks-plugin-related-post-link',
            edit: ToolbarCustomButton
        });
    }
}
export default Toolbar;



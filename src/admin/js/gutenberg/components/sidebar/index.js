import LinkingSettingPanel from "./linking/linking-setting-panel";

const {registerPlugin} = wp.plugins;

export const LinkingSelector = {
    register: () => {
        // Register the NER settings panel
        registerPlugin('semlinks-plugin-ner', {render: LinkingSettingPanel});
    }
}


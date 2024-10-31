import {CapabilitiesClient} from "../../clients/capabilities-client";

export const CapabilitiesService = {
    isCurrentUserAllowedTo: (capability) => {
        return CapabilitiesClient.getCurrentUserCapabilities().then((capabilities) => {
            return Object.keys(capabilities).includes(capability) && capabilities[capability] === true;
        });
    }
}
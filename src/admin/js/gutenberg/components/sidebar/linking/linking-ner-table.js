import {LinkingNerRow} from "./linking-ner-row";
import {__} from "@wordpress/i18n";

export const LinkingNerTable = ({entities, withType = false, withAction = true}) => {
    return (
        <table className={"wp-list-table widefat fixed pages sortable "}>
            <thead>
            <tr>
                <td style={{width: "120px"}}>{__("Entity", "semlinks")}</td>
                {withType && (<td style={{width: "120px"}}>{__("Type", "semlinks")}</td>)}
                <td style={{width: "50px"}}>{__("Score", "semlinks")}</td>
                <td style={{width: "50px"}}>{__("Number of occurrences", "semlinks")}</td>
                {withAction && (<td style={{width: "60px"}}>{__("Actions", "semlinks")}</td>)}

            </tr>
            </thead>
            <tbody>
            {entities.map((entity) => (
                <LinkingNerRow
                    entity={entity}
                    withType={withType}
                    withAction={withAction}
                />
            ))}
            </tbody>
        </table>
    );
}
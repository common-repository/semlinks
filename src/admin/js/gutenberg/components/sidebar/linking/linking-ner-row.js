import {NerClient} from "../../../../clients/ner-client";
import {useState} from '@wordpress/element';
import {Button, Icon} from '@wordpress/components';
import {__} from "@wordpress/i18n";

export const LinkingNerRow = (
    {
        entity,
        withType = false,
        withAction = true
    }) => {
    const [status, setStatus] = useState(entity.is_candidate ? 'candidate' : 'created');
    const [loading, setLoading] = useState(false);

    const addAsTag = async (entity) => {
        setLoading(true);
        entity.entity = NerClient.decodeEntities(entity.entity)
        await NerClient.createNewTagForEntity(entity)
            .catch(async (error) => {
                setLoading(false);
                if (error.code && error.code === 'term_exists') {
                    // The tag exists in WP but not in CSP dictionary
                    await NerClient.addTagToCSPDictionary(error.data.term_id, entity.type);

                    return await NerClient.getTagById(error.data.term_id);
                }

                // Another error occurred
                console.error(error);
            })
            .then(() => {
                setStatus('created')
                setLoading(false);
            });
    }

    return (
        <tr>
            <td>{NerClient.decodeEntities(entity.entity)}</td>
            {withType && (
                <td>{__(entity.type, 'semlinks')}</td>
            )}
            <td>{entity.score}</td>
            <td>{entity.count}</td>
            {withAction && (<td>
                {loading && (
                    <td><span className={"spinner semlinks-plugin-spinner"}/></td>
                )}
                {!loading && status === 'candidate' && (
                    <Button
                        onClick={() => addAsTag(entity)}
                        icon={<Icon icon="tag"/>}
                        label={__('Add as tag', 'semlinks')}
                        isSmall
                        isPrimary
                        disabled={entity.selectedTag}
                    ></Button>
                )}
                {status === 'created' && (
                    <Icon sx={{color: "green"}} icon="yes-alt"/>
                )}
            </td>)}
        </tr>
    );
}
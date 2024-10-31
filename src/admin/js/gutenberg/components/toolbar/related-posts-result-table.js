import {
    Spinner,
    Button
} from '@wordpress/components';
import {toggleFormat} from '@wordpress/rich-text';
import { __ } from '@wordpress/i18n';

export const RelatedPostsResultTable = ({
                                           isLoading,
                                           posts,
                                           value,
                                           onChange,
                                           setOpen,
                                           permalinks
                                       }) => {

    const handleSelection = (post) => {
        setOpen(false);
        onChange(
            toggleFormat(
                value, {
                    type: 'semlinks-plugin/link-to-related-post',
                    attributes: {
                        href: permalinks[post.ID]
                    }
                }
            )
        );
    }

    return (
        <div className={"semlinks-plugin-modal-result-table-container"}>
            <table className={"wp-list-table widefat fixed pages sortable "}>
                {isLoading && <tr>
                    <td>
                        <Spinner/>
                    </td>
                </tr>
                }

                {(!isLoading && posts.length > 0) ? posts.map((post) => (
                    <tr>
                        <td>
                            <strong>{post.post_title}</strong>
                            <Button className={"float-right"} onClick={() => handleSelection(post)}
                                    variant="primary">{__("Select", "semlinks")}</Button>
                        </td>
                    </tr>
                )) : (!isLoading ? <tr>
                    <td><span>{__("No result", "semlinks")}</span></td>
                </tr> : <></>)
                }
            </table>
        </div>
    );
}
import {
    Spinner,
    Button
} from '@wordpress/components';
import {__} from '@wordpress/i18n';

export const LinkingRelatedPostsTable = ({
                                             isLoading,
                                             posts,
                                             setPosts
                                         }) => {
    const handleDelete = (postId) => {
        setPosts(posts.filter((post) => post.ID !== postId));
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
                            <Button className={"float-right"} onClick={() => handleDelete(post.ID)}
                                    variant="primary">{__("Delete", "semlinks")}</Button>
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
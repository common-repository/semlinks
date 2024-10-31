import apiFetch from '@wordpress/api-fetch';

export const RelatedPostsClient = {
    fetchRelatedPosts: (data) => {
        return apiFetch({
            path: '/semlinks-plugin/v1/related-posts',
            method: 'POST',
            data: data
        })
    },

    saveRelatedPosts: (data) => {
        return apiFetch({
            path: '/semlinks-plugin/v1/related-posts/save-related-posts',
            method: 'POST',
            data: data
        })
    },

    getContentFromSelectedBlock: (selectedBlock, selectedText) => {
        return (selectedBlock.attributes.content !== "" ? selectedBlock.attributes.content : selectedText);
    }
}
import {useEffect} from "react";
import {NerClient} from "../../../../clients/ner-client";
import {dispatch, useSelect} from '@wordpress/data';
import {useState} from '@wordpress/element';
import {Button, Modal, Panel, PanelBody, PanelRow} from '@wordpress/components';
import {LinkingNerTable} from "./linking-ner-table";
import {__} from "@wordpress/i18n";
import {store as editorStore} from '@wordpress/editor';
import {RelatedPostsClient} from "../../../../clients/related-posts-client";
import {LinkingRelatedPostsTable} from "./linking-related-posts-table";
import RelatedPostsMetaBox from "../../../../meta-box/related-posts/meta-box";

const getUniqueAndCountedEntities = (entities, sort = false) => {
    const countedEntities = entities
        // We want to count how many times an entity is found
        .map((entity) => {
            entity.count = entities.filter((e) => e.entity === entity.entity).length
            return entity;
        })

    return countedEntities
        // We want only one instance of entity based on entity.entity
        .filter((entity, index, self) => self.findIndex((e) => e.entity === entity.entity) === index);
}
const getTableData = (setLoading, setError, setEmpty, setKnownEntities, setRelatedPosts, postId, postContent, postIntro, postTitle) => {
    setLoading(true);
    setError(null);
    const promises = [
        NerClient.fetchNamedEntities({
            content: postContent,
            matcher: "csp_basic_matcher"
        }),
        RelatedPostsClient.fetchRelatedPosts({
            postId: postId,
            content: postContent,
            introduction: postIntro,
            title: postTitle
        })
    ]

    Promise.all(promises).then(([nerResponse, relatedPostsResponse]) => {
        setEmpty((nerResponse.entities.length === 0 && relatedPostsResponse.posts.length === 0));
        setKnownEntities(getUniqueAndCountedEntities(nerResponse.entities));
        setRelatedPosts(relatedPostsResponse.posts);
        setLoading(false);
    }).catch((error) => {
        console.error(error);
        setError(error);
        setLoading(false);
    });
}

export const LinkingModal = ({currentPost, pluginSettings}) => {
    const [isOpen, setIsOpen] = useState(false);
    const [loading, setLoading] = useState(true);
    const [empty, setEmpty] = useState(false);
    const [error, setError] = useState(null);
    const [modalView, setModalView] = useState(null);
    const [isPluginCorrectlyConfigured, setIsPluginCorrectlyConfigured] = useState(false);

    const [knownEntities, setKnownEntities] = useState([]);
    const [relatedPosts, setRelatedPosts] = useState([]);

    const postContent = useSelect(
        (select) => select(editorStore).getEditedPostContent('postType', currentPost.type, currentPost.id),
        []
    );
    const postId = wp.data.select("core/editor").getCurrentPostId();
    const postIntro = (currentPost.excerpt !== "" ? currentPost.excerpt : currentPost.title);
    const postTitle = currentPost.title;

    const initialTags = useSelect((select) => (select(editorStore).getEditedPostAttribute("tags")));
    useEffect(() => {
        if (isOpen) {
            getTableData(setLoading, setError, setEmpty, setKnownEntities, setRelatedPosts, postId, postContent, postIntro, postTitle)
        }
    }, [isOpen]);

    const onValidate = () => {
        const promises = [
            NerClient.fetchNamedEntities({
                content: postContent,
                matcher: "csp_basic_matcher"
            }),
            RelatedPostsClient.saveRelatedPosts({
                postId: postId,
                posts: relatedPosts.map((post) => post.ID)
            })
        ]

        Promise.all(promises)
            .then(([nerResponse, relatedPostsResponse]) => {
                RelatedPostsMetaBox.replaceRelatedPostsTableContent(relatedPostsResponse)

                return nerResponse
            }).then(({entities, article}) => {
            if (pluginSettings && !pluginSettings.ner_only_add_as_tag) {
                // Update the post content with the extracted entities and their links
                dispatch(editorStore).resetBlocks(
                    wp.blocks.parse(article.text)
                );
            }

            const entityIds = Object.values(entities).map((entity) => entity.id);
            const newTags = [...initialTags, ...entityIds]
                .filter((value, index, self) => self.indexOf(value) === index);
            const edits = {tags: newTags};
            dispatch('core').editEntityRecord(
                'postType',
                currentPost.type,
                currentPost.id,
                edits
            )

            setLoading(false);
            setIsOpen(false);
        });
    };


    const modalViews = {
        loading: () => (<div style={{textAlign: "center"}}><span className={"spinner semlinks-plugin-spinner"}/></div>),
        error: () => (<div>{__("An error occurred while finding links", "semlinks")}</div>),
        empty: () => (<div>{__("No links were found", "semlinks")}</div>),
        result: () => (
            <Panel>
                <PanelBody
                    title={__("Related posts", "semlinks")}
                    initialOpen={true}
                >
                    <PanelRow>
                        <LinkingRelatedPostsTable
                            posts={relatedPosts}
                            setPosts={setRelatedPosts}
                        />
                    </PanelRow>
                </PanelBody>

                <PanelBody
                    title={__("Known named entities", "semlinks")}
                    initialOpen={true}
                >
                    <PanelRow>
                        <LinkingNerTable entities={knownEntities} withAction={false}/>
                    </PanelRow>
                </PanelBody>
            </Panel>
        )
    }

    useEffect(() => {
        let currentView = "result";
        if (error) {
            currentView = "error";
        }
        if (loading) {
            currentView = "loading";
        }
        if (empty) {
            currentView = "empty";
        }
        setModalView(modalViews[currentView]())
    }, [modalView, loading, error, empty]);

    useEffect(() => {
        if (pluginSettings !== undefined) {
            setIsPluginCorrectlyConfigured(
                pluginSettings.is_api_key_valid === "true"
                && pluginSettings.allowed_features.includes("NER")
                && pluginSettings.allowed_features.includes("LOOKALIKE")
                && pluginSettings.ner_dictionary
            )
        }
    }, [pluginSettings]);

    const onClick = (e) => {
        if (!isPluginCorrectlyConfigured) {
            e.preventDefault();
            alert(__("No dictionary is configured, please contact your administrator.", "semlinks"))
            return;
        }

        setIsOpen(true)
    }

    return (
        <div>
            <button
                className="button button-primary semlinks-plugin-meta-box-action-button"
                onClick={onClick}
            >
                {__('Find links', 'semlinks')}
            </button>
            {isOpen && (
                <Modal
                    title={__('SemLinks', 'semlinks')}
                    onRequestClose={() => setIsOpen(false)}
                    style={{minWidth: "90%", paddingBottom: "64px"}}
                >
                    {modalView}

                    <div style={{
                        position: "fixed",
                        bottom: "10px",
                        right: "10px",
                        backgroundColor: "white",
                    }}>
                        <Button
                            style={{marginRight: "1em"}}
                            variant={"secondary"}
                            onClick={() => setIsOpen(false)}
                        >
                            {__('Cancel', 'semlinks')}
                        </Button>

                        <Button
                            variant={"primary"}
                            onClick={onValidate}
                            disabled={loading || error}
                        >
                            {__('Create', 'semlinks')}
                        </Button>
                    </div>
                </Modal>
            )}
        </div>
    );
}
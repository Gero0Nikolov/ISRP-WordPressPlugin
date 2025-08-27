// LL Variables
var isrpLLLocked = false;
var isrpLoadedPosts = [];
var isrpPostsToObject = {};
var isrpLoadingLabel = "<p id='isrp-ll-loader' class='isrp-ll-loader'>" + isrpLLStrings.loading + "</p>";
var isrpDataObject;
var isrpPostInViewportID;
var isrpLastScrollPosition;

// Create Cacheable Objects
var $isrpLLTrigger;

// DOM Ready
jQuery( document ).ready( function(){
    // Add the current post into the loaded posts container
    let postID = parseInt( jQuery( "article" ).attr( "id" ).split( "post-" )[ 1 ] );
    isrpLoadedPosts.push( postID );

    // Cache current post as Post in Viewport
    isrpPostInViewportID = postID;

    // Add the current post into the Posts to Object relations
    createRelation( postID, jQuery( "article .entry-title" ).html(), window.location.href );

    // Build LL Trigger
    jQuery( "main" ).append( "<div id='isrp-ll-trigger'></div>" );

    // Cache LL Trigger
    $isrpLLTrigger = jQuery( "#isrp-ll-trigger" ).length > 0 ? jQuery( "#isrp-ll-trigger" ) : false;

    // Attach the Scroll event if LL Trigger is available
    if ( $isrpLLTrigger !== false ) {
        jQuery( window ).on( "scroll", function(){
            // Update URL based on the Scrolling of the User
            if ( !jQuery( "#post-"+ isrpPostInViewportID ).isInViewport() ) {
                let currentScrollPosition = jQuery( window ).scrollTop();
                let postChangedFlag = false;

                if ( currentScrollPosition < isrpLastScrollPosition ) { // Up Scrolling
                    for ( let countPosts = isrpLoadedPosts.indexOf( isrpPostInViewportID ); countPosts >= 0; countPosts-- ) {
                        if ( jQuery( "#post-"+ isrpLoadedPosts[ countPosts ] ).isInViewport() ) {
                            isrpPostInViewportID = isrpLoadedPosts[ countPosts ];
                            postChangedFlag = true;
                            break;
                        }
                    }
                } else { // Down Scrolling
                    for ( let countPosts = isrpLoadedPosts.indexOf( isrpPostInViewportID ); countPosts < isrpLoadedPosts.length; countPosts++ ) {
                        if ( jQuery( "#post-"+ isrpLoadedPosts[ countPosts ] ).isInViewport() ) {
                            isrpPostInViewportID = isrpLoadedPosts[ countPosts ];
                            postChangedFlag = true;
                            break;
                        }
                    }
                }

                // Update URL based on the new Post ID
                if ( postChangedFlag ) {
                    updateAddress( isrpPostInViewportID, isrpPostsToObject[ isrpPostInViewportID ].title, isrpPostsToObject[ isrpPostInViewportID ].url );
                }
            }

            // Update Last Scroll Position
            isrpLastScrollPosition = jQuery( window ).scrollTop();

            // Lazy Load Event
            if ( !isrpLLLocked && $isrpLLTrigger.isFullyInViewport() ) {
                // Lock the LL
                isrpLLLocked = true;

                // Append the Loading Sign
                jQuery( isrpLoadingLabel ).insertBefore( $isrpLLTrigger );

                // Load a the new post
                jQuery.ajax( {
                    url: isrpLLConfig.ajaxUrl,
                    type: "POST",
                    dataType: "json",
                    data: {
                        action: "isrp_ll_get_post",
                        listed_posts: isrpLoadedPosts,
                        nonce: isrpLLConfig.nonce
                    },
                    success: function( response ) {
                        jQuery( "#isrp-ll-loader" ).remove();

                        if ( response && response.post_id ) {
                            isrpDataObject = response;

                            jQuery.ajax( {
                                url: isrpDataObject.permalink,
                                type: "GET",
                                data: {},
                                success: function( response ) {
                                    if ( typeof response !== "undefined" ) {
                                        isrpLoadedPosts.push( isrpDataObject.post_id );

                                        let postBody = response.split( /<main.*?>/ )[ 1 ].split( "</main>" )[ 0 ];
                                        jQuery( postBody ).insertBefore( $isrpLLTrigger );

                                        let newPostTitle = jQuery( "main>article:last-of-type .entry-title" ).html();

                                        isrpPostInViewportID = isrpDataObject.post_id;

                                        createRelation( isrpDataObject.post_id, newPostTitle, isrpDataObject.permalink, true );

                                        updateAddress( isrpDataObject.post_id, newPostTitle, isrpDataObject.permalink );

                                        isrpLLLocked = false;
                                    }
                                },
                                error: function( response ) {
                                    isrpLLLocked = false;
                                }
                            } );
                        } else {
                            isrpLLLocked = false;
                        }
                    },
                    error: function( response ) {
                        jQuery( "#isrp-ll-loader" ).remove();
                        isrpLLLocked = false;
                    }
                } );
            }
        } );
    }
} );

jQuery.fn.isFullyInViewport = function() {
    var viewport = {};
    viewport.top = jQuery( window ).scrollTop();
    viewport.bottom = viewport.top + jQuery( window ).height();
    var bounds = {};
    bounds.top = jQuery( this ).offset().top;
    bounds.bottom = bounds.top + jQuery( this ).outerHeight();
    return ((bounds.top >= viewport.top) && (bounds.bottom <= viewport.bottom));
};

jQuery.fn.isInViewport = function() {
    var viewport = {};
    viewport.top = jQuery( window ).scrollTop();
    viewport.bottom = viewport.top + jQuery( window ).height();
    var bounds = {};
    bounds.top = jQuery( this ).offset().top;
    bounds.bottom = bounds.top + jQuery( this ).outerHeight();
    return ((viewport.top >= bounds.top) && (viewport.bottom <= bounds.bottom));
};

function updateAddress( id, title, url ) {
    window.history.replaceState( { post_id: id }, title, url );
}

function createRelation( id, title, url, isDynamic = false ) {
    // Add the Post to the Relations if it doesn't exists
    if ( typeof isrpPostsToObject[ id ] === "undefined" ) {
        // Create Post to Object Relation
        isrpPostsToObject[ id ] = {
            title: title,
            url: url,
            urlObject: new URL( url )
        };
    }
}
jQuery(document).ready(function($){
    
    // --- 1. MEDIA UPLOADER ---
    var mediaUploader;
    $('#seac_upload_logo_btn').click(function(e) {
        e.preventDefault();
        if (mediaUploader) { mediaUploader.open(); return; }
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Admin Logo',
            button: { text: 'Choose Logo' },
            multiple: false
        });
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#seac_logo_url').val(attachment.url);
            $('#seac_logo_preview').css('background-image', 'url(' + attachment.url + ')');
        });
        mediaUploader.open();
    });
    $('#seac_remove_logo_btn').click(function(e){
        e.preventDefault();
        $('#seac_logo_url').val('');
        $('#seac_logo_preview').css('background-image', 'none');
    });

    // --- 2. MENU MANAGER ---
    
    // Safety check: Does the variable exist?
    if ( typeof seacData === 'undefined' ) {
        console.error("SEAC Error: seacData variable is missing.");
        $('#seac_role_tabs').html('<p style="color:red; padding:10px;">Error: Menu data not loaded.</p>');
        return;
    }

    var roles = seacData.roles;
    var masterMenu = seacData.menu;
    var activeRole = 'administrator'; // Default

    // A. Render Tabs
    var $tabsContainer = $('#seac_role_tabs');
    $tabsContainer.empty();
    
    // Sort roles to put Administrator first
    var sortedRoles = Object.keys(roles).sort(function(a,b){
        if(a === 'administrator') return -1;
        if(b === 'administrator') return 1;
        return 0;
    });

    // Build Tab Buttons
    $.each(sortedRoles, function(i, roleKey){
        var roleData = roles[roleKey];
        var activeClass = (roleKey === activeRole) ? 'active' : '';
        var tabHtml = '<button type="button" class="seac-role-tab '+activeClass+'" data-role="'+roleKey+'">' + roleData.name + '</button>';
        $tabsContainer.append(tabHtml);
    });

    // B. Render Menu List
    function renderMenuList( role ) {
        var $list = $('#seac_menu_list');
        $list.empty();

        if( masterMenu.length === 0 ) {
            $list.html('<li style="padding:20px;">No menu items found.</li>');
            return;
        }

        $.each(masterMenu, function(index, item){
            
            // Icon Logic
            var iconHtml = '';
            if( item.icon.indexOf('dashicons-') !== -1 ) {
                iconHtml = '<span class="dashicons ' + item.icon + '"></span>';
            } else if ( item.icon.indexOf('http') !== -1 || item.icon.indexOf('data:') !== -1 ) {
                 iconHtml = '<img src="' + item.icon + '" style="max-width:20px; max-height:20px;" />';
            } else {
                iconHtml = '<span class="dashicons dashicons-admin-generic"></span>';
            }

            var liHtml = `
                <li class="seac-menu-item" data-original-slug="${item.slug}">
                    <div class="seac-item-handle">
                        <span class="dashicons dashicons-menu"></span>
                    </div>
                    <div class="seac-item-icon">
                        ${iconHtml}
                    </div>
                    <div class="seac-item-details">
                        <input type="text" class="seac-rename-input" value="${item.original_name}" placeholder="Rename item...">
                        <span class="seac-original-label">Original: ${item.original_name}</span>
                    </div>
                    <div class="seac-item-actions">
                        <button type="button" class="seac-visibility-toggle" title="Toggle Visibility">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </div>
                </li>
            `;
            $list.append(liHtml);
        });

        // Enable Drag & Drop
        if ($.fn.sortable) {
            $list.sortable({
                handle: '.seac-item-handle',
                placeholder: 'seac-sortable-placeholder',
                forcePlaceholderSize: true
            });
        }
    }

    // Initial Render
    renderMenuList(activeRole);

    // C. Tab Switching Logic
    $('.seac-role-tab').click(function(){
        $('.seac-role-tab').removeClass('active');
        $(this).addClass('active');
        activeRole = $(this).data('role');
        renderMenuList(activeRole);
    });

    // D. Visibility Toggle Logic
    $(document).on('click', '.seac-visibility-toggle', function(){
        var $btn = $(this);
        var $icon = $btn.find('.dashicons');
        var $li = $btn.closest('li');

        if( $li.hasClass('seac-hidden') ) {
            $li.removeClass('seac-hidden');
            $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        } else {
            $li.addClass('seac-hidden');
            $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        }
    });

});
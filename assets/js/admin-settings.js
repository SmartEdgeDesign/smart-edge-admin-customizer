jQuery(document).ready(function($){
    
    // --- 1. MEDIA UPLOADER LOGIC (Keep existing) ---
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

    // --- 2. MENU MANAGER LOGIC (New!) ---
    
    // Safety check if data exists
    if( typeof seacData === 'undefined' ) return;

    var roles = seacData.roles;
    var masterMenu = seacData.menu;
    var activeRole = 'administrator'; // Default tab

    // A. Render Tabs
    var $tabsContainer = $('#seac_role_tabs');
    $.each(roles, function(roleKey, roleData){
        var activeClass = (roleKey === activeRole) ? 'active' : '';
        var tabHtml = '<button type="button" class="seac-role-tab '+activeClass+'" data-role="'+roleKey+'">' + roleData.name + '</button>';
        $tabsContainer.append(tabHtml);
    });

    // B. Render Menu List
    function renderMenuList( role ) {
        var $list = $('#seac_menu_list');
        $list.empty();

        // Loop through the master menu items
        $.each(masterMenu, function(index, item){
            // Clean up the icon class (strip the 'dashicons-' prefix if using WP icons for display)
            var iconClass = item.icon; 
            
            var liHtml = `
                <li class="seac-menu-item" data-original-slug="${item.slug}">
                    <div class="seac-item-handle">
                        <span class="dashicons dashicons-menu"></span>
                    </div>
                    <div class="seac-item-icon">
                        <span class="dashicons ${iconClass}"></span>
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

        // Initialize Sortable (Drag & Drop)
        $list.sortable({
            handle: '.seac-item-handle',
            placeholder: 'seac-sortable-placeholder',
            forcePlaceholderSize: true
        });
    }

    // Initial Render
    renderMenuList(activeRole);

    // C. Handle Tab Switching
    $('.seac-role-tab').click(function(){
        // Update UI
        $('.seac-role-tab').removeClass('active');
        $(this).addClass('active');
        
        // Update Data
        activeRole = $(this).data('role');
        
        // Re-render list (In Phase 2, we will save/load specific configs here)
        renderMenuList(activeRole);
    });

    // D. Handle Visibility Toggle
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
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
    
    if ( typeof seacData === 'undefined' ) return;

    var roles = seacData.roles;
    var masterMenu = seacData.menu; // The default WordPress menu structure
    var savedSettings = seacData.saved_settings || {}; // Existing saves
    var activeRole = 'administrator';
    
    // This object holds the CURRENT state of all tabs
    var currentConfig = {};

    // Initialize Config: Load saved settings OR default to master menu
    $.each(roles, function(roleKey, roleData){
        if( savedSettings[roleKey] ) {
            currentConfig[roleKey] = savedSettings[roleKey];
        } else {
            // If no save exists, clone the master menu
            // We use JSON parse/stringify to deep clone the array so editing one role doesn't edit others
            currentConfig[roleKey] = JSON.parse(JSON.stringify(masterMenu));
        }
    });

    // A. Render Tabs
    var $tabsContainer = $('#seac_role_tabs');
    $tabsContainer.empty();
    
    var sortedRoles = Object.keys(roles).sort(function(a,b){
        if(a === 'administrator') return -1;
        if(b === 'administrator') return 1;
        return 0;
    });

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
        
        var menuItems = currentConfig[role];

        if( !menuItems || menuItems.length === 0 ) {
            $list.html('<li style="padding:20px;">No menu items found.</li>');
            return;
        }

        $.each(menuItems, function(index, item){
            
            // Icon Logic
            var iconHtml = '';
            if( item.icon.indexOf('dashicons-') !== -1 ) {
                iconHtml = '<span class="dashicons ' + item.icon + '"></span>';
            } else if ( item.icon.indexOf('http') !== -1 || item.icon.indexOf('data:') !== -1 ) {
                 iconHtml = '<img src="' + item.icon + '" style="max-width:20px; max-height:20px;" />';
            } else {
                iconHtml = '<span class="dashicons dashicons-admin-generic"></span>';
            }

            var hiddenClass = (item.hidden === true) ? 'seac-hidden' : '';
            var hiddenIcon = (item.hidden === true) ? 'dashicons-hidden' : 'dashicons-visibility';

            var liHtml = `
                <li class="seac-menu-item ${hiddenClass}" data-slug="${item.slug}" data-original-name="${item.original_name}">
                    <div class="seac-item-handle">
                        <span class="dashicons dashicons-menu"></span>
                    </div>
                    <div class="seac-item-icon">
                        ${iconHtml}
                    </div>
                    <div class="seac-item-details">
                        <input type="text" class="seac-rename-input" value="${item.rename || item.original_name}" placeholder="Rename item...">
                        
                        <input type="text" class="seac-icon-input" value="${item.icon}" placeholder="dashicons-admin-home">
                        
                        <span class="seac-original-label">Original: ${item.original_name}</span>
                    </div>
                    <div class="seac-item-actions">
                        <button type="button" class="seac-visibility-toggle" title="Toggle Visibility">
                            <span class="dashicons ${hiddenIcon}"></span>
                        </button>
                    </div>
                </li>
            `;
            $list.append(liHtml);
        });

        if ($.fn.sortable) {
            $list.sortable({
                handle: '.seac-item-handle',
                placeholder: 'seac-sortable-placeholder',
                forcePlaceholderSize: true
            });
        }
    }

    // Helper: Scrape the DOM and update 'currentConfig' for the active role
    function saveCurrentTabState() {
        var newOrder = [];
        $('#seac_menu_list li').each(function(){
            var $li = $(this);
            newOrder.push({
                slug: $li.data('slug'),
                original_name: $li.data('original-name'),
                rename: $li.find('.seac-rename-input').val(),
                icon: $li.find('.seac-icon-input').val(),
                hidden: $li.hasClass('seac-hidden')
            });
        });
        currentConfig[activeRole] = newOrder;
    }

    // Initial Render
    renderMenuList(activeRole);

    // C. Tab Switching
    $('.seac-role-tab').click(function(){
        // 1. Save current state before switching
        saveCurrentTabState();

        // 2. Switch UI
        $('.seac-role-tab').removeClass('active');
        $(this).addClass('active');
        
        // 3. Update Active Role
        activeRole = $(this).data('role');
        
        // 4. Render new data
        renderMenuList(activeRole);
    });

    // D. Visibility Toggle
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

    // E. FORM SUBMISSION (The Save)
    $('form').submit(function(){
        // Save the currently open tab first
        saveCurrentTabState();

        // Convert the huge config object to JSON
        var jsonString = JSON.stringify(currentConfig);

        // Put it in the hidden input
        $('#seac_menu_config_input').val(jsonString);
    });

});
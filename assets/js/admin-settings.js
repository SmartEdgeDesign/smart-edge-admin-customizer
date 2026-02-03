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
    var masterMenu = seacData.menu;
    var savedSettings = seacData.saved_settings || {};
    var activeRole = 'administrator';
    
    var currentConfig = {};

    // --- INITIALIZATION ---
    $.each(roles, function(roleKey, roleData){
        if( savedSettings[roleKey] ) {
            var config = savedSettings[roleKey];
            
            // ORPHAN LOGIC: Detect new items (e.g. Links) and add them to list.
            var savedSlugs = config.map(function(item){ return item.slug; });
            
            var orphans = masterMenu.filter(function(item){
                // Skip default separators to avoid ghost lines
                if ( item.type === 'separator' ) return false;
                return savedSlugs.indexOf(item.slug) === -1;
            });
            
            if ( orphans.length > 0 ) {
                config = config.concat(orphans);
            }
            
            currentConfig[roleKey] = config;
        } else {
            // No save? Start clean.
            currentConfig[roleKey] = JSON.parse(JSON.stringify(masterMenu));
        }
    });

    // Render Tabs
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

    // Render List
    function renderMenuList( role ) {
        var $list = $('#seac_menu_list');
        $list.empty();
        
        var menuItems = currentConfig[role];

        if( !menuItems || menuItems.length === 0 ) {
            $list.html('<li style="padding:20px;">No menu items found.</li>');
            return;
        }

        $.each(menuItems, function(index, item){
            appendMenuItem(item);
        });

        if ($.fn.sortable) {
            $list.sortable({
                handle: '.seac-item-handle',
                placeholder: 'seac-sortable-placeholder',
                forcePlaceholderSize: true
            });
        }
    }

    function appendMenuItem( item ) {
        var hiddenClass = (item.hidden === true) ? 'seac-hidden' : '';
        var hiddenIcon = (item.hidden === true) ? 'dashicons-hidden' : 'dashicons-visibility';
        var $list = $('#seac_menu_list');

        if ( item.type === 'separator' ) {
            var liHtml = `
                <li class="seac-menu-item seac-is-separator ${hiddenClass}" data-slug="${item.slug}" data-type="separator">
                     <div class="seac-item-handle" style="width:100%; text-align:center; padding:5px 0; color:#ccc;">
                        <span class="dashicons dashicons-menu"></span> —————— Divider ——————
                    </div>
                    <div class="seac-item-actions">
                         <button type="button" class="seac-visibility-toggle" title="Toggle Visibility">
                            <span class="dashicons ${hiddenIcon}"></span>
                        </button>
                    </div>
                    <input type="hidden" class="seac-rename-input" value="">
                    <input type="hidden" class="seac-icon-input" value="">
                </li>
            `;
            $list.append(liHtml);
        } else {
            var iconHtml = '';
            if( item.icon.indexOf('dashicons-') !== -1 ) {
                iconHtml = '<span class="dashicons ' + item.icon + '"></span>';
            } else if ( item.icon.indexOf('http') !== -1 || item.icon.indexOf('data:') !== -1 ) {
                 iconHtml = '<img src="' + item.icon + '" style="max-width:20px; max-height:20px;" />';
            } else {
                iconHtml = '<span class="dashicons dashicons-admin-generic"></span>';
            }

            var liHtml = `
                <li class="seac-menu-item ${hiddenClass}" data-slug="${item.slug}" data-original-name="${item.original_name}" data-type="item">
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
        }
    }

    function saveCurrentTabState() {
        var newOrder = [];
        $('#seac_menu_list li').each(function(){
            var $li = $(this);
            newOrder.push({
                slug: $li.data('slug'),
                original_name: $li.data('original-name'),
                type: $li.data('type'),
                rename: $li.find('.seac-rename-input').val(),
                icon: $li.find('.seac-icon-input').val(),
                hidden: $li.hasClass('seac-hidden')
            });
        });
        currentConfig[activeRole] = newOrder;
    }

    renderMenuList(activeRole);

    $('.seac-role-tab').click(function(){
        saveCurrentTabState();
        $('.seac-role-tab').removeClass('active');
        $(this).addClass('active');
        activeRole = $(this).data('role');
        renderMenuList(activeRole);
    });

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

    // ADD DIVIDER
    $('#seac_add_divider_btn').click(function(e){
        e.preventDefault();
        var uniqueID = 'sep_' + Date.now();
        var newItem = { slug: uniqueID, type: 'separator', hidden: false };
        appendMenuItem(newItem);
        var $list = $('#seac_menu_list');
        $list.scrollTop($list[0].scrollHeight);
    });

    // RESET BUTTON (THE FIX)
    $('#seac_reset_menu_btn').click(function(e){
        e.preventDefault();
        if( confirm('Are you sure you want to reset the menu for the "' + roles[activeRole].name + '" role to default? This will remove all custom dividers and layout changes.') ) {
            
            // 1. Get the CLEAN master menu
            currentConfig[activeRole] = JSON.parse(JSON.stringify(masterMenu));
            var jsonString = JSON.stringify(currentConfig);
            
            // 2. Put it in the box
            $('#seac_menu_config_input').val(jsonString);
            
            // 3. USE NATIVE SUBMIT (Bypasses jQuery handler so we don't save the messy list)
            $('.seac-settings-wrap form')[0].submit();
        }
    });

    // SAVE HANDLER
    $('.seac-settings-wrap form').submit(function(e){
        saveCurrentTabState();
        var jsonString = JSON.stringify(currentConfig);
        $('#seac_menu_config_input').val(jsonString);
        return true; 
    });

});
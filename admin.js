// admin/assets/admin.js
jQuery(document).ready(function($) {
    
    // ניהול טאבים
    $('.ura-tab').on('click', function(e) {
        e.preventDefault();
        
        $('.ura-tab').removeClass('active');
        $(this).addClass('active');
        
        var tabId = $(this).data('tab');
        $('.ura-tab-content').hide();
        $('#' + tabId).show();
    });
    
    // AJAX שינוי סטטוס הזמנה
    $('.ura-order-status').on('change', function() {
        var orderId = $(this).data('order-id');
        var newStatus = $(this).val();
        var nonce = $(this).data('nonce');
        
        $.ajax({
            url: ura_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ura_update_order_status',
                order_id: orderId,
                new_status: newStatus,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('סטטוס ההזמנה עודכן בהצלחה', 'success');
                } else {
                    showNotification('שגיאה בעדכון הסטטוס', 'error');
                }
            }
        });
    });
    
    // ניהול בסיס ידע - Drag & Drop
    $('.ura-knowledge-base-items').sortable({
        update: function(event, ui) {
            var itemOrder = $(this).sortable('toArray', { attribute: 'data-item-id' });
            
            $.ajax({
                url: ura_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ura_update_knowledge_order',
                    order: itemOrder,
                    nonce: ura_ajax.nonce
                }
            });
        }
    });
    
    // אופטימיזציית פרומפטים עם AI
    $('.ura-optimize-prompt').on('click', function() {
        var promptContent = $('#ura-prompt-content').val();
        var promptId = $(this).data('prompt-id');
        
        $.ajax({
            url: ura_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ura_optimize_prompt',
                prompt_content: promptContent,
                prompt_id: promptId,
                nonce: ura_ajax.nonce
            },
            beforeSend: function() {
                $('#ura-optimization-loader').show();
            },
            success: function(response) {
                $('#ura-optimization-loader').hide();
                if (response.success) {
                    $('#ura-optimized-preview').val(response.data.optimized_prompt);
                    $('#ura-optimization-modal').show();
                }
            }
        });
    });
    
    // הצגת היסטוריית פרומפטים
    $('.ura-show-prompt-history').on('click', function() {
        var promptId = $(this).data('prompt-id');
        
        $.ajax({
            url: ura_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ura_get_prompt_history',
                prompt_id: promptId,
                nonce: ura_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayPromptHistory(response.data.history);
                }
            }
        });
    });
    
    // פונקציות עזר
    function showNotification(message, type) {
        var notification = $('<div class="ura-notification ura-notification-' + type + '">' + message + '</div>');
        $('body').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    function displayPromptHistory(history) {
        var historyHtml = '<div class="ura-prompt-history">';
        historyHtml += '<h3>היסטוריית גרסאות</h3>';
        
        history.forEach(function(version) {
            historyHtml += '<div class="ura-history-version">';
            historyHtml += '<h4>גרסה מ-' + version.date + '</h4>';
            historyHtml += '<pre>' + version.content + '</pre>';
            historyHtml += '<button class="ura-restore-version" data-version-id="' + version.id + '">שחזר גרסה זו</button>';
            historyHtml += '</div>';
        });
        
        historyHtml += '</div>';
        
        $('#ura-history-modal .ura-modal-content').html(historyHtml);
        $('#ura-history-modal').show();
    }
    
    // סגירת מודלים
    $('.ura-modal-close').on('click', function() {
        $(this).closest('.ura-modal').hide();
    });
});
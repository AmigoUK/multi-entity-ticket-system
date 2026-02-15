/**
 * Test Data Manager — button handlers & AJAX.
 *
 * @package METS
 * @since   1.2.0
 */
(function ($) {
    'use strict';

    var $status = $('#mets-test-data-status');

    function getMessage(resp, fallback) {
        return (resp && resp.data && resp.data.message) ? resp.data.message : fallback;
    }

    function showStatus(html, type) {
        var cls = type === 'error' ? 'notice-error' : 'notice-success';
        $status.html('<div class="notice ' + cls + '" style="padding:12px;">' + html + '</div>');
    }

    function setLoading($btn, loading) {
        if (loading) {
            $btn.prop('disabled', true).data('orig-text', $btn.text()).text('Processing…');
            $status.html('<p><span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span>Please wait…</p>');
        } else {
            $btn.prop('disabled', false).text($btn.data('orig-text'));
        }
    }

    // Import
    $('#mets-import-test-data').on('click', function (e) {
        e.preventDefault();
        if (!confirm('Import curated test data?\n\nThis will create 3 entities, 4 agent users, 30 tickets, replies, SLA rules, business hours, and KB content.')) {
            return;
        }

        var $btn = $(this);
        setLoading($btn, true);

        $.post(mets_test_data.ajax_url, {
            action: 'mets_import_test_data',
            nonce: mets_test_data.nonce
        }).done(function (resp) {
            if (resp && resp.success) {
                showStatus(getMessage(resp, 'Import complete.'), 'success');
                setTimeout(function () { location.reload(); }, 1500);
            } else {
                showStatus(getMessage(resp, 'Import failed.'), 'error');
                setLoading($btn, false);
            }
        }).fail(function () {
            showStatus('Request failed. Check the console for details.', 'error');
            setLoading($btn, false);
        });
    });

    // Remove
    $('#mets-remove-test-data').on('click', function (e) {
        e.preventDefault();
        if (!confirm('REMOVE all test data?\n\nThis will permanently delete all test entities, agents, tickets, replies, SLA rules, business hours, and KB content that were imported.\n\nThis action cannot be undone.')) {
            return;
        }

        var $btn = $(this);
        setLoading($btn, true);

        $.post(mets_test_data.ajax_url, {
            action: 'mets_remove_test_data',
            nonce: mets_test_data.nonce
        }).done(function (resp) {
            if (resp && resp.success) {
                showStatus(getMessage(resp, 'Removal complete.'), 'success');
                setTimeout(function () { location.reload(); }, 1500);
            } else {
                showStatus(getMessage(resp, 'Removal failed.'), 'error');
                setLoading($btn, false);
            }
        }).fail(function () {
            showStatus('Request failed. Check the console for details.', 'error');
            setLoading($btn, false);
        });
    });

})(jQuery);

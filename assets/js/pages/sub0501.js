/**
 * sub0501 contact form interactions (jQuery)
 */
(function ($) {
    'use strict';

    function initFileAttach($root) {
        var $fileBtn = $root.find('.filebtn');
        var $fileInput = $root.find('.fileinput');
        var $filesWrap = $root.find('.files');
        if (!$fileBtn.length || !$fileInput.length || !$filesWrap.length) return;

        var fileList = [];

        function syncInput() {
            var input = $fileInput.get(0);
            if (!input) return;
            var data = new DataTransfer();
            fileList.forEach(function (file) {
                data.items.add(file);
            });
            input.files = data.files;
        }

        function renderFiles() {
            $filesWrap.empty();
            if (!fileList.length) {
                $filesWrap.addClass('empty');
                return;
            }

            $filesWrap.removeClass('empty');
            fileList.forEach(function (file, idx) {
                var $item = $('<div>', { class: 'file' });
                var $name = $('<span>', { class: 'filename', text: file.name });
                var $remove = $('<i>', {
                    class: 'remove',
                    role: 'button',
                    tabindex: 0,
                    'aria-label': 'Remove file',
                    'data-index': idx
                });

                $item.append($name, $remove);
                $filesWrap.append($item);
            });
        }

        function addFiles(files) {
            var added = false;
            files.forEach(function (file) {
                var exists = fileList.some(function (existing) {
                    return existing.name === file.name;
                });
                if (exists) {
                    window.alert('이미 ' + file.name + ' 파일이 존재합니다.');
                    return;
                }
                fileList.push(file);
                added = true;
            });

            if (added) {
                renderFiles();
                syncInput();
            }
        }

        $fileBtn.on('click', function () {
            $fileInput.trigger('click');
        });

        $fileInput.on('change', function (event) {
            var input = event.target;
            var files = Array.prototype.slice.call(input.files || []);
            if (!files.length) return;
            addFiles(files);
            input.value = '';
        });

        $filesWrap.on('click', '.remove', function () {
            var idx = parseInt($(this).attr('data-index'), 10);
            if (Number.isNaN(idx)) return;
            if (!window.confirm('파일을 삭제할까요?')) return;
            fileList.splice(idx, 1);
            renderFiles();
            syncInput();
        });

        $filesWrap.on('keydown', '.remove', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            $(this).trigger('click');
        });

        renderFiles();
    }

    function initPolicyModal($root) {
        var $policy = $root.find('.policy');
        var $policyOpen = $root.find('.link');
        var $policyClose = $root.find('.policy .close');
        if (!$policy.length || !$policyOpen.length) return;

        var prevBodyOverflow = '';
        var prevHtmlOverflow = '';

        function openPolicy() {
            $policy.addClass('open').attr('aria-hidden', 'false');
            prevBodyOverflow = $('body').css('overflow');
            prevHtmlOverflow = $('html').css('overflow');
            $('body').css('overflow', 'hidden');
            $('html').css('overflow', 'hidden');
            if (window.scrollInstance && typeof window.scrollInstance.stop === 'function') {
                window.scrollInstance.stop();
            }
            $policyClose.blur();
            $policyOpen.blur();
        }

        function closePolicy() {
            $policy.removeClass('open').attr('aria-hidden', 'true');
            $('body').css('overflow', prevBodyOverflow || '');
            $('html').css('overflow', prevHtmlOverflow || '');
            if (window.scrollInstance && typeof window.scrollInstance.start === 'function') {
                window.scrollInstance.start();
            }
            $policyClose.blur();
        }

        $policyOpen.on('click', openPolicy);
        if ($policyClose.length) {
            $policyClose.on('click', closePolicy);
        }

        $policy.on('click', function (event) {
            if (event.target === $policy.get(0)) {
                closePolicy();
            }
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                closePolicy();
            }
        });
    }

    $(function () {
        var $root = $('.cont1');
        if (!$root.length) return;
        initFileAttach($root);
        initPolicyModal($root);
    });
})(jQuery);

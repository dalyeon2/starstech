/**
 * sub0501 contact form interactions
 */
(function ($) {
    'use strict';

    function initFileAttach($root) {
        var $fileBtn = $root.find('.filebtn');
        var $fileInput = $root.find('.fileinput');
        var $filesWrap = $root.find('.files');
        if (!$fileBtn.length || !$fileInput.length || !$filesWrap.length) return null;

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

        function resetFiles() {
            fileList = [];
            renderFiles();
            syncInput();
        }

        renderFiles();

        return {
            reset: resetFiles
        };
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

    function resolveBbsUrl() {
        if (window.g5_bbs_url) return window.g5_bbs_url;
        var base = (typeof resolveSiteBasePath === 'function') ? resolveSiteBasePath() : '';
        if (typeof joinPath === 'function') return joinPath(base, '/bbs');
        if (base) return base.replace(/\/+$/, '') + '/bbs';
        return '/bbs';
    }

    function extractAlertMessage(html) {
        if (!html || typeof html !== 'string') return '';
        var match = html.match(/alert\(['"]([^'"]+)/);
        if (!match) return '';
        return match[1].replace(/\\n/g, '\n');
    }

    function initInquiryForm($root, fileControl) {
        var $form = $root.find('form.form');
        if (!$form.length) return;

        var $submitBtn = $form.find('.submit');
        var submitting = false;

        function getFieldValue(name) {
            var $el = $form.find('[name="' + name + '"]');
            return $el.length ? $.trim($el.val()) : '';
        }

        function getSelectedInquiry() {
            var $checked = $form.find('input[name="inquiry"]:checked');
            if (!$checked.length) return null;
            var value = $checked.val();
            var label = $.trim($checked.closest('.option').text());
            return { value: value, label: label };
        }

        function validateEmail(email) {
            return /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/.test(email);
        }

        function buildContent(data) {
            var lines = [];
            lines.push('문의 유형: ' + data.typeLabel);
            lines.push('기업/소속명: ' + data.company);
            lines.push('담당자 성명/직급: ' + data.manager);
            lines.push('국가: ' + data.country);
            lines.push('이메일: ' + data.email);
            lines.push('');
            lines.push('문의 내용:');
            lines.push(data.message);
            return lines.join('\\n');
        }

        function buildPassword() {
            return Math.random().toString(36).slice(2) + Date.now().toString(36);
        }

        function setSubmitting(state) {
            submitting = state;
            if ($submitBtn.length) {
                $submitBtn.prop('disabled', state);
                $submitBtn.toggleClass('is-loading', state);
            }
        }

        $form.on('submit', function (event) {
            event.preventDefault();
            if (submitting) return;

            var inquiry = getSelectedInquiry();
            var company = getFieldValue('company');
            var manager = getFieldValue('manager');
            var country = getFieldValue('country');
            var email = getFieldValue('email');
            var subject = getFieldValue('subject');
            var message = getFieldValue('message');
            var privacy = $form.find('input[name="privacy"]').is(':checked');

            if (!inquiry) {
                window.alert('문의 유형을 선택해주세요.');
                return;
            }
            if (!company || !manager || !country || !email) {
                window.alert('기본정보를 모두 입력해주세요.');
                return;
            }
            if (!validateEmail(email)) {
                window.alert('이메일 형식이 올바르지 않습니다.');
                return;
            }
            if (!subject || !message) {
                window.alert('제목과 문의 내용을 입력해주세요.');
                return;
            }
            if (!privacy) {
                window.alert('개인정보 수집 및 이용에 동의해주세요.');
                return;
            }

            var boTable = $form.data('boTable') || 'inquiry';
            var bbsUrl = resolveBbsUrl();
            var writeUrl = $form.data('writeUrl') || (bbsUrl + '/write_update.php');
            var tokenUrl = $form.data('tokenUrl') || (bbsUrl + '/write_token.php');
            var successMessage = $form.data('successMessage') || '문의가 접수되었습니다.';

            var payload = {
                typeLabel: inquiry.label || inquiry.value || '',
                company: company,
                manager: manager,
                country: country,
                email: email,
                subject: subject,
                message: message
            };

            setSubmitting(true);

            $.ajax({
                type: 'POST',
                url: tokenUrl,
                data: { bo_table: boTable },
                dataType: 'json'
            }).done(function (tokenRes) {
                if (!tokenRes || tokenRes.error || !tokenRes.token) {
                    window.alert(tokenRes && tokenRes.error ? tokenRes.error : '토큰 발급에 실패했습니다.');
                    setSubmitting(false);
                    return;
                }

                var formData = new FormData();
                formData.append('uid', Date.now().toString());
                formData.append('w', '');
                formData.append('bo_table', boTable);
                formData.append('wr_id', '');
                formData.append('wr_subject', payload.subject);
                formData.append('wr_content', buildContent(payload));
                formData.append('wr_name', payload.manager || payload.company || '문의자');
                formData.append('wr_email', payload.email);
                formData.append('wr_password', buildPassword());
                formData.append('token', tokenRes.token);
                if (payload.typeLabel) {
                    formData.append('ca_name', payload.typeLabel);
                }
                formData.append('wr_1', payload.typeLabel);
                formData.append('wr_2', payload.company);
                formData.append('wr_3', payload.manager);
                formData.append('wr_4', payload.country);
                formData.append('wr_5', payload.email);

                var fileInput = $form.find('.fileinput').get(0);
                if (fileInput && fileInput.files && fileInput.files.length) {
                    Array.prototype.forEach.call(fileInput.files, function (file) {
                        formData.append('bf_file[]', file);
                    });
                }

                $.ajax({
                    type: 'POST',
                    url: writeUrl,
                    data: formData,
                    processData: false,
                    contentType: false
                }).done(function (response) {
                    var errorMessage = extractAlertMessage(response);
                    if (errorMessage) {
                        window.alert(errorMessage);
                        setSubmitting(false);
                        return;
                    }

                    window.alert(successMessage);
                    $form.get(0).reset();
                    if (fileControl && typeof fileControl.reset === 'function') {
                        fileControl.reset();
                    }
                    setSubmitting(false);
                }).fail(function () {
                    window.alert('문의 접수 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
                    setSubmitting(false);
                });
            }).fail(function () {
                window.alert('토큰 발급에 실패했습니다.');
                setSubmitting(false);
            });
        });
    }

    $(function () {
        var $root = $('.cont1');
        if (!$root.length) return;
        var fileControl = initFileAttach($root);
        initPolicyModal($root);
        initInquiryForm($root, fileControl);
    });
})(jQuery);
